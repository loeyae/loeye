<?php

namespace loeye\unit\base;

use loeye\base\AppConfig;
use PHPUnit\Framework\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2020-01-19 at 09:41:07.
 */
class AppConfigTest extends TestCase
{

    /**
     * @var AppConfig
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $_ENV['LOEYE_PROFILE_ACTIVE'] = 'dev';
        $this->object = new AppConfig();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testGetSetting(): void
    {
        $actual = $this->object->getSetting('constants.BASE_SERVER_URL');
        $expected = 'http://localhost:8088';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testSetTimezone(): void
    {
        $this->object->setTimezone('Asia/Chongqing');
        $actual = $this->object->getTimezone();
        $expected = 'Asia/Chongqing';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testGetTimezone(): void
    {
        $actual = $this->object->getTimezone();
        $expected = 'Asia/Shanghai';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testSetLocale(): void
    {
        $this->object->setLocale('en_US');
        $actual = $this->object->getLocale();
        $expected = 'en_US';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testGetLocale(): void
    {
        $actual = $this->object->getLocale();
        $expected = 'zh_CN';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testGetActiveProfile(): void
    {
        $actual = $this->object->getActiveProfile();
        $expected = 'dev';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testGetServerName(): void
    {
        $actual = $this->object->getServerName();
        $expected = 'dev-demo';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \loeye\base\AppConfig
     */
    public function testGetServerPort(): void
    {
        $actual = $this->object->getServerPort();
        $expected = 80;
        $this->assertEquals($expected, $actual);
    }
}
