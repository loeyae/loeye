<?php

/**
 * LoggerTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 18:16
 */

namespace loeye\unit\base;

use loeye\base\Logger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('log'));
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testWarn()
    {
        $file = vfsStream::url('log/unit.log');
        Logger::warn(['test message', 'log message'], $file);
        $name = 'unit-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[WARNING]app: test message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[WARNING]app: log message', file_get_contents
        ($stream->url()));
        vfsStreamWrapper::unregister();
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testTrigger()
    {
        $file = vfsStream::url('log/trigger.log');
        Logger::trigger('test message', __FILE__, __LINE__, Logger::LOEYE_LOGGER_TYPE_INFO,
            $file);
        $name = 'trigger-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[INFO]app: test message', file_get_contents($stream->url
        ()));
        $this->assertStringContainsString('[INFO]app: ('.__FILE__.':', file_get_contents
        ($stream->url()));
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testWarning()
    {
        $file = vfsStream::url('log/warning.log');
        Logger::warning('test message', $file);
        $name = 'warning-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[WARNING]app: test message', file_get_contents
        ($stream->url()));
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testHandle()
    {
        $file = vfsStream::url('log/handle.log');
        Logger::handle(500, 'error message', __FILE__, __LINE__, $file);
        $name = 'handle-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[ERROR]app: [other] error message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: ('.__FILE__, file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: Stack trace', file_get_contents
        ($stream->url()));
        Logger::handle(E_ERROR, 'error message', __FILE__, __LINE__, $file);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[ERROR]app: [core] error message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: ('.__FILE__, file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: Stack trace', file_get_contents
        ($stream->url()));
        Logger::handle(E_WARNING, 'error message', __FILE__, __LINE__, $file);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[WARNING]app: [core] error message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[WARNING]app: ('.__FILE__, file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[WARNING]app: Stack trace', file_get_contents
        ($stream->url()));
        Logger::handle(E_NOTICE, 'error message', __FILE__, __LINE__, $file);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[NOTICE]app: [core] error message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[NOTICE]app: ('.__FILE__, file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[NOTICE]app: Stack trace', file_get_contents
        ($stream->url()));
        Logger::handle(E_USER_ERROR, 'error message', __FILE__, __LINE__, $file);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[ERROR]app: [user] error message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: ('.__FILE__, file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: Stack trace', file_get_contents
        ($stream->url()));
        Logger::handle(E_USER_WARNING, 'error message', __FILE__, __LINE__, $file);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[WARNING]app: [user] error message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[WARNING]app: ('.__FILE__, file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[WARNING]app: Stack trace', file_get_contents
        ($stream->url()));
        Logger::handle(E_USER_NOTICE, 'error message', __FILE__, __LINE__, $file);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[NOTICE]app: [user] error message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[NOTICE]app: ('.__FILE__, file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[NOTICE]app: Stack trace', file_get_contents
        ($stream->url()));
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testLog()
    {
        $file = vfsStream::url('log/log.log');
        Logger::log('test message', Logger::LOEYE_LOGGER_TYPE_ERROR, $file);
        $name = 'log-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[ERROR]app: test message', file_get_contents($stream->url()));
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testCritical()
    {
        $file = vfsStream::url('log/critical.log');
        Logger::critical('critical message', $file);
        $name = 'critical-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[CRITICAL]app: critical message', file_get_contents
        ($stream->url()));
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testTrace()
    {
        $file = vfsStream::url('log/trace.log');
        Logger::trace('trace message', 500, __FILE__, __LINE__, Logger::LOEYE_LOGGER_TYPE_ERROR,
            $file);
        $name = 'trace-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[ERROR]app: trace message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: error code 500', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: ('. __FILE__, file_get_contents
        ($stream->url()));
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testGetTraceInfo()
    {
        $traceInfo = Logger::getTraceInfo();
        $this->assertIsArray($traceInfo);
        $this->assertStringContainsString(__FILE__, $traceInfo[0]);
    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testException()
    {
        $file = vfsStream::url('log/exception.log');
        Logger::exception(new \Exception('exception message'), $file);
        $name = 'exception-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[ERROR]app: exception message', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: error code 0', file_get_contents
        ($stream->url()));
        $this->assertStringContainsString('[ERROR]app: ('. __FILE__, file_get_contents
        ($stream->url()));

    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testNotice()
    {
        $file = vfsStream::url('log/notice.log');
        Logger::notice('notice message', $file);
        $name = 'notice-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[NOTICE]app: notice message', file_get_contents
        ($stream->url()));

    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testDebug()
    {
        $file = vfsStream::url('log/debug.log');
        Logger::debug('debug message', $file);
        $name = 'debug-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[DEBUG]app: debug message', file_get_contents
        ($stream->url()));

    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testError()
    {
        $file = vfsStream::url('log/error.log');
        Logger::error('error message', $file);
        $name = 'error-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[ERROR]app: error message', file_get_contents
        ($stream->url()));

    }

    /**
     * @covers \loeye\base\Logger
     */
    public function testInfo()
    {
        $file = vfsStream::url('log/info.log');
        Logger::info('info message', $file);
        $name = 'info-'.date('Y-m-d').'.log';
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($name));
        $stream = vfsStreamWrapper::getRoot()->getChild($name);
        $this->assertStringContainsString('[INFO]app: info message', file_get_contents
        ($stream->url()));

    }

}
