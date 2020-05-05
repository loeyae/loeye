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
use loeye\Centra;
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
                $this->createContext($request);
                $render = $this->process();
            }
            $header = $render->header();
            $cookies = $render->cookie();
            if ($cookies) {
                foreach ($render->cookie() as $cookie) {
                    $header['Set-Cookie'][] = $cookie->__toString();
                }
            }
            $code = $render->code();
            $output = $render->output();
            $reason = $render->reason();
            if ($render->redirect()) {
                $header['Location'] = $render->redirect();
                $code = 302;
                $output = '';
                $reason = 'Moved Temporarily';
            }
            return new Response($code, $header, $output, $render->version(),
                $reason);
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
        $port = $this->getServerPort();
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
        $periodicTimers = $this->getPeriodicTask();
        foreach ($periodicTimers as $item) {
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
        $response = $this->createResponse($myRequest);
        $context->setResponse($response);
        Centra::$context = $context;
        Centra::$request = $myRequest;
        Centra::$response = $response;
        return $context;
    }
}