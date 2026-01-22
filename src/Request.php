<?php

namespace Lkt\Http;

use Lkt\Factory\Instantiator\Instances\AbstractInstance;
use Lkt\Factory\Schemas\Schema;
use Lkt\Http\Enums\AccessLevel;
use Lkt\Http\Routes\AbstractRoute;
use Lkt\Users\Interfaces\SessionUserInterface;
use \Lkt\WebItems\WebItem;

class Request
{
    readonly public AccessLevel $accessLevel;
    readonly public string $targetComponent;
    readonly public string $targetAccessPolicy;
    readonly public array $attemptToGrantPerms;
    readonly public string $extractedTargetInstanceIdFromParamsKey;
    readonly public WebItem|null $targetWebItem;
    readonly public AbstractInstance|null $targetInstance;
    readonly public SessionUserInterface|null $loggedUser;

    /** @var HttpEventHandler[] */
    readonly public array $httpEventHandlers;

    readonly public bool $hasValidAccess;
    readonly public int $page;


    public function __construct(
        readonly public array       $params = [],
        AbstractRoute $route,
        bool $ensureLoggedUser = true,
    )
    {
        $this->accessLevel = $route->getAccessLevel();

        $this->loggedUser = Router::getRouteLoggedUser($route);

        if ($this->accessLevel === AccessLevel::OnlyNotLoggedUsers && $this->loggedUser) {
            $this->hasValidAccess = false;
            return;
        }

        if ($ensureLoggedUser && !$this->loggedUser && ($this->accessLevel === AccessLevel::OnlyLoggedUsers || $this->accessLevel === AccessLevel::OnlyAdminUsers)) {
            $this->hasValidAccess = false;
            return;
        }

        if ($this->accessLevel === AccessLevel::OnlyAdminUsers && count($this->loggedUser?->getAdminRolesData()) === 0) {
            $this->hasValidAccess = false;
            return;
        }

        $this->httpEventHandlers = $route->getHttpEventHandlers();

        // Page
        $extractPageKey = $route->getPageValueParamsExtractionKey();
        if ($extractPageKey) $this->page = (int)$this->params[$extractPageKey];

        // Access Level: Component
        $extractWebItemKey = $route->getWebItemValueParamsExtractionKey();
        if ($extractWebItemKey) {
            $this->targetWebItem = WebItem::detectWebItem($this->params[$extractWebItemKey]);
            if ($this->targetWebItem) $this->targetComponent = $this->targetWebItem->component;
            else $this->targetComponent = '';

        } else {
            $this->targetComponent = $route->getTargetComponent();
            $this->targetWebItem = null;
        }
        $this->targetAccessPolicy = $route->getTargetAccessPolicy();
        $this->attemptToGrantPerms = $route->getGrantedPermsAttempt();

        $targetIsLoggedUser = $route->getTargetIsLoggedUser();

        if ($targetIsLoggedUser) {
            $this->targetInstance = $this->loggedUser;
        } else {

            // Access Level: Component Instance
            $extractIdKey = $route->getIdColumnValueParamsExtractionKey();

            if ($this->targetComponent && $extractIdKey) {
                $schema = Schema::get($this->targetComponent);
                $instance = $schema->getItemInstance((int)$this->params[$extractIdKey]);

                $this->extractedTargetInstanceIdFromParamsKey = $extractIdKey;
                $targetInstance = $instance;
                if (!$instance) {
                    $this->hasValidAccess = false;
                    return;
                }
            } elseif ($this->targetComponent && $route->isAnonymousTarget()) {
                $schema = Schema::get($this->targetComponent);
                $instance = $schema->getItemInstance();
                $targetInstance = $instance;

            } else {
                $targetInstance = null;
            }
            $this->targetInstance = $targetInstance;

            if ($this->targetComponent){
                if ($this->accessLevel === AccessLevel::OnlyAdminUsers) {
                    $isValid = true;
                    foreach ($route->getRequiredPermissions() as $permission) {
                        $isValid = $isValid && $this->loggedUser->hasAdminPermission($this->targetComponent, $permission, $this->targetInstance);
                    }
                    if (!$isValid) {
                        $this->hasValidAccess = $isValid;
                        return;
                    }

                } else if ($this->accessLevel === AccessLevel::OnlyLoggedUsers) {
                    $isValid = true;
                    foreach ($route->getRequiredPermissions() as $permission) {
                        $isValid = $isValid && $this->loggedUser->hasAppPermission($this->targetComponent, $permission, $this->targetInstance);
                    }

                    if (!$isValid) {
                        $this->hasValidAccess = $isValid;
                        return;
                    }
                }
            }
        }

        $this->hasValidAccess = true;
    }

    public static function getCurrent(): static|null
    {
        return Router::getRequest();
    }
}