<?php
/**
 * CreateService.php
 *
 * PHP version 7
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @version  2020/4/11 19:21
 * @link     https://github.com/loeyae/loeye.git
 */


namespace loeye\commands;

use Doctrine\Persistence\Mapping\ClassMetadata;
use loeye\Centra;
use loeye\commands\helper\EntityGeneratorTrait;
use loeye\commands\helper\GeneratorUtils;
use loeye\console\Command;
use loeye\database\QueryHelper;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;
use SmartyException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Class CreateService
 * @package loeye\commands
 */
class CreateService extends Command
{

    use EntityGeneratorTrait;

    protected const BASE_DIR_NAME = 'services';

    protected $name = 'loeye:create-service';
    protected $desc = 'create service';
    protected $params = [
        ['db-id', 'd', 'required' => false, 'help' => 'database setting id', 'default' => 'default'],
        ['filter', 'f', 'required' => false, 'help' => 'filter', 'default' => null],
        ['force', null, 'required' => false, 'help' => 'force update file', 'default' => false],
    ];
    protected $property;

    protected $requestBodyTemplate = <<<'EOF'
$req->setContent('application/json', json_encode(array('request_data' => <{$parameter}>), JSON_UNESCAPED_SLASHES | 
JSON_UNESCAPED_UNICODE));
EOF;

    protected $getHandlerParameterStatementTemplate = <<<'EOF'
        $<{$parameter}> = $this->checkNotEmptyPathParameter('<{$parameter}>');
EOF;

    protected $postHandlerParameterStatementTemplate = <<<'EOF'
        $<{$parameter}> = $this->queryHelper->parseQuery($req)'];
EOF;

    protected $methodDocTemplate = <<<'EOF'
     * @inheritDoc
EOF;


    private $handlerDir;
    private $clientDir;

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
    protected function generateFile(SymfonyStyle $ui, ClassMetadata $metadata, $namespace, $destPath, $force): void
    {
        $entityName = GeneratorUtils::getClassName($metadata->reflClass->name);
        $namespace .= '\\' . $entityName;
        $destPath .= D_S . lcfirst($entityName);
        $serverClass = $this->getServerClass($metadata->reflClass->name);
        $this->writeClient($ui, $entityName, $serverClass, $force);
        $this->writeAbstractHandler($ui, $namespace, $entityName, $serverClass, $destPath, $force);
        $this->writeHandler($ui, $namespace, $metadata->reflClass->name, $serverClass, $destPath, $force);
    }

    /**
     * writeClient
     *
     * @param SymfonyStyle $ui
     * @param string $entityName
     * @param string $serverClass
     * @param bool $force
     * @throws ReflectionException
     * @throws SmartyException
     */
    protected function writeClient(SymfonyStyle $ui, $entityName, $serverClass, $force): void
    {

        $clientName = ucfirst($entityName) . 'Client';
        $namespace = GeneratorUtils::getNamespace($this->clientDir);
        $fullClientClassName = $namespace . '\\' . $clientName;
        $ui->text(sprintf('Processing Client "<info>%s</info>"', $fullClientClassName));
        $classBody = $this->generateClientBody($serverClass, $entityName);
        $code = $this->generateClientFile($clientName, $namespace, $classBody);

        GeneratorUtils::writePHPClass($this->clientDir, $clientName, $code, $force);
    }

    /**
     * generateClientFile
     *
     * @param string $className
     * @param string $namespace
     * @param string $classBody
     *
     * @return string
     * @throws SmartyException
     */
    protected function generateClientFile($className, $namespace, $classBody): string
    {
        $variables = [
            'className' => $className,
            'namespace' => $namespace,
            'classBody' => $classBody,
        ];

        return GeneratorUtils::getCodeFromTemplate('service/Client', $variables);
    }

    /**
     * generateClientBody
     *
     * @param $serverClass
     * @param $entityName
     * @return string
     * @throws ReflectionException
     * @throws SmartyException
     */
    protected function generateClientBody($serverClass, $entityName): string
    {
        $refClass = new ReflectionClass($serverClass);
        $methods = $refClass->getMethods();
        $body = [];
        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isFinal() || $method->isPrivate()) {
                continue;
            }
            [$paramsStatement, $params, $type, $path, $requestBody] = $this->generateParameter($method,
                $entityName);
            $variables = [
                'methodName' => $method->getName(),
                'paramsStatement' => $paramsStatement,
                'params' => $params,
                'path' => $path,
                'method' => $type,
                'requestBody' => $requestBody,
            ];
            $body[] = GeneratorUtils::getCodeFromTemplate('service/ClientMethod', $variables);
        }
        return implode("\r\n", $body);
    }

    /**
     * generateParameter
     *
     * @param ReflectionMethod $method
     * @param $entityName
     * @return array
     */
    protected function generateParameter(ReflectionMethod $method, $entityName): array
    {
        $paramsStatementArray = [];
        $paramsArray = [];
        $parameters = $method->getParameters();
        $path = '\'/' . strtolower($entityName) . '/' . $method->getName();
        $type = $method->getName() === 'get' ? 'GET' : 'POST';
        foreach ($parameters as $parameter) {
            $pType = $parameter->getType();
            if (!$pType) {
                $pType = 'mixed';
            }
            $paramsStatementArray[] = '     * @param ' . $pType . ' $' . $parameter->getName();
            $paramsArray[] = $parameter->getName();
        }
        $requestBody = '';
        $mappedParamsArray = array_map(static function ($item) {
            return '$' . $item;
        }, $paramsArray);
        if (!empty($parameters)) {
            if ($type === 'GET') {
                $m = array_map(static function ($item) {
                    return '\'. $' . $item;
                }, $paramsArray);
                $path .= '/' . implode('.\'/', $m);
            } else {
                $path .= '\'';
                $m = array_map(static function ($item) {
                    return '\'' . $item . '\' => $' . $item;
                }, $paramsArray);
                $p = ['parameter' => '[' . implode(',', $m) . ']'];
                $requestBody = GeneratorUtils::generateCodeByTemplate($p, $this->requestBodyTemplate);
            }
        } else {
            $path .= '\'';
        }
        return [implode("\r\n", $paramsStatementArray), implode(', ', $mappedParamsArray), $type, $path, $requestBody];
    }

    /**
     * writeAbstractHandler
     *
     * @param SymfonyStyle $ui
     * @param $namespace
     * @param $className
     * @param $serverClass
     * @param $destPath
     * @param $force
     * @throws SmartyException
     */
    protected function writeAbstractHandler(SymfonyStyle $ui, $namespace, $className, $serverClass, $destPath, $force): void
    {
        $abstractClassName = 'Abstract' . ucfirst($className) . 'Handler';
        $fullClassName = $namespace . '\\' . $abstractClassName;
        $ui->text(sprintf('Processing AbstractClassFile "<info>%s</info>"', $fullClassName));
        $variable = [
            'className' => $abstractClassName,
            'namespace' => $namespace,
            'fullServerClass' => $serverClass,
            'serverClass' => GeneratorUtils::getClassName($serverClass),
        ];
        $code = GeneratorUtils::getCodeFromTemplate('service/AbstractBaseHandler', $variable);
        GeneratorUtils::writePHPClass($destPath, $abstractClassName, $code, $force);
    }

    /**
     * writeHandler
     *
     * @param SymfonyStyle $ui
     * @param $namespace
     * @param $className
     * @param $serverClass
     * @param $destPath
     * @param $force
     * @throws ReflectionException
     * @throws SmartyException
     */
    protected function writeHandler(SymfonyStyle $ui, $namespace, $className, $serverClass, $destPath, $force): void
    {
        $refClass = new ReflectionClass($serverClass);
        $entityName = GeneratorUtils::getClassName($className);
        $methods = $refClass->getMethods();
        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isFinal() || $method->isPrivate()) {
                continue;
            }
            $methodName = $method->getName();
            $nClassName = ucfirst($methodName) . 'Handler';
            $abstractClassName = 'Abstract' . $entityName . 'Handler';
            $fullClassName = $namespace . '\\' . $nClassName;
            $ui->text(sprintf('Processing ClassFile "<info>%s</info>"', $fullClassName));
            $type = $methodName === 'get' ? 'GET' : 'POST';
            $parameters = $method->getParameters();
            $propertyStatement = '';
            $methodDoc = $this->methodDocTemplate;
            $generateMethod = 'generate' . ucfirst($methodName) . 'HandlerParameter';
            if (method_exists($this, $generateMethod)) {
                [$parameterStatement, $parameter] = $this->$generateMethod($entityName);
            } else {
                [$parameterStatement, $parameter] = $this->generatePostHandlerParameter($parameters);
            }
            $useStatement = 'use ' . $className . ';';
            $useStatement .= "\r\nuse loeye\\error\ValidateError;";
            $useStatement .= "\r\nuse Psr\Cache\InvalidArgumentException;";
            if ($methodName !== 'page') {
                $useStatement .= "\r\nuse Throwable;";
            }
            $methodDoc .= "\r\n     * @throws ValidateError";
            $methodDoc .= "\r\n     * @throws InvalidArgumentException";
            $methodDoc .= "\r\n     * @throws Throwable";
            $propertyStatement = "    protected \$group = '" . $this->getGroup($methodName) . "';\r\n";

            $variable = [
                'className' => $nClassName,
                'useStatement' => $useStatement,
                'propertyStatement' => $propertyStatement,
                'methodDoc' => $methodDoc,
                'namespace' => $namespace,
                'abstractClassName' => $abstractClassName,
                'method' => $method->getName(),
                'parameterStatement' => $parameterStatement,
                'parameter' => $parameter,
            ];
            if ($methodName === 'page') {
                $code = GeneratorUtils::getCodeFromTemplate('service/PageHandler', $variable);
            } else {
                $code = GeneratorUtils::getCodeFromTemplate('service/Handler', $variable);
            }
            GeneratorUtils::writePHPClass($destPath, $nClassName, $code, $force);
        }
    }

    /**
     * generateAllHandlerParameter
     *
     * @param $entityName
     * @return array
     */
    protected function generateAllHandlerParameter($entityName): array
    {
        $parameterStatement = <<<'EOF'
        $criteria = $this->queryHelper->setDefaultHits(100)->parseQuery($req);
        $validateData = $this->validate($criteria, <{$entityName}>::class, $this->group);
        $orderBy = $this->queryHelper->getOrderBy();
        $start = $this->queryHelper->getStart();
        $offset = $this->queryHelper->getOffset();
EOF;
        $parameter = '$validateData, $orderBy, $start, $offset';
        return [GeneratorUtils::generateCodeByTemplate(['entityName' => $entityName], $parameterStatement), $parameter];
    }

    /**
     * generateDeleteHandlerParameter
     *
     * @param $entityName
     * @return array
     */
    protected function generateDeleteHandlerParameter($entityName): array
    {
        $parameterStatement = <<<'EOF'
        $id = $this->checkNotEmptyPathParameter('id');
        $validatedData = $this->validate(['id' => $id], <{$entityName}>::class, $this->group);
EOF;
        $parameter = '$validatedData[\'id\']';
        return [GeneratorUtils::generateCodeByTemplate(['entityName' => $entityName], $parameterStatement), $parameter];
    }

    /**
     * generateInsertHandlerParameter
     *
     * @param $entityName
     * @return array
     */
    protected function generateInsertHandlerParameter($entityName): array
    {
        $parameterStatement = <<<'EOF'
        $validatedData = $this->validate($req, <{$entityName}>::class, $this->group);
EOF;
        $parameter = '$validatedData';
        return [GeneratorUtils::generateCodeByTemplate(['entityName' => $entityName], $parameterStatement), $parameter];
    }

    /**
     * generateOneHandlerParameter
     *
     * @param $entityName
     * @return array
     */
    protected function generateOneHandlerParameter($entityName): array
    {
        $parameterStatement = <<<'EOF'
        $criteria = $this->queryHelper->parseQuery($req);
        $validatedData = $this->validate($criteria, <{$entityName}>::class, $this->group);
        $orderBy = $this->queryHelper->getOrderBy();
EOF;
        $parameter = '$validatedData, $orderBy';
        return [GeneratorUtils::generateCodeByTemplate(['entityName' => $entityName], $parameterStatement), $parameter];
    }

    /**
     * generatePageHandlerParameter
     *
     * @param $entityName
     * @return array
     */
    protected function generatePageHandlerParameter($entityName): array
    {
        $parameterStatement = <<<'EOF'
        $query = $this->queryHelper->parseQuery($req);
        $expression = $this->getExpression($query);
        if ($expression) {
            $validatedData = $this->validate($this->expressionToArray($expression), <{$entityName}>::class, 
           $this->group);
            $filteredCompositeExpression = $this->filterCompositeExpression($expression, $validatedData);
            $criteria = $this->expressionToCriteria($filteredCompositeExpression);
        } else {
            $criteria = null;
        }
        $start = $this->queryHelper->getStart() ?? 0;
        $offset = $this->queryHelper->getOffset() ?? 10;
        $orderBy = $this->queryHelper->getOrderBy();
        $groupBy = $this->queryHelper->getGroupBy();
        $having = $this->queryHelper->getHaving();
EOF;
        $parameter = '$criteria, $start, $offset, $orderBy, $groupBy, $having';
        return [GeneratorUtils::generateCodeByTemplate(['entityName' => $entityName], $parameterStatement), $parameter];
    }

    /**
     * generateUpdateHandlerParameter
     *
     * @param $entityName
     * @return array
     */
    protected function generateUpdateHandlerParameter($entityName): array
    {
        $parameterStatement = <<<'EOF'
        $id = $this->checkNotEmptyPathParameter('id');
        $data = $this->queryHelper->parseQuery($req);
        $validatedData = $this->validate(array_merge(['id' => $id], $data), <{$entityName}>::class, $this->group);
        $id = $validatedData['id'];
        unset($validatedData['id']);
EOF;
        $parameter = '$id, $validatedData';
        return [GeneratorUtils::generateCodeByTemplate(['entityName' => $entityName], $parameterStatement), $parameter];
    }

    /**
     * generateGetHandlerParameter
     *
     * @param $entityName
     * @return array
     */
    protected function generateGetHandlerParameter($entityName): array
    {
        $parameterStatement = <<<'EOF'
        $id = $this->checkNotEmptyPathParameter('id');
        $this->validate(['id' => $id], <{$entityName}>::class, $this->group);
EOF;

        return [GeneratorUtils::generateCodeByTemplate(['entityName' => $entityName],
            $parameterStatement), '$id'];
    }

    /**
     * generatePostHandlerParameter
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     */
    protected function generatePostHandlerParameter($parameters): array
    {
        $codes = [];
        $parameterList = [];
        foreach ($parameters as $parameter) {
            $code = GeneratorUtils::generateCodeByTemplate(['parameter' => $parameter->getName()],
                $this->postHandlerParameterStatementTemplate);
            try {
                $default = $parameter->getDefaultValue();
                if (is_numeric($default) || is_bool($default)) {
                    $code = str_replace(';', ' ?? ' . $default . ';', $code);
                } else if ($default === null) {
                    $code = str_replace(';', ' ?? null;', $code);
                } else {
                    $code = str_replace(';', ' ?? \'' . $default . '\';', $code);
                }
            } catch (Throwable $e) {
                $e->getTraceAsString();
            }
            $codes[] = $code;
            $parameterList[] = '$' . $parameter->getName();
        }
        return [implode("\r\n", $codes), implode(', ', $parameterList)];
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
     *
     * @param InputInterface $input
     *
     * @param SymfonyStyle $ui
     * @return string
     * @throws SmartyException
     */
    protected function getDestPath(InputInterface $input, SymfonyStyle $ui): string
    {
        $baseDir = dirname(PROJECT_DIR);
        [$handlerDir, $clientDir, $clientConfDir] = $this->mkdir($baseDir, $ui);
        $this->handlerDir = $handlerDir;
        $this->clientDir = $clientDir;
        $appConfig = Centra::$appConfig;
        $clientConf = GeneratorUtils::getCodeFromTemplate('service/ClientConf', ['port' => $appConfig->getSetting
        ('server.port')]);
        $clientConfPath = GeneratorUtils::buildPath($clientConfDir, 'master.yml');
        GeneratorUtils::writeFile($clientConfPath, $clientConf);
        $ui->text(sprintf('Processing Client Config  "<info>%s</info>"', $clientConfPath));
        return $handlerDir;
    }

    /**
     * @param string $baseDir
     * @param SymfonyStyle $ui
     * @return array
     */
    protected function mkdir($baseDir, SymfonyStyle $ui): array
    {
        $handlerDir = $baseDir . D_S . 'app' . D_S . self::BASE_DIR_NAME . D_S . 'handler';
        if (!file_exists($handlerDir) && (!mkdir($handlerDir, 0755, true) || !is_dir($handlerDir))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $handlerDir));
        }
        $ui->block(sprintf('create dir: %1s', $handlerDir));
        $clientDir = $baseDir . D_S . 'app' . D_S . self::BASE_DIR_NAME . D_S . 'client';
        if (!file_exists($clientDir) && (!mkdir($clientDir, 0755, true) || !is_dir($clientDir))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $clientDir));
        }
        $ui->block(sprintf('create dir: %1s', $clientDir));
        $clientConfDir = $baseDir . D_S . 'app' . D_S .'conf' . D_S .'client';
        if (!file_exists($clientConfDir) && (!mkdir($clientConfDir, 0755, true) || !is_dir($clientConfDir))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $clientConfDir));
        }
        $ui->block(sprintf('create dir: %1s', $clientConfDir));
        return [$handlerDir, $clientDir, $clientConfDir];
    }

    /**
     * @param string $methodName
     * @return string
     */
    private function getGroup(string $methodName): ?string
    {
        switch ($methodName) {
            case 'insert':
                return 'create';
            case 'update':
            case 'delete':
                return $methodName;
            default:
                return 'query';
        }
    }

}