<?php

/**
 * GenerateEntity.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/7 14:12
 */


namespace loeye\commands;


use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Version;
use Doctrine\Persistence\Mapping\ClassMetadata;
use loeye\base\AppConfig;
use loeye\base\DB;
use loeye\console\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEntity extends Command
{
    protected $params = [
        ['db-id', 'd', 'required' => false, 'help' => 'database setting id', 'default' => 'default'],
        ['filter', 'f', 'required' => false, 'help' => 'filter', 'default' => null],
        ['force', null, 'required' => false, 'help' => 'force update file', 'default' => false],
    ];
    protected $name = 'loeye:generate-entity';
    protected $desc = 'generate entity';

    /**
     * @inheritDoc
     */
    public function process(InputInterface $input, OutputInterface $output)
    {
        define('LOEYE_MODE', LOEYE_MODE_DEV);
        $dbId = $input->getOption('db-id');
        $appConfig     = new AppConfig();
        $dbKey         = $appConfig->getSetting('application.database.' . $dbId) ?? 'default';
        $db            = DB::getInstance($appConfig, $dbKey);
        $entityManager = $db->em();
        $this->convertMapping($input, $output, $entityManager);
        if (class_exists(Version::class) && Version::VERSION < '3.0') {
            $this->generateEntity($input, $output, $entityManager);
        }
        $metaData = $this->getMetaData($input, $output, $entityManager);
        $this->updateDateTime($metaData);
    }

    /**
     * @param ClassMetadata[] $metaData
     */
    protected function updateDateTime($metaData): void
    {
        foreach ($metaData as $datum) {
            if ($datum->hasField('createTime') || $datum->hasField('modifyTime')) {
                $path = dirname(PROJECT_DIR) . '/' . str_replace('\\', DIRECTORY_SEPARATOR,
                        $datum->name) .'.php';
                self::updateGedmoInfo($path);
            }
        }
    }

    /**
     * @param $file
     * @return int
     */
    protected static function updateGedmoInfo($file): int
    {
        $fp = fopen($file, 'rb');
        $line = 0;
        $updated = 0;
        while (!feof($fp)) {
            $line++;
            $content = fgets($fp);
            if (mb_strpos($content, 'use Doctrine\ORM\Mapping as ORM;') !== false) {
                var_dump($line, 'import');
                 fseek($fp, $line, SEEK_CUR);
                 fwrite($fp, "use Doctrine\ORM\Mapping as ORM;\r\nuse Gedmo\Mapping\Annotation as Gedmo;");
                 $line++;
                 fseek($fp, $line, SEEK_END);
                 $updated++;
            }
            if (mb_strpos($content, 'private $createTime = \'CURRENT_TIMESTAMP\';') !== false) {
                var_dump($line, 'create');
                fseek($fp, $line, SEEK_CUR);
                fwrite($fp, 'private $createTime;');
                fseek($fp, $line-1, SEEK_CUR);
                fwrite($fp, "     * @Gedmo\Timestampable(on=\"create\")\r\n    */");
                $line++;
                fseek($fp, $line, SEEK_END);
                $updated++;
            }
            if (mb_strpos($content, 'private $modifyTime = \'CURRENT_TIMESTAMP\';') !== false) {
                var_dump($line, 'update');
                fseek($fp, $line, SEEK_CUR);
                fwrite($fp, 'private $modifyTime;');
                fseek($fp, $line-1, SEEK_CUR);
                fwrite($fp, "     * @Gedmo\Timestampable(on=\"update\")\r\n    */");
                $line++;
                fseek($fp, $line, SEEK_END);
                $updated++;
            }
            if (mb_strpos($content, 'public function') !== false) {
                break;
            }
            if( $updated === 3) {
                break;
            }
        }
        fclose($fp);
        return $updated;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManager $em
     * @return ClassMetadata[]
     */
    protected function getMetaData(InputInterface $input, OutputInterface $output, EntityManager $em): array
    {
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        return MetadataFilter::filter($metaData, $input->getOption('filter'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManager $em
     * @throws DBALException
     */
    protected function generateEntity(InputInterface $input, OutputInterface $output, EntityManager $em): void
    {
        $command = new GenerateEntitiesCommand();
        $command->setHelperSet($this->createHelperSet($em));
        $generateEntitiesInput = new ArgvInput(
            ['', '--generate-annotations=true', '--regenerate-entities=true', '--update-entities=true',
                '--generate-methods=true', '--extend=loeye\\database\\Entity', '--no-backup',
                dirname(PROJECT_DIR) . '/'],
            $command->getDefinition()
        );
        $command->run($generateEntitiesInput, $output);
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManager $entityManager
     * @throws DBALException
     */
    protected function convertMapping(InputInterface $input, OutputInterface $output, EntityManager $entityManager): void
    {
        $command = new ConvertMappingCommand();
        $command->setHelperSet($this->createHelperSet($entityManager));
        $covertMappingInput = new ArgvInput(
            ['', '-f', '--from-database', '--namespace=app\\models\\entity\\', '--extend=loeye\\database\\Entity',
            'annotation', dirname(PROJECT_DIR) .'/'],
            $command->getDefinition()
        );
        $command->run($covertMappingInput, $output);
    }

    /**
     * @param EntityManager $entityManager
     * @return HelperSet
     * @throws DBALException
     */
    protected function createHelperSet(EntityManager $entityManager): HelperSet
    {
        static $helper;
        if (!$helper) {
            $platform = $entityManager->getConnection()->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('enum', 'string');
            $platform->registerDoctrineTypeMapping('set', 'string');
            $platform->registerDoctrineTypeMapping('varbinary', 'string');
            $platform->registerDoctrineTypeMapping('tinyblob', 'text');
            $helper = ConsoleRunner::createHelperSet($entityManager);
        }
        return $helper;
    }
}