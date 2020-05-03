<?php

/**
 * EsiUtilTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/2 18:23
 */

namespace loeye\unit\base;

use loeye\base\EsiUtil;
use PHPUnit\Framework\TestCase;

class EsiUtilTest extends TestCase
{

    /**
     * @covers \loeye\base\EsiUtil
     */
    public function testModuleServer()
    {
        $esiUtil1 = new EsiUtil();
        $this->assertEquals('localhost:80', $esiUtil1->getModuleServer());
        $this->assertTrue($esiUtil1->isTryCatchEnable());
        $esiUtil1->setTryCatchEnable(false);
        $this->assertFalse($esiUtil1->isTryCatchEnable());
        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $esiUtil2 = new EsiUtil();
        $this->assertEquals('127.0.0.1:80', $esiUtil2->getModuleServer());
        $_SERVER['HTTP_HOST'] = '192.168.0.2';
        $_SERVER['SERVER_PORT'] = 8080;
        $esiUtil3 = new EsiUtil();
        $this->assertEquals('192.168.0.2:8080', $esiUtil3->getModuleServer());
        $esiUtil4 = new EsiUtil('localhost');
        $this->assertEquals('localhost', $esiUtil4->getModuleServer());
        $esiUtil5 = EsiUtil::getInstance('localhost');
        $this->assertNotSame($esiUtil4, $esiUtil5);
        $this->assertEquals('localhost', $esiUtil5->getModuleServer());
        $esiUtil6 = EsiUtil::getInstance('localhost:8080');
        $this->assertSame($esiUtil5, $esiUtil6);
        $this->assertEquals('localhost', $esiUtil6->getModuleServer());
        $esiUtil7 = EsiUtil::getHttpsInstance('localhost:8080');
        $this->assertNotSame($esiUtil6, $esiUtil7);
        $this->assertEquals('localhost:8080', $esiUtil7->getModuleServer());
        $esiUtil8 = EsiUtil::getHttpsInstance('localhost');
        $this->assertSame($esiUtil7, $esiUtil8);
        $this->assertEquals('localhost:8080', $esiUtil8->getModuleServer());
    }

    /**
     * @covers \loeye\base\EsiUtil
     */
    public function testIncludeModule()
    {
        $esiUtil = new EsiUtil('localhost:8080');
        $esiUtil->includeModule('test.include');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('http://localhost:8080/_remote/?m_id=test.include', $output);
        ob_clean();
        $esiUtil->includeModule('test.include', ['page' => 2]);
        $output1 = $this->getActualOutput();
        $this->assertStringContainsString('src="http://localhost:8080/_remote/?m_id=test.include&page=2"',
            $output1);
        ob_clean();
        $esiUtil->includeModule('test.include', ['page' => 2], 'esi include fail');
        $output2 = $this->getActualOutput();
        $this->assertEquals('<esi:try><esi:attempt><esi:include src="http://localhost:8080/_remote/?m_id=test.include&page=2"/></esi:attempt><esi:except>esi include fail</esi:except></esi:try>',
            $output2);
        ob_clean();
        $esiUtil->setModuleIdPrefix('unit.');
        $esiUtil->includeModule('test.include', ['page' => 2], 'esi include fail');
        $output3 = $this->getActualOutput();
        $this->assertEquals('<esi:try><esi:attempt><esi:include src="http://localhost:8080/_remote/?m_id=unit.test.include&page=2"/></esi:attempt><esi:except>esi include fail</esi:except></esi:try>',
            $output3);
        ob_clean();
    }

    /**
     * @covers \loeye\base\EsiUtil
     */
    public function testIncludeModuleWithModuleServer()
    {
        $esiUtil = EsiUtil::getInstance('localhost:8080');
        $esiUtil->includeModuleWithModuleServer('localhost:8081','test.include');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('http://localhost:8081/_remote/?m_id=test.include',
            $output);
        ob_clean();
        $esiUtil->includeModuleWithModuleServer('localhost:8081','test.include', ['page' => 1]);
        $output = $this->getActualOutput();
        $this->assertStringContainsString('http://localhost:8081/_remote/?m_id=test.include&page=1',
            $output);
        ob_clean();
        $esiUtil->includeModuleWithModuleServer('localhost:8081','test.include', [], 'esi include fail');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('http://localhost:8081/_remote/?m_id=test.include',
            $output);
        $this->assertStringContainsString('esi include fail', $output);
        ob_clean();
    }

    /**
     * @covers \loeye\base\EsiUtil
     * @throws \Exception
     */
    public function testSpecialIncludeModule()
    {
        $esiUtil = new EsiUtil('localhost:8080');
        $esiUtil->specialIncludeModule(['handler' => 'test'],'test.include');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('http://localhost:8080/_remote/?m_id=test.include',
            $output);
        ob_clean();
        $esiUtil->specialIncludeModule(['handler' => 'test'],'test.include', ['page' => 1]);
        $output = $this->getActualOutput();
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('http://localhost:8080/_remote/?m_id=test.include&page=1',
            $output);
        ob_clean();
        $esiUtil->specialIncludeModule(['handler' => 'test'],'test.include', ['page' => 1], 'esi include fail');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('esi include fail',
            $output);
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('http://localhost:8080/_remote/?m_id=test.include&page=1',
            $output);
        ob_clean();
    }

    /**
     * @covers \loeye\base\EsiUtil
     * @expectedException \RuntimeException
     */
    public function testSpecialIncludeModuleException()
    {
        $esiUtil = EsiUtil::getInstance('localhost');
        $esiUtil->specialIncludeModule(['a' => 'b'], 'test.include');
    }

    /**
     * @covers \loeye\base\EsiUtil
     * @throws \Exception
     */
    public function testSpecialIncludeModuleWithModuleServer()
    {
        $esiUtil = new EsiUtil('localhost:8080', true);
        $esiUtil->specialIncludeModuleWithModuleServer(['handler' => 'test'], 'localhost','test.include');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('https://localhost/_remote/?m_id=test.include',
            $output);
        ob_clean();
        $esiUtil->specialIncludeModuleWithModuleServer(['handler' => 'test'], 'localhost','test.include', ['page' => 1]);
        $output = $this->getActualOutput();
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('https://localhost/_remote/?m_id=test.include&page=1',
            $output);
        ob_clean();
        $esiUtil->specialIncludeModuleWithModuleServer(['handler' => 'test'], 'localhost','test.include', ['page' => 1], 'esi include fail');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('esi include fail',
            $output);
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('https://localhost/_remote/?m_id=test.include&page=1',
            $output);
        ob_clean();
        $esiUtil->setQueryArray(['page' => 1]);
        $esiUtil->specialIncludeModuleWithModuleServer(['handler' => 'test'], 'localhost','test.include');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('https://localhost/_remote/?m_id=test.include&page=1',
            $output);
        ob_clean();
        $this->assertEquals(['page' => 1], $esiUtil->getQueryArray());
        $esiUtil->addQueryParam('sort', 'id');
        $esiUtil->specialIncludeModuleWithModuleServer(['handler' => 'test'], 'localhost','test.include');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('https://localhost/_remote/?m_id=test.include&page=1&sort=id',
            $output);
        ob_clean();
        $this->assertEquals(['page' => 1, 'sort' => 'id'], $esiUtil->getQueryArray());
        $esiUtil->setQueryString('group=uid');
        $esiUtil->specialIncludeModuleWithModuleServer(['handler' => 'test'], 'localhost','test.include');
        $output = $this->getActualOutput();
        $this->assertStringContainsString('handler="test"',
            $output);
        $this->assertStringContainsString('https://localhost/_remote/?m_id=test.include&page=1&sort=id&group=uid',
            $output);
        ob_clean();
        $this->assertEquals(['page' => 1, 'sort' => 'id', 'group' => 'uid'],
            $esiUtil->getQueryArray());
    }
}

