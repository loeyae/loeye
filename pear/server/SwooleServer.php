<?php

/**
 * SwooleServer.php
 *
 * @category PHP
 * @package LOEYE
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/4 0:25
 */

namespace loeye\server;


use loeye\std\Server;

/**
 * Class SwooleServer
 * @package loeye\server
 */
class SwooleServer extends Server
{

    public static function isSupported()
    {
        return extension_loaded('swoole');
    }

    public function run()
    {
        // TODO: Implement run() method.
    }
}