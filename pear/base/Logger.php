<?php

/**
 * Logger.php
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

use DateTimeZone;
use Monolog;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Throwable;

/**
 * Logger
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Logger
{

    public const LOEYE_LOGGER_TYPE_CRITICAL = Monolog\Logger::CRITICAL;
    public const LOEYE_LOGGER_TYPE_ERROR = Monolog\Logger::ERROR;
    public const LOEYE_LOGGER_TYPE_WARNING = Monolog\Logger::WARNING;
    public const LOEYE_LOGGER_TYPE_NOTICE = Monolog\Logger::NOTICE;
    public const LOEYE_LOGGER_TYPE_INFO = Monolog\Logger::INFO;
    public const LOEYE_LOGGER_TYPE_DEBUG = Monolog\Logger::DEBUG;
    public const LOEYE_LOGGER_TYPE_CONTEXT_TRACE = 50;

    private static $logger = [];

    /**
     * getLogger
     *
     * @param string $name logger name
     * @param string $file log file
     * @param Monolog\Handler\HandlerInterface $handler
     *
     * @return Monolog\Logger
     */
    private static function getLogger($name, $file = null, $handler = null): Monolog\Logger
    {
        $logfile = $file ?? RUNTIME_LOG_DIR . DIRECTORY_SEPARATOR
            . PROJECT_NAMESPACE . DIRECTORY_SEPARATOR
            . 'error-' . $name . '.log';
        $key = md5($logfile);
        if (!isset(self::$logger[$key])) {
            $dateFormat = 'Y-m-d H:i:s';
            $output = "[%datetime%][%level_name%]%channel%: %message%\n";
            $formatter = new LineFormatter($output, $dateFormat);
            $logLevel = defined('RUNTIME_LOGGER_LEVEL') ? RUNTIME_LOGGER_LEVEL : static::LOEYE_LOGGER_TYPE_DEBUG;
            if (!$handler) {
                $handler = new RotatingFileHandler($logfile, 10, $logLevel);
            }
            $handler->setFormatter($formatter);
            $logger = new Monolog\Logger($name);
            Monolog\Logger::setTimezone(new DateTimeZone('Asia/Shanghai'));
            $logger->pushHandler($handler);
            self::$logger[$key] = $logger;
        }
        return self::$logger[$key];
    }

    /**
     * handle
     *
     * @param int $no no
     * @param string $message message
     * @param string $file file
     * @param int $line line
     * @param string $logFile
     *
     * @return void
     */
    public static function handle($no, $message, $file, $line, $logFile = null): void
    {
        switch ($no) {
            case E_ERROR:
                $message = '[core] ' . $message;
                $type = self::LOEYE_LOGGER_TYPE_ERROR;
                break;
            case E_USER_ERROR:
                $message = '[user] ' . $message;
                $type = self::LOEYE_LOGGER_TYPE_ERROR;
                break;
            case E_WARNING:
                $message = '[core] ' . $message;
                $type = self::LOEYE_LOGGER_TYPE_WARNING;
                break;
            case E_USER_WARNING:
                $message = '[user] ' . $message;
                $type = self::LOEYE_LOGGER_TYPE_WARNING;
                break;
            case E_NOTICE:
                $message = '[core] ' . $message;
                $type = self::LOEYE_LOGGER_TYPE_NOTICE;
                break;
            case E_USER_NOTICE:
                $message = '[user] ' . $message;
                $type = self::LOEYE_LOGGER_TYPE_NOTICE;
                break;
            default:
                $message = '[other] ' . $message;
                $type = self::LOEYE_LOGGER_TYPE_ERROR;
                break;
        }
        $log = [$message, '(' . $file . ':' . $line . ')', 'Stack trace:'];
        $log = array_merge($log, self::getTraceInfo());
        self::log($log, $type, $logFile);
    }

    /**
     * trigger
     *
     * @param string $message message
     * @param string $file file
     * @param string $line line
     * @param int $type logger type
     * @param string $logFile
     *
     * @return void
     */
    public static function trigger(
        $message, $file, $line, $type = Logger::LOEYE_LOGGER_TYPE_WARNING, $logFile = null
    ): void
    {
        $log = [$message, '(' . $file . ':' . $line . ')'];
        self::log($log, $type, $logFile);
    }

    /**
     * trace
     *
     * @param string $message message
     * @param int $code code
     * @param string $file file
     * @param string $line line
     * @param int $type log type
     * @param string $logFile
     *
     * @return void
     */
    public static function trace($message, $code, $file, $line, $type =
    self::LOEYE_LOGGER_TYPE_DEBUG, $logFile = null): void
    {
        $log = [];
        $log[] = $message;
        $log[] = 'error code ' . $code;
        $log[] = '(' . $file . ':' . $line . ')';
        $log[] = 'Stack trace:';
        $log = array_merge($log, self::getTraceInfo());
        self::log($log, $type, $logFile);
    }

    /**
     * exception
     *
     * @param Throwable $exc
     * @param string $logFile
     *
     * @return void
     */
    public static function exception(Throwable $exc, $logFile = null): void
    {
        self::trace($exc->getMessage(), $exc->getCode(), $exc->getFile(), $exc->getLine(),
            self::LOEYE_LOGGER_TYPE_ERROR, $logFile);
    }

    /**
     * log
     *
     * @param string|array $message message
     * @param int $type message type
     * @param string $file file
     *
     * @return void
     */
    public static function log($message, $type = self::LOEYE_LOGGER_TYPE_ERROR, $file = null): void
    {
        $name = PROJECT_NAMESPACE;
        $logger = self::getLogger($name, $file);
        if (is_array($message)) {
            foreach ($message as $msg) {
                $logger->log($type, $msg);
            }
        } else {
            $logger->log($type, $message);
        }
    }

    /**
     * critical
     *
     * @param string|array $message
     * @param string $file
     */
    public static function critical($message, $file = null): void
    {
        static::log($message, static::LOEYE_LOGGER_TYPE_CRITICAL, $file);
    }

    /**
     * error
     *
     * @param string|array $message
     * @param string $file
     */
    public static function error($message, $file = null): void
    {
        static::log($message, static::LOEYE_LOGGER_TYPE_ERROR, $file);
    }

    /**
     * warning
     *
     * @param string|array $message
     * @param string $file
     */
    public static function warning($message, $file = null): void
    {
        static::warn($message, $file);
    }

    /**
     * warn
     * @param string|array $message
     * @param string $file
     */
    public static function warn($message, $file = null): void
    {
        static::log($message, static::LOEYE_LOGGER_TYPE_WARNING, $file);
    }

    /**
     * debug
     *
     * @param string|array $message
     * @param string $file
     */
    public static function debug($message, $file = null): void
    {
        static::log($message, static::LOEYE_LOGGER_TYPE_DEBUG, $file);
    }

    /**
     * info
     *
     * @param string|array $message
     * @param string $file
     */
    public static function info($message, $file = null): void
    {
        static::log($message, static::LOEYE_LOGGER_TYPE_INFO, $file);
    }

    /**
     * notice
     *
     * @param string|array $message
     * @param string $file
     */
    public static function notice($message, $file = null): void
    {
        static::log($message, static::LOEYE_LOGGER_TYPE_NOTICE, $file);
    }

    /**
     * getTraceInfo
     *
     * @return array
     */
    public static function getTraceInfo(): array
    {
        $message = [];
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $msg = "{$t['file']}({$t['line']}): ";
            if (isset($t['class'])) {
                $msg .= $t['class'] . '->';
            }
            $msg .= "{$t['function']}()";
            $message[] = $msg;
        }
        if (filter_has_var(INPUT_SERVER, 'REQUEST_URI')) {
            $message[] = '# REQUEST_URI: ' . filter_input(INPUT_SERVER, 'REQUEST_URI') . PHP_EOL;
        }
        return $message;
    }

}
