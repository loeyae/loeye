<?php

/**
 * SampleClient.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/7 21:26
 */

namespace client;


use loeye\client\Client;
use loeye\client\Request;
use loeye\client\Response;

class SampleClient extends Client
{

    public function getIp(&$ret = false)
    {
        $path = '/ip';
        $req = new Request();
        $this->setReq($req, 'GET', $path);
        return $this->request(__METHOD__, $req, $ret);
    }
    
    /**
     * @inheritDoc
     */
    public function responseHandle($cmd, Response $resp)
    {
        return json_decode($resp->getContent(), true);
    }
}