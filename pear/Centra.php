<?php

/**
 * Centra.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/5 19:11
 */

namespace loeye;


use loeye\base\AppConfig;

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