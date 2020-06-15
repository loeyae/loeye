<?php

/**
 * FactoryTest.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 11:49
 */

namespace loeye\unit\base;

use loeye\base\AppConfig;
use loeye\base\AutoLoadRegister;
use loeye\base\Cache;
use loeye\base\Context;
use loeye\base\DB;
use loeye\base\Exception;
use loeye\base\Factory;
use loeye\base\Translator;
use loeye\Centra;
use loeye\client\ParallelClientManager;
use loeye\plugin\BuildQueryPlugin;
use loeye\render\HtmlRender;
use loeye\render\JsonRender;
use loeye\render\SegmentRender;
use loeye\render\XmlRender;
use loeye\std\Plugin;
use loeye\std\Render;
use loeye\std\Router;
use loeye\web\Request;
use loeye\web\Response;
use PHPUnit\Framework\TestCase;
use const loeye\base\RENDER_TYPE_HTML;
use const loeye\base\RENDER_TYPE_JSON;
use const loeye\base\RENDER_TYPE_SEGMENT;
use const loeye\base\RENDER_TYPE_XML;

class FactoryTest extends TestCase
{

    /**
     * @covers \loeye\base\Factory
     */
    public function testTranslator()
    {
        $translator = Factory::translator();
        $translator1 = Factory::translator();
        $this->assertInstanceOf(Translator::class, $translator);
        $this->assertInstanceOf(Translator::class, $translator1);
        $this->assertSame($translator, $translator1);
    }


    /**
     * @covers \loeye\base\Factory
     */
    public function testGetRender()
    {
        $this->assertInstanceOf(Render::class, Factory::getRender(RENDER_TYPE_HTML, new Response
        (new Request())));
        $this->assertInstanceOf(HtmlRender::class, Factory::getRender(RENDER_TYPE_HTML, new Response(new Request())));
        $this->assertInstanceOf(Render::class, Factory::getRender(RENDER_TYPE_JSON, new Response(new Request())));
        $this->assertInstanceOf(JsonRender::class, Factory::getRender(RENDER_TYPE_JSON, new Response(new Request())));
        $this->assertInstanceOf(Render::class, Factory::getRender(RENDER_TYPE_XML, new Response(new Request())));
    }

    /**
     * @covers \loeye\base\Factory
     * @expectedException \loeye\error\BusinessException
     */
    public function testIncludeViewWithException()
    {
        $context = new Context();
        Factory::includeView($context, ['name' => 'default']);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testIncludeView()
    {
        $context = new Context();
        Factory::includeView($context, ['src' => 'default.php', 'handle' => 'default.php']);
        $content = $this->getActualOutput();
        ob_clean();
        $this->assertStringContainsString('<title>Default Page</title>', $content);
        $this->assertStringContainsString('<a href="/">Home</a>', $content);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testAutoload()
    {
//        $this->assertFalse(class_exists('\\mock\\classes\\Test', true));
        Factory::autoload(PROJECT_DIR .DIRECTORY_SEPARATOR);
        spl_autoload_register(static function($className){
            return AutoLoadRegister::load($className);
        });
        $this->assertTrue(class_exists('\\mock\\classes\\Test', true));
    }

    /**
     * @covers \loeye\base\Factory
     * @expectedException \loeye\error\BusinessException
     */
    public function testIncludeLayoutWithException()
    {
        $context = new Context();
        Factory::includeLayout($context, '', []);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testIncludeLayout()
    {
        $context = new Context();
        Factory::includeLayout($context, '<p>Main Content</p>', ['layout' => 'layout.php']);
        $content = $this->getActualOutput();
        ob_clean();
        $this->assertStringContainsString('<title>Layout Page</title>', $content);
        $this->assertStringContainsString('<p>Main Content</p>', $content);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testFetchFile()
    {
        $file = PROJECT_VIEWS_DIR . DIRECTORY_SEPARATOR .'layout.php';
        $content = Factory::fetchFile($file, ['content' => '<p>Main Content</p>']);
        $this->assertStringContainsString('<title>Layout Page</title>', $content);
        $this->assertStringContainsString('<p>Main Content</p>', $content);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testIncludeErrorPage()
    {
        $context = new Context();
        $exc = new \Exception('error message');
        $content = Factory::includeErrorPage($context, $exc);
        $this->assertStringContainsString('<title>出错了</title>', $content);
        $this->assertStringContainsString('内部错误', $content);
        $exc = new Exception();
        $content = Factory::includeErrorPage($context, $exc);
        $this->assertStringContainsString('<title>Error 500 Page</title>', $content);
        $this->assertStringContainsString('500 Error Page', $content);
        $errorFile = 'DefaultError.php';
        $content = Factory::includeErrorPage($context, $exc, $errorFile);
        $this->assertStringContainsString('<title>Error Page</title>', $content);
        $this->assertStringContainsString('Default Error Page', $content);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testDb()
    {
        $db = Factory::db();
        $db1 = Factory::db();
        $this->assertInstanceOf(DB::class, $db);
        $this->assertInstanceOf(DB::class, $db1);
        $this->assertNotSame($db, $db1);
    }

    /**
     * @covers \loeye\base\Factory
     * @expectedException \loeye\error\BusinessException
     */
    public function testGetPluginWithException()
    {
        $plugin = Factory::getPlugin([]);
    }

    /**
     * @covers \loeye\base\Factory
     * @expectedException \loeye\error\ResourceException
     */
    public function testGetPluginWithInvalid()
    {
        $plugin = Factory::getPlugin(['name' => '\plugins\InvalidPlugin', 'src' => PROJECT_DIR .
            DIRECTORY_SEPARATOR .'plugins'. DIRECTORY_SEPARATOR .'InvalidPlugin.php']);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testGetPlugin()
    {
        $plugin = Factory::getPlugin(['name' => '\loeye\plugin\BuildQueryPlugin']);
        $this->assertInstanceOf(BuildQueryPlugin::class, $plugin);
        $plugin = Factory::getPlugin(['name' => '\plugins\TestPlugin', 'src' => PROJECT_DIR .
            DIRECTORY_SEPARATOR. 'plugins' . DIRECTORY_SEPARATOR .'TestPlugin.php']);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertEquals('plugins\TestPlugin', get_class($plugin));
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testIncludeHandle()
    {
        $context = new Context();
        $this->assertNull($context->getRouter());
        Factory::includeHandle($context, ['handle' => 'default.php']);
        $this->assertNotNull($context->getRouter());
        $this->assertInstanceOf(Router::class, $context->getRouter());
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testCache()
    {
        $cache = Factory::cache();
        $cache1 = Factory::cache();
        $this->assertInstanceOf(Cache::class, $cache);
        $this->assertInstanceOf(Cache::class, $cache1);
        $this->assertNotSame($cache, $cache1);
    }

    /**
     * @covers \loeye\base\Factory
     */
    public function testParallelClientManager()
    {
        $parallelClientManager = Factory::parallelClientManager();
        $parallelClientManager1 = Factory::parallelClientManager();
        $this->assertInstanceOf(ParallelClientManager::class, $parallelClientManager);
        $this->assertInstanceOf(ParallelClientManager::class, $parallelClientManager1);
        $this->assertSame($parallelClientManager, $parallelClientManager1);
    }
}
