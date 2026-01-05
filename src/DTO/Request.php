<?php

namespace Lkt\Http\DTO;

use Lkt\Factory\Instantiator\Instances\AbstractInstance;
use Lkt\Factory\Schemas\Schema;
use Lkt\Http\Enums\AccessLevel;
use Lkt\Http\Router;
use Lkt\Http\Routes\AbstractRoute;
use Lkt\Users\Interfaces\SessionUserInterface;

class Request
{
    readonly public AccessLevel $accessLevel;
    readonly public string $targetComponent;
    readonly public string $extractedTargetInstanceIdFromParamsKey;
    readonly public AbstractInstance|null $targetInstance;
    readonly public SessionUserInterface $loggedUser;

    readonly public bool $hasValidAccess;


    public function __construct(
        readonly public array       $params = [],
        AbstractRoute $route,
    )
    {
        $this->accessLevel = $route->getAccessLevel();

        $this->loggedUser = Router::getRouteLoggedUser($route);

        if ($this->accessLevel === AccessLevel::OnlyNotLoggedUsers && $this->loggedUser) {
            $this->hasValidAccess = false;
            return;
        }

        if (!$this->loggedUser && ($this->accessLevel === AccessLevel::OnlyLoggedUsers || $this->accessLevel === AccessLevel::OnlyAdminUsers)) {
            $this->hasValidAccess = false;
            return;
        }

        $hasValidAccess = true;





        // Access Level: Component
        $this->targetComponent = $route->getTargetComponent();

        // Access Level: Component Instance
        $extractIdKey = $route->getIdColumnValueParamsExtractionKey();

        if ($this->targetComponent && $extractIdKey) {
            $schema = Schema::get($this->targetComponent);
            $instance = $schema->getItemInstance((int)$this->params[$extractIdKey]);

            $this->extractedTargetInstanceIdFromParamsKey = $extractIdKey;
            $this->targetInstance = $instance;
        }
    }
}