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

namespace loeye\service;

use const loeye\base\RENDER_TYPE_JSON;

/**
 * Response
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Response extends \loeye\std\Response
{

    private $_serverProtocol;
    private $_contentType;
    /**
     * @var string
     */
    private $_responseData;

    /**
     * __construct
     *
     * @param \loeye\std\Request $req request
     *
     */
    public function __construct(\loeye\std\Request $req)
    {
        parent::__construct($req);
        $this->_serverProtocol = $req->getServerProtocol();
        $this->setStatusCode(LOEYE_REST_STATUS_OK);
        $this->setReason('OK');
        $this->_contentType    = 'text/plain; charset=utf-8';
        $this->header = [];
        $this->setFormat(RENDER_TYPE_JSON);
    }


    /**
     * setContent
     *
     * @param mixed  $data        data
     * @param string $contentType content type
     *
     * @return void
     */
    public function setContent($data, $contentType = null): void
    {
        if (!empty($contentType)) {
            $this->_contentType = $contentType;
        }
        $this->output = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(): void
    {
        $header = $this->_serverProtocol . ' ' . $this->getStatusCode() . ' ' . $this->getReason();
        header($header);
        parent::setHeaders();
        if (!array_key_exists('Content-Type', $this->header)) {
            header('Content-Type:'. $this->_contentType);
        }
    }

    /**
     * output
     *
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

}
