<?php

namespace Lkt\Http\Networking;

final class Networking
{
    protected static ?Networking $instance = null;

    protected string $remoteAddress = '';
    protected string $httpProtocol = '';
    protected string $httpProtocolVersion = '';
    protected string $httpHost = '';
    protected string $requestUri = '';
    protected string $requestMethod = '';
    protected string $cleanedRequestUri = '';
    protected string $serverName = '';
    protected string $publicUrl = '';

    public function __construct()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'];

        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] > 0) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $remoteAddr = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $this->remoteAddress = $remoteAddr;

        $httpXForwardedProto = '';
        if (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
            $_SERVER['SERVER_PORT'] = '443';
            $httpXForwardedProto = 'https';
        }

        $this->httpProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) || ($httpXForwardedProto === 'https') ? 'https' : 'http';

        $protocol = 'HTTP/1.0';
        if ('HTTP/1.1' == $_SERVER['SERVER_PROTOCOL']) $protocol = 'HTTP/1.1';
        $this->httpProtocolVersion = $protocol;

        $this->httpHost = "{$this->httpProtocol}://{$_SERVER['HTTP_HOST']}";
        $this->requestUri = $_SERVER['REQUEST_URI'];

        $uri = explode('?', $this->requestUri);
        $this->cleanedRequestUri = $uri[0];

        $this->serverName = $_SERVER['SERVER_NAME'];

        $this->requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
    }

    private static function getInstance(): self
    {
        if (!self::$instance instanceof self) self::$instance = new self();
        return self::$instance;
    }

    public static function getRemoteAddress(): string
    {
        return self::getInstance()->remoteAddress;
    }

    public static function getHttpProtocol(): string
    {
        return self::getInstance()->httpProtocol;
    }

    public static function getHttpProtocolVersion(): string
    {
        return self::getInstance()->httpProtocolVersion;
    }

    public static function getHttpHost(): string
    {
        return self::getInstance()->httpHost;
    }

    public static function getServerName(): string
    {
        return self::getInstance()->serverName;
    }

    public static function getRequestUri(): string
    {
        return self::getInstance()->cleanedRequestUri;
    }

    public static function getFullRequestUri(): string
    {
        return self::getInstance()->requestUri;
    }

    public static function getRequestMethod(): string
    {
        return self::getInstance()->requestMethod;
    }

    public static function getPublicUrl(): string
    {
        return self::getInstance()->publicUrl;
    }

    public static function setPublicUrl(string $url): self
    {
        $r = self::getInstance();
        $r->publicUrl = $url;
        return $r;
    }
}


