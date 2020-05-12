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
     * @var AppConfig
     */
    public static $appConfig;

    /**
     */
    public static function init(): void
    {
        (static::$appConfig instanceof AppConfig) ?: static::$appConfig = new AppConfig();
    }

}