<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Enums\AccessLevel;
use Lkt\Http\Enums\RouteMethod;
use Lkt\Http\Enums\SiteMapChangeFrequency;
use Lkt\Http\HttpEventHandler;
use Lkt\Http\Router;
use Lkt\Http\SiteMap\SiteMapConfig;

abstract class AbstractRoute
{
    protected RouteMethod $method = RouteMethod::Get;

    protected string $route = '';
    protected $handler = null;
    protected array $accessCheckers = [];

    protected AccessLevel $accessLevel = AccessLevel::Public;

    protected string $targetComponent = '';
    protected bool $targetIsLoggedUser = false;
    protected string $targetAccessPolicy = '';
    protected string $extractIdColumnValueFromParamsKey = '';
    protected string $extractPageFromParamsKey = '';
    protected string $extractWebItemFromParamsKey = '';
    protected bool $anonymousTarget = false;

    protected array $attemptToGrantPerms = [];

    protected $loggedUserChecker = null;

    protected SiteMapConfig|null $siteMap = null;

    protected array $requiredPermissions = [];

    protected array $httpEventHandlers = [];

    public function __construct(string $route, callable $handler)
    {
        $this->route = $route;
        $this->handler = $handler;
    }

    public function addHttpEventHandler(HttpEventHandler $eventHandler): static
    {
        $this->httpEventHandlers[] = $eventHandler;
        return $this;
    }

    /**
     * @return HttpEventHandler[]
     */
    public function getHttpEventHandlers(): array
    {
        return $this->httpEventHandlers;
    }

    public function getMethod(): string
    {
        return $this->method->value;
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

    public function setTargetAccessPolicy(string $accessPolicy): static
    {
        $this->targetAccessPolicy = $accessPolicy;
        return $this;
    }

    public function setGrantedPermsAttempt(array $perms): static
    {
        $this->attemptToGrantPerms = $perms;
        return $this;
    }

    public function setAnonymousTarget(bool $status = true): static
    {
        $this->anonymousTarget = $status;
        return $this;
    }

    public function isAnonymousTarget(): bool
    {
        return $this->anonymousTarget;
    }

    public function setRequiredPermissions(array $permissions): static
    {
        $this->requiredPermissions = $permissions;
        return $this;
    }

    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    public function getTargetComponent(): string
    {
        return $this->targetComponent;
    }

    public function getTargetAccessPolicy(): string
    {
        return $this->targetAccessPolicy;
    }

    public function getGrantedPermsAttempt(): array
    {
        return $this->attemptToGrantPerms;
    }

    public function setTargetIsLoggedUser(bool $state = true): static
    {
        $this->targetIsLoggedUser = $state;
        return $this;
    }

    public function getTargetIsLoggedUser(): bool
    {
        return $this->targetIsLoggedUser;
    }

    public function setIdColumnValueParamsExtractionKey(string $column): static
    {
        $this->extractIdColumnValueFromParamsKey = $column;
        return $this;
    }

    public function setPageValueParamsExtractionKey(string $column): static
    {
        $this->extractPageFromParamsKey = $column;
        return $this;
    }

    public function setWebItemValueParamsExtractionKey(string $column): static
    {
        $this->extractWebItemFromParamsKey = $column;
        return $this;
    }

    public function getIdColumnValueParamsExtractionKey(): string
    {
        return $this->extractIdColumnValueFromParamsKey;
    }

    public function getPageValueParamsExtractionKey(): string
    {
        return $this->extractPageFromParamsKey;
    }

    public function getWebItemValueParamsExtractionKey(): string
    {
        return $this->extractWebItemFromParamsKey;
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

    public function addToSiteMap(string|SiteMapChangeFrequency $changeFrequency = SiteMapChangeFrequency::Never, float $priority = 0.0): static
    {
        if (is_string($changeFrequency)) {
            $changeFrequency = SiteMapChangeFrequency::tryFrom($changeFrequency);
        }
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