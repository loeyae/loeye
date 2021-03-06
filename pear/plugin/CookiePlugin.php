<?php

/**
 * CookiePlugin.php
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
use loeye\lib\Cookie;
use loeye\std\Plugin;

/**
 * CookiePlugin
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class CookiePlugin implements Plugin
{

    protected $dataKey = 'set_cookie_data';
    protected $outKey = 'get_cookie_result';

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
        $setKey = Utils::getData($inputs, 'set', $this->dataKey);
        $setData = Utils::getData($context, $setKey);
        if (!empty($setData)) {
            foreach ($setData as $key => $value) {
                if (is_numeric($key)) {
                    continue;
                }
                Cookie::setCookie($context, $value);
            }
        }
        $key = Utils::getData($inputs, 'get', null);
        $data = array();
        if (empty($key)) {
            $cookie = $context->getRequest()->cookies->all();
            if (!empty($cookie)) {
                foreach ($cookie as $key => $value) {
                    $data[$key] = $value;
                }
            }
        } else {
            foreach ((array)$key as $item) {
                $data[$item] = $context->getRequest()->cookies->get($item);
            }
        }
        Utils::setContextData($data, $context, $inputs, $this->outKey);
    }

}
