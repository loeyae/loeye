<?php

/**
 * HtmlRender.php
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
use loeye\web\Resource;

/**
 * Description of HtmlRender
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class HtmlRender extends Render
{

    /**
     * header
     *
     * @return array|null
     */
    public function header(): ?array
    {
        $this->response->addHeader('Content-Type', 'text/html; charset=UTF-8');
        return $this->response->getHeaders();
    }

    /**
     * output
     *
     * @return string|null
     */
    public function output(): ?string
    {
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="zh">';
        $html .= '<head>';
        $html .= $this->_renderHead($this->response);
        $html .= $this->_renderResource($this->response,
            Resource::RESOURCE_TYPE_CSS);
        $html .= '</head>';
        $html .= '<body>';
        $html .= $this->_renderBody($this->response);
        $html .= $this->_renderResource($this->response,
            Resource::RESOURCE_TYPE_JS);
        $html .= '</body>';
        $html .= '</html>';
        return $html;
    }

    /**
     * _renderHead
     *
     * @param Response $response response
     *
     * @return string
     */
    private function _renderHead(Response $response): string
    {
        $head = $response->getHtmlHead();
        return implode(PHP_EOL, $head);
    }

    /**
     * _renderBody
     *
     * @param Response $response response
     *
     * @return string|null
     */
    private function _renderBody(Response $response): ?string
    {
        $output = $response->getOutput();
        $mapped = array_map([__CLASS__, 'format'], $output);
        return self::format($mapped);
    }

    /**
     * renderResource
     *
     * @param Response $response response
     * @param string               $type     type
     *
     * @return string|null
     */
    private function _renderResource(Response $response, $type): ?string
    {
        $resource = $response->getResource($type);
        if ($resource instanceof Resource) {
            return $resource->toHtml();
        }

        return '';
    }

    /**
     * format
     *
     * @param mixed $item item
     *
     * @return string
     */
    public static function format($item): string
    {
        if (is_array($item)) {
            return implode(PHP_EOL, $item);
        }

        return $item;
    }

}
