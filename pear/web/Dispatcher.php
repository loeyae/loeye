<?php

/**
 * Dispatcher.php
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
use loeye\base\Factory;
use loeye\base\ModuleDefinition;
use loeye\base\Router;
use loeye\base\UrlManager;
use loeye\base\Utils;
use loeye\Centra;
use loeye\error\BusinessException;
use loeye\error\ResourceException;
use loeye\lib\ModuleParse;
use loeye\std\ParallelPlugin;
use loeye\std\Render;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Cache\Exception\CacheException;
use Throwable;
use function loeye\base\ExceptionHandler;

/**
 * Description of Dispatcher
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Dispatcher extends \loeye\std\Dispatcher
{

    /**
     * @var ModuleDefinition
     */
    private $_mDfnObj;


    /**
     * dispatch
     *
     * @param mixed $moduleId module id
     *
     * @return Render
     */
    public function dispatch($moduleId = null): Render
    {
        try {
            $this->initAppConfig();
            $this->initConfigConstants();
            $moduleId = $this->parseUrl($moduleId);
            if (empty($moduleId)) {
                $moduleId = Centra::$context->getRequest()->getModuleId();
            }

            if (empty($moduleId)) {
                throw new ResourceException(ResourceException::PAGE_NOT_FOUND_MSG,
                    ResourceException::PAGE_NOT_FOUND_CODE);
            }
            $this->initIOObject($moduleId);
            $this->initLogger();
            $this->setTimezone();
            $this->initComponent();
            $this->_mDfnObj = new ModuleDefinition(Centra::$context->getAppConfig(), $moduleId);

            $result = $this->executeModule();
            if ($result instanceof Render) {
                return $result;
            }

            $result = $this->redirectUrl();
            if ($result instanceof Render) {
                return $result;
            }
            $view = $this->getView();
            $this->executeView($view);
            return $this->executeOutput();
        } catch (InvalidArgumentException $e) {
            return ExceptionHandler($e, Centra::$context);
        } catch (Throwable $e) {
            return ExceptionHandler($e, Centra::$context);
        } finally {
            if ($this->processMode > LOEYE_PROCESS_MODE__NORMAL) {
                $this->setTraceDataIntoContext(array());
                Utils::logContextTrace(Centra::$context, null, false);
            }
        }
    }

    /**
     * execute module
     *
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    protected function executeModule()
    {
        Centra::$context->setModule($this->_mDfnObj);
        Centra::$context->setParallelClientManager(Factory::parallelClientManager());

        $inputs = $this->_mDfnObj->getInputs();
        $this->_setArrayInContext($inputs);
        $setting = ModuleParse::parseInput($this->_mDfnObj->getSetting(), Centra::$context);
        $continueOnError = false;
        if (isset($setting['continue_on_error']) && $setting['continue_on_error'] === 'true') {
            $continueOnError = true;
        }
        $cacheAble = true;
        if (isset($setting['cache_able'])) {
            $cacheAble = ModuleParse::conditionResult($setting['cache_able'], Centra::$context);
        }
        if ($cacheAble && isset($setting['cache'])) {
            Centra::$context->setExpire($setting['cache']);
        }
        if ($cacheAble) {
            Centra::$context->loadCacheData();
        }

        if ($this->processMode > LOEYE_PROCESS_MODE__NORMAL) {
            $this->setTraceDataIntoContext(array());
        }
        $mockMode = Centra::$context->getRequest()->getQuery('ly_p_m');
        if ($this->processMode > LOEYE_PROCESS_MODE__NORMAL && $mockMode === 'mock') {
            $mockPlugins = $this->_mDfnObj->getMockPlugins();
            [$returnStatus] = $this->_executePlugin($mockPlugins, false, true);
        } else {
            $plugins = $this->_mDfnObj->getPlugins();
            [$returnStatus] = $this->_executePlugin($plugins, false, $continueOnError);
        }
        if ($cacheAble) {
            Centra::$context->cacheData();
        }
        if (!empty($returnStatus) && is_string($returnStatus)) {
            Centra::$context->getResponse()->setRenderId($returnStatus);
        }
        return $returnStatus;
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
        $request = Centra::$context->getRequest();
        $request->setRouter(Centra::$context->getRouter());
        $request->setModuleId($moduleId);
        $response = Centra::$context->getResponse();
        if (defined('MOBILE_RENDER_ENABLE') && MOBILE_RENDER_ENABLE && $request->getDevice()) {
            $response->setRenderId(Response::DEFAULT_MOBILE_RENDER_ID);
        }
    }


    /**
     * _executeRouter
     *
     * @return string|null
     * @throws BusinessException
     */
    private function _executeRouter(): ?string
    {
        $moduleId = null;
        $router = new Router();
        Centra::$context->setRouter($router);
        if (Centra::$context->getRequest()->getQuery('m_id')) {
            $moduleId = Centra::$context->getRequest()->getQuery('m_id');
        } else {
            $requestUrl = Centra::$context->getRequest()->getUri()->getPath();
            $moduleId = $router->match($requestUrl);
        }
        return $moduleId;
    }


    /**
     * getView
     *
     * @return array|null
     * @throws Exception
     */
    protected function getView(): ?array
    {
        $renderId = Centra::$context->getResponse()->getRenderId();
        $views = $this->_mDfnObj->getViews();
        if (!empty($renderId) && !empty($views)) {
            if ($renderId === Response::DEFAULT_MOBILE_RENDER_ID && !isset($views[$renderId])) {
                $renderId = Response::DEFAULT_RENDER_ID;
            }
            return $this->_mDfnObj->getView($renderId);
        }
        return null;
    }


    /**
     * CacheContent
     *
     * @param array $view view setting
     * @param string $content content
     *
     * @return void
     * @throws Exception
     * @throws CacheException
     */
    protected function CacheContent($view, $content): void
    {
        if (isset($view['cache'])) {
            if (isset($view['expire'])) {
                $expire = $view['expire'];
            } else if (is_string($view['cache']) || is_numeric($view['cache'])) {
                $expire = (int)$view['cache'];
            } else {
                $expire = 0;
            }
            $cacheParams = [];
            if (is_array($view['cache'])) {
                $cacheParams = ModuleParse::parseInput($view['cache'], Centra::$context);
            }
            Utils::setPageCache(Centra::$context->getAppConfig(), Centra::$context->getRequest()->getModuleId(), $content, $expire, $cacheParams);
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
                $cacheParams = ModuleParse::parseInput($view['cache'], Centra::$context);
            }
            $content = Utils::getPageCache(Centra::$context->getAppConfig(), Centra::$context->getRequest()->getModuleId(), $cacheParams);
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
    protected function getCacheId($view): string
    {
        $cacheId = $this->_mDfnObj->getModuleId();
        if (isset($view['id'])) {
            $cacheId .= '.' . Centra::$context->get($view['id']);
        }
        return $cacheId;
    }


    /**
     * _executePlugin
     *
     * @param array $pluginSetting plugin setting
     * @param boolean $isParallel is parallel
     * @param boolean $continueOnError continue on error
     *
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    private function _executePlugin($pluginSetting, $isParallel = false, $continueOnError = false): array
    {
        $pluginList = array();
        $returnStatus = true;
        if (is_array($pluginSetting)) {
            foreach ($pluginSetting as $plugin) {
                reset($plugin);
                if (!empty($plugin[ModuleParse::CONDITION_KEY]) && (ModuleParse::conditionResult(
                            $plugin[ModuleParse::CONDITION_KEY],
                            Centra::$context) === false)) {
                    continue;
                }
                $key = key($plugin);
                if (ModuleParse::isCondition($key)) {
                    if (ModuleParse::groupConditionResult($key, Centra::$context) === false) {
                        continue;
                    }
                    $result = $this->_executePlugin(current($plugin), $isParallel);
                    [$returnStatus, $oPluginList] = $result;
                    $pluginList = $this->_mergePluginList($pluginList, $oPluginList);
                    if ($this->processMode === LOEYE_PROCESS_MODE__ERROR_EXIT) {
                        return array(
                            $returnStatus,
                            $pluginList,
                        );
                    }
                } else if (ModuleParse::isParallel($key)) {
                    if ($isParallel === true) {
                        throw new Exception(
                            'parallel can not nest',
                            BusinessException::INVALID_CONFIG_SET_CODE);
                    }
                    $result = $this->_executePlugin(current($plugin), true);
                    [, $oPluginList] = $result;
                    $returnStatus = $this->_executeParallelPlugin($oPluginList);
                    $pluginList = $this->_mergePluginList($pluginList, $oPluginList);
                    if ($this->processMode === LOEYE_PROCESS_MODE__ERROR_EXIT) {
                        return array(
                            $returnStatus,
                            $pluginList,
                        );
                    }
                } else {
                    $pluginList[] = $plugin;
                    if ($isParallel === false) {
                        $setting = ModuleParse::parseInput($plugin, Centra::$context);
                        if (isset($setting['inputs'])) {
                            $this->_setArrayInContext($setting['inputs']);
                        }
                        if ($this->checkContextCacheData($setting)) {
                            continue;
                        }
                        $pluginObj = Factory::getPlugin($plugin);
                        if ($pluginObj instanceof ParallelPlugin) {
                            $pluginObj->prepare(Centra::$context, $setting);
                            Centra::$context->getParallelClientManager()->execute();
                            Centra::$context->getParallelClientManager()->reset();
                        }
                        $returnStatus = $pluginObj->process(Centra::$context, $setting);
                        if ($this->processMode > LOEYE_PROCESS_MODE__NORMAL) {
                            $this->setTraceDataIntoContext($plugin);
                        }
                    }
                }
                $breakStatus = $this->_handleError($plugin);
                if ($breakStatus === true) {
                    return array(
                        $returnStatus,
                        $pluginList,
                    );
                }

                if ($returnStatus instanceof Render) {
                    return [$returnStatus, $pluginList];
                }

                if (($returnStatus === false) && $continueOnError === false) {
                    return array(
                        $returnStatus,
                        $pluginList,
                    );
                }
            }
        }
        return array(
            $returnStatus,
            $pluginList,
        );
    }


    /**
     * _executeParallelPlugin
     *
     * @param array $pluginList plugin list
     * @param boolean $continueOnError continue on error
     *
     * @return boolean
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    private function _executeParallelPlugin($pluginList, $continueOnError = false): bool
    {
        $returnStatus = true;
        $pluginObjList = array();
        $settingList = array();
        foreach ($pluginList as $id => $plugin) {
            $setting = ModuleParse::parseInput($plugin, Centra::$context);
            if (isset($setting['inputs'])) {
                $this->_setArrayInContext($setting['inputs']);
            }
            if ($this->checkContextCacheData($setting)) {
                continue;
            }
            $pluginObj = Factory::getPlugin($plugin);
            if (!($pluginObj instanceof ParallelPlugin)) {
                throw new Exception(
                    'plugin of parallel must ParallelPlugin instance',
                    BusinessException::INVALID_PLUGIN_INSTANCE_CODE
                );
            }
            $pluginObj->prepare(Centra::$context, $setting);
            $pluginObjList[$id] = $pluginObj;
            $settingList[$id] = $setting;
        }

        Centra::$context->getParallelClientManager()->execute();
        Centra::$context->getParallelClientManager()->reset();

        foreach ($pluginObjList as $id => $pluginObj) {
            $setting = $settingList[$id];
            $returnStatus = $pluginObj->process(Centra::$context, $setting);

            $breakStatus = $this->_handleError($pluginList[$id]);
            if ($breakStatus === true) {
                return false;
            }

            if ($returnStatus instanceof Render) {
                return $returnStatus;
            }

            if (($returnStatus === false) && $continueOnError === false) {
                return false;
            }
        }

        if ($this->processMode > LOEYE_PROCESS_MODE__NORMAL) {
            $this->setTraceDataIntoContext($pluginList);
        }
        return $returnStatus;
    }

    /**
     * checkContextCacheData
     *
     * @param $setting
     * @return bool
     */
    protected function checkContextCacheData($setting): bool
    {
        if (isset($setting['check_cache'])) {
            if (Utils::checkContextCacheData(Centra::$context, [], $setting['check_cache'])) {
                return true;
            }
        } else if (Utils::checkContextCacheData(Centra::$context, $setting)) {
            return true;
        }
        return false;
    }


    /**
     * _mergePluginList
     *
     * @param array $pluginList1 plugin list 1
     * @param array $pluginList2 plugin list 2
     *
     * @return array
     */
    private function _mergePluginList($pluginList1, $pluginList2): array
    {
        foreach ($pluginList2 as $plugin) {
            $pluginList1[] = $plugin;
        }
        return $pluginList1;
    }


    /**
     * _setArrayInContext
     *
     * @param array $inputs inputs
     *
     * @return void
     */
    private function _setArrayInContext($inputs): void
    {
        if (is_array($inputs)) {
            foreach ($inputs as $key => $value) {
                Centra::$context->set($key, $value);
            }
        }
    }


    /**
     * _handleError
     *
     * @param array $plugin plugin setting
     *
     * @return bool
     * @throws BusinessException
     * @throws Exception
     */
    private function _handleError($plugin): bool
    {
        if (isset($plugin[LOEYE_PLUGIN_HAS_ERROR])) {
            foreach ($plugin[LOEYE_PLUGIN_HAS_ERROR] as $key => $errorSetting) {
                if ($key === 'default') {
                    $errors = Centra::$context->getErrors();
                } else {
                    $errors = Centra::$context->getErrors($key);
                }
                if (empty($errors)) {
                    continue;
                }
                $type = 'error';
                (!empty($errorSetting['type'])) && $type = $errorSetting['type'];
                switch ($type) {
                    case 'view':
                        if (!empty($errorSetting['render_id'])) {
                            Centra::$context->getResponse()->setRenderId($errorSetting['render_id']);
                        }
                        break;
                    case 'url':
                        $url = $this->_getRedirectUrl($errorSetting);
                        if (!empty($url)) {
                            Centra::$context->getResponse()->setRedirect($url);
                        } else {
                            throw new BusinessException(
                                BusinessException::INVALID_RENDER_SET_MSG,
                                BusinessException::INVALID_RENDER_SET_CODE);
                        }
                        break;
                    case 'json':
                    case 'xml':
                        $this->_output($errorSetting);
                        Centra::$context->getResponse()->setRenderId(null);
                        break;
                    default :
                        $error = current($errors);
                        $code = 500;
                        if ($error instanceof \Exception) {
                            $message = $error->getMessage();
                            $code = $error->getCode();
                        } else {
                            $message = is_array($error) ? print_r($errors, true) : $error;
                        }
                        if (isset($errorSetting['code'])) {
                            $code = $errorSetting['code'];
                        }
                        if (isset($errorSetting['message'])) {
                            $message = $errorSetting['message'];
                        }
                        $error = new \Exception($message, $code);
                        $file = !empty($errorSetting['page']) ? $errorSetting['page'] : null;
                        $errorContent = Factory::includeErrorPage(Centra::$context, $error, $file);
                        Centra::$context->getResponse()->addOutput($errorContent);
                        Centra::$context->getResponse()->setRenderId(null);
                        break;
                }
                $this->processMode = LOEYE_PROCESS_MODE__ERROR_EXIT;
                return true;
            }
        }
        return false;
    }


    /**
     * _output
     *
     * @param array $errorSetting error setting
     *
     * @return void
     */
    private function _output($errorSetting): void
    {
        Centra::$context->getResponse()->setFormat($errorSetting['type']);
        $status = Utils::getData($errorSetting, 'code', 200);
        Centra::$context->getResponse()->addOutput($status, 'status');
        $message = Utils::getData($errorSetting, 'msg', 'OK');
        Centra::$context->getResponse()->addOutput($message, 'message');
        $data = array();
        $outDataKey = Utils::getData($errorSetting, 'data', null);
        if (!empty($outDataKey)) {
            $data = Utils::getData(Centra::$context, $outDataKey);
        } else if (isset($errorSetting['error'])) {
            $data = Centra::$context->getErrors($errorSetting['error']);
        }
        Centra::$context->getResponse()->addOutput($data, 'data');
        $url = $this->_getRedirectUrl($errorSetting);
        if (!empty($url)) {
            Centra::$context->getResponse()->addOutput($url, 'redirect');
        }
    }


    /**
     * _getRedirectUrl
     *
     * @param array $errorSetting error setting
     *
     * @return string
     */
    private function _getRedirectUrl($errorSetting): string
    {
        $url = null;
        $routerKey = Utils::getData($errorSetting, 'router_key');
        if (!empty($routerKey) && Centra::$context->getRouter() instanceof Router) {
            $parameter = Utils::getData($errorSetting, 'params', array());
            $router = Centra::$context->getRouter();
            $url = $router->generate($routerKey, $parameter);
        } else {
            $url = Utils::getData($errorSetting, 'url');
        }
        return $url;
    }


    /**
     * parseUrl
     *
     * @param string $moduleId module id
     *
     * @return string|null
     * @throws BusinessException
     */
    protected function parseUrl($moduleId = null): ?string
    {
        if (empty($moduleId)) {
            if (Centra::$context->getRouter() instanceof UrlManager) {
                $moduleId = Centra::$context->getRouter()->match(filter_input(INPUT_SERVER, 'REQUEST_URI'));
            } else {
                $moduleId = $this->_executeRouter();
            }
        }
        return $moduleId;
    }


    /**
     * _addResource
     *
     * @param string $type type
     * @param mixed $resource resource
     *
     * @return void
     * @throws BusinessException
     */
    protected function _addResource($type, $resource): void
    {
        $res = new Resource($type, $resource);
        Centra::$context->getResponse()->addResource($res);
    }

}
