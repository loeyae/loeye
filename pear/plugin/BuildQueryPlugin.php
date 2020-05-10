<?php

/**
 * BuildQueryPlugin.php
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 * 
 * @author  Zhang Yi <loeyae@gmail.com>
 * @version 2020年3月25日 下午7:50:03
 */

namespace loeye\plugin;

use loeye\{base\Context,
    base\Factory,
    base\Utils,
    database\ExpressionFactory,
    error\ValidateError,
    std\Plugin,
    validate\Validation};
use const loeye\base\PROJECT_SUCCESS;

/**
 * BuildQueryPlugin
 *
 * @author Zhang Yi <loeyae@gmail.com>
 */
class BuildQueryPlugin implements Plugin {

    public const INPUT_ORIGIN = 100;

    protected $inDataKey     = 'BuildQueryPlugin_input';
    protected $outDataKey    = 'BuildQueryPlugin_output';
    protected $outErrorsKey  = 'BuildQueryPlugin_errors';
    protected $prefixKey     = 'prefix';
    protected $pageKey       = 'page';
    protected $hitsKey       = 'hits';
    protected $sortKey       = 'sort';
    protected $orderKey      = 'order';
    protected $groupKey      = 'group';
    protected $havingKey     = 'having';
    protected $denyQueryKey  = 'deny';
    protected $allowedFields = 'fields';
    protected $validateKey   = 'validate';
    protected $criteriaKey   = 'criteria';

    public const PAGE_NAME           = 'p';
    public const HITS_NAME           = 'h';
    public const ORDER_NAME          = 'o';
    public const SORT_NAME           = 's';
    public const INPUT_TYPE          = 'type';
    public const DEFAULT_HITS        = 10;
    public const DEFAULT_PAGE        = 1;
    public const ORDER_ASC           = 'ASC';
    public const ORDER_DESC          = 'DESC';
    public const PARAMETER_ERROR_MSG = 'Page and Hits must be number';
    private $group = 'query';

    /**
     * process
     *
     * @param Context $context context
     * @param array               $inputs  inputs
     *
     * @return string|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(Context $context, array $inputs)
    {
        $prefix   = Utils::checkNotEmpty($inputs, $this->prefixKey);
        $method   = Utils::getData($inputs, self::INPUT_TYPE, null);
        $deny     = (bool) Utils::getData($inputs, $this->denyQueryKey, false);
        $criteria = (bool) Utils::getData($inputs, $this->criteriaKey, false);
        $validate = Utils::getData($inputs, $this->validateKey);
        $fields   = Utils::getData($inputs, $this->allowedFields);
        $data     = null;
        if (null === $method) {
            $data = Utils::getContextData($context, $inputs, $this->inDataKey);
        } else {
            $data = $this->getData($context, (int)$method);
        }
        if (!$this->parsePaging($context, $inputs, $prefix, $data)) {
            return PROJECT_SUCCESS;
        }
        $this->parseAdvanced($context, $inputs, $prefix, $data);
        $query = $data;
        if ($data) {
            if ($deny) {
                $query = null;
            } else if ($criteria) {
                $expression = ExpressionFactory::create($data);
                if ($expression) {
                    if ($validate) {
                        try {
                            $validatedData = Validation::validate(ExpressionFactory::toFieldArray($expression), $validate, [], $this->group);
                        } catch (ValidateError $e) {
                            Utils::addErrors($e->getValidateMessage(), $context, $inputs,
                                $this->outErrorsKey);
                            return PROJECT_SUCCESS;
                        }
                        $filteredCompositeExpression = ExpressionFactory::filter($expression,
                            Utils::entity2array(Factory::db()->em(), $validatedData));
                        $query = ExpressionFactory::toCriteria($filteredCompositeExpression);
                    } else {
                        $query = ExpressionFactory::toCriteria($expression);
                    }
                } else {
                    $query = null;
                }
            } else if ($fields) {
                $fields = array_fill_keys($fields, null);
                $query = array_intersect_key($data, $fields);
                if ($validate) {
                    try {
                        $entity = Validation::validate($query, $validate, [], $this->group);
                        $validated = Utils::entity2array(Factory::db()->em(), $entity);
                        $query = array_filter($validated, static function ($item) {
                            return $item !== null;
                        });
                    } catch (ValidateError $e) {
                        Utils::addErrors($e->getValidateMessage(), $context, $inputs,
                            $this->outErrorsKey);
                        return PROJECT_SUCCESS;
                    }
                }
            } else {
                $query = $data;
            }
        }
        $context->set($prefix . '_input', $query);

    }

    /**
     * @param Context $context
     * @param array $inputs
     * @param $prefix
     * @param $data
     */
    protected function parseAdvanced(Context $context, array $inputs, $prefix, &$data): void
    {
        $sortKey  = Utils::getData($inputs, $this->sortKey, self::SORT_NAME);
        $orderKey = Utils::getData($inputs, $this->orderKey, self::ORDER_NAME);
        $group    = Utils::getData($inputs, $this->groupKey);
        $having   = Utils::getData($inputs, $this->havingKey);
        $sort  = null;
        $order = null;
        if (null !== $data) {
            $order = $this->pop($data, $orderKey);
            $sort  = $this->pop($data, $sortKey);
        }
        if ($sort) {
            $sortArray = (array)$sort;
            $orderArray = (array)$order;
            $sortCount = count($sortArray);
            $orderArray = array_slice(array_pad($orderArray, $sortCount, 0), 0, $sortCount);
            $orderBy = array_combine(array_map(static function($item){
                return htmlentities($item);
            }, $sortArray), array_map(static function($item){
                return $item > 0 ? self::ORDER_ASC :self::ORDER_DESC;
            }, $orderArray));
            $context->set($prefix . '_orderBy', $orderBy);
        }
        if ($group) {
            $context->set($prefix . '_groupBy', array_map(static function($item){
                return htmlentities($item);
            }, (array)$group));
        }
        if ($having) {
            $context->set($prefix . '_having', $having);
        }
    }

    /**
     * @param Context $context
     * @param array $inputs
     * @param $prefix
     * @param $data
     * @return bool
     */
    protected function parsePaging(Context $context, array $inputs, $prefix, &$data): bool
    {
        $pageKey  = Utils::getData($inputs, $this->pageKey, self::PAGE_NAME);
        $hitsKey  = Utils::getData($inputs, $this->hitsKey, self::HITS_NAME);
        $page  = self::DEFAULT_PAGE;
        $hits  = self::DEFAULT_HITS;
        if ($data) {
            $page = (int)$this->pop($data, $pageKey, self::DEFAULT_PAGE);
            $hits = (int)$this->pop($data, $hitsKey, self::DEFAULT_HITS);
        }
        if ($page <= 0 || $hits <= 0) {
            $context->addErrors($this->outErrorsKey, Factory::translator()->getString(self::PARAMETER_ERROR_MSG));
            return false;
        }
        $context->set($prefix . '_start', ($page - 1) * $hits);
        $context->set($prefix . '_offset', $hits);
        return true;
    }

    /**
     * pop value from array
     * 
     * @param array $data    data
     * @param mixed $key     key
     * @param mixed $default default
     * 
     * @return mixed
     */
    protected function pop(array &$data, $key, $default = null)
    {
        $value = $default;
        if (isset($data[$key])) {
            $value = $data[$key];
            unset($data[$key]);
        }
        return $value;
    }

    /**
     * @param Context $context
     * @param $method
     * @return array|null
     */
    private function getData(Context $context, $method): ?array
    {
        if ($method === INPUT_GET) {
            return $context->getRequest()->getQuery()  ?? [];
        }
        if ($method === INPUT_POST) {
            return $context->getRequest()->getBody() ?? [];
        }
        if ($method === self::INPUT_ORIGIN) {
            return json_decode($context->getRequest()->getContent(), true)  ?: [];
        }
        return $context->getRequest()->getRequest() ?? [];
    }

}
