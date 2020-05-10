<?php

/**
 * GenerateEntityModule.php
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @author  Zhang Yi <loeyae@gmail.com>
 * @version SVN: $Id: Zhang Yi $
 */

namespace loeye\commands;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use loeye\commands\helper\EntityGeneratorTrait;
use loeye\commands\helper\GeneratorUtils;
use loeye\console\Command;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use SmartyException;
use Symfony\Component\Console\{Input\InputInterface, Style\SymfonyStyle};

/**
 * GenerateEntityModule
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class GenerateEntityModule extends Command
{

    use EntityGeneratorTrait;

    protected $args = [
    ];
    protected $params = [
        ['db-id', 'd', 'required' => false, 'help' => 'database setting id', 'default' => 'default'],
        ['filter', 'f', 'required' => false, 'help' => 'filter', 'default' => null],
        ['force', null, 'required' => false, 'help' => 'force update file', 'default' => false],
    ];
    protected $name = 'loeye:generate-entity-module';
    protected $desc = 'generate module with entity';

    private $fileNameMapping = [
        'insert' => 'create',
        'page' => 'list'
    ];
    /**
     * @var string
     */
    private $outputDir;

    /**
     * generateFile
     *
     * @param SymfonyStyle $ui
     * @param ClassMetadata $metadata
     * @param string $namespace
     * @param string $destPath
     * @param boolean $force
     * @throws ReflectionException
     * @throws SmartyException
     */
    protected function generateFile(SymfonyStyle $ui, ClassMetadataInfo $metadata, $namespace,
                                    $destPath, $force): void
    {
        $entityName = GeneratorUtils::getClassName($metadata->reflClass->name);
        $namespace .= '\\' . lcfirst($entityName);
        $serverClass = $this->getServerClass($metadata->reflClass->name);
        $this->writeModule($ui, $namespace, $entityName, $serverClass,
            $metadata->reflClass->name, $force);
    }

    /**
     *
     * @param InputInterface $input
     * @param SymfonyStyle $ui
     * @return string
     * @throws SmartyException
     */
    protected function getDestPath(InputInterface $input, SymfonyStyle $ui): string
    {
        $path = GeneratorUtils::buildPath(PROJECT_DIR, 'conf', 'modules');
        $this->writeModuleToken($ui, $path, $input->getOption('force') ?? false);
        $this->outputDir = $path;
        return GeneratorUtils::buildPath(PROJECT_DIR, 'plugins');
    }

    /**
     * getServerClass
     *
     * @param string $className
     * @return string
     */
    protected function getServerClass($className): string
    {
        return '\\' . str_replace('entity', 'server', $className) . 'Server';
    }

    /**
     * generateAbstractPluginClass
     *
     * @param $fileName
     * @return string
     * @throws SmartyException
     */
    protected function generateModuleToken($fileName): string
    {
        $variables = [
            'fileName' => $fileName,
            'moduleId' => $fileName,
            'pluginStatement' => GeneratorUtils::getCodeFromTemplate('entity/ModuleToken'),
        ];

        return GeneratorUtils::getCodeFromTemplate('entity/Module', $variables);
    }


    /**
     * writeAbstractPluginClass
     *
     * @param SymfonyStyle $ui
     * @param string $outputDirectory
     * @param boolean $force
     * @throws SmartyException
     */
    public function writeModuleToken(SymfonyStyle $ui, $outputDirectory, $force = false): void
    {
        $fileName = 'token';
        $path = GeneratorUtils::buildPath($outputDirectory, $fileName.'.yml');
        $ui->text(sprintf('Processing AbstractPlugin "<info>%s</info>"', $path));
        $code = $this->generateModuleToken($fileName);

        GeneratorUtils::writeFile($path, $code, $force);
    }

    /**
     * write plugin class
     *
     * @param SymfonyStyle $ui
     * @param string $namespace
     * @param string $className
     * @param string $serverClass
     * @param $entityFullName
     * @param bool $force
     * @throws ReflectionException
     * @throws SmartyException
     */
    public function writeModule(SymfonyStyle $ui, $namespace, $className, $serverClass,
                                $entityFullName, $force = false): void
    {
        $refClass = new ReflectionClass($serverClass);
        $methods = $refClass->getMethods();
        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isFinal() || $method->isPrivate()) {
                continue;
            }
            $methodName = $method->getName();
            $fileName = $this->fileNameMapping[$methodName] ?? $methodName;

            $nClassName = ucfirst($className) . ucfirst($methodName) . 'Plugin';

            $fullClassName = $namespace . '\\' . $nClassName;
            $pluginStatement = $this->generatePluginStatement($nClassName, $fullClassName,
                $entityFullName, $fileName);
            $path = GeneratorUtils::buildPath($this->outputDir, lcfirst($className), $fileName.'.yml');
            $ui->text(sprintf('Processing Plugin "<info>%s</info>"', $path));
            $code = GeneratorUtils::getCodeFromTemplate('entity/Module',
                ['fileName' => $fileName, 'moduleId' => lcfirst($className).'.'.$fileName, 'pluginStatement' => $pluginStatement]);
            GeneratorUtils::writeFile($path, $code, $force);
        }
    }

    /**
     * generate params statement
     *
     * @param string $className
     * @param $classFullName
     * @param $entityFullName
     * @param $fileName
     * @return string
     * @throws SmartyException
     */
    protected function generatePluginStatement($className, $classFullName, $entityFullName,
                                               $fileName): string
    {
        $param = [
            'pluginName' => $className,
            'pluginFullName' => $classFullName,
            'entityFullName' => $entityFullName,
        ];
        return GeneratorUtils::getCodeFromTemplate('entity/Module'.ucfirst($fileName), $param);
    }

}
