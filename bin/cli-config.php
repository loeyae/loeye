<?php

/**
 * cli-config.php
 *
 * @author  Zhang Yi <loeyae@gmail.com>
 * @version SVN: $Id: Zhang Yi $
 */

use Doctrine\ORM\Tools\Console\ConsoleRunner;

if (!defined('LOEYE_MODE')) {
    define('LOEYE_MODE', 'dev');
}

if (count($_SERVER['argv']) < 2) {
    echo ' ' . PHP_EOL;
    echo '             Not enough arguments (missing: "db-id").' . PHP_EOL;
    echo ' ' . PHP_EOL;
    echo 'loeye-orm <db-id> [command] [--]' . PHP_EOL;
    exit(0);
}
$dbId            = $_SERVER['argv'][2];
unset($_SERVER['argv'][2]);
$_SERVER['argv'] = array_values($_SERVER['argv']);
$command         = $_SERVER['argv'][1] ?? null;
if ($command === 'convert:mapping') {
    $_SERVER['argv'][1] = 'orm:convert-mapping';
    $_SERVER['argv'][] = '--from-database';
    $_SERVER['argv'][] = '-f';
    $_SERVER['argv'][] = '--namespace=app\\models\\entity\\';
    $_SERVER['argv'][] = 'annotation';
    $_SERVER['argv'][] = dirname(PROJECT_DIR) . '/';
} else if ($command === 'generate:proxies') {
    $_SERVER['argv'][1] = 'orm:generate-proxies';
    $_SERVER['argv'][] = realpath(PROJECT_DIR . '/models/proxy');
} else if ($command === 'generate:repositories') {
    $_SERVER['argv'][1] = 'orm:generate-repositories';
    $_SERVER['argv'][] = dirname(PROJECT_DIR) . '/';
} else if ($command === 'generate:entities') {
    $_SERVER['argv'][1] = 'orm:generate-entities';
    $_SERVER['argv'][] = '--generate-annotations=true';
    $_SERVER['argv'][] = '--regenerate-entities=true';
    $_SERVER['argv'][] = '--update-entities=true';
    $_SERVER['argv'][] = '--generate-methods=true';
    $_SERVER['argv'][] = '--no-backup';
    $_SERVER['argv'][] = dirname(PROJECT_DIR) . '/';
}
$appConfig     = new \loeye\base\AppConfig();
$dbKey         = $appConfig->getSetting('application.database.' . $dbId) ?? 'default';
$db            = \loeye\base\DB::getInstance($appConfig, $dbKey);
$entityManager = $db->em();
$platform      = $entityManager->getConnection()->getDatabasePlatform();
$platform->registerDoctrineTypeMapping("enum", "string");
$platform->registerDoctrineTypeMapping("set", "string");
$platform->registerDoctrineTypeMapping("varbinary", "string");
$platform->registerDoctrineTypeMapping("tinyblob", "text");
return ConsoleRunner::createHelperSet($entityManager);
