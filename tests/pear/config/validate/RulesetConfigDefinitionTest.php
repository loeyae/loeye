<?php

namespace loeye\unit\config\validate;

use loeye\config\Processor;
use loeye\config\validate\RulesetConfigDefinition;
use loeye\unit\TestCase;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Yaml\Parser;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2020-01-16 at 11:01:50.
 */
class RulesetConfigDefinitionTest extends TestCase
{

    /**
     * @var RulesetConfigDefinition
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new RulesetConfigDefinition;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * @covers \loeye\config\validate\RulesetConfigDefinition::getConfigTreeBuilder
     */
    public function testGetConfigTreeBuilder()
    {
        $dumper = new YamlReferenceDumper();
        $definition = $dumper->dump($this->object);
        $this->assertStringContainsString('settings', $definition);
        $this->assertStringContainsString('rulesets', $definition);
        $processor = new Processor();
        $parser = new Parser();
        $configs = $parser->parseFile(PROJECT_CONFIG_DIR.DIRECTORY_SEPARATOR.'validate/unit/ruleset.yml');
        $settins = $processor->processConfiguration($this->object, $configs);
        $this->assertIsArray($settins);
        $this->assertArrayHasKey('settings', $settins);
        $this->assertArrayHasKey('rulesets', $settins);
    }

}