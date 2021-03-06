<?php

/**
 * SegmentRender.php
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
 * Description of SegmentRender
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class SegmentRender extends Render
{

    /**
     * header
     *
     * @return array|null
     */
    public function header(): ?array
    {
        $headers = $this->response->getHeaders();
        if (!array_key_exists('Content-Type', $headers) && !array_key_exists('Content-type', $headers) && !array_key_exists('content-type', $headers)) {
            $this->response->addHeader('Content-Type', 'text/html; charset=UTF-8');
        }
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
        $mapped = array_map([HtmlRender::class, 'format'], $output);
        return HtmlRender::format($mapped);
    }

}
