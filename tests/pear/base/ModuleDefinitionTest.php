<?php

/**
 * ModuleDefinitionTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 18:18
 */

namespace loeye\unit\base;

use loeye\base\Factory;
use loeye\base\ModuleDefinition;
use PHPUnit\Framework\TestCase;

class ModuleDefinitionTest extends TestCase
{
    /**
     * @var ModuleDefinition
     */
    private $moduleDefinition;

    protected function setUp()
    {
        parent::setUp();
        $this->moduleDefinition = new ModuleDefinition(Factory::appConfig(), 'loeyae.login');
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     */
    public function testGetModuleId()
    {
        $this->assertEquals('loeyae.login', $this->moduleDefinition->getModuleId());
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     */
    public function testGetInputs()
    {
        $inputs = $this->moduleDefinition->getInputs();
        $this->assertIsArray($inputs);
        $this->assertArrayHasKey('login', $inputs);
        $this->assertArrayHasKey('set', $inputs);
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     */
    public function testGetSetting()
    {
        $setting = $this->moduleDefinition->getSetting();
        $this->assertIsArray($setting);
        $this->assertArrayHasKey('error_page', $setting);
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     */
    public function testGetPlugins()
    {
        $plugins = $this->moduleDefinition->getPlugins();
        $this->assertIsArray($plugins);
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     */
    public function testGetMockPlugins()
    {
        $plugins = $this->moduleDefinition->getMockPlugins();
        $this->assertNull($plugins);
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     */
    public function testGetViews()
    {
        $views = $this->moduleDefinition->getViews();
        $this->assertIsArray($views);
    }

    /**
     * @throws \loeye\base\Exception
     */
    public function testGetView()
    {
        $view = $this->moduleDefinition->getView();
        $this->assertNotNull($view);
        $this->assertIsArray($view);
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     * @expectedException \loeye\error\BusinessException
     */
    public function testGetViewWithException()
    {
        $this->moduleDefinition->getView('html');
    }

    /**
     * @covers \loeye\base\ModuleDefinition
     * @expectedException \loeye\error\ResourceException
     */
    public function testModuleNotExists()
    {
        $moduleDefinition = new ModuleDefinition(Factory::appConfig(), 'loeyae.logon');
    }

}
