<?php

/**
 * Request.php
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

namespace loeye\std;

use GuzzleHttp\Psr7\Uri;
use const loeye\base\RENDER_TYPE_SEGMENT;

/**
 * Request
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Request
{

    private $lang = 'zh_CN';
    private $country = 'cn';

    /**
     * @var Router
     */
    private $router;

    protected $_allowedFormatType = array();
    private $isAjaxRequest;
    public $isHttps;
    public $isFlashRequest;
    public $requestMethod;

    /**
     * @var string
     */
    private $moduleId;

    /**
     * @var array
     */
    private $query;

    /**
     * @var array
     */
    private $body;
    /**
     * @var string
     */
    private $content;
    /**
     * @var array
     */
    private $files;
    /**
     * @var array
     */
    private $header;
    /**
     * @var array
     */
    private $cookie;
    /**
     * @var array
     */
    private $server;
    /**
     * @var Uri
     */
    private $uri;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $moduleId = null;
        $argc = func_num_args();
        if ($argc > 0) {
            $moduleId = func_get_arg(0);
        }
        $this->setModuleId($moduleId);
        $this->_getIsAjaxRequest();
        $this->_getIsFlashRequest();
        $this->_getIsSecureConnection();
        $this->_getRequestType();
    }

    /**
     * setRouter
     *
     * @param Router $router
     * @return Request
     */
    public function setRouter(Router $router): Request
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * getPathVariable
     *
     * @param null $key
     * @return array|mixed|null
     */
    public function getPathVariable($key = null)
    {
        if (null === $key) {
            return $this->router->getPathVariable();
        }
        $pathVariable = $this->router->getPathVariable();
        return $pathVariable[$key] ?? null;
    }

    /**
     * _getRequestType
     *
     * @return void
     */
    private function _getRequestType(): void
    {
        $this->requestMethod ?: $this->requestMethod = mb_strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * _getIsAjaxRequest
     *
     * @return void
     */
    private function _getIsAjaxRequest(): void
    {
        $this->isAjaxRequest ?: $this->isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }

    /**
     * getIsSecureConnection
     *
     * @return void
     */
    private function _getIsSecureConnection(): void
    {
        $this->isHttps = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1)) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * getIsFlashRequest
     *
     * @return void
     */
    private function _getIsFlashRequest(): void
    {
        $this->isFlashRequest = isset($_SERVER['HTTP_USER_AGENT']) && (mb_stripos($_SERVER['HTTP_USER_AGENT'], 'Shockwave') !== false || mb_stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') !== false);
    }

    /**
     * setModuleId
     *
     * @param string $moduleId module id
     *
     * @return Request
     */
    public function setModuleId(string $moduleId): Request
    {
        $this->moduleId = $moduleId;
        return $this;
    }

    /**
     * getModuleId
     *
     * @return string
     */
    public function getModuleId(): string
    {
        $this->moduleId ?: $this->_findModuleId();
        return $this->moduleId;
    }


    /**
     * getFormatType
     *
     * @return string
     */
    public function getFormatType(): string
    {
        $format = $this->getQuery('fmt') ?? RENDER_TYPE_SEGMENT;
        if (in_array($format, $this->_allowedFormatType, true)) {
            return $format;
        }

        return RENDER_TYPE_SEGMENT;
    }

    /**
     * @return array|mixed|null
     */
    private function _findModuleId()
    {
        return $this->getQuery('m_id');
    }

    /**
     * setQuery
     *
     * @param array $query
     * @return Request
     */
    public function setQuery(array $query): Request
    {
        $this->query = $query;
        return $this;
    }

    /**
     * addQuery
     *
     * @param $key
     * @param $value
     * @return Request
     */
    public function addQuery($key, $value): Request
    {
        $this->query[$key] = $value;
        return $this;
    }

    /**
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getQuery(string $key = null)
    {
        if (null === $key) {
            return $this->query;
        }
        return $this->query[$key] ?? null;
    }

    /**
     * @param array $body
     * @return Request
     */
    public function setBody(array $body): Request
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return Request
     */
    public function addBody($key, $value): Request
    {
        $this->body[$key] = $value;
        return $this;
    }

    /**
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getBody(string $key = null)
    {
        if (null === $key) {
            return $this->body;
        }
        return $this->body[$key] ?? null;
    }

    /**
     * @param $content
     * @return Request
     */
    public function setContent($content): Request
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param array $files
     * @return Request
     */
    public function setFiles(array $files): Request
    {
        $this->files = $files;
        return $this;
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param array $header
     * @return Request
     */
    public function setHeader(array $header): Request
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getHeader(string $key = null)
    {
        if (null === $key) {
            return $this->header;
        }
        return $this->header[$key] ?? null;
    }

    /**
     * @param array $cookie
     * @return Request
     */
    public function setCookie(array $cookie): Request
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return Request
     */
    public function addCookie($key, $value): Request
    {
        $this->cookie[$key] = $value;
        return $this;
    }

    /**
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getCookie(string $key = null)
    {
        if (null === $key) {
            return $this->cookie;
        }
        return $this->cookie[$key] ?? null;
    }

    /**
     * @param array $server
     * @return Request
     */
    public function setServer(array $server): Request
    {
        $this->server = $server;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return Request
     */
    public function addServer($key, $value): Request
    {
        $this->server[$key] = $value;
        return $this;
    }

    /**
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getServer(string $key = null)
    {
        if (null === $key) {
            return $this->server;
        }
        return $this->server[$key] ?? null;
    }

    /**
     * @param string $url
     * @return Request
     */
    public function setUri(string $url): Request
    {
        $this->uri = new Uri($url);
        return $this;
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }

}
