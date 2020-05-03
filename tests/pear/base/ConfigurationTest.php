<?php

namespace loeye\unit\base;

use InvalidArgumentException;
use loeye\base\Configuration;
use loeye\config\app\DeltaDefinition;
use loeye\config\module\ConfigDefinition;
use loeye\unit\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2020-01-18 at 13:55:22.
 */
class ConfigurationTest extends TestCase {

    /**
     * @var Configuration
     */
    protected $object;


    protected function setUp()
    {
        $_ENV['LOEYE_PROFILE_ACTIVE'] = 'dev';
        $baseDir = PROJECT_CONFIG_DIR;
        $cacheDir = RUNTIME_CACHE_DIR;
        $definition = [new \loeye\config\app\ConfigDefinition(), new DeltaDefinition()];
        $this->object = new Configuration('app', null, $definition, null,
        $baseDir, $cacheDir);
    }


    protected function tearDown()
    {
        unset($this->object);
    }


    /**
     * @covers \loeye\base\Configuration
     */
    public function testGetBaseDir()
    {
        $expected = PROJECT_UNIT_DIR .DIRECTORY_SEPARATOR. 'conf'
            .DIRECTORY_SEPARATOR.'app';
        $actual = $this->object->getBaseDir();
        $this->assertEquals($expected, $actual);
    }


    /**
     * @covers \loeye\base\Configuration
     */
    public function testGetBundle()
    {
        $expected = null;
        $actual = $this->object->getBundle();
        $this->assertEquals($expected, $actual);
    }


    /**
     * @covers \loeye\base\Configuration
     */
    public function testGetContext()
    {
        $expected = null;
        $actual = $this->object->getContext();
        $this->assertEquals($expected, $actual);
    }


    /**
     * @covers \loeye\base\Configuration
     * @expectedException InvalidArgumentException
     */
    public function testSetDefinition()
    {
        $configuration = new Configuration('app', null);
        $configuration->setDefinition(new \loeye\config\app\ConfigDefinition());
        $configuration->bundle('test');
        $this->assertEquals('test', $configuration->getBundle());
        $this->assertNull($configuration->getContext());
        $this->assertEquals('aaa', $configuration->get('test'));
        unset($configuration);
        $configuration = new Configuration('modules', 'loeyae', new ConfigDefinition());
        $config = $configuration->getConfig();
        $this->assertArrayHasKey('loeyae.login', $config);
        $this->assertArrayHasKey('loeyae.logout', $config);
        $configuration->setDefinition('test');
    }


    /**
     * @covers \loeye\base\Configuration
     */
    public function testGetDefinition()
    {
        $this->assertIsArray($this->object->getDefinition());
    }

    /**
     * @covers \loeye\base\Configuration
     */
    public function testContext()
    {
        $this->object->context('profile=dev');
        $this->assertEquals('profile=dev', $this->object->getContext());
        $this->assertEquals('http://localhost:8088', $this->object->get('constants.BASE_SERVER_URL'));
    }


    /**
     * @covers \loeye\base\Configuration
     */
    public function testGet()
    {
        $expected = 'http://localhost';
        $actual = $this->object->get('constants.BASE_SERVER_URL');
        $this->assertEquals($expected, $actual);
        $this->assertEquals('dev', $this->object->get('profile'));
        $_SERVER['LOEYE_APP_SECRET'] = '111111';
        $this->assertEquals('10001', $this->object->get('application.setting.appid'));
        $this->assertEquals('111111', $this->object->get('application.setting.appsecret'));
        putenv('LOEYE_APP_ID=10002');
        $this->assertEquals('10002', $this->object->get('application.setting.appid'));
    }


    /**
     * @covers \loeye\base\Configuration
     */
    public function testGetConfig()
    {
        $actual = $this->object->getConfig();
        $this->assertIsArray($actual);
        $this->assertEquals('http://localhost', $actual['constants']['BASE_SERVER_URL']);
        $actual = $this->object->getConfig(null, 'profile=dev');
        $this->assertIsArray($actual);
        $this->assertEquals('http://localhost:8088', $actual['constants']['BASE_SERVER_URL']);
        $actual = $this->object->getConfig('test');
        $this->assertIsArray($actual);
        $this->assertEquals('aaa', $actual['test']);
    }


    /**
     * @covers \loeye\base\Configuration
     */
    public function testGetSettings()
    {
        $actual = $this->object->getSettings();
        $this->assertIsArray($actual);
        $this->assertEquals('http://localhost', $actual['constants']['BASE_SERVER_URL']);
        $actual = $this->object->getSettings('test');
        $this->assertIsArray($actual);
        $this->assertEquals('aaa', $actual['test']);
    }

}
