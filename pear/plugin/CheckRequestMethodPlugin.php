<?php

/**
 * CheckRequestMethodPlugin.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/10 18:21
 */

namespace loeye\plugin;


use loeye\base\Context;
use loeye\base\Factory;
use loeye\base\Utils;
use loeye\error\RequestMethodNotSupportedException;
use loeye\std\Plugin;
use const loeye\base\PROJECT_SUCCESS;

class CheckRequestMethodPlugin implements Plugin
{

    /**
     * @inheritDoc
     */
    public function process(Context $context, array $inputs)
    {
        $allowed = Utils::getData($inputs, 'allowed', 'GET');
        $upperAllowed = strtoupper($allowed);
        if (strtoupper($context->getRequest()->requestMethod) !== $upperAllowed) {
            $context->getResponse()->setStatusCode(RequestMethodNotSupportedException::DEFAULT_ERROR_CODE);
            $context->getResponse()->setReason(RequestMethodNotSupportedException::DEFAULT_ERROR_MSG);
            return Factory::getRender($context->getResponse()->getFormat(), $context->getResponse());
        }
        return PROJECT_SUCCESS;
    }
}