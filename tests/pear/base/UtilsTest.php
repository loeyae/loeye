<?php

/**
 * UtilsTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 23:58
 */

namespace loeye\unit\base;

use loeye\base\Context;
use loeye\base\Utils;
use PHPUnit\Framework\TestCase;

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
    public function testCheckNotEmptyWithExceptionByArray()
    {
        $context = [];
        Utils::checkNotEmpty($context, 'output');
    }

    public function testCheckNotEmpty()
    {
        Utils::checkNotEmpty();
    }

    public function testGetPageCache()
    {

    }

    public function testRemoveErrors()
    {

    }

    public function testCopyListProperties()
    {

    }

    public function testThrowException()
    {

    }

    public function testSetPageCache()
    {

    }

    public function testLog()
    {

    }

    public function testGetArrayLevel()
    {

    }

    public function testErrorHandle()
    {

    }

    public function testMbUcfirst()
    {

    }

    public function testIncludeModule()
    {

    }

    public function testSource2entity()
    {

    }

    public function testDateFormat()
    {

    }

    public function testAsciiToUtf8()
    {

    }

    public function testFilterResultArray()
    {

    }

    public function testUncamelize()
    {

    }

    public function testLogContextTrace()
    {

    }

    public function testCopyProperties()
    {

    }

    public function testEndWith()
    {

    }

    public function testSetWriteMethodValue()
    {

    }

    public function testCamelize()
    {

    }

    public function testCheckKeyExist()
    {

    }

    public function testCheckNotNull()
    {

    }

    public function testUnsetContextData()
    {

    }

    public function testIncludeView()
    {

    }

    public function testErrorLog()
    {

    }

    public function testEntity2array()
    {

    }

    public function testCheckNotEmptyContextData()
    {

    }

    public function testGetContextData()
    {

    }

    public function testUsc2ToUtf8()
    {

    }

    public function testSetTraceDataIntoContext()
    {

    }

    public function testGetReadMethodValue()
    {

    }

    public function testEntities2array()
    {

    }

    public function testPaginator2array()
    {

    }

    public function testAddErrors()
    {

    }

    public function testGetData()
    {

    }

    public function testAddParallelClient()
    {

    }

    public function testHasException()
    {

    }

    public function testKeyFilter()
    {

    }

    public function testFilterResult()
    {

    }

    public function testCallUserFuncArray()
    {

    }

    public function testCheckContextCacheData()
    {

    }

    public function testSetContextData()
    {

    }

    public function testIncludeTpl()
    {

    }

    public function testStartWith()
    {

    }

    public function testCheckValue()
    {

    }

    public function testThrowError()
    {

    }

    public function testGetErrors()
    {

    }

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
