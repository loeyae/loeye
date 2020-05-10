<?php

/**
 * JWTPlugin.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/9 21:01
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\plugin;


use Firebase\JWT\ExpiredException;
use loeye\base\Context;
use loeye\base\Factory;
use loeye\base\Utils;
use loeye\error\PermissionException;
use loeye\lib\JWTUtils;
use loeye\std\Plugin;

class JWTPlugin implements Plugin
{

    protected $inputKey = 'encrypt_data';
    protected $outputKey = 'jwt';
    private $expireKey = 'expire';
    private $issKey = 'iss';
    private $audKey = 'aud';

    /**
     * @inheritDoc
     */
    public function process(Context $context, array $inputs)
    {
        $encryptData = Utils::getContextData($context, $inputs, $this->inputKey);
        $utils = JWTUtils::getInstance();
        $format = Utils::getData($inputs, 'format');
        if ($format) {
            $context->getResponse()->setFormat($format);
        }
        if ($encryptData) {
            $expire = Utils::getData($inputs, $this->expireKey);
            if (null !== $expire) {
                $utils->setLifeTime($expire);
            }
            $iss = Utils::getData($inputs, $this->issKey);
            if (null !== $iss) {
                $utils->setIss($iss);
            }
            $aud = Utils::getData($inputs, $this->audKey);
            if (null !== $aud) {
                $utils->setAud($iss);
            }
            Utils::setContextData($utils->createToken($encryptData), $context, $inputs, $this->outputKey);
        } else {
            try {
                $token = $utils->verifyTokenByHeader();
                Utils::setContextData($token, $context, $inputs, $this->outputKey);
            } catch (ExpiredException $e) {
                Utils::errorLog($e);
                $context->getResponse()->setStatusCode(PermissionException::ACCESS_DENIED);
                $context->getResponse()->setReason(PermissionException::TOKEN_EXPIRED_MSG);
                $context->getResponse()->addOutput(PermissionException::TOKEN_EXPIRED_MSG, 'data');
                return Factory::getRender($context->getResponse()->getFormat(), $context->getResponse());
            } catch (\Throwable $e) {
                Utils::errorLog($e);
                $context->getResponse()->setStatusCode(PermissionException::ACCESS_DENIED);
                $context->getResponse()->setReason(PermissionException::TOKEN_INVALID_MSG);
                $context->getResponse()->addOutput(PermissionException::TOKEN_INVALID_MSG, 'data');
                return Factory::getRender($context->getResponse()->getFormat(), $context->getResponse());
            }
        }
    }
}