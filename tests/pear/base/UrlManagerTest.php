<?php

/**
 * UrlManagerTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 23:25
 */

namespace loeye\unit\base;

use loeye\base\UrlManager;
use PHPUnit\Framework\TestCase;

class UrlManagerTest extends TestCase
{

    /**
     * @var UrlManager
     */
    private $router;

    protected function setUp()
    {
        parent::setUp();
        $this->router = new UrlManager(
            ['/<module:\w+>/<controller:\w+>/<action:\w+>/<id:\w+>' => '{module}/{controller}/{action}',
            '/<module:\w+>/<controller:\w+>/<action:\w+>' => '{module}/{controller}/{action}',
            '/<module:\w+>/<controller:\w+>/' => '{module}/{controller}/index',
            ]);
    }

    /**
     * @covers \loeye\base\UrlManager
     * @covers \loeye\std\Router
     */
    public function testMatch()
    {
        $matched = $this->router->match('http://localhost/admin/login/');
        $this->assertEquals('admin/login/index', $matched);
        $this->assertEquals('/<module:\w+>/<controller:\w+>/', $this->router->getMatchedRule());
        $this->assertNotEmpty($this->router->getMatchedData());
        $this->assertArrayHasKey('module', $this->router->getMatchedData());
        $this->assertArrayHasKey('controller', $this->router->getMatchedData());
        $this->assertNotEmpty($this->router->getSettings());
        $this->assertArrayHasKey('module', $this->router->getSettings());
        $this->assertArrayHasKey('controller', $this->router->getSettings());
        $this->assertNull($this->router->getPathVariable());
        $matched = $this->router->match('http://localhost/admin/item/detail/1');
        $this->assertEquals('admin/item/detail', $matched);
        $this->assertEquals('/<module:\w+>/<controller:\w+>/<action:\w+>/<id:\w+>', $this->router->getMatchedRule());
        $this->assertNotEmpty($this->router->getMatchedData());
        $this->assertArrayHasKey('module', $this->router->getMatchedData());
        $this->assertArrayHasKey('controller', $this->router->getMatchedData());
        $this->assertArrayHasKey('action', $this->router->getMatchedData());
        $this->assertArrayHasKey('id', $this->router->getMatchedData());
        $this->assertNotEmpty($this->router->getSettings());
        $this->assertArrayHasKey('module', $this->router->getSettings());
        $this->assertArrayHasKey('controller', $this->router->getSettings());
        $this->assertArrayHasKey('action', $this->router->getSettings());
        $this->assertIsArray($this->router->getPathVariable());
        $this->assertNotEmpty($this->router->getPathVariable());
        $this->assertArrayHasKey('id', $this->router->getPathVariable());
    }

    /**
     * @covers \loeye\std\Router
     */
    public function testSetSettings()
    {
        $this->router->setSettings(['page' => 1]);
        $this->assertNotNull($this->router->getSettings());
    }

    /**
     * @covers \loeye\base\UrlManager
     */
    public function testGenerate()
    {
        $url = $this->router->generate(['module' => 'admin', 'action' => 'login']);
        $this->assertNull($url);
        $url = $this->router->generate(['module' => 'admin', 'controller' => 'login']);
        $this->assertEquals('http://localhost/admin/login/', $url);
        $url = $this->router->generate(['module' => 'admin', 'controller' => 'item', 'action' => 'list']);
        $this->assertEquals('http://localhost/admin/item/list', $url);
        $url = $this->router->generate(['module' => 'admin', 'controller' => 'item', 'action' => 'list', 'page' => 1]);
        $this->assertEquals('http://localhost/admin/item/list?page=1', $url);
        $url = $this->router->generate(['module' => 'admin', 'controller' => 'item', 'action' => 'list', 'id' => 1]);
        $this->assertEquals('http://localhost/admin/item/list/1', $url);

    }

    /**
     * @covers \loeye\std\Router
     */
    public function testSetPathVariable()
    {
        $this->router->setPathVariable(['page' => 1]);
        $this->assertNotNull($this->router->getPathVariable());
    }

}
