<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Router;

abstract class AbstractRoute
{
    protected const METHOD = 'GET';

    protected string $route = '';
    protected $handler = null;

    public function __construct(string $route, callable $handler)
    {
        $this->route = $route;
        $this->handler = $handler;
    }

    public function getMethod(): string
    {
        return static::METHOD;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getHandler(): callable
    {
        return $this->handler;
    }

    public static function register(string $route, callable $handler): static
    {
        $r = new static($route, $handler);
        Router::addRoute($r);
        return $r;
    }
}