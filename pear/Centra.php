<?php

/**
 * Centra.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/5 19:11
 */

namespace loeye;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use loeye\base\AppConfig;
use loeye\base\Context;
use loeye\base\Factory;
use loeye\std\Request;
use loeye\std\Response;
use loeye\std\Server;
use Psr\Cache\InvalidArgumentException;
use Throwable;

class Centra
{
    /**
     * @var Context
     */
    public static $context;

    /**
     * @var Request
     */
    public static $request;

    /**
     * @var Response
     */
    public static $response;

    /**
     * @var AppConfig
     */
    public static $appConfig;

    /**
     * @param string $dispatcher
     */
    public static function init($dispatcher = Server::DEFAULT_DISPATCHER): void
    {
        static::$appConfig = new AppConfig();
        static::createRequest($dispatcher);
        static::createResponse(static::$request);
        static::$context = new Context(static::$appConfig);
        static::$context->setRequest(static::$request);
        static::$context->setResponse(static::$response);
    }

    /**
     * @param string $dispatcher
     */
    public static function createRequest($dispatcher = Server::DEFAULT_DISPATCHER): void
    {
        static::$request = ($dispatcher === Server::SERVICE_DISPATCHER) ? new service\Request() : new
        web\Request();
    }

    /**
     * @param Request $request
     * @param string $dispatcher
     */
    public static function createResponse(Request $request, $dispatcher = Server::DEFAULT_DISPATCHER): void
    {
        static::$response = ($dispatcher === Server::SERVICE_DISPATCHER) ? new service\Response($request) : new
        web\Response($request);
    }

    /**
     * @param string $dbId
     * @return base\DB
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public static function db($dbId = 'default'): base\DB
    {
        return Factory::db($dbId);
    }

    /**
     * @param string $dbId
     * @return EntityManager
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public static function em($dbId = 'default'): EntityManager
    {
        return Factory::db($dbId)->em();
    }

    /**
     * @param string $dbId
     * @return QueryBuilder
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public static function qb($dbId = 'default'): QueryBuilder
    {
        return Factory::db($dbId)->qb();
    }

    /**
     * @param string|null $type
     * @return base\Cache
     */
    public static function cache($type = null): base\Cache
    {
        return Factory::cache($type);
    }

}