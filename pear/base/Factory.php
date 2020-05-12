<?php

/**
 * Factory.php
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

namespace loeye\base;

use FilesystemIterator;
use loeye\Centra;
use loeye\client\ParallelClientManager;
use loeye\error\BusinessException;
use loeye\error\ResourceException;
use loeye\std\Plugin;
use loeye\std\Render;
use loeye\std\Response;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Description of Factory
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Factory
{

    /**
     * getPlugin
     *
     * @param array $pluginSetting plugin setting
     *
     * @return Plugin|object
     * @throws Exception
     * @throws ReflectionException
     */
    public static function getPlugin($pluginSetting)
    {
        if (!isset($pluginSetting['name'])) {
            throw new BusinessException(BusinessException::INVALID_PLUGIN_SET_MSG, BusinessException::INVALID_PLUGIN_SET_CODE);
        }
        $class = $pluginSetting['name'];
        if (!isset($pluginSetting['src'])) {
            $rec = new ReflectionClass($class);
        } else {
            $file = AutoLoadRegister::realAliasFile($pluginSetting['src']);
            AutoLoadRegister::loadFile($file);
            $rec = new ReflectionClass($class);
        }
        $plugin =  $rec->newInstanceArgs();
        if (!($plugin instanceof Plugin)) {
            throw new ResourceException(ResourceException::PAGE_NOT_FOUND_MSG, ResourceException::PAGE_NOT_FOUND_CODE);
        }
        return $plugin;
    }

    /**
     * includeLayout
     *
     * @param Context $context context
     * @param string $content content
     * @param array $setting view setting
     *
     * @return void
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function includeLayout(Context $context, $content, $setting): void
    {
        if (!isset($setting['layout'])) {
            throw new BusinessException(BusinessException::INVALID_RENDER_SET_MSG, BusinessException::INVALID_RENDER_SET_CODE);
        }
        $file = AutoLoadRegister::realAliasFile($setting['layout']);
        if (!is_file($file)) {
            $dno = strrpos($file, '.');
            $file = PROJECT_VIEWS_DIR . '/'
                . str_replace('.', '/', substr($file, 0, $dno)) . substr($file, $dno);
        }
        include $file;
    }

    /**
     * includeView
     *
     * @param Context $context context
     * @param array $setting view setting
     *
     * @return void
     * @throws Exception
     */
    public static function includeView(Context $context, $setting): void
    {
        if (!isset($setting['src'])) {
            throw new BusinessException(BusinessException::INVALID_RENDER_SET_MSG, BusinessException::INVALID_RENDER_SET_CODE);
        }
        $file = AutoLoadRegister::realAliasFile($setting['src']);
        if (!is_file($file)) {
            $dno = strrpos($file, '.');
            $file = PROJECT_VIEWS_DIR . '/'
                . str_replace('.', '/', substr($file, 0, $dno)) . substr($file, $dno);
        }
        self::includeHandle($context, $setting);
        include $file;
    }

    /**
     * includeHandle
     *
     * @param Context $context context
     * @param array $setting setting
     *
     * @return void
     */
    public static function includeHandle(Context $context, $setting): void
    {
        if (isset($setting['handle'])) {
            $handle = AutoLoadRegister::realAliasFile($setting['handle']);
            if (!is_file($handle)) {
                $dno = strrpos($handle, '.');
                $handle = PROJECT_HANDLE_DIR . '/'
                    . str_replace('.', '/', substr($handle, 0, $dno)) . substr($handle, $dno);
            }
            include $handle;
        }
    }

    /**
     * getRender
     *
     * @param string $format format
     * @param Response $response
     *
     * @return Render
     * @throws ReflectionException
     */
    public static function getRender($format, Response $response):
    Render
    {
        $renderFormat = array(
            RENDER_TYPE_HTML,
            RENDER_TYPE_SEGMENT,
            RENDER_TYPE_JSON,
            RENDER_TYPE_XML,
        );
        if (!in_array($format, $renderFormat, true)) {
            $format = RENDER_TYPE_SEGMENT;
        }
        $class = '' . ucfirst($format) . 'Render';
        $className = '\\loeye\\render\\' . $class;
        $renderObj = new ReflectionClass($className);
        return $renderObj->newInstance($response);
    }

    /**
     * includeErrorPage
     *
     * @param Context $context context
     * @param Throwable $e exception
     * @param string $errorPage error page
     *
     * @return string
     */
    public static function includeErrorPage(
        Context $context, Throwable $e, $errorPage = null
    ): ?string
    {
        $errorPath = PROJECT_ERRORPAGE_DIR . '/';
        $errorPage = $errorPage ? $errorPath . $errorPage : null;
        if (!is_file($errorPage)) {
            $errorPage = $errorPath . 'Error' . substr($e->getCode(), 0, 3) . '.php';
        }
        if (is_file($errorPage)) {
            return self::fetchFile($errorPage, ['context' => $context, 'exc' => $e]);
        }

        $defaultError = 'General';
        $defaultErrorPage = $errorPath . $defaultError . 'Error.php';
        if (is_file($defaultErrorPage)) {
            return self::fetchFile($defaultErrorPage, ['context' => $context, 'exc' => $e]);
        }

        return self::_getErrorPageInfo($context, $e);
    }

    /**
     * fetchFile
     *
     * @param string $file file path
     * @param array $parameter
     *
     * @return string
     */
    public static function fetchFile($file, $parameter = []): string
    {
        if ($parameter) {
            extract($parameter, EXTR_OVERWRITE & EXTR_PREFIX_INVALID, 'var');
        }
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * _getErrorPageInfo
     *
     * @param Context $context context
     * @param Throwable $e e
     *
     * @return string
     */
    private static function _getErrorPageInfo(Context $context, $e): string
    {
        $appConfig = $context->getAppConfig() ?? Centra::$appConfig;
        $debug = $appConfig ? $appConfig->getSetting('debug', false) : false;
        if ($debug) {
            $traceInfo = nl2br($e->getTraceAsString());
            $html = <<<EOF
<!DOCTYPE html>
<html lang="zh_CN">
    <head>
        <title>出错了</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            body {
                width: 100%;
                height: auto;
                margin: 0 auto;
            }
            #main {
                width: 100%;
                height: auto;
                margin-top: 100px;
                margin-left: auto;
                margin-right: auto;
            }
            #main div {
                width: 90%;
                height: auto;
                line-height: 30px;
                overflow: visible;
                white-space: normal;
                clear:both;
                text-align:center;
                border: 1px #0f3c54 solid;
                font-size: 1.1em;
                color: #ff0000;
                padding: 10px;
                margin: 10px auto;
            }
            #main div span {
                margin-right: 10px;
            }
            #main .info {
                text-align:left;
                margin-left:20px;
            }
        </style>
    </head>
    <body>
        <div id="main">
            <div><span>error code: </span>{$e->getCode()}</div>
            <div><span>error message: </span>{$e->getMessage()}</div>
            <div><span>error trace info:</span><p class="info">{$traceInfo}</p></div>
        </div>
    </body>
</html>
EOF;
        } else {
            $html = <<<EOF
<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <title>出错了</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            body {
                width: 100%;
                height: auto;
                margin: 0 auto;
            }
            #main {
                width: 100%;
                height: auto;
                margin-top: 100px;
                margin-left: auto;
                margin-right: auto;
            }
            #main div {
                width: 90%;
                height: auto;
                line-height: 30px;
                overflow: visible;
                white-space: normal;
                clear:both;
                text-align:center;
                border: 1px #0f3c54 solid;
                font-size: 1.1em;
                color: #ff0000;
                padding: 10px;
                margin: 10px auto;
            }
            #main div span {
                margin-right: 10px;
            }
            #main .info {
                text-align:left;
                margin-left:20px;
            }
        </style>
    </head>
    <body>
        <div id="main">
            <div><span>Internal Error</span></div>
        </div>
    </body>
</html>
EOF;
        }
        return $html;
    }

    /**
     * autoload
     *
     * @param string $dir dir
     * @param bool $ignore ignore children dir
     *
     * @return void
     */
    public static function autoload($dir, $ignore = true): void
    {
        $dir = AutoLoadRegister::realAliasFile($dir);
        if (!file_exists($dir)) {
            return;
        }
        AutoLoadRegister::addDir($dir);
        if (!$ignore) {
            foreach (new FilesystemIterator($dir) as $fs) {
                if ($fs->isDir()) {
                    static::autoload($fs->getRealPath());
                }
            }
        }
    }

    /**
     *
     * @staticvar array  $cache array of Cache's instance
     * @param string $type
     *
     * @return Cache
     */
    public static function cache($type = null): Cache
    {
        $c = Cache::init($type);
        if (!$c) {
            $c = Cache::init(Cache::CACHE_TYPE_FILE);
        }
        return $c;
    }

    /**
     *
     * @staticvar array  $db   array of DB's instance
     * @param string $type
     * @return DB
     * @throws Throwable
     * @throws InvalidArgumentException
     */
    public static function db($type = 'default'): DB
    {
        return DB::init($type);
    }

    /**
     * translator
     *
     * @staticvar \loeye\base\Translator $translator
     *
     * @return Translator
     */
    public static function translator(): Translator
    {
        static $translator = null;
        if (null === $translator) {
            $translator = new Translator(Centra::$appConfig);
        }
        return $translator;
    }

    /**
     * parallelClientManager
     *
     * @return ParallelClientManager
     */
    public static function parallelClientManager(): ParallelClientManager
    {
        static $manager = null;
        if (null === $manager) {
            $manager = new ParallelClientManager();
        }
        return $manager;
    }

}
