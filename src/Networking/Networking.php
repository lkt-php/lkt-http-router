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

    public static function getInstance(): self
    {
        if (!self::$instance instanceof self) self::$instance = new self();
        return self::$instance;
    }

    public function getRemoteAddress(): string
    {
        return $this->remoteAddress;
    }

    public function getHttpProtocol(): string
    {
        return $this->httpProtocol;
    }

    public function getHttpProtocolVersion(): string
    {
        return $this->httpProtocolVersion;
    }

    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getRequestUri(): string
    {
        return $this->cleanedRequestUri;
    }

    public function getFullRequestUri(): string
    {
        return $this->requestUri;
    }

    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public static function setPublicUrl(string $url): self
    {
        $r = self::getInstance();
        $r->publicUrl = $url;
        return $r;
    }
}


