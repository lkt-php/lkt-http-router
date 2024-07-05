<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Router;
use Lkt\Http\SiteMap\SiteMapConfig;

abstract class AbstractRoute
{
    protected const METHOD = 'GET';

    protected string $route = '';
    protected $handler = null;
    protected array $accessCheckers = [];

    protected bool $onlyLoggedUser = false;
    protected bool $onlyNotLoggedUser = false;

    protected $loggedUserChecker = null;

    protected SiteMapConfig|null $siteMap = null;

    protected array $laminimConfig = [];

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

    public function getRouterIndex(): string
    {
        return implode('_', [$this->getMethod(), $this->getRoute()]);
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

    public function setLaminimConfig(array $laminimConfig): static
    {
        $this->laminimConfig = $laminimConfig;
        return $this;
    }

    public function getLaminimConfig(): array
    {
        return $this->laminimConfig;
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

    public function addAccessChecker(callable $checker): static
    {
        $this->accessCheckers[] = $checker;
        return $this;
    }

    public function getAccessCheckers(): array
    {
        return $this->accessCheckers;
    }

    public function addToSiteMap(string $changeFrequency = SiteMapConfig::CHANGE_FREQUENCY_NEVER, float $priority = 0.0): static
    {
        $this->siteMap = new SiteMapConfig($this->route, $changeFrequency, $priority);
        return $this;
    }

    public function hasSiteMapConfig(): bool
    {
        return is_object($this->siteMap);
    }

    public function getSiteMapConfig(): SiteMapConfig
    {
        return $this->siteMap;
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