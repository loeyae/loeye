<?php

/**
 * JsonRender.php
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

namespace loeye\render;

use loeye\std\Render;
use loeye\std\Response;

/**
 * Description of JsonRender
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class JsonRender extends Render
{
    //put your code here

    /**
     * header
     *
     * @return array|null
     */
    public function header(): ?array
    {
        $this->response->addHeader('Content-Type', 'application/json; charset=UTF-8');
        return $this->response->getHeaders();
    }

    /**
     * output
     *
     * @return string|null
     */
    public function output(): ?string
    {
        $output = $this->response->getOutput();

        $json = json_encode($output, true);

        return $json;
    }

}
