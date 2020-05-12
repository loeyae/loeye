<?php

/**
 * SimpleDispatcher.php
 *
 * PHP version 7
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @version  2018-07-23 22:44:28
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\web;

use loeye\base\Exception;
use loeye\base\UrlManager;
use loeye\base\Utils;
use loeye\Centra;
use loeye\error\ResourceException;
use loeye\lib\ModuleParse;
use loeye\std\Controller;
use loeye\std\Render;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Cache\Exception\CacheException;
use function loeye\base\ExceptionHandler;

/**
 * SimpleDispatcher
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class SimpleDispatcher extends \loeye\std\Dispatcher
{

    public const KEY_MODULE             = 'module';
    public const KEY_CONTROLLER         = 'controller';
    public const KEY_ACTION             = 'action';
    public const KEY_REWRITE            = 'rewrite';
    public const KEY_REQUEST_URI        = 'u';
    public const KEY_REQUEST_MODULE     = 'm';
    public const KEY_REQUEST_CONTROLLER = 'c';
    public const KEY_REQUEST_ACTION     = 'a';

    protected $module;
    protected $controller;
    protected $action;
    protected $rewrite;

    /**
     * dispatcher
     *
     * @param string|null $moduleId module id
     *
     * @return Render
     */
    public function dispatch($moduleId = null): Render
    {
        try {
            $this->initAppConfig();
            $this->initConfigConstants();
            $this->parseUrl();
            $this->initIOObject($moduleId ?? $this->module);
            $this->initLogger();
            $this->setTimezone();
            $this->initComponent();
            $object = $this->executeModule();
            $result = $this->redirectUrl();
            if ($result instanceof Render) {
                return $result;
            }
            $view = $this->getView($object);
            $this->executeView($view);
            return $this->executeOutput();
        } catch (Exception $exc) {
            return ExceptionHandler($exc, $this->context);
        } catch (\Exception $exc) {
            return ExceptionHandler($exc, $this->context);
        } finally {
            if ($this->processMode > LOEYE_PROCESS_MODE__NORMAL) {
                $this->setTraceDataIntoContext(array());
                Utils::logContextTrace($this->context);
            }
        }
    }

    /**
     * initIOObject
     *
     * @param string $moduleId moduleId
     *
     * @return void
     */
    protected function initIOObject($moduleId): void
    {
        $this->context->getRequest()->setModuleId($moduleId);

        if (defined('MOBILE_RENDER_ENABLE') && MOBILE_RENDER_ENABLE && $this->context->getRequest
            ()->getDevice()) {
            $this->context->getResponse()->setRenderId(Response::DEFAULT_MOBILE_RENDER_ID);
        }
    }

    /**
     * @param Controller $object
     * @return array
     */
    protected function getView(Controller $object): array
    {
        $view = [];
        if (!empty($object->view)) {
            if (is_string($object->view)) {
                $view = ['src' => $object->view];
            } else {
                $view = (array) $object->view;
            }
        }
        if (!empty($object->layout)) {
            $view['layout'] = $object->layout;
        }
        return $view;
    }

    /**
     * executeModule
     *
     * @throws Exception
     * @throws ReflectionException
     */
    protected function executeModule(): Controller
    {
        $controllerNamespace = $this->context->getAppConfig()->getSetting('controller_namespace', '');
        if (!$controllerNamespace) {
            $controllerNamespace = PROJECT_NAMESPACE . '\\controllers';
        }
        $controller = $controllerNamespace . ($this->module ? '\\'. $this->module : '') .
            '\\' . ucfirst($this->controller) . ucfirst(self::KEY_CONTROLLER);

        $action = ucfirst($this->action) . ucfirst(self::KEY_ACTION);

        if (!class_exists($controller)) {
            throw new ResourceException(ResourceException::PAGE_NOT_FOUND_MSG, ResourceException::PAGE_NOT_FOUND_CODE);
        }
        $ref    = new ReflectionClass($controller);
        $object = $ref->newInstance($this->context);
        if (!($object instanceof Controller)) {
            throw new ResourceException(ResourceException::INVALID_CONTROLLER_CODE,
                ResourceException::INVALID_CONTROLLER_MSG);
        }
        if (!method_exists($object, $action)) {
            throw new ResourceException(ResourceException::PAGE_NOT_FOUND_MSG, ResourceException::PAGE_NOT_FOUND_CODE);
        }
        $prepare = $object->prepare();
        if ($prepare) {
            $refMethod = new ReflectionMethod($object, $action);
            $refMethod->invoke($object);
        }
        return $object;
    }

    /**
     * init
     *
     * @param array $setting base conf setting
     * <p>
     * ['module'    => default module,
     * 'controller' => default controller,
     * 'action'     => default action,
     * 'rewrite'    => rewrite rule]
     *
     * rewrite ex: '/<module:\w+>/<controller:\w+>/<action:\w+>.html' => '{module}/{controller}/{action}'
     * </p>
     *
     * @return void
     */
    public function init(array $setting): void
    {
        isset($setting[self::KEY_MODULE]) && $this->module     = $setting[self::KEY_MODULE];
        isset($setting[self::KEY_CONTROLLER]) && $this->controller = $setting[self::KEY_CONTROLLER];
        isset($setting[self::KEY_ACTION]) && $this->action     = $setting[self::KEY_ACTION];
        isset($setting[self::KEY_REWRITE]) && $this->rewrite    = $setting[self::KEY_REWRITE];
        if ($this->rewrite) {
            $this->context->setRouter(new UrlManager($this->rewrite));
        }
    }

    /**
     * parseUrl
     *
     * @throws Exception
     */
    protected function parseUrl(): void
    {
        $requestUrl = $this->context->getRequest()->getUri()->getPath();
        $path       = null;
        if ($this->context->getRouter() instanceof UrlManager) {
            $path   = $this->context->getRouter()->match($requestUrl);
            if ($path === false) {
                throw new ResourceException(ResourceException::PAGE_NOT_FOUND_MSG, ResourceException::PAGE_NOT_FOUND_CODE);
            }
        }
        if ($path === null) {
            $path = $this->context->getRequest()->getQuery(self::KEY_REQUEST_URI);
        }
        if (!empty($path)) {
            $parts = explode('/', trim($path, '/'));
            if (isset($parts[2])) {
                $this->module     = $parts[0];
                $this->controller = Utils::camelize($parts[1]);
                $this->action     = Utils::camelize($parts[2]);
            } else if (isset($parts[1])) {
                $this->controller = Utils::camelize($parts[0]);
                $this->action     = Utils::camelize($parts[1]);
            } else {
                $this->controller = Utils::camelize($parts[0]);
            }
        } else {
            $this->module = $this->context->getRequest()->getQuery(self::KEY_REQUEST_MODULE);
            $this->controller = Utils::camelize($this->context->getRequest()->getQuery
            (self::KEY_REQUEST_CONTROLLER));
            $this->action = Utils::camelize($this->context->getRequest()->getQuery(self::KEY_REQUEST_ACTION));
        }
        if (empty($this->module) && empty($this->controller)) {
            throw new ResourceException(ResourceException::PAGE_NOT_FOUND_MSG, ResourceException::PAGE_NOT_FOUND_CODE);
        }
        if (empty($this->action)) {
            $this->action = 'index';
        }
    }

    /**
     * cacheContent
     *
     * @param array $view view setting
     * @param string $content content
     *
     * @return void
     * @throws Exception
     * @throws CacheException
     */
    protected function cacheContent($view, $content): void
    {
        if (isset($view['cache'])) {
            if (isset($view['expire'])) {
                $expire = $view['expire'];
            } else if (is_string($view['cache']) or is_numeric($view['cache'])) {
                $expire = (int)$view['cache'];
            } else {
                $expire = 0;
            }
            $cacheParams = [];
            if (is_array($view['cache'])) {
                $cacheParams = ModuleParse::parseInput($view['cache'], $this->context);
            }
            Utils::setPageCache($this->context->getRequest()->getModuleId(), $content, $expire, $cacheParams);
        }
    }

    /**
     * getContent
     *
     * @param array $view view setting
     *
     * @return string|null
     * @throws CacheException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function getContent($view): ?string
    {
        $content = null;
        if (isset($view['cache'])) {
            $cacheParams = [];
            if (is_array($view['cache'])) {
                $cacheParams = ModuleParse::parseInput($view['cache'], $this->context);
            }
            $content = Utils::getPageCache($this->context->getRequest()->getModuleId(), $cacheParams);
        }
        return $content;
    }

    /**
     * getCacheId
     *
     * @param array $view view setting
     *
     * @return string
     */
    protected function getCacheId($view = array()): string
    {
        $cacheId = $this->module . '_' . $this->controller . '_' . $this->action;
        if (isset($view['id'])) {
            $cacheId .= '.' . $this->context->get($view['id']);
        }
        return $cacheId;
    }

}
