<?php

/**
 * Render.php
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

/**
 * interface Render
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
abstract class Render
{

    /**
     * @var Response
     */
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function code()
    {
        return $this->response;
    }

    /**
     * header
     *
     * @return array|null
     */
    abstract public function header(): ?array ;

    /**
     * output
     *
     * @return string|null
     */
    abstract public function output(): ?string ;
}