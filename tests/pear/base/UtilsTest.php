<?php

/**
 * UtilsTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 23:58
 */

namespace loeye\unit\base;

use loeye\base\AppConfig;
use loeye\base\Cache;
use loeye\base\Context;
use loeye\base\DB;
use loeye\base\Exception;
use loeye\base\Factory;
use loeye\base\Logger;
use loeye\base\Router;
use loeye\base\Utils;
use loeye\Centra;
use loeye\models\entity\Test;
use loeye\render\JsonRender;
use PHPUnit\Framework\TestCase;
use React\Stream\Util;
use Symfony\Component\Filesystem\Filesystem;

class UtilsTest extends TestCase
{

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\LogicException
     */
    public function testCheckNotEmptyWithExceptionByContext()
    {
        $context = new Context();
        Utils::checkNotEmpty($context, 'output');
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\LogicException
     */
    public function testCheckNotEmptyWithExceptionByContextValueIsZero()
    {
        $context = new Context();
        $context->set('output', 0);
        Utils::checkNotEmpty($context, 'output', false);
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\LogicException
     */
    public function testCheckNotEmptyWithExceptionByArray()
    {
        $context = [];
        Utils::checkNotEmpty($context, 'output');
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\LogicException
     */
    public function testCheckNotEmptyWithExceptionByArrayValueIsZero()
    {
        $context = ['test' => 0];
        Utils::checkNotEmpty($context, 'test', false);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCheckNotEmpty()
    {
        $context = new Context();
        $context->set('test', 'aa');
        $this->assertEquals('aa', Utils::checkNotEmpty($context, 'test'));
        $context->set('test', 0);
        $this->assertEquals(0, Utils::checkNotEmpty($context, 'test'));
        $data = ['test' => 'a'];
        $this->assertEquals('a', Utils::checkNotEmpty($data, 'test'));
        $data = ['test' => 0];
        $this->assertEquals(0, Utils::checkNotEmpty($data, 'test'));
    }

    /**
     * @covers \loeye\base\Utils
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \loeye\base\Exception
     */
    public function testPageCache()
    {
        $appConfig = new AppConfig();
        Cache::getInstance($appConfig, 'templates')->delete('loeye.login');
        $this->assertNull(Utils::getPageCache($appConfig, 'loeye.login'));
        Utils::setPageCache($appConfig, 'loeye.login', 'test');
        $this->assertEquals('test', Utils::getPageCache($appConfig, 'loeye.login'));
        Cache::getInstance($appConfig, 'templates')->delete('loeye.login');
        Utils::setPageCache($appConfig, 'loeye.login', 'test', 0);
        $this->assertEquals(null, Utils::getPageCache($appConfig, 'loeye.login'));
        Cache::getInstance($appConfig, 'templates')->delete('loeye.login?a=1');
        Utils::setPageCache($appConfig, 'loeye.login', 'test', 1, ['a' => 1]);
        $this->assertEquals('test', Utils::getPageCache($appConfig, 'loeye.login', ['a' => 1]));
        Cache::getInstance($appConfig, 'templates')->delete('loeye.login?a=1');
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\BusinessException
     */
    public function testRemoveErrorsWithEexcption()
    {
        $context = new Context();
        $context->addErrors('error_1', 'error message 1');
        Utils::removeErrors($context, [], null);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testRemoveErrors()
    {
        $context = new Context();
        $context->addErrors('error_1', 'error message 1');
        $context->addErrors('error_2', 'error message 2');
        $context->addErrors('error_3', 'error message 3');
        Utils::removeErrors($context, ['err' => 'error_1'], null);
        $this->assertNull($context->getErrors('error_1'));
        Utils::removeErrors($context, ['err_key' => 'error_2'], null);
        $this->assertNull($context->getErrors('error_2'));
        Utils::removeErrors($context, [], 'error_3');
        $this->assertNull($context->getErrors('error_3'));
    }

    /**
     * @covers \loeye\base\Utils
     * @throws \ReflectionException
     */
    public function testCopyListProperties()
    {
        $data = [
            ['id' => 1, 'name' => 'name 1'],
            ['id' => 2, 'name' => 'name 2'],
            ['id' => 3, 'name' => 'name 3'],
        ];
        $array = Utils::copyListProperties($data, Test::class);
        $this->assertIsArray($array);
        $this->assertInstanceOf(Test::class, $array[0]);
        $this->assertEquals(1, $array[0]->getId());
        $this->assertInstanceOf(Test::class, $array[1]);
        $this->assertEquals(2, $array[1]->getId());
        $this->assertInstanceOf(Test::class, $array[2]);
        $this->assertEquals(3, $array[2]->getId());
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\base\Exception
     */
    public function testThrowException()
    {
        Utils::throwException('error message');
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\BusinessException
     */
    public function testThrowExceptionWithBusinessException()
    {
        Utils::throwException('error message', 500, [], \loeye\error\BusinessException::class);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testLog()
    {
        $appLogFile = RUNTIME_LOG_DIR.DIRECTORY_SEPARATOR.PROJECT_NAMESPACE
            .DIRECTORY_SEPARATOR.'error-'.PROJECT_NAMESPACE.'-'.date('Y-m-d').'.log';
        Utils::log('error message');
        $this->assertTrue(file_exists($appLogFile));
        $appLogFile = RUNTIME_LOG_DIR.DIRECTORY_SEPARATOR.PROJECT_NAMESPACE
            .DIRECTORY_SEPARATOR.'trace-'.PROJECT_NAMESPACE.'-'.date('Y-m-d').'.log';
        Utils::log('trace message', Logger::LOEYE_LOGGER_TYPE_CONTEXT_TRACE);
        $this->assertTrue(file_exists($appLogFile));
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testGetArrayLevel()
    {
        $array = [1];
        $this->assertEquals(1, Utils::getArrayLevel($array));
        $array = [1, [1]];
        $this->assertEquals(2, Utils::getArrayLevel($array));
        $array = [1, [1],[1,[1, [1]]]];
        $this->assertEquals(4, Utils::getArrayLevel($array));
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testErrorHandle()
    {
        Utils::errorHandle(0, 'error message', __FILE__, __LINE__);
        $appLogFile = RUNTIME_LOG_DIR.DIRECTORY_SEPARATOR.PROJECT_NAMESPACE
            .DIRECTORY_SEPARATOR.'error-'.PROJECT_NAMESPACE.'-'.date('Y-m-d').'.log';
        $this->assertTrue(file_exists($appLogFile));
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testMbUcfirst()
    {
        $this->assertEquals('A12bs', Utils::mbUcfirst('a12bs'));
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testIncludeModule()
    {
        Centra::$appConfig = new AppConfig();
        $render = Utils::includeModule('loeyae.include.output');
        $this->assertInstanceOf(JsonRender::class, $render);
        $this->assertEquals(200, $render->code());
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testSource2entity()
    {
        $test = Utils::source2entity(['id' => 1, 'name' => 'name 1'],Test::class);
        $this->assertInstanceOf(Test::class, $test);
        $this->assertEquals(1, $test->getId());
        $source = new Test();
        $source->setId(11);
        $source->setName('test');
        $target = Utils::source2entity($source, Test::class);
        $this->assertInstanceOf(Test::class, $target);
        $this->assertNotSame($source, $target);
        $this->assertEquals(11, $target->getId());
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testDateFormat()
    {
        $date = Utils::dateFormat('zh_CN', time());
        $this->assertNotNull($date);
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $date);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testAsciiToUtf8()
    {
        $string = Utils::asciiToUtf8('');
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testFilterResultArray()
    {
        $data = false;
        $error = false;
        $result = [];
        Utils::filterResult($result, $data, $error);
        $this->assertIsArray($data);
        $this->assertFalse($error);
        $data = false;
        $error = false;
        $result = new Exception();
        Utils::filterResult($result, $data, $error);
        $this->assertFalse($data);
        $this->assertInstanceOf(Exception::class, $error);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testUncamelize()
    {
        $string = Utils::uncamelize('UtilsTest');
        $this->assertEquals('utils_test', $string);
        $string = Utils::uncamelize('utilsTest');
        $this->assertEquals('utils_test', $string);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testLogContextTrace()
    {
        $context = new Context();
        $context->setTraceData(LOEYE_CONTEXT_TRACE_KEY, ['trace_time' => time()]);
        Utils::logContextTrace($context);
        $appLogFile = RUNTIME_LOG_DIR.DIRECTORY_SEPARATOR.PROJECT_NAMESPACE
            .DIRECTORY_SEPARATOR.'trace-'.PROJECT_NAMESPACE.'-'.date('Y-m-d').'.log';
        $this->assertTrue(file_exists($appLogFile));
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCopyProperties()
    {
        $test = new Test();
        Utils::copyProperties(['id' => 1, 'name' => 'test', 'sex' => 1], $test);
        $this->assertEquals(1, $test->getId());
        $this->assertEquals('test', $test->getName());
        $source = new Test();
        $source->setId(11);
        $source->setName('tex');
        $target = new Test();
        Utils::copyProperties($source, $target);
        $this->assertEquals($source->getId(), $target->getId());
        $this->assertEquals($source->getName(), $target->getName());
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testEndWith()
    {
        $this->assertTrue(Utils::endWith('IndexAction', 'Action'));
        $this->assertFalse(Utils::endWith('IndexAction', 'action'));
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testSetWriteMethodValue()
    {
        $test = new Test();
        $this->assertNull($test->getId());
        Utils::setWriteMethodValue($test, 'id', 1);
        $this->assertEquals(1, $test->getId());
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCamelize()
    {
        $this->assertEquals('indexAction', Utils::camelize('index_action'));
        $this->assertEquals('indexAction', Utils::camelize('index_Action'));
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\LogicException
     */
    public function testCheckKeyExistWithExceptionByContext()
    {
        $context = new Context();
        Utils::checkKeyExist($context, 'test');
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\LogicException
     */
    public function testCheckKeyExistWithExceptionByArray()
    {
        $context = [];
        Utils::checkKeyExist($context, 'test');
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCheckKeyExist()
    {
        $context = new Context();
        $context->set('test', 'aa');
        $this->assertEquals('aa', Utils::checkKeyExist($context, 'test'));
        $array = ['text' => 'message'];
        $this->assertEquals('message', Utils::checkKeyExist($array, 'text'));
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\DataException
     */
    public function testCheckNotNull()
    {
        $a = null;
        Utils::checkNotNull($a);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testUnsetContextData()
    {
        $context = new Context();
        $context->set('key1', 'value1');
        $context->set('key2', 'value2');
        $context->set('key3', 'value3');
        $context->set('key4', 'value4');
        $this->assertNotNull($context->get('key1'));
        $this->assertNotNull($context->get('key2'));
        $this->assertNotNull($context->get('key3'));
        $this->assertNotNull($context->get('key4'));
        Utils::unsetContextData($context, ['in' => 'key1'], null);
        $this->assertNull($context->get('key1'));
        Utils::unsetContextData($context, ['input' => 'key2'], null);
        $this->assertNull($context->get('key2'));
        Utils::unsetContextData($context, ['input_key' => 'key3'], null);
        $this->assertNull($context->get('key3'));
        Utils::unsetContextData($context, [], 'key4');
        $this->assertNull($context->get('key4'));

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testIncludeView()
    {
        $context = new Context();
        $context->setRouter(new Router());
        Utils::includeView('default.php', ['context' => $context]);
        $view = $this->getActualOutput();
        ob_clean();
        $this->assertNotNull($view);
        $content = 'default content';
        Utils::includeView(PROJECT_VIEWS_DIR.DIRECTORY_SEPARATOR.'layout.php', ['content' =>
            $content]);
        $view1 = $this->getActualOutput();
        ob_clean();
        $this->assertNotNull($view1);
        $this->assertStringContainsString('default content', $view1);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testErrorLog()
    {
        Utils::errorLog(new Exception());
        $appLogFile = RUNTIME_LOG_DIR.DIRECTORY_SEPARATOR.PROJECT_NAMESPACE
            .DIRECTORY_SEPARATOR.'error-'.PROJECT_NAMESPACE.'-'.date('Y-m-d').'.log';
        $this->assertTrue(file_exists($appLogFile));

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testEntity2array()
    {
        $test = new Test();
        $test->setId(1);
        $test->setName('test');
        $data = Utils::entity2array(DB::getInstance(new AppConfig())->em(), $test);
        $this->assertNotNull($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('test', $data['name']);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCheckNotEmptyContextData()
    {
        $context = new Context();
        $context->set('key1', 'val1');
        $context->set('key2', 0);
        $context->set('key3', 'val3');
        $context->set('key4', 'val4');
        $this->assertEquals('val1', Utils::checkNotEmptyContextData($context, ['in' => 'key1']));
        $this->assertEquals(0, Utils::checkNotEmptyContextData($context, ['input' => 'key2']));
        $this->assertEquals('val3', Utils::checkNotEmptyContextData($context, ['input_key' =>
            'key3']));
        $this->assertEquals('val4', Utils::checkNotEmptyContextData($context, [], 'key4'));
    }

    /**
     * @covers \loeye\base\Utils
     * @expectedException \loeye\error\BusinessException
     */
    public function testCheckNotEmptyContextDataWithException()
    {
        $context = new Context();
        $context->set('key1', 'val1');
        $context->set('key2', 0);
        Utils::checkNotEmptyContextData($context, [], null);
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testGetContextData()
    {
        $context = new Context();
        $context->set('key1', 'val1');
        $context->set('key2', 0);
        $context->set('key3', 'val3');
        $context->set('key4', 'val4');
        $this->assertEquals('val1', Utils::getContextData($context, ['in' => 'key1']));
        $this->assertEquals(0, Utils::getContextData($context, ['input' => 'key2']));
        $this->assertEquals('val3', Utils::getContextData($context, ['input_key' => 'key3']));
        $this->assertEquals('val4', Utils::getContextData($context, [], 'key4'));
    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testUsc2ToUtf8()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testSetTraceDataIntoContext()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testGetReadMethodValue()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testEntities2array()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testPaginator2array()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testAddErrors()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testGetData()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testAddParallelClient()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testHasException()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testKeyFilter()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testFilterResult()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCallUserFuncArray()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCheckContextCacheData()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testSetContextData()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testIncludeTpl()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testStartWith()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testCheckValue()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testThrowError()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testGetErrors()
    {

    }

    /**
     * @covers \loeye\base\Utils
     */
    public function testMimeType()
    {
        $file = PROJECT_DIR .DIRECTORY_SEPARATOR .'htdocs'.DIRECTORY_SEPARATOR.'index.html';
        $mimeType = Utils::mimeType($file);
        $this->assertEquals('text/html; charset=utf-8', $mimeType);
        $file = PROJECT_DIR .DIRECTORY_SEPARATOR .'htdocs'.DIRECTORY_SEPARATOR.'favicon.ico';
        $mimeType = Utils::mimeType($file);
        $this->assertEquals('image/x-icon; charset=binary', $mimeType);
        $file = PROJECT_DIR .DIRECTORY_SEPARATOR .'htdocs'.DIRECTORY_SEPARATOR. 'css' .
            DIRECTORY_SEPARATOR.'style.css';
        $mimeType = Utils::mimeType($file);
        $this->assertEquals('text/css', $mimeType);
        $file = PROJECT_DIR .DIRECTORY_SEPARATOR .'htdocs'.DIRECTORY_SEPARATOR. 'js' .
            DIRECTORY_SEPARATOR.'main.js';
        $mimeType = Utils::mimeType($file);
        $this->assertEquals('text/javascript', $mimeType);
        $file = PROJECT_DIR .DIRECTORY_SEPARATOR .'htdocs'.DIRECTORY_SEPARATOR.'package.json';
        $mimeType = Utils::mimeType($file);
        $this->assertEquals('application/json', $mimeType);
        $file = PROJECT_DIR .DIRECTORY_SEPARATOR .'phpunit.xml';
        $mimeType = Utils::mimeType($file);
        $this->assertEquals('application/xml; charset=us-ascii', $mimeType);
    }
}
