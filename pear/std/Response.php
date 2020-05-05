<?php

/**
 * Response.php
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

use ArrayAccess;

/**
 * interface Response
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
abstract class Response
{

    protected $header = array();
    protected $output = array();
    protected $format;

    /**
     * @var int
     */
    private $statusCode = 200;
    /**
     * @var string
     */
    private $reason = 'Ok';
    /**
     * @var string
     */
    private $version = '1.1';

    /**
     * @param string $version
     * @return Response
     */
    public function setVersion(string  $version): Response
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param int $code
     * @return Response
     */
    public function setStatusCode(int $code): Response
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param string $reason
     * @return Response
     */
    public function setReason(string $reason): Response
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * addHeader
     *
     * @param string $name  header name
     * @param string $value header value
     *
     * @return Response
     */
    public function addHeader($name, $value): Response
    {
        $this->header[$name] = $value;
        return $this;
    }

    /**
     * addOutput
     *
     * @param mixed  $data data
     * @param string $key  key
     *
     * @return Response
     */
    public function addOutput($data, $key = null): Response
    {
        if ($key !== null) {
            $this->output[$key] = $data;
        } else {
            $this->output[] = $data;
        }
        return $this;
    }

    /**
     * setFormat
     *
     * @param string $format format
     *
     * @return Response
     */
    public function setFormat($format): Response
    {
        $this->format = $format;
        return $this;
    }

    /**
     * getFormat
     *
     * @return mixed
     */
    public function getFormat()
    {
        return (!empty($this->format)) ? $this->format : null;
    }

    /**
     * getHeader
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->header;
    }


    /**
     * setHeaders
     *
     * @return void
*/
    public function setHeaders(): void
    {
        foreach ($this->header as $key => $value) {
            if (is_numeric($key)) {
                header($value);
            } else {
                header("$key:$value");
            }
        }
    }

    abstract public function getOutput();
}
