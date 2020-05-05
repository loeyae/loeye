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
use loeye\base\Context;
use loeye\std\Request;
use loeye\std\Response;

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

}