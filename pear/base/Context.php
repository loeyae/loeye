<?php

/**
 * Context.php
 *
 * PHP version 7
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version  2018-07-23 22:44:28
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\base;

use ArrayAccess;
use Generator;
use loeye\Centra;
use loeye\client\ParallelClientManager;
use loeye\std\Request;
use loeye\std\Response;
use loeye\std\Router;
use loeye\web\Template;
use Psr\Cache\InvalidArgumentException;
use Throwable;

/**
 * Context
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Context
{

    public const ERRORS_KEY = 'errors';

    /**
     * @var ContextData[]
     */
    private $_data;

    /**
     * @var array
     */
    private $_cdata;
    /**
     * @var array
     */
    private $_traceData;
    /**
     * @var array
     */
    private $_errors;
    /**
     * @var AppConfig
     */
    private $_appConfig;
    /**
     * @var Request
     */
    private $_request;
    /**
     * @var Response
     */
    private $_response;
    /**
     * @var ParallelClientManager
     */
    private $_parallelClientManager;
    /**
     * @var Router
     */
    private $_router;
    /**
     * @var int
     */
    private $_expire;
    /**
     * @var Template
     */
    private $_template;
    /**
     * @var ModuleDefinition
     */
    private $_mDfnObj;

    /**
     * @var bool
     */
    private $_errorProcessed;
    private $db = [];
    private $cache = [];

    /**
     * __construct
     *
     * @param AppConfig $appConfig application configuration
     *
     * @return void
     */
    public function __construct(AppConfig $appConfig = null)
    {
        $this->_appConfig = $appConfig ?? Centra::$appConfig ?? new AppConfig();
        $this->_data = array();
        $this->_cdata = array();
        $this->_traceData = array();
        $this->_errors = array();
        $this->_errorProcessed = false;
    }

    /**
     * setExpire
     *
     * @param int $expire expire
     *
     * @return void
     */
    public function setExpire($expire): void
    {
        if ($expire !== null) {
            $this->_expire = (int)$expire;
        }
    }

    /**
     * getExpire
     *
     * @return null|int
     */
    public function getExpire(): ?int
    {
        return $this->_expire ?? null;
    }

    /**
     * @param string $type
     * @return DB
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function db($type = 'default'): DB
    {
        if (!isset($this->db[$type])) {
            $this->db[$type] = DB::init($type);
        }
        return $this->db[$type];
    }

    /**
     * @param null $type
     * @return Cache
     */
    public function cache($type = null): Cache
    {
        $sType = $type ?? 'default';
        if (!isset($this->cache[$sType])) {
            $this->cache[$sType] = Cache::init($type);
        }
        return $this->cache[$sType];
    }

    /**
     * cacheData
     *
     * @return void
     */
    public function cacheData(): void
    {
        $g = $this->getDataGenerator();
        $data = [];
        foreach ($g as $key => $value) {
            if (!$value->isExpire()) {
                $data[$key] = serialize($value);
            }
        }
        if ($data) {
            $this->cache()->set($this->getRequest()->getModuleId(), $data, $this->getExpire());
        }
    }

    /**
     * loadCacheData
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function loadCacheData(): void
    {
        $array = $this->cache()->get($this->getRequest()->getModuleId());
        if ($array) {
            foreach ($array as $key => $value) {
                $cdata = unserialize($value, ['allowed_classes' => true]);
                if (($cdata instanceof ContextData) && !$cdata->isExpire()) {
                    $this->_data[$key] = $cdata;
                    $this->_cdata[$key] = $cdata;
                }
            }
        }
    }

    /**
     * __set
     *
     * @param string $key key
     * @param mixed $value value
     *
     * @return void
     */
    public function __set($key, $value): void
    {
        $this->_data[$key] = ContextData::init($value);
    }

    /**
     * __unset
     *
     * @param string $key key
     *
     * @return void
     */
    public function __unset($key): void
    {
        if (isset($this->_data[$key])) {
            unset($this->_data[$key]);
        }
    }

    /**
     * __get
     *
     * @param string $key key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key]->getData();
        }
        return null;
    }

    /**
     * __isset
     *
     * @param string $key key
     *
     * @return boolean
     */
    public function __isset($key): bool
    {
        return isset($this->_data[$key]);
    }

    /**
     * set
     *
     * @param string $key key
     * @param mixed $value value
     * @param int $expire int
     *
     * @return void
     */
    public function set($key, $value, $expire = 1): void
    {
        $this->_data[$key] = ContextData::init($value, $expire);
    }

    /**
     * get
     *
     * @param string $key key
     * @param mixed $default default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->_data)) {
            $data = $this->_data[$key];
            if ($data->isExpire()) {
                unset($this->_data[$key]);
                return $default;
            }
            return $data();
        }
        return $default;
    }

    /**
     * getWithTrace
     *
     * @param string $key key
     *
     * @return mixed
     */
    public function getWithTrace($key)
    {
        if (array_key_exists($key, $this->_data)) {
            $data = $this->_data[$key];
            return $data(true);
        }
        return null;
    }


    /**
     * setTraceData
     *
     * @param string $key key
     * @param mixed $value value
     *
     * @return void
     */
    public function setTraceData($key, $value): void
    {
        $this->_traceData[$key] = $value;
    }

    /**
     * getTraceData
     *
     * @param string $key key
     *
     * @return mixed
     */
    public function getTraceData($key)
    {
        return $this->_traceData[$key] ?? null;
    }

    /**
     * getData
     *
     * @return array
     */
    public function getData(): array
    {
        $data = [];
        foreach ($this->_data as $key => $value) {
            $data[$key] = $value();
        }
        return $data;
    }

    /**
     * getDataGenerator
     *
     */
    public function getDataGenerator(): Generator
    {
        foreach ($this->_data as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * isExist
     *
     * @param string $key key
     *
     * @return boolean
     */
    public function isExist($key): bool
    {
        return isset($this->_data[$key]) && !$this->_data[$key]->isExpire();
    }

    /**
     * isExistKey
     *
     * @param string $key key
     *
     * @return boolean
     */
    public function isExistKey($key): bool
    {
        return array_key_exists($key, $this->_data);
    }

    /**
     * isExpire
     *
     * @param string $key key
     *
     * @return boolean
     */
    public function isExpire($key): bool
    {
        if (isset($this->_cdata[$key])) {
            return $this->_cdata[$key]->isExpire();
        }
        return true;
    }

    /**
     * isEmpty
     *
     * @param string $key key
     * @param bool $ignore ignore
     *
     * @return boolean
     */
    public function isEmpty($key, $ignore = true): bool
    {
        if (!isset($this->_data[$key])) {
            return true;
        }
        return $this->_data[$key]->isEmpty($ignore);
    }

    /**
     * unsetKey
     *
     * @param string $key key
     *
     * @return void
     */
    public function unsetKey($key): void
    {
        if (isset($this->_data[$key])) {
            unset($this->_data[$key]);
        }
    }

    /**
     * setAppConfig
     *
     * @param AppConfig $appConfig app config
     *
     * @return void
     */
    public function setAppConfig(AppConfig $appConfig = null): void
    {
        $this->_appConfig = $appConfig;
    }

    /**
     * getAppConfig
     *
     * @return AppConfig
     */
    public function getAppConfig()
    {
        return $this->_appConfig;
    }

    /**
     * setRequest
     *
     * @param Request $request request
     *
     * @return void
     */
    public function setRequest(Request $request = null): void
    {
        $this->_request = $request;
    }

    /**
     * getRequest
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * setResponse
     *
     * @param Response $response response
     *
     * @return void
     */
    public function setResponse(Response $response = null): void
    {
        $this->_response = $response;
    }

    /**
     * getResponse
     *
     * @return Response $response response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * setParallelClientManager
     *
     * @param ParallelClientManager $clientManager
     * @return void
     */
    public function setParallelClientManager(ParallelClientManager $clientManager = null): void
    {
        $this->_parallelClientManager = $clientManager;
    }

    /**
     * getParallelClientMgr
     *
     * @return ParallelClientManager
     */
    public function getParallelClientManager()
    {
        return $this->_parallelClientManager;
    }

    /**
     * setRouter
     *
     * @param Router $router router
     *
     * @return void
     */
    public function setRouter(Router $router = null): void
    {
        $this->_router = $router;
    }

    /**
     * getRouter
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * setModule
     *
     * @param ModuleDefinition $module module definition
     *
     * @return void
     */
    public function setModule(ModuleDefinition $module = null): void
    {
        $this->_mDfnObj = $module;
    }

    /**
     * getModule
     *
     * @return ModuleDefinition|null
     */
    public function getModule()
    {
        return $this->_mDfnObj;
    }

    /**
     * setTemplate
     *
     * @param Template $template template
     *
     * @return void
     */
    public function setTemplate(Template $template = null): void
    {
        $this->_template = $template;
    }

    /**
     * getTemplate
     *
     * @return Template|null
     */
    public function getTemplate()
    {
        return $this->_template;
    }

    /**
     * addErrors
     *
     * @param string $errorKey error key
     * @param mixed $errorList error list
     *
     * @return void
     */
    public function addErrors($errorKey, $errorList): void
    {
        if (!is_array($errorList)) {
            $errorList = array($errorList);
        }
        if (isset($this->_errors[$errorKey])) {
            $this->_errors[$errorKey] = array_merge($this->_errors[$errorKey], $errorList);
        } else {
            $this->_errors[$errorKey] = $errorList;
        }
    }

    /**
     * getErrors
     *
     * @param string $errorKey error key
     *
     * @return mixed
     */
    public function getErrors($errorKey = null)
    {
        if (isset($errorKey)) {
            return $this->_errors[$errorKey] ?? null;
        }
        return $this->_errors;
    }

    /**
     * removeErrors
     *
     * @param string $errorKey error key
     *
     * @return void
     */
    public function removeErrors($errorKey): void
    {
        if (isset($this->_errors[$errorKey])) {
            unset($this->_errors[$errorKey]);
        }
    }

    /**
     * hasErrors
     *
     * @param string $errorKey error key
     *
     * @return boolean
     */
    public function hasErrors($errorKey = null): bool
    {
        if (isset($errorKey)) {
            return !empty($this->_errors[$errorKey]);
        }
        return !empty($this->_errors);
    }

}
