<?php
/**
 * ContextTest.php
 *
 * PHP version 7
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @version  2020/4/18 14:24
 * @link     https://github.com/loeyae/loeye2.git
 */

namespace loeye\unit\base;

use loeye\base\AppConfig;
use loeye\base\Context;
use loeye\base\ContextData;
use loeye\base\Exception;
use loeye\base\ModuleDefinition;
use loeye\base\Router;
use loeye\base\UrlManager;
use loeye\client\ParallelClientManager;
use loeye\web\Request;
use loeye\web\Response;
use loeye\web\Template;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{

    /**
     * @covers \loeye\base\Context
     */
    public function testMagic()
    {
        $context = new Context(new AppConfig());
        $this->assertFalse(isset($context->test));
        $context->test = 'sample';
        $this->assertTrue(isset($context->test));
        $this->assertTrue($context->isExist('test'));
        $this->assertTrue($context->isExistKey('test'));
        $this->assertEquals('sample', $context->test);
        $this->assertNull( $context->get('test'));
        unset($context->test);
        $this->assertFalse(isset($context->test));
        $this->assertFalse($context->isExist('test'));
        $this->assertFalse($context->isExistKey('test'));
        $this->assertNull($context->test);
        $this->assertEquals('aaa', $context->get('test', 'aaa'));
    }

    /**
     * @covers \loeye\base\Context
     */
    public function testTraceData()
    {
        $context = new Context();
        $context->setTraceData('trace', debug_backtrace());
        $traceData = $context->getTraceData('trace');
        $this->assertIsArray($traceData);
        $this->assertEquals(__CLASS__, $traceData[0]['class']);
    }

    /**
     * @covers \loeye\base\Context
     */
    public function testData()
    {
        $context = new Context();
        $context->set('one', 1);
        $context->set('two', 2, 2);
        $context->set('ever', 0, 0);
        $this->assertTrue($context->isExist('one'));
        $this->assertTrue($context->isExist('two'));
        $this->assertTrue($context->isExist('ever'));
        $this->assertFalse($context->isEmpty('one'));
        $this->assertFalse($context->isEmpty('two'));
        $this->assertFalse($context->isEmpty('ever'));
        $this->assertTrue($context->isEmpty('ever', false));
        $data = $context->getData();
        $this->assertIsArray($data);
        $this->assertEquals(3, count($data));
        $this->assertArrayHasKey('one', $data);
        $this->assertArrayHasKey('two', $data);
        $this->assertArrayHasKey('ever', $data);
        $this->assertFalse($context->isEmpty('one'));
        $this->assertFalse($context->isExist('one'));
        $this->assertTrue($context->isExist('two'));
        $this->assertTrue($context->isExist('ever'));
        $this->assertEquals(null, $context->get('one'));
        $this->assertEquals(2, $context->get('two'));
        $this->assertEquals(0, $context->get('ever'));
        $this->assertTrue($context->isEmpty('one'));
        $this->assertFalse($context->isExist('one'));
        $this->assertFalse($context->isExist('two'));
        $this->assertTrue($context->isExist('ever'));
        $this->assertEquals(0, $context->get('ever'));
        $this->assertTrue($context->isExist('ever'));
        $context->set('one',1);
        $this->assertTrue($context->isExist('one'));
        $this->assertEquals(1, $context->getWithTrace('one'));
        $this->assertTrue($context->isExist('one'));
        $this->assertEquals(1, $context->get('one'));
        $this->assertFalse($context->isExist('one'));
        $context->unsetKey('ever');
        $this->assertFalse($context->isExist('ever'));
        $this->assertNull($context->getWithTrace('ever'));
        $context->set('one', 1);
        $context->set('two', 2, 2);
        $context->set('ever', 0, 0);
        $this->assertTrue($context->isExist('one'));
        $this->assertTrue($context->isExist('two'));
        $this->assertTrue($context->isExist('ever'));
        $generator = $context->getDataGenerator();
        $this->assertIsIterable($generator);
        $this->assertTrue($context->isExist('one'));
        $this->assertTrue($context->isExist('two'));
        $this->assertTrue($context->isExist('ever'));
        foreach ($generator as $key => $value) {
            $value();
        }
        $this->assertFalse($context->isExist('one'));
        $this->assertTrue($context->isExist('two'));
        $this->assertTrue($context->isExist('ever'));
    }

    /**
     * @covers \loeye\base\Context
     */
    public function testObject()
    {
        $context = new Context();
        $this->assertInstanceOf(AppConfig::class, $context->getAppConfig());
        $this->assertNull($context->getModule());
        $context->setModule(new ModuleDefinition($context->getAppConfig(), 'loeyae.login'));
        $this->assertInstanceOf(ModuleDefinition::class, $context->getModule());
        $this->assertNull($context->getParallelClientManager());
        $context->setParallelClientManager(new ParallelClientManager());
        $this->assertInstanceOf(ParallelClientManager::class, $context->getParallelClientManager());
        $this->assertNull($context->getRequest());
        $context->setRequest(new Request());
        $this->assertInstanceOf(\loeye\std\Request::class, $context->getRequest());
        $this->assertNull($context->getResponse());
        $context->setResponse(new Response());
        $this->assertInstanceOf(\loeye\std\Response::class, $context->getResponse());
        $this->assertNull($context->getRouter());
        $context->setRouter(new Router());
        $this->assertInstanceOf(\loeye\std\Router::class, $context->getRouter());
        $context->setRouter(new UrlManager($context->getAppConfig()));
        $this->assertInstanceOf(\loeye\std\Router::class, $context->getRouter());
        $this->assertNull($context->getTemplate());
        $context->setTemplate(new Template($context));
        $this->assertInstanceOf(Template::class, $context->getTemplate());
    }

    /**
     * @covers \loeye\base\Context
     */
    public function testErrors()
    {
        $context = new Context();
        $this->assertFalse($context->hasErrors());
        $context->addErrors('validate_errors', 'field one error');
        $this->assertFalse($context->hasErrors('validate_error'));
        $this->assertTrue($context->hasErrors('validate_errors'));
        $this->assertIsArray($context->getErrors());
        $this->assertEquals(1, count($context->getErrors()));
        $this->assertIsArray($context->getErrors('validate_errors'));
        $this->assertEquals(1, count($context->getErrors('validate_errors')));
        $context->addErrors('validate_errors', 'field two error');
        $this->assertEquals(2, count($context->getErrors('validate_errors')));
        $context->addErrors('validate_error', 'field one error');
        $this->assertTrue($context->hasErrors('validate_error'));
        $this->assertEquals(2, count($context->getErrors()));
        $context->removeErrors('validate_errors');
        $this->assertFalse($context->hasErrors('validate_errors'));
        $this->assertEquals(1, count($context->getErrors()));
        $context->removeErrors('validate_error');
        $this->assertFalse($context->hasErrors('validate_error'));
        $this->assertEquals(0, count($context->getErrors()));
    }

    /**
     * @covers \loeye\base\Context
     */
    public function testCacheData()
    {
        $context = new Context();
        $context->setRequest(new Request('loeyae.login'));
        $context->setExpire(3);
        $context->set('cache', 'test');
        $context->cacheData();
        $this->assertTrue($context->isExpire('cache'));
        unset($context);
        $context = new Context();
        $context->setRequest(new Request('loeyae.login'));
        $this->assertNull($context->getExpire());
        $context->loadCacheData();
        $this->assertFalse($context->isExpire('cache'));
        sleep(3);
        $context->loadCacheData();
        $context = new Context();
        $context->setRequest(new Request('loeyae.login'));
        $this->assertNull($context->getExpire());
        $context->loadCacheData();
        $this->assertTrue($context->isExpire('cache'));
        $this->assertNull($context->get('cache'));
    }

    /**
     * @covers \loeye\base\ContextData
     */
    public function testContextData()
    {
        $data = ContextData::init(1);
        $this->assertEquals('1', $data);
        $array = ContextData::init([1,2]);
        $this->assertEquals(var_export([1,2], true), (string)$array);
    }

}
