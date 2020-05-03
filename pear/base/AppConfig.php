<?php

/**
 * AppConfig.php
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
 * @link     https://github.com/loeyae/loeye2.git
 */

namespace loeye\base;

use ArrayAccess;
use loeye\config\app\ConfigDefinition;
use loeye\config\app\DeltaDefinition;
use loeye\error\BusinessException;
use loeye\std\ConfigTrait;

/**
 * Description of AppConfig
 *
 * @author  Zhang Yi <loeyae@gmail.com>
 */
class AppConfig
{

    use ConfigTrait;

    public const BUNDLE = 'app';

    private $_timezone;
    private $_locale;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * __construct
     *
     */
    public function __construct()
    {
        $definitions = [new ConfigDefinition(), new DeltaDefinition()];
        $this->configuration = $this->bundleConfig($definitions);
        $this->processConfiguration($this->configuration);
    }

    /**
     * processConfiguration
     *
     * @param Configuration $configuration
     */
    protected function processConfiguration(Configuration $configuration): void
    {
        $profile = $configuration->get('profile');
        $cloneConfig = clone $configuration;
        $deltaConfig = [];
        if ($profile) {
            $deltaConfig = $cloneConfig->getConfig(null, ['profile' => $profile]) ?? [];
        }
        $this->configuration->merge($deltaConfig);
    }


    /**
     * getSetting
     *
     * @param string $key ex: key|key1.key2
     * @param mixed $default default value
     *
     * @return mixed
     */
    public function getSetting($key, $default = null)
    {
        return $this->configuration->get($key, $default);
    }


    /**
     * setTimezone
     *
     * @param string $timezone timezone
     *
     * @return void
     */
    public function setTimezone($timezone): void
    {
        $this->_timezone = $timezone;
    }

    /**
     * getTimezone
     *
     * @return string
     */
    public function getTimezone(): string
    {
        if (!empty($this->_timezone)) {
            return $this->_timezone;
        }
        $timezone = $this->getSetting('configuration.timezone');
        $timezoneList = timezone_identifiers_list();
        if (!empty($timezone) && in_array($timezone, $timezoneList, true)) {
            return $timezone;
        }
        return 'UTC';
    }

    /**
     * setLocale
     *
     * @param string $locale locale
     *
     * @return void
     */
    public function setLocale($locale): void
    {
        $supported = (array)$this->getSetting('locale.supported_languages', ['zh_CN']);
        if (in_array($locale, $supported, true)) {
            $this->_locale = $locale;
        }
    }

    /**
     * getLocale
     *
     * @return string
     */
    public function getLocale(): string
    {
        if (!empty($this->_locale)) {
            return $this->_locale;
        }
        $locale = $this->getSetting('locale.default');
        $supported = (array)$this->getSetting('locale.supported_languages', ['zh_CN']);
        return in_array($locale, $supported, true) ? $locale : $supported[0];
    }

    /**
     * getActiveProfile
     *
     * @return string|null
     */
    public function getActiveProfile(): ?string
    {
        return $this->getSetting('profile');
    }

    /**
     * getServerPort
     *
     * @return int
     */
    public function getServerPort(): int
    {
        return $this->getSetting('server.port') ?? 80;
    }

    /**
     * getServerName
     *
     * @return string
     */
    public function getServerName(): string
    {
        return $this->getSetting('server.name') ?? gethostname();
    }

}
