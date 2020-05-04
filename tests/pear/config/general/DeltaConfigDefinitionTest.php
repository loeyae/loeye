<?php

namespace loeye\unit\config\general;

use loeye\config\general\DeltaConfigDefinition;
use loeye\config\Processor;
use loeye\unit\TestCase;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Yaml\Parser;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2020-01-16 at 15:13:23.
 */
class DeltaConfigDefinitionTest extends TestCase {

    /**
     * @var DeltaConfigDefinition
     */
    protected $object;


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new DeltaConfigDefinition;
    }


    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }


    /**
     * @covers \loeye\config\general\DeltaConfigDefinition::getConfigTreeBuilder
     */
    public function testGetConfigTreeBuilder()
    {
        $dumper = new YamlReferenceDumper();
        $definition = $dumper->dump($this->object);
        $this->assertStringContainsString('settings', $definition);
        $processor = new Processor();
        $parser = new Parser();
        $configs = $parser->parseFile(PROJECT_CONFIG_DIR.DIRECTORY_SEPARATOR.'unit/general/delta.yml');
        $settings = $processor->processConfiguration($this->object, $configs);
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('settings', $settings);
        $this->assertArrayHasKey('test', $settings);
    }

}