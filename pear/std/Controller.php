<?php

/**
 * Controller.php
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

namespace loeye\std;

use loeye\base\Context;
use loeye\base\Exception;
use loeye\base\Factory;
use const loeye\base\RENDER_TYPE_JSON;

/**
 * Controller
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
abstract class Controller
{

    /**
     * Context instance
     *
     * @var Context
     */
    protected $context;
    public $layout;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * prepare
     *
     * @return mixed
     */
    public function prepare()
    {
        return true;
    }

    /**
     * indexAction
     */
    abstract public function IndexAction();

    /**
     * @param $data
     * @param int $code
     * @param string $reason
     *
     * @return Render
     */
    protected function json($data, $code = 200, $reason = 'Ok'): Render
    {
        return $this->response(RENDER_TYPE_JSON, $data, $code, $reason);
    }

    /**
     * @param $type
     * @param $data
     * @param int $code
     * @param string $reason
     *
     * @return Render
     */
    protected function response($type, $data, $code = 200, $reason = 'Ok'): Render
    {
        $this->context->getResponse()->setStatusCode($code);
        $this->context->getResponse()->setReason($reason);
        $this->context->getResponse()->addOutput($data);
        return Factory::getRender($type, $this->context->getResponse());
    }

    /**
     * render
     *
     * @param string $src view page
     *
     * @return array
     */
    protected function render($src): array
    {
        $view = ['src' => $src];
        if ($this->layout) {
            $view['layout'] = $this->layout;
        }
        return $view;
    }

    /**
     * template
     *
     * @param string $tpl template file
     * @param array $data data
     * @param mixed $cache cache setting
     * @param string $id cache id
     *
     * @return array
     */
    protected function template($tpl, $data = array(), $cache = 7200, $id = null): array
    {
        $view = ['tpl' => $tpl, 'data' => $data, 'cache' => $cache, 'id' => $id];

        if ($this->layout) {
            $view['layout'] = $this->layout;
        }
        return $view;
    }

    /**
     * output
     *
     * @param mixed $data data
     * @param string $format output format
     */
    protected function output($data, $format = RENDER_TYPE_JSON): void
    {
        $this->context->getResponse()->setFormat($format);
        $this->context->getResponse()->addOutput($data);
    }

    /**
     * redirectUrl
     *
     * @param string $redirectUrl redirect url
     *
     * @return void
     */
    public function redirectUrl($redirectUrl): void
    {
        $this->context->getResponse()->redirect($redirectUrl);
    }

    /**
     * redirect
     *
     * @param array $params parmeter
     * <p>
     * ex: ['controller' => 'main', 'action' => 'index']
     * </p>
     *
     * @return void
     * @throws Exception
     */
    public function redirect(array $params): void
    {
        $routeKey = $params[0];
        unset($params[0]);
        $url = $this->context->getRouter()->generate($routeKey, $params);
        $this->redirectUrl($url);
    }

}
