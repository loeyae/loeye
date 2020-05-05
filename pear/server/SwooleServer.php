<?php

/**
 * SwooleServer.php
 *
 * @category PHP
 * @package LOEYE
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/4 0:25
 */

namespace loeye\server;


use loeye\base\Context;
use loeye\std\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process;

/**
 * Class SwooleServer
 * @package loeye\server
 */
class SwooleServer extends Server
{

    /**
     * @return bool
     */
    public static function isSupported(): bool
    {
        return extension_loaded('swoole');
    }

    /**
     * createServer
     *
     * @return \Swoole\Http\Server
     */
    protected function createServer(): \Swoole\Http\Server
    {
        $sslConfig = $this->getSSLConfig();
        $port = $this->getServerPort();
        if ($sslConfig) {
            $port = ($port === 80) ? 443 : $port;
            $server = new \Swoole\Http\Server('0.0.0.0', $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP |
                SWOOLE_SSL);
            $server->set([
                'ssl_cert_file' => $sslConfig['cert_file'],
                'ssl_key_file' => $sslConfig['key_file'] ?? null
            ]);
        }
        return new \Swoole\Http\Server('0.0.0.0', $port);
    }

    /**
     * @param \Swoole\Http\Server $server
     */
    protected function onRequest(\Swoole\Http\Server $server)
    {
        $server->on('request', function (Request $request, Response $response){
            $path = $request->server['request_uri'];
            if (($file = $this->isStaticFile($path)) !== false) {
                $render = $this->staticRouter($file);
            } else {
                $context = $this->createContext($request);
                $render = $this->process($context);
            }
            $response->status($render->code(), $render->reason());
            $header = $render->header();
            if ($header) {
                foreach ($header as $key => $value) {
                    $response->header($key, $value);
                }
            }
            $cookie = $render->cookie();
            if ($cookie) {
                foreach ($cookie as $item) {
                    $response->cookie($item->getName(), $item->getValue(), $item->getExpiresTime(),
                        $item->getPath(), $item->getDomain(), $item->isSecure(), $item->isHttpOnly(),
                        $item->getSameSite());
                }
            }
            $response->end($render->output());
        });
    }

    protected function loadPeriodicTimer(\Swoole\Http\Server $server)
    {
        $periodicTimers = $this->getPeriodicTask();
        if ($periodicTimers) {
            foreach ($periodicTimers as $item) {
                $server->tick($item['interval'], $item['callback']);
            }
        }
    }

    /**
     * @param Request $request
     * @return Context
     */
    protected function createContext(Request $request): Context
    {
        $context = new Context($this->appConfig);
        $myRequest = $this->createRequest();
        $response = $this->createResponse($myRequest);
        $router = $this->createRouter();
        $myRequest->setRouter($router)
            ->setUri($request->server['request_uri'])
            ->setMethod($request->server['request_method'])
            ->setServer($request->server)
            ->setCookie($request->cookie)
            ->setQuery($request->get)
            ->setBody($request->post)
            ->setContent($request->rawContent())
            ->setFiles($request->files)
            ->setHeader($request->header);
        $context->setRouter($router);
        $context->setRequest($myRequest);
        $context->setResponse($response);
        return $context;
    }

    /**
     * run
     */
    public function run(): void
    {
        $server = $this->createServer();
        $this->onRequest($server);
        $this->loadPeriodicTimer($server);
        $server->start();
    }
}