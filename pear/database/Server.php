<?php

/**
 * Server.php
 *
 * PHP version 7
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @version  2020年1月20日 下午6:38:55
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\database;

use loeye\base\AppConfig;
use loeye\base\Context;
use loeye\base\DB;
use loeye\base\Exception;
use Psr\Cache\InvalidArgumentException;
use Throwable;

/**
 * Server
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Server
{
    use EntityTrait;
    use RepositoryTrait;

    /**
     *
     * @var DB;
     */
    protected $db;

    /**
     *
     * @var string
     */
    protected $entityClass;

    /**
     *
     * @var AppConfig
     */
    protected $appConfig;

    /**
     * Server constructor.
     * @param Context $context
     * @param null $type
     * @param bool $singleConnection
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function __construct(Context $context, $type = null, $singleConnection = true)
    {
        $this->db = $singleConnection ? $context->db($type) : new DB($type);
    }

    /**
     * setEntity
     *
     * @param string $entity
     */
    final public function setEntity($entity): void
    {
        $this->entityClass = $entity;
    }

    /**
     *
     * @return string
     */
    final public function getEntity(): string
    {
        return $this->entityClass;
    }

}
