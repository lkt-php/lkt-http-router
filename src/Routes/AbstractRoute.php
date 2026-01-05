<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Enums\AccessLevel;
use Lkt\Http\Router;
use Lkt\Http\SiteMap\SiteMapConfig;

abstract class AbstractRoute
{
    protected const METHOD = 'GET';

    protected string $route = '';
    protected $handler = null;
    protected array $accessCheckers = [];

    protected AccessLevel $accessLevel = AccessLevel::Public;

    protected string $targetComponent = '';
    protected string $extractIdColumnValueFromParamsKey = '';

    protected $loggedUserChecker = null;

    protected SiteMapConfig|null $siteMap = null;

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

    public function setOnlyLoggedUsers(): static
    {
        $this->accessLevel = AccessLevel::OnlyLoggedUsers;
        return $this;
    }

    public function setOnlyNotLoggedUsers(): static
    {
        $this->accessLevel = AccessLevel::OnlyNotLoggedUsers;
        return $this;
    }

    public function setAdminRoute(): static
    {
        $this->accessLevel = AccessLevel::OnlyAdminUsers;
        return $this;
    }

    public function setTargetComponent(string $component): static
    {
        $this->targetComponent = $component;
        return $this;
    }

    public function getTargetComponent(): string
    {
        return $this->targetComponent;
    }

    public function setIdColumnValueParamsExtractionKey(string $column): static
    {
        $this->extractIdColumnValueFromParamsKey = $column;
        return $this;
    }

    public function getIdColumnValueParamsExtractionKey(): string
    {
        return $this->extractIdColumnValueFromParamsKey;
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
        return $this->accessLevel === AccessLevel::OnlyLoggedUsers;
    }

    public function isOnlyForNotLoggedUsers(): bool
    {
        return $this->accessLevel === AccessLevel::OnlyNotLoggedUsers;
    }

    public function isAdminRoute(): bool
    {
        return $this->accessLevel === AccessLevel::OnlyAdminUsers;
    }

    public function addAccessChecker(callable $checker): static
    {
        $this->accessCheckers[] = $checker;
        return $this;
    }

    public function getAccessLevel(): AccessLevel
    {
        return $this->accessLevel;
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

    public static function admin(string $route, callable $handler): static
    {
        $r = new static($route, $handler);
        $r->setAdminRoute();
        Router::addRoute($r);
        return $r;
    }
}