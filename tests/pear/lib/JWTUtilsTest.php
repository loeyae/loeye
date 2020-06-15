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
    }

    /**
     * @covers \loeye\lib\JWTUtils
     */
    public function testToken()
    {
        $jwt = JWTUtils::init(new \loeye\std\Request())->createToken(['uid' => 1, 'name' => 'test']);
        $payload = JWTUtils::init(new \loeye\std\Request())->verifyToken($jwt);
        $this->assertEquals(1, $payload->uid);
        $this->assertEquals('test', $payload->name);
    }

    /**
     * @covers \loeye\lib\JWTUtils
     * @expectedException Firebase\JWT\ExpiredException
     */
    public function testTokenWithExpired()
    {
        $jwt = JWTUtils::init(new Request())->setLifeTime(-1)->createToken(['uid' => 1, 'name' => 'test']);
        JWTUtils::init(new Request())->verifyToken($jwt);
    }

    public function testVerifyToken()
    {
        $request = new Request();
        $jwt = JWTUtils::init($request)->createToken(['uid' => 1, 'name' => 'test']);
        $request->addHeader('Authorization', $jwt);
        $payload = JWTUtils::init($request)->verifyTokenByHeader();
        $this->assertEquals(1, $payload->uid);
        $this->assertEquals('test', $payload->name);
        
    }


}
