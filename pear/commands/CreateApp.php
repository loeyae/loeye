<?php

/**
 * CreateApp.php
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @author  Zhang Yi <loeyae@gmail.com>
 * @version SVN: $Id: Zhang Yi $
 */

namespace loeye\commands;

use loeye\commands\helper\GeneratorUtils;
use loeye\console\Command;
use loeye\std\Server;
use RuntimeException;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface, Style\SymfonyStyle};
use SmartyException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CreateApp
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class CreateApp extends Command
{

    protected $name = 'loeye:create-app';
    protected $desc = 'create application';
    protected $args = [];
    protected $params = [
        ['dispatcher', 'd', 'required' => false, 'help' => 'dispatcher type', 'default' => null],
        ['path', 'p', 'required' => false, 'help' => 'path', 'default' => null]
    ];
    protected $dirMap = [
        'app' => [
            'commands' => null,
            'conf' => [
                'modules' => null,
                'router' => null,
                'app' => null,
                'cache' => null,
                'database' => null,
                'validate' => null,
            ],
            'controllers' => null,
            'errors' => null,
            'models' => [
                'entity' => null,
                'repository' => null,
                'proxy' => null,
                'server' => null,
            ],
            'plugins' => null,
            'resource' => null,
            'views' => null,
        ],
        'htdocs' => [
            'static' => [
                'css' => null,
                'js' => null,
                'images' => null,
            ]
        ],
        'runtime' => null,
    ];

    /**
     * process
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws SmartyException
     */
    public function process(InputInterface $input, OutputInterface $output): void
    {
        define('LOEYE_MODE', LOEYE_MODE_DEV);
        $ui = new SymfonyStyle($input, $output);
        $dir = $input->getOption('path') ?? getcwd();
        $ui->block($dir);
        $this->mkdir($ui, $dir, $this->dirMap);
        $this->initFile($input, $ui, $dir);
    }


    /**
     *
     * @param SymfonyStyle $ui
     * @param string $base
     * @param mixed $var
     *
     * @return string
     */
    protected function mkdir(SymfonyStyle $ui, string $base, $var): ?string
    {
        $dir = $base;
        if (is_array($var)) {
            foreach ($var as $key => $val) {
                $this->mkdir($ui, $this->mkdir($ui, $base, $key), $val);
            }
        } else {
            if (null !== $var) {
                $dir .= D_S . $var;
            }
            if (!file_exists($dir)) {
                $ui->block(sprintf('mkdir: %1s', $dir));
                if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
                }
            }
        }
        return $dir;
    }


    /**
     * initFile
     *
     * @param SymfonyStyle $ui
     * @param string $base
     * @return void
     * @throws SmartyException
     */
    protected function initFile(InputInterface $input, SymfonyStyle $ui, string $base): void
    {
        $type = $input->getOption('type') ?? Server::DEFAULT_DISPATCHER;
        $fileSystem = new Filesystem();
        switch ($type) {
            case Server::SIMPLE_DISPATCHER:
                $this->initSimpleDispatcherFile($ui, $base, $fileSystem);
                break;
            case Server::SERVICE_DISPATCHER:
                $this->initServiceDispatcherFile($ui, $base, $fileSystem);
                break;
            default:
                $this->initDefaultDispatcherFile($ui, $base, $fileSystem);
                break;
        }
        $dbConfig = $this->buildAppConfigFile($base, 'database');
        $fileSystem->dumpFile($dbConfig, GeneratorUtils::getCodeFromTemplate('app/DatabaseConfig'));
        $ui->block(sprintf('create file: %1s', $dbConfig));
        $cacheConfig = $this->buildAppConfigFile($base, 'cache');
        $fileSystem->dumpFile($cacheConfig, GeneratorUtils::getCodeFromTemplate('app/CacheConfig'));
        $ui->block(sprintf('create file: %1s', $cacheConfig));
        $generalErrorFile = GeneratorUtils::buildPath($base, 'app', 'errors', 'GeneralError.php');
        $fileSystem->dumpFile($generalErrorFile, GeneratorUtils::getCodeFromTemplate('app/GeneralError'));
        $ui->block(sprintf('create file: %1s', $generalErrorFile));
        $layout = GeneratorUtils::buildPath($base, 'app', 'views', 'layout.tpl');
        $fileSystem->dumpFile($layout, GeneratorUtils::getCodeFromTemplate('app/Layout'));
        $ui->block(sprintf('create file: %1s', $layout));
        $home = GeneratorUtils::buildPath($base, 'app', 'views', 'home.tpl');
        $fileSystem->dumpFile($home, GeneratorUtils::getCodeFromTemplate('app/Home'));
        $ui->block(sprintf('create file: %1s', $home));
        $css = GeneratorUtils::buildPath($base, 'htdocs', 'static', 'css', 'bootstrap.css');
        $fileSystem->dumpFile($css, GeneratorUtils::getCodeFromTemplate('app/BootstrapCSS'));
        $ui->block(sprintf('create file: %1s', $css));
        $app = GeneratorUtils::buildPath($base, 'App.php');
        $fileSystem->dumpFile($app, GeneratorUtils::getCodeFromTemplate('app/App'));
        $ui->block(sprintf('create file: %1s', $app));
    }

    /**
     * @param SymfonyStyle $ui
     * @param string $base
     * @param Filesystem $fileSystem
     * @throws SmartyException
     */
    protected function initDefaultDispatcherFile(SymfonyStyle $ui, string $base, Filesystem $fileSystem): void
    {
        $appConfig = $this->buildAppConfigFile($base, 'app');
        $fileSystem->dumpFile($appConfig, GeneratorUtils::getCodeFromTemplate('app/AppConfig'));
        $ui->block(sprintf('create file: %1s', $appConfig));
        $moduleConfig = $this->buildConfigFile($base, 'modules');
        $fileSystem->dumpFile($moduleConfig, GeneratorUtils::getCodeFromTemplate('app/ModuleConfig'));
        $ui->block(sprintf('create file: %1s', $moduleConfig));
        $routerConfig = $this->buildConfigFile($base, 'router');
        $fileSystem->dumpFile($routerConfig, GeneratorUtils::getCodeFromTemplate('app/RouteConfig'));
        $ui->block(sprintf('create file: %1s', $routerConfig));
    }

    /**
     * @param SymfonyStyle $ui
     * @param string $base
     * @param Filesystem $fileSystem
     * @throws SmartyException
     */
    protected function initSimpleDispatcherFile(SymfonyStyle $ui, string $base, Filesystem $fileSystem): void
    {
        $appConfig = $this->buildAppConfigFile($base, 'app');
        $fileSystem->dumpFile($appConfig, GeneratorUtils::getCodeFromTemplate('app/AppConfigSimple'));
        $ui->block(sprintf('create file: %1s', $appConfig));
        $controller = GeneratorUtils::buildPath($base, 'app', 'controllers','IndexController.php');
        $fileSystem->dumpFile($controller, GeneratorUtils::getCodeFromTemplate('app/Controller'));
        $ui->block(sprintf('create file: %1s', $controller));
    }

    /**
     * @param SymfonyStyle $ui
     * @param string $base
     * @param Filesystem $fileSystem
     * @throws SmartyException
     */
    protected function initServiceDispatcherFile(SymfonyStyle $ui, string $base, Filesystem $fileSystem): void
    {
        $appConfig = $this->buildAppConfigFile($base, 'app');
        $fileSystem->dumpFile($appConfig, GeneratorUtils::getCodeFromTemplate('app/AppConfigService'));
        $ui->block(sprintf('create file: %1s', $appConfig));
        $handler = GeneratorUtils::buildPath($base, 'app', 'services', 'handler', 'IndexHandler.php');
        $fileSystem->dumpFile($handler, GeneratorUtils::getCodeFromTemplate('app/Handler'));
        $ui->block(sprintf('create file: %1s', $handler));

    }


    /**
     * buildAppConfigFile
     *
     * @param string $base
     * @param string $type
     *
     * @return string
     */
    protected function buildAppConfigFile(string $base, string $type): string
    {
        return GeneratorUtils::buildPath($base, 'app', 'conf', $type, 'master.yml');
    }


    /**
     * buildConfigFile
     *
     * @param string $base
     * @param string $type
     *
     * @return string
     */
    protected function buildConfigFile(string $base, string $type): string
    {
        return GeneratorUtils::buildPath($base, 'app', 'conf', $type, 'master.yml');
    }

    /**
     * replaceProperty
     *
     * @param string $tpl
     *
     * @return string
     * @throws SmartyException
     */
    protected function replaceProperty(string $tpl): string
    {
        return GeneratorUtils::getCodeFromTemplate($tpl, ['property' => $this->property]);
    }

}
