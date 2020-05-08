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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
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
        $this->updateEntity($metaData);
        if (class_exists(Version::class) && Version::VERSION < '3.0') {
            $this->generateRepository($input, $output, $entityManager);
        }
    }

    protected function generateRepository(InputInterface $input, OutputInterface $output, EntityManager $em)
    {
        $command = new GenerateRepositoriesCommand();
        $command->setHelperSet($this->createHelperSet($em));
        $generateEntitiesInput = new ArgvInput(
            ['', dirname(PROJECT_DIR) . '/'],
            $command->getDefinition()
        );
        $command->run($generateEntitiesInput, $output);
    }

    /**
     * @param ClassMetadata[] $metaData
     */
    protected function updateEntity($metaData): void
    {
        foreach ($metaData as $datum) {
            if ($datum->hasField('createTime') || $datum->hasField('modifyTime')) {
                $path = dirname(PROJECT_DIR) . '/' . str_replace('\\', DIRECTORY_SEPARATOR,
                        $datum->name) .'.php';
                $name = substr($datum->name, strrpos($datum->name, '\\')+1);
                self::updateEntityInfo($path, $name, $datum);
            }
        }
    }

    /**
     * @param $file
     * @param $name
     * @param ClassMetadata $metadata
     * @return void
     */
    protected static function updateEntityInfo($file, $name, ClassMetadata $metadata): void
    {
        $fp = new \SplFileObject( $file, 'rb');
        $line = 0;
        $newContent = [];
        while (!$fp->eof()) {
            $content = $fp->fgets();
            $newContent[] = $content;
            if (preg_match('/private \$(?<name>\w+)/', $content, $matches)) {
                $validate = self::generateValidate($matches['name'], $metadata);
            }
            if (mb_strpos($content, 'use Doctrine\\ORM\\Mapping as ORM') !== false) {
                 $newContent[] = "use Gedmo\\Mapping\\Annotation as Gedmo;\r\n";
                 $line++;
            } elseif (mb_strpos($content, '* @ORM\\Entity') !== false) {
                $newContent[$line] = " * @ORM\Entity(repositoryClass=\"\\app\models\\repository\\". $name ."Repository\")\r\n";
            } elseif (mb_strpos($content, 'private $createTime = \'CURRENT_TIMESTAMP\';') !== false) {
                $newContent[$line] = "    private \$createTime;\r\n";
                array_splice($newContent, $line - 1, 0, ["     * @Gedmo\Timestampable(on=\"create\")\r\n"]);
                $line++;
            } elseif (mb_strpos($content, 'private $modifyTime = \'CURRENT_TIMESTAMP\';') !== false) {
                $newContent[$line] = "    private \$modifyTime;\r\n";
                array_splice($newContent, $line - 1, 0, ["     * @Gedmo\Timestampable(on=\"update\")\r\n"]);
                $line++;
            }
            $line++;
        }
        file_put_contents($file, implode('', $newContent));
    }

    protected static function generateValidate($name, ClassMetadataInfo $metadata)
    {
        $valudate = [];
        $fieldMapping = $metadata->getFieldMapping($name);
        if (in_array($name, $metadata->identifier)) {
            $valudate[] = "     * @Assert\IsNull(groups={\"insert\"})\r\n";
            $valudate[] = "     * @Assert\NotNull(groups={\"update\", \"delete\"})\r\n";
        } elseif (!$fieldMapping['nullable']) {
            $valudate[] = "     * @Assert\NotNull(groups={\"insert\", \"update\"})\r\n";
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManager $em
     * @return ClassMetadata[]
     */
    protected function getMetaData(InputInterface $input, OutputInterface $output, EntityManager $em): array
    {
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadatas = $cmf->getAllMetadata();
        return MetadataFilter::filter($metadatas, $input->getOption('filter'));
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