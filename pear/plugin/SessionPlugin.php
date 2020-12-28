<?php

/**
 * SessionPlugin.php
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

namespace loeye\plugin;

use loeye\base\Context;
use loeye\base\Utils;
use loeye\std\Plugin;

/**
 * SessionPlugin
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class SessionPlugin implements Plugin
{

    protected $outKey = 'session_get_data';
    protected $dataKey = 'session_set_data';

    /**
     * process
     *
     * @param Context $context context
     * @param array $inputs inputs
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(Context $context, array $inputs): void
    {
        if (session_id() === '') {
            session_start();
        }
        $setKey = Utils::getData($inputs, 'set', $this->dataKey);
        $setData = Utils::getData($context, $setKey);
        if (!empty($setData)) {
            foreach ($setData as $key => $value) {
                if (is_numeric($key)) {
                    continue;
                }
                $_SESSION[$key] = $value;
            }
        }
        $unset = Utils::getData($inputs, 'unset', null);
        if (!empty($unset)) {
            foreach ((array)$unset as $item) {
                unset($_SESSION[$item]);
            }
        }
        $key = Utils::getData($inputs, 'get', null);
        $data = array();
        if (empty($key)) {
            $session = $context->getRequest()->getSession()->all();
            foreach ($session as $key => $value) {
                $data[$key] = $value;
            }
        } else {
            foreach ((array)$key as $item) {
                $data[$item] = $context->getRequest()->getSession()->get($item);
            }
        }
        Utils::setContextData($data, $context, $inputs, $this->outKey);
    }

}
