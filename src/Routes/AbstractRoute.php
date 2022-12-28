<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Router;

abstract class AbstractRoute
{
    protected const METHOD = 'GET';

    protected string $route = '';
    protected $handler = null;
    protected bool $onlyLoggedUser = false;

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

    public function setOnlyLoggedUsers(bool $status = true): static
    {
        $this->onlyLoggedUser = $status;
        return $this;
    }

    public function isOnlyForLoggedUsers(): bool
    {
        return $this->onlyLoggedUser;
    }

    public static function register(string $route, callable $handler): static
    {
        $r = new static($route, $handler);
        Router::addRoute($r);
        return $r;
    }

    public static function onlyLoggedUsers(string $route, callable $handler): static
    {
        $r = new static($route, $handler);
        $r->setOnlyLoggedUsers();
        Router::addRoute($r);
        return $r;
    }
}