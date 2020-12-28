<?php

/**
 * Server.php
 *
 * @category PHP
 * @package LOEYE
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/4 0:27
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\std;


use loeye\base\Context;
use loeye\base\UrlManager;
use loeye\base\Utils;
use loeye\Centra;
use loeye\render\SegmentRender;
use loeye\web\SimpleDispatcher;
use function GuzzleHttp\Psr7\mimetype_from_extension;

abstract class Server
{

    public const DEFAULT_DISPATCHER = 'default';
    public const SIMPLE_DISPATCHER = 'simple';
    public const SERVICE_DISPATCHER = 'service';

    /**
     * Server constructor.
     */
    public function __construct()
    {
        Centra::init();
        define('LOEYE_MODE', Centra::$appConfig->getSetting('debug', false) ? LOEYE_MODE_DEV :
            LOEYE_MODE_PROD);
    }

    /**
     * getDispatcher
     *
     * @param Context $context
     * @return Dispatcher
     */
    protected function getDispatcher(Context $context): Dispatcher
    {
        $map = [
            self::DEFAULT_DISPATCHER => \loeye\web\Dispatcher::class,
            self::SIMPLE_DISPATCHER => SimpleDispatcher::class,
            self::SERVICE_DISPATCHER => \loeye\service\Dispatcher::class,
        ];
        $dispatcher = Centra::$appConfig->getSetting('server.dispatcher', self::DEFAULT_DISPATCHER);
        $dispatcherClass = $map[$dispatcher] ?? \loeye\web\Dispatcher::class;
        $processMode = Centra::$appConfig->getSetting('application.process_mode',
            LOEYE_PROCESS_MODE__NORMAL);
        return new $dispatcherClass($context, $processMode);
    }

    /**
     * @return mixed
     */
    protected function getServerPort()
    {
        return Centra::$appConfig->getSetting('server.port', 80);
    }

    /**
     * @return array|null
     */
    protected function getSSLConfig(): ?array
    {
        return Centra::$appConfig->getSetting('server.ssl');
    }

    /**
     * @return array
     */
    protected function getPeriodicTask(): array
    {
        $periodicTimers = Centra::$appConfig->getSetting('server.periodic', []);
        return array_filter($periodicTimers, static function($item){
            return !empty(trim($item['callback']));
        });
    }

    /**
     * @return mixed|null
     */
    protected function getStaticPath()
    {
        return Centra::$appConfig->getSetting('server.static_path');
    }

    /**
     * staticRouter
     *
     * @param $path
     * @return Render
     */
    protected function staticRouter($path): Render
    {
        $response = new \loeye\web\Response(new \loeye\web\Request());
        if(!is_file($path)) {
            $response->setStatusCode(404);
            $response->setReason('Bad Request');
            $response->addHeader('Content-Type',
                mimetype_from_extension(pathinfo($path, PATHINFO_EXTENSION)));
        } else {
            $response->addHeader('Content-Type', Utils::mimeType($path));
            $response->addOutput(file_get_contents($path));
        }
        return new SegmentRender($response);
    }

    /**
     * process
     *
     * @param Context $context
     * @return Render|null
     */
    protected function process(Context $context): ?Render
    {
        $dispatcher = $this->getDispatcher($context);
        return $dispatcher->dispatch();
    }

    /**
     * createRequest
     *
     * @return Request
     */
    protected function createRequest(): Request
    {
        $dispatcher = Centra::$appConfig->getSetting('server.dispatcher', self::DEFAULT_DISPATCHER);
        return ($dispatcher === self::SERVICE_DISPATCHER) ? new \loeye\service\Request() : new
        \loeye\web\Request();
    }

    /**
     * createResponse
     *
     * @param Request $request
     * @return Response
     */
    protected function createResponse(Request $request): Response
    {
        $dispatcher = Centra::$appConfig->getSetting('server.dispatcher', self::DEFAULT_DISPATCHER);
        return ($dispatcher === self::SERVICE_DISPATCHER) ? new \loeye\service\Response($request) : new
        \loeye\web\Response($request);
    }

    /**
     * @param Request $request
     * @return UrlManager|null
     */
    protected function createRouter(Request $request): ?UrlManager
    {
        $rewrite = Centra::$appConfig->getSetting('server.rewrite');
        if ($rewrite) {
            return new UrlManager($request, $rewrite);
        }
        return null;
    }

    /**
     * @param $path
     * @return bool|string
     */
    protected function isStaticFile($path)
    {
        $staticPath = $this->getStaticPath();
        if ($staticPath) {
            foreach ((array)$staticPath as $item) {
                if (is_file(PROJECT_DIR . DIRECTORY_SEPARATOR . $item .DIRECTORY_SEPARATOR .$path)) {
                    return PROJECT_DIR . DIRECTORY_SEPARATOR . $item .DIRECTORY_SEPARATOR .$path;
                }
            }
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (in_array($ext, ['css', 'js', 'ico', 'png', 'jpg', 'gif', 'jpeg'])) {
            return $path;
        }
        return false;
    }

    abstract public function run();

}