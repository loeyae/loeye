<?php

/**
 * ExceptionTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/2 21:30
 */

namespace loeye\unit\base;

use JmesPath\Env;
use loeye\base\Context;
use loeye\base\Exception;
use loeye\base\ModuleDefinition;
use loeye\render\JsonRender;
use loeye\render\SegmentRender;
use loeye\render\XmlRender;
use loeye\web\Request;
use loeye\web\Response;
use PHPUnit\Framework\TestCase;
use function loeye\base\ExceptionHandler;

class ExceptionTest extends TestCase
{
    /**
     * @covers \loeye\base\Exception
     */
    public function testExceptionHandler()
    {
        $context = new Context();
        $context->setRequest(new \loeye\web\Request());
        $context->setResponse(new Response());
        $exc = new \Exception('error message');
        $render = ExceptionHandler($exc, $context);
        $this->assertInstanceOf(SegmentRender::class, $render);
        $context->setResponse(new Response());
        $moduleDefinition = $this->createMock(ModuleDefinition::class);
        $moduleDefinition->method('getSetting')->willReturn(['error_page' => ['default' =>
            'DefaultError.php']]);
        $context->setModule($moduleDefinition);
        $render = ExceptionHandler(new Exception(), $context);
        $this->assertStringContainsString('<title>Error Page</title>', $render->output());
        $context->setResponse(new Response());
        $moduleDefinition = $this->createMock(ModuleDefinition::class);
        $moduleDefinition->method('getSetting')->willReturn(['error_page' => ['default' =>
            'DefaultError.php', 500 => 'Error500.php']]);
        $context->setModule($moduleDefinition);
        $render = ExceptionHandler(new Exception(Exception::DEFAULT_ERROR_MSG,
            Exception::DEFAULT_ERROR_CODE, ['page' => 1]), $context);
        $this->assertStringContainsString('<title>Error 500 Page</title>', $render->output());
        $this->assertInstanceOf(SegmentRender::class, $render);
        $request = $this->createMock(Request::class);
        $request->method('getFormatType')->willReturn('json');
        $context->setResponse(new Response());
        $context->setRequest($request);
        $render = ExceptionHandler($exc, $context);
        $this->assertInstanceOf(JsonRender::class, $render);
        $output = json_decode($render->output(), true);
        $this->assertEquals(500, Env::search('status.code', $output));
        $this->assertEquals('Internal Error', Env::search('status.message', $output));
        $this->assertEquals('error message', Env::search('data', $output));
        $request1 = $this->createMock(Request::class);
        $request1->method('getFormatType')->willReturn('xml');
        $context->setRequest($request1);
        $context->setResponse(new Response());
        $render = ExceptionHandler($exc, $context);
        $this->assertInstanceOf(XmlRender::class, $render);
    }
}
