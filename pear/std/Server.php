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


use loeye\base\AppConfig;
use loeye\base\Context;
use loeye\base\Factory;
use loeye\base\UrlManager;
use loeye\base\Utils;
use loeye\Centra;
use loeye\lib\Cookie;
use loeye\render\SegmentRender;
use loeye\web\SimpleDispatcher;
use function GuzzleHttp\Psr7\mimetype_from_extension;

abstract class Server
{

    public const DEFAULT_DISPATCHER = 'default';
    public const SIMPLE_DISPATCHER = 'simple';
    public const SERVICE_DISPATCHER = 'service';

    /**
     * @var AppConfig
     */
    protected $appConfig;

    /**
     * Server constructor.
     */
    public function __construct()
    {
        if (!Centra::$appConfig) {
            Centra::$appConfig  = new AppConfig();
        }
        $this->appConfig = Centra::$appConfig;
        define('LOEYE_MODE', $this->appConfig->getSetting('debug', false) ? LOEYE_MODE_DEV :
            LOEYE_MODE_PROD);
    }

    /**
     * getDispatcher
     *
     * @return Dispatcher
     */
    protected function getDispatcher(): Dispatcher
    {
        $map = [
            self::DEFAULT_DISPATCHER => \loeye\web\Dispatcher::class,
            self::SIMPLE_DISPATCHER => SimpleDispatcher::class,
            self::SERVICE_DISPATCHER => \loeye\service\Dispatcher::class,
        ];
        $dispatcher = $this->appConfig->getSetting('server.dispatcher', self::DEFAULT_DISPATCHER);
        $dispatcherClass = $map[$dispatcher] ?? \loeye\web\Dispatcher::class;
        $processMode = $this->appConfig->getSetting('application.process_mode',
            LOEYE_PROCESS_MODE__NORMAL);
        return new $dispatcherClass($processMode);
    }

    /**
     * @return mixed
     */
    protected function getServerPort()
    {
        return $this->appConfig->getSetting('server.port', 80);
    }

    /**
     * @return array|null
     */
    protected function getSSLConfig(): ?array
    {
        return $this->appConfig->getSetting('server.ssl');
    }

    /**
     * @return array
     */
    protected function getPeriodicTask(): array
    {
        $periodicTimers = $this->appConfig->getSetting('server.periodic', []);
        return array_filter($periodicTimers, static function($item){
            return !empty(trim($item['callback']));
        });
    }

    /**
     * @return mixed|null
     */
    protected function getStaticPath()
    {
        return $this->appConfig->getSetting('server.static_path');
    }

    /**
     * staticRouter
     *
     * @param $path
     * @return Render
     */
    protected function staticRouter($path): Render
    {
        $response = new \loeye\web\Response();
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
     * @return Render|null
     */
    protected function process(): ?Render
    {
        $dispatcher = $this->getDispatcher();
        return $dispatcher->dispatch();
    }

    /**
     * createRequest
     *
     * @return Request
     */
    protected function createRequest(): Request
    {
        $dispatcher = $this->appConfig->getSetting('server.dispatcher', self::DEFAULT_DISPATCHER);
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
        $dispatcher = $this->appConfig->getSetting('server.dispatcher', self::DEFAULT_DISPATCHER);
        return ($dispatcher === self::SERVICE_DISPATCHER) ? new \loeye\service\Response($request) : new
        \loeye\web\Response();
    }

    /**
     * @return UrlManager|null
     */
    protected function createRouter(): ?UrlManager
    {
        $rewrite = $this->appConfig->getSetting('server.rewrite');
        if ($rewrite) {
            return new UrlManager($rewrite);
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