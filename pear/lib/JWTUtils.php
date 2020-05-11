<?php

/**
 * JWTUtils.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/9 16:36
 * @link     https://github.com/loeyae/loeye.git
 */


namespace loeye\lib;
use Firebase\JWT\JWT;

use loeye\base\Utils;
use loeye\Centra;

class JWTUtils
{
    public const DEFAULT_LIFETIME = 7200;
    public const FUN_OPENSSL = 'openssl';
    public const DEFAULT_SUB = 'loeye token';
    private $defaultKey = '1234567890abcdef';

    /**
     * @var string
     */
    private $alg;
    private $fun;
    /**
     * @var string
     */
    private $key;
    /**
     * @var int
     */
    private $lifeTime;

    /**
     * @var string
     */
    private $aud;

    /**
     * @var string
     */
    private $iss;

    private function __construct()
    {
        $tokenSetting = Centra::$appConfig->getSetting('token');
        $alg = $tokenSetting['alg'] ?? 'HS256';
        $this->alg = isset(JWT::$supported_algs[$this->alg]) ? $alg : 'HS256';
        [$function] = JWT::$supported_algs[$this->alg];
        $this->fun = $function;
        $this->key = $tokenSetting['key'] ?? $this->defaultKey;
        $this->lifeTime = $tokenSetting['expire'] ?? self::DEFAULT_LIFETIME;
        if (($function === self::FUN_OPENSSL)) {
            if (!is_file($this->key)) {
                throw new \RuntimeException('cert file not exists');
            }
            $this->key = file_get_contents($this->key);
        }
        $this->parseIss();
        $this->parseAud();
    }

    /**
     * @return mixed|string
     */
    private static function parseAddr()
    {
        return (Centra::$request->getServer('remote_addr') ?? filter_input(INPUT_SERVER, 'REMOTE_ADDR')) ?: ($_SERVER['REMOTE_ADDR']
        ?? '127.0.0.1');
    }

    /**
     * @return void
     */
    private function parseAud(): void
    {
        $host = (Centra::$request->getServer('remote_host') ?? filter_input(INPUT_SERVER, 'REMOTE_HOST')) ?: ($_SERVER['REMOTE_HOST'] ?? null);
        $this->aud = $host ?? gethostbyaddr(self::parseAddr());
    }

    /**
     * @return void
     */
    private function parseIss(): void
    {
        if (defined('BASE_SERVER_URL')) {
            $this->iss = parse_url(BASE_SERVER_URL, PHP_URL_HOST);
        } else {
            $this->iss = (Centra::$request->getServer('http_host') ?? filter_input(INPUT_SERVER, 'HTTP_HOST')) ?:
                ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
    }

    /**
     * @return JWTUtils
     */
    public static function getInstance(): JWTUtils
    {
        static $instance;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * @param string $iss
     * @return JWTUtils
     */
    public function setIss(string $iss): JWTUtils
    {
        $this->iss = $iss;
        return $this;
    }

    /**
     * @return string
     */
    public function getIss(): string
    {
        return $this->iss;
    }

    /**
     * @param string $aud
     * @return JWTUtils
     */
    public function setAud(string $aud): JWTUtils
    {
        $this->aud = $aud;
        return $this;
    }

    /**
     * @return string
     */
    public function getAud(): string
    {
        return $this->aud;
    }

    /**
     * @param int $lifeTime
     * @return JWTUtils
     */
    public function setLifeTime(int $lifeTime): JWTUtils
    {
        $this->lifeTime = $lifeTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getLifeTime(): int
    {
        return $this->lifeTime;
    }

    /**
     * @param array $encryptInfo
     * @param null $keyId
     * @param null $head
     * @return string
     */
    public function createToken(array $encryptInfo, $keyId = null, $head = null): string
    {
        $now = time();
        $payload = [
            'iss' => $this->getIss(), #'签发者',
            'sub' => self::DEFAULT_SUB, #'主题',
            'aud' => $this->getAud(), #'接收方',
            'exp' => $now + $this->lifeTime, #'过期时间',
            'iat' => $now, #'创建时间',
            'nbf' => $now, #'在什么时间之前，该Token不可用',
            'jti' => $encryptInfo['uid'] ?? $encryptInfo['appId'] ?? null, #'Token唯一标识'
        ];
        $payload = array_merge($payload, $encryptInfo);
        $key = $this->key;
        if ($this->fun === self::FUN_OPENSSL) {
            $key = openssl_pkey_get_public($this->key);
        }
        return JWT::encode($payload, $key, $this->alg, $keyId, $head);
    }

    /**
     * @param $encryptString
     * @return object
     */
    public function verifyToken($encryptString)
    {
        $key = $this->key;
        if ($this->fun === self::FUN_OPENSSL) {
            $key = openssl_pkey_get_private($this->key);
        }
        return JWT::decode($encryptString, $key, [$this->alg]);
    }

    /**
     * @return object
     */
    public function verifyTokenByHeader()
    {
        $token = Centra::$request->getHeader('authorization') ?? filter_input(INPUT_SERVER, 'HTTP_Authorization') ?: ($_SERVER['HTTP_Authorization'] ?? '');
        if (Utils::startWith($token, 'Bearer ')) {
            $token = substr($token, 7);
        }
        return $this->verifyToken($token);
    }
}