<?php

/**
 * IndexHandler.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version <{$smarty.now|date_format: "%Y-%m-%d %H:%M:%S"}>
 */

namespace app\services\handler;

use loeye\service\Handler;

class IndexHandler extends Handler
{

    /**
     * @inheritDoc
     */
    protected function process($req)
    {
        return ['Hello World'];
    }
}