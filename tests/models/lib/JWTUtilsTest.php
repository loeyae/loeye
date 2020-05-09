<?php

/**
 * JWTUtilsTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/9 17:51
 */

namespace loeye\models\lib;

use loeye\base\AppConfig;
use loeye\Centra;
use loeye\lib\JWTUtils;
use loeye\web\Request;
use PHPUnit\Framework\TestCase;

class JWTUtilsTest extends TestCase
{

    public function testCreateToken()
    {
        Centra::$appConfig = new AppConfig();
        Centra::$request = new Request();
        Centra::$request->setServer($_SERVER);
        $jwt = JWTUtils::getInstance()->createToken(['uid' => 1, 'name' => 1]);
        var_dump(JWTUtils::getInstance()->getToken($jwt));
    }
}
