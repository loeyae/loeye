<?php

/**
 * IndexController.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version <{$smarty.now|date_format: "%Y-%m-%d %H:%M:%S"}>
 */

namespace app\controllers;

use loeye\std\Controller;

class IndexController extends Controller
{
    /**
     * @inheritDoc
     */
    public function IndexAction()
    {
        $this->view = ['tpl' => 'home.tpl'];
    }

}