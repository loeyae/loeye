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

use Symfony\Component\HttpFoundation\Cookie;

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

    /**
     * @return int
     */
    public function code(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return string
     */
    public function reason(): string
    {
        return $this->response->getReason();
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return $this->response->getProtocolVersion();
    }

    /**
     * @return Cookie[]|null
     */
    public function cookie(): ?array
    {
        return $this->response->headers->getCookies();
    }

    /**
     * @return string|null
     */
    public function redirect(): ?string
    {
        return $this->response->getRedirect();
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
