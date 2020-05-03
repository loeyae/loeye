<?php

/**
 * default.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 12:09
 */
if ($context instanceof \loeye\base\Context) {
    $context->setRouter(new \loeye\base\Router());
}