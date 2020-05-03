<?php

/**
 * TestPlugin.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 18:04
 */

namespace plugins;


use loeye\base\Context;
use loeye\std\Plugin;

class TestPlugin implements Plugin
{

    /**
     * @inheritDoc
     */
    public function process(Context $context, array $inputs)
    {
        $context->set('TestPlugin_output', 'test');
    }
}