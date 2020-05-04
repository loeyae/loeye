<?php

/**
 * ReactServer.php
 *
 * @category PHP
 * @package LOEYE
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/4 0:26
 */

namespace loeye\server;


use loeye\base\Context;
use loeye\std\Server;
use phpseclib\File\ASN1;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Response;

/**
 * Class ReactServer
 * @package loeye\server
 */
class ReactServer extends Server
{

    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct()
    {
        parent::__construct();
        $this->loop = \React\EventLoop\Factory::create();
    }

    /**
     * @return \React\Http\Server
     */
    protected function createServer(): \React\Http\Server
    {
        return new \React\Http\Server(function (ServerRequestInterface $request) {
            $path = $request->getUri()->getPath();
            if (($file = $this->isStaticFile($path)) !== false) {
                $render = $this->staticRouter($file);
            } else {
                $context = $this->createContext($request);
                $render = $this->process($context);
            }
            return new Response(200, $render->header(), $render->output());
        });
    }

    /**
     * createSocket
     * 
     * @return \React\Socket\Server
     */
    protected function createSocket(): \React\Socket\Server
    {
        $sslConfig = $this->getSSLConfig();
        $port = $this->appConfig->getSetting('server.port', 80);
        if ($sslConfig) {
            $port = ($port === 80) ? 443 : $port;
            return new \React\Socket\Server('tls://0.0.0.0:'.$port, $this->loop, [
                'local_cert' => $sslConfig['cert_file']
            ]);
        }
        return new \React\Socket\Server('0.0.0.0:'.$port, $this->loop);
    }

    /**
     * loadPeriodicTimer
     */
    protected function loadPeriodicTimer(): void
    {
        $periodicTimers = $this->appConfig->getSetting('server.periodic', []);
        $filtered = array_filter($periodicTimers, static function($item){
            return !empty(trim($item['callback']));
        });
        foreach ($filtered as $item) {
            $this->loop->addPeriodicTimer($item['interval'], $item['callback']);
        }
    }

    /**
     * run
     */
    public function run(): void
    {
        $this->loadPeriodicTimer();
        $server = $this->createServer();
        $socket = $this->createSocket();
        $server->listen($socket);
        $this->loop->run();
    }

    /**
     * @param ServerRequestInterface $request
     * @return Context
     */
    private function createContext(ServerRequestInterface $request): Context
    {
        $myRequest = $this->createRequest();
        $myRequest->setUri($request->getUri()->__toString())
            ->setMethod($request->getMethod())
            ->setQuery($request->getQueryParams())
            ->setBody($request->getParsedBody())
            ->setContent($request->getBody())
            ->setCookie($request->getCookieParams())
            ->setHeader($request->getHeaders())
            ->setFiles($request->getUploadedFiles())
            ->setServer($request->getServerParams());
        $context = new Context($this->appConfig);
        $router = $this->createRouter();
        $myRequest->setRouter($router);
        $context->setRequest($myRequest);
        $context->setResponse($this->createResponse($myRequest));
        return $context;
    }
}