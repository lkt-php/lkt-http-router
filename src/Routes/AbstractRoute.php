<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Router;

abstract class AbstractRoute
{
    protected const METHOD = 'GET';

    protected string $route = '';
    protected $handler = null;
    protected array $accessCheckers = [];

    protected bool $onlyLoggedUser = false;
    protected bool $onlyNotLoggedUser = false;

    protected $loggedUserChecker = null;

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

    public function setOnlyNotLoggedUsers(bool $status = true): static
    {
        $this->onlyNotLoggedUser = $status;
        return $this;
    }

    public function setLoggedUserChecker(callable $handler): static
    {
        $this->loggedUserChecker = $handler;
        return $this;
    }

    public function getLoggedUserChecker(): ?callable
    {
        return $this->loggedUserChecker;
    }

    public function isOnlyForLoggedUsers(): bool
    {
        return $this->onlyLoggedUser;
    }

    public function isOnlyForNotLoggedUsers(): bool
    {
        return $this->onlyNotLoggedUser;
    }

    public function setAccessCheckers(array $names): static
    {
        $this->accessCheckers = $names;
        return $this;
    }

    public function getAccessCheckers(): array
    {
        return $this->accessCheckers;
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

    public static function onlyNotLoggedUsers(string $route, callable $handler): static
    {
        $r = new static($route, $handler);
        $r->setOnlyNotLoggedUsers();
        Router::addRoute($r);
        return $r;
    }
}