<?php

/**
 * Factory.php
 *
 * @category PHP
 * @package LOEYE
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/4 0:15
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\server;

use loeye\base\AppConfig;
use loeye\Centra;
use loeye\std\Server;

/**
 * Class Factory
 *
 * @package loeye\server
 */
class Factory
{

    public const SERVER_TYPE_SWOOLE = 'swoole';
    public const SERVER_TYPE_REACT = 'react';

    /**
     * @return Server
     */
    public static function create(): Server
    {
        $appConfig = new AppConfig();
        Centra::$appConfig = $appConfig;
        $type = $appConfig->getSetting('server.type', self::SERVER_TYPE_SWOOLE);
        if ($type !== self::SERVER_TYPE_REACT && SwooleServer::isSupported()) {
            return new SwooleServer();
        }
        return new ReactServer();
    }

}