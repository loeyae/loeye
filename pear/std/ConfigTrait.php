<?php

/**
 * ConfigTrait.php
 *
 * PHP version 7
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @version  2018-07-23 22:44:28
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\std;

use loeye\base\AppConfig;
use loeye\base\Cache;
use loeye\base\Configuration;
use loeye\base\DB;
use loeye\config\cache\ConfigDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * ConfigTrait
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
trait ConfigTrait
{

    /**
     * bundleConfig
     *
     * @param array|ConfigurationInterface|null $definition definition
     * @param string $bundle bundle
     *
     * @return Configuration
     */
    protected function bundleConfig($definition, $bundle = null): Configuration
    {
        return new Configuration(static::BUNDLE, $bundle, $definition);
    }

    /**
     * propertyConfig
     *
     * @param string $property property
     * @param array|ConfigurationInterface|null $definition definition
     * @param string $bundle bundle
     *
     * @return Configuration
     */
    protected function propertyConfig($property, $definition, $bundle = null): Configuration
    {
        return new Configuration($property, $bundle, $definition);
    }

    /**
     * cacheConfig
     *
     * @return Configuration
     */
    protected function cacheConfig(): Configuration
    {
        $definition = new ConfigDefinition();
        return $this->propertyConfig(Cache::BUNDLE, $definition);
    }

    /**
     * databaseConfig
     *
     * @return Configuration
     */
    protected function databaseConfig(): Configuration
    {
        $definition = new \loeye\config\database\ConfigDefinition();
        return $this->propertyConfig(DB::BUNDLE, $definition);
    }


}
