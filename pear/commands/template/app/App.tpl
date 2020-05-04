<?php

/**
 * app.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version <{$smarty.now|date_format: "%Y-%m-%d %H:%M:%S"}>
 */
require_once 'vendor/autoload.php';

$server = \loeye\server\Factory::create();
$server->run();