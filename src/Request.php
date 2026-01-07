<?php

namespace Lkt\Http;

use Lkt\Factory\Instantiator\Instances\AbstractInstance;
use Lkt\Factory\Schemas\Schema;
use Lkt\Http\Enums\AccessLevel;
use Lkt\Http\Routes\AbstractRoute;
use Lkt\Users\Interfaces\SessionUserInterface;

class Request
{
    readonly public AccessLevel $accessLevel;
    readonly public string $targetComponent;
    readonly public string $targetAccessPolicy;
    readonly public array $attemptToGrantPerms;
    readonly public string $extractedTargetInstanceIdFromParamsKey;
    readonly public AbstractInstance|null $targetInstance;
    readonly public SessionUserInterface|null $loggedUser;

    readonly public bool $hasValidAccess;


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

        // Access Level: Component
        $this->targetComponent = $route->getTargetComponent();
        $this->targetAccessPolicy = $route->getTargetAccessPolicy();
        $this->attemptToGrantPerms = $route->getGrantedPermsAttempt();

        // Access Level: Component Instance
        $extractIdKey = $route->getIdColumnValueParamsExtractionKey();

        if ($this->targetComponent && $extractIdKey) {
            $schema = Schema::get($this->targetComponent);
            $instance = $schema->getItemInstance((int)$this->params[$extractIdKey]);

            $this->extractedTargetInstanceIdFromParamsKey = $extractIdKey;
            $this->targetInstance = $instance;
            if (!$instance) {
                $this->hasValidAccess = false;
                return;
            }
        } elseif ($this->targetComponent && $route->isAnonymousTarget()) {
            $schema = Schema::get($this->targetComponent);
            $instance = $schema->getItemInstance();
            $this->targetInstance = $instance;

        } else {
            $this->targetInstance = null;
        }

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

        $this->hasValidAccess = true;
    }
}