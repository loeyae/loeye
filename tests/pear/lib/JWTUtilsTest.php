<?php

/**
 * JWTUtilsTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/9 17:51
 */

namespace loeye\unit\lib;

use loeye\base\AppConfig;
use loeye\Centra;
use loeye\lib\JWTUtils;
use loeye\web\Request;
use PHPUnit\Framework\TestCase;

class JWTUtilsTest extends TestCase
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        Centra::$appConfig = new AppConfig();
        Centra::$request = new Request();
        Centra::$request->setServer($_SERVER);
    }

    /**
     * @covers \loeye\lib\JWTUtils
     */
    public function testToken()
    {
        $jwt = JWTUtils::getInstance()->createToken(['uid' => 1, 'name' => 'test']);
        $payload = JWTUtils::getInstance()->verifyToken($jwt);
        $this->assertEquals(1, $payload->uid);
        $this->assertEquals('test', $payload->name);
    }

    /**
     * @covers \loeye\lib\JWTUtils
     * @expectedException Firebase\JWT\ExpiredException
     */
    public function testTokenWithExpired()
    {
        $jwt = JWTUtils::getInstance()->setLifeTime(-1)->createToken(['uid' => 1, 'name' => 'test']);
        JWTUtils::getInstance()->verifyToken($jwt);
    }

    public function testVerifyToken()
    {
        $jwt = JWTUtils::getInstance()->createToken(['uid' => 1, 'name' => 'test']);
        Centra::$request->addHeader('Authorization', $jwt);
        $payload = JWTUtils::getInstance()->verifyTokenByHeader();
        $this->assertEquals(1, $payload->uid);
        $this->assertEquals('test', $payload->name);
        
    }


}
