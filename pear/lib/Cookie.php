<?php

/**
 * Cookie.php
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

namespace loeye\lib;

use loeye\base\Context;
use loeye\Centra;
use loeye\std\Request;
use loeye\std\Response;

/**
 * Description of Cookie
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class Cookie
{

    public const UNIQUE_ID_NAME = 'LOUID';
    public const USRE_MESSAGE_INFO = 'LOUSI';
    public const CRYPT_COOKIE_FIELDS = 'loc';

    /**
     * setCookie
     *
     * @param Context $context
     * @param string $name name
     * @param mixed $value value
     * @param int $expire expire time
     * @param string $path path
     * @param string $domain domain
     * @param bool $secure secure
     * @param bool $httpOnly http only
     *
     * @return boolean
     */
    public static function setCookie(
        Context $context, $name, $value = null, $expire = 0, $path = '/', $domain = null, $secure =
        false,
        $httpOnly = true
    ): bool
    {
        $cookie = \Symfony\Component\HttpFoundation\Cookie::create($name, $value, $expire, $path,
        $domain, $secure, $httpOnly);
        $context->getResponse()->addCookie($cookie);
        return true;
    }

    /**
     * getCookie
     *
     * @param Context $context
     * @param string|null $name name
     *
     * @return string|null
     */
    public static function getCookie(Context $context, $name=null): ?string
    {
        return $context->getRequest()->getCookie($name);
    }

    /**
     * destructCookie
     *
     * @param Context $context
     * @param string $name name
     *
     * @return boolean
     */
    public static function destructCookie(Context $context, $name): bool
    {
        return self::setCookie($context, $name, null, -1, '/');
    }

    /**
     * setLoeyeCookie
     *
     * @param Context $context
     * @param string $name name
     * @param string $value value
     * @param bool $crypt is crypt
     *
     * @return boolean
     */
    public static function setLoeyeCookie(Context $context, $name, $value, $crypt = false): bool
    {

        $userMessageInfo = self::getLoeyeCookie($context, null, $decode=false);
        if (!empty($userMessageInfo[self::CRYPT_COOKIE_FIELDS])) {
            $cryptFields = $userMessageInfo[self::CRYPT_COOKIE_FIELDS];
        } else {
            $cryptFields = array();
        }
        if ($crypt) {
            $userMessageInfo[$name] = Secure::crypt(self::uniqueId($context), $value);
            if (!in_array($name, $cryptFields, true)) {
                $cryptFields[] = $name;
            }
        } else {
            $userMessageInfo[$name] = $value;
        }
        $userMessageInfo[self::CRYPT_COOKIE_FIELDS] = Secure::crypt(self::uniqueId($context), json_encode
        ($cryptFields));
        return self::setCookie($context, self::USRE_MESSAGE_INFO, json_encode($userMessageInfo));
    }

    /**
     * getLoeyeCookie
     *
     * @param Context $context
     * @param string $name name
     * @param bool $decode
     *
     * @return mixed
     */
    public static function getLoeyeCookie(Context $context, $name = null, $decode = true)
    {
        if ($cookie = $context->getRequest()->getCookie(self::USRE_MESSAGE_INFO)) {
            $userMessageInfo = json_decode($cookie, true);
            $cryptFields     = json_decode(Secure::crypt
            (self::uniqueId($context), $userMessageInfo[self::CRYPT_COOKIE_FIELDS], true), true);
            if (!empty($cryptFields) && $decode) {
                foreach ($userMessageInfo as $key => $value) {
                    if (in_array($key, $cryptFields, true)) {
                        $userMessageInfo[$key] = Secure::crypt(self::uniqueId($context), $value,
                            true);
                    }
                }
            }
            $userMessageInfo[self::CRYPT_COOKIE_FIELDS] = $cryptFields;
            if (!empty($name)) {
                return $userMessageInfo[$name] ?? null;
            }
            return $userMessageInfo;
        }
        return null;
    }

    /**
     * getUniqueId
     *
     * @param Context $context
     * @return string
     */
    public static function uniqueId(Context $context): string
    {
        $sessionId = session_id();
        if ($sessionId) {
            return $sessionId;
        }
        if ($cookie = $context->getRequest()->getCookie(self::UNIQUE_ID_NAME)) {
            return $cookie;
        }
        $uniqueId = Secure::uniqueId($context);
        self::setCookie($context, self::UNIQUE_ID_NAME, $uniqueId);
        return $uniqueId;
    }

    /**
     * createCrumb
     *
     * @param Context $context
     * @param string $name name
     *
     * @return string
     */
    public static function createCrumb(Context $context, $name): string
    {
        $uid    = self::uniqueId($context);
        $string = $name . md5(($name . $uid));
        return hash('crc32', $string);
    }

    /**
     * validateCrumb
     *
     * @param Context $context
     * @param string $name name
     * @param string $crumb crumb
     *
     * @return boolean
     */
    public static function validateCrumb(Context $context, $name, $crumb): bool
    {
        $oCrumb = self::createCrumb($context, $name);
        return ($oCrumb === $crumb);
    }

}
