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
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Version;
use loeye\base\AppConfig;
use loeye\base\DB;
use loeye\base\Factory;
use loeye\commands\helper\GeneratorUtils;
use loeye\console\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
     * @param $name
     * @return string
     */
    private static function getRepositoryName($name): string
    {
        return str_replace('\\entity\\', '\\repository\\', $name).'Repository';
    }

    /**
     * @inheritDoc
     */
    public function process(InputInterface $input, OutputInterface $output)
    {
        define('LOEYE_MODE', LOEYE_MODE_DEV);
        $ui = new SymfonyStyle($input, $output);
        $dbId = $input->getOption('db-id');
        $appConfig     = Factory::appConfig();
        $dbKey         = $appConfig->getSetting('application.database.' . $dbId) ?? 'default';
        $db            = DB::init($dbKey);
        $entityManager = $db->em();
        $this->convertMapping($input, $output, $entityManager);
        if (class_exists(Version::class) && Version::VERSION < '3.0') {
            $this->generateEntity($input, $output, $entityManager);
        }
        $metaData = $this->getMetaData($input, $output, $entityManager);
        $this->updateEntity($metaData);
        if (class_exists(Version::class) && Version::VERSION < '3.0') {
            $this->generateRepository($input, $ui, $metaData);
        }
    }

    /**
     * @param InputInterface $input
     * @param SymfonyStyle $ui
     * @param ClassMetadataInfo[] $metaData
     * @throws \SmartyException
     */
    protected function generateRepository(InputInterface $input, SymfonyStyle $ui,  $metaData): void
    {
        foreach ($metaData as $metaDatum) {
            $repositoryClass = self::getRepositoryName($metaDatum->name);
            $className = GeneratorUtils::getClassName($repositoryClass);
            $namespace = GeneratorUtils::getNamespaceByFullName($repositoryClass);
            $code = GeneratorUtils::getCodeFromTemplate('entity/Repository', ['namespace' =>
                $namespace, 'className' => $className]);
            GeneratorUtils::writePHPClass(dirname(PROJECT_DIR) .'/', $repositoryClass, $code, true);
            $ui->text(sprintf('Processing entity "<info>%s</info>"', $repositoryClass));
        }
    }

    /**
     * @param ClassMetadataInfo[] $metaData
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function updateEntity($metaData): void
    {
        foreach ($metaData as $datum) {
            if ($datum->hasField('createTime') || $datum->hasField('modifyTime')) {
                $path = dirname(PROJECT_DIR) . '/' . str_replace('\\', DIRECTORY_SEPARATOR,
                        $datum->name) .'.php';
                self::updateEntityInfo($path, $datum->name, $datum);
            }
        }
    }

    /**
     * @param $file
     * @param $name
     * @param ClassMetadataInfo $metadata
     * @return void
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected static function updateEntityInfo($file, $name, ClassMetadataInfo $metadata): void
    {
        $fp = new \SplFileObject( $file, 'rb');
        $line = 0;
        $newContent = [];
        $entityLine = 0;
        while (!$fp->eof()) {
            $content = $fp->fgets();
            $newContent[] = $content;
            if (preg_match('/private \$(?<name>\w+)/', $content, $matches)) {
                $validate = self::generateValidate($matches['name'], $metadata);
                array_splice($newContent, $line - 1, 0, $validate);
                $line += count($validate);
            }
            if (mb_strpos($content, 'use Doctrine\\ORM\\Mapping as ORM') !== false) {
                 $newContent[] = "use Gedmo\\Mapping\\Annotation as Gedmo;\r\n";
                 $newContent[] = "use Symfony\Component\Validator\Constraints as Assert;\r\n";
                 $line+=2;
            } elseif (mb_strpos($content, '* @ORM\\Entity') !== false) {
                $entityLine = $line;
                $newContent[$line] = " * @ORM\Entity(repositoryClass=\"". self::getRepositoryName
                    ($name)."\")\r\n";
            } elseif (mb_strpos($content, 'private $createTime = \'CURRENT_TIMESTAMP\';') !== false) {
                $newContent[$line] = "    private \$createTime;\r\n";
                array_splice($newContent, $line - 1, 0, ["     * @Gedmo\Timestampable(on=\"create\")\r\n"]);
                $line++;
            } elseif (mb_strpos($content, 'private $modifyTime = \'CURRENT_TIMESTAMP\';') !== false) {
                $newContent[$line] = "    private \$modifyTime;\r\n";
                array_splice($newContent, $line - 1, 0, ["     * @Gedmo\Timestampable(on=\"update\")\r\n"]);
                $line++;
            } elseif (mb_strpos($content, '$this->createTime = $createTime') !== false) {
                array_splice($newContent, $line, 0,
                    [
                        "        if (is_string(\$createTime)) {\r\n",
                        "            \$createTime = \DateTime::createFromFormat('Y-m-d H:i:s', \$createTime);\r\n",
                        "        }\r\n"]);
                $line += 3;
            } elseif (mb_strpos($content, '$this->modifyTime = $modifyTime') !== false) {
                array_splice($newContent, $line, 0,
                    ["        if (is_string(\$modifyTime)) {\r\n",
                        "            \$modifyTime = \DateTime::createFromFormat('Y-m-d H:i:s', \$modifyTime);\r\n",
                        "        }\r\n"]);
                $line += 3;
            } elseif (mb_strpos($content, 'private $deleteTime') !== false) {
                array_splice($newContent, $entityLine + 1, 0, [" * @Gedmo\SoftDeleteable(fieldName=\"deleteTime\",  timeAware=false, hardDelete=false)\r\n"]);
                $line++;
            }
            $line++;
        }
        file_put_contents($file, implode('', $newContent));
    }

    /**
     * @param $name
     * @param ClassMetadataInfo $metadata
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected static function generateValidate($name, ClassMetadataInfo $metadata): array
    {
        $validate = [];
        $fieldMapping = $metadata->getFieldMapping($name);
        $columnName = $metadata->getColumnName($name);
        if (($fieldMapping['type'] === 'datetime' && isset($fieldMapping['options']['default'])
            && $fieldMapping['options']['default'] === 'CURRENT_TIMESTAMP') || $columnName
            === 'delete_time') {
            $validate[] = "     * @Assert\IsNull(groups={\"create\",\"update\"})\r\n";
            $validate[] = "     * @Assert\DateTime(groups={\"query\"})\r\n";
            return $validate;
        }
        if (in_array($columnName, $metadata->identifier, true)) {
            $validate[] = "     * @Assert\IsNull(groups={\"create\"})\r\n";
            $validate[] = "     * @Assert\GreaterThan(value=\"0\", groups={\"update\", \"delete\"})\r\n";
        } elseif (!$fieldMapping['nullable']) {
            $validate[] = "     * @Assert\NotNull(groups={\"create\"})\r\n";
            if (isset($fieldMapping['length']) && $fieldMapping['length'] > 0) {
                $validate[] = "     * @Assert\Length(max=". $fieldMapping['length'] .", min=1, normalizer=\"trim\", allowEmptyString=false, groups={\"create\",\"update\"})\r\n";
            }
        } else if (isset($fieldMapping['length']) && $fieldMapping['length'] > 0) {
            $validate[] = "     * @Assert\Length(max=". $fieldMapping['length'] .", normalizer=\"trim\", allowEmptyString=true, groups={\"create\",\"update\"})\r\n";
        }
        $validate[] = self::buildTypeValidate($fieldMapping);
        return $validate;
    }

    /**
     * @param $fieldMapping
     * @return string
     */
    protected static function buildTypeValidate($fieldMapping): ?string
    {
        switch ($fieldMapping['type']){
            case 'integer':
            case 'tinyint':
            case 'smallint':
            case 'bool':
            case 'mediumint':
            case 'bigint':
                return "     * @Assert\Regex(pattern=\"/\d+/\", groups={\"create\",\"update\", \"query\"})\r\n";
            case 'decimal':
            case 'float':
            case 'double':
                return "     * @Assert\Regex(pattern=\"/^\d{1,". $fieldMapping['precision'] ."}\.\d{1,".$fieldMapping['scale']."}$/\", 
                groups={\"create\",\"update\", \"query\"})\r\n";
            default:
                return "     * @Assert\NotBlank(allowNull=true, normalizer=\"trim\", groups={\"create\",\"update\", \"query\"})\r\n";
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param EntityManager $em
     * @return ClassMetadataInfo[]
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