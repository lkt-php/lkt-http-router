<?php

namespace Lkt\Http;

use Lkt\Factory\Schemas\Enums\AccessPolicyEndOfLife;
use Lkt\Factory\Schemas\Schema;
use Lkt\Http\Enums\AccessLevel;
use Lkt\Http\Enums\HttpEvent;
use Lkt\Users\Enums\RoleCapability;
use Lkt\WebItems\Enums\WebItemAction;

class BasicHttpHandler
{
    public const Page = [self::class, 'pg'];
    public const List = [self::class, 'ls'];
    public const Create = [self::class, 'mk'];
    public const Read = [self::class, 'r'];
    public const Update = [self::class, 'up'];
    public const Drop = [self::class, 'rm'];

    public static function r(Request $request): Response
    {
        $accessPolicy = $request->targetAccessPolicy;
        if ($request->targetWebItem) {
            if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAdminActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAdminActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
            else {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAppActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAppActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
        }

        if ($accessPolicy) {
            $request->targetInstance->setAccessPolicy($accessPolicy, AccessPolicyEndOfLife::UntilNextRead);
        }

        $perm = [];
        if ($request->loggedUser) {
            $perm = $request->loggedUser->attemptToGrantPermissions(
                $request->accessLevel,
                $request->targetComponent,
                $request->attemptToGrantPerms,
                $request->targetInstance,
            );
        }

        $perm = array_unique($perm);

        return Response::ok([
            'item' => $request->targetInstance->autoRead(),
            'perm' => $perm,
        ]);
    }

    public static function mk(Request $request): Response
    {
        $accessPolicy = $request->targetAccessPolicy;
        if ($request->targetWebItem) {
            if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAdminActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAdminActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
            else {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAppActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAppActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
        }

        if ($accessPolicy) {
            $request->targetInstance->setAccessPolicy($accessPolicy, AccessPolicyEndOfLife::UntilNextWrite);
        }
        $request->targetInstance->autoCreate($request->params);

        if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::SuccessCreate, $request->httpEventHandlers, []);

        return Response::ok(['id' => $request->targetInstance->getId()]);
    }

    public static function up(Request $request): Response
    {
        $accessPolicy = $request->targetAccessPolicy;
        if ($request->targetWebItem) {
            if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAdminActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAdminActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
            else {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAppActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAppActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
        }

        if ($accessPolicy) {
            $request->targetInstance->setAccessPolicy($accessPolicy, AccessPolicyEndOfLife::UntilNextWrite);
        }
        $request->targetInstance->autoUpdate($request->params);

        if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::SuccessUpdate, $request->httpEventHandlers, []);

        return Response::ok(['id' => $request->targetInstance->getId()]);
    }

    public static function rm(Request $request): Response
    {
        if ($request->targetWebItem) {
            if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
                if (!in_array(WebItemAction::Drop, $request->targetWebItem->getEnabledAdminActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }
            }
            else {
                if (!in_array(WebItemAction::Drop, $request->targetWebItem->getEnabledAppActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }
            }
        }

        $request->targetInstance->delete();

        if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::SuccessDrop, $request->httpEventHandlers, []);
        return Response::ok();
    }

    public static function pg(Request $request): Response
    {
        $accessPolicy = $request->targetAccessPolicy;
        if ($request->targetWebItem) {
            if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
                if (!in_array(WebItemAction::Page, $request->targetWebItem->getEnabledAdminActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAdminActionAccessPolicy(WebItemAction::Page);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
            else {
                if (!in_array(WebItemAction::Page, $request->targetWebItem->getEnabledAppActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAppActionAccessPolicy(WebItemAction::Page);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
        }

        if (!$request->targetComponent) return Response::badRequest();

        $schema = Schema::get($request->targetComponent);
        $helperInstance = $schema->getItemInstance();
        $builder = $helperInstance::getQueryCaller();

        if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
            $capability = $request->loggedUser->getAdminCapability($request->targetComponent, 'ls');
        } else {
            $capability = $request->loggedUser->getAppCapability($request->targetComponent, 'ls');
        }

        if ($capability && $capability === RoleCapability::Owned) {
            $ownershipField = $schema->getOwnershipField();
            if ($ownershipField) {
                $builder->andIntegerEqual($ownershipField->getColumn(), $request->loggedUser->getId());
            }
        }
        $rawResults = $helperInstance::getPage($request->page, $builder);
        $results = [];
        foreach ($rawResults as $rawResult) {
            if ($accessPolicy) {
                $rawResult->setAccessPolicy($accessPolicy, AccessPolicyEndOfLife::UntilNextRead);
            }
            $results[] = $rawResult->autoRead();
        }

        $perm = [];
        if ($request->loggedUser) {
            $perm = $request->loggedUser->attemptToGrantPermissions(
                $request->accessLevel,
                $request->targetComponent,
                $request->attemptToGrantPerms,
                null,
            );
        }

        return Response::ok([
            'results' => $results,
            'maxPage' => $helperInstance::getAmountOfPages($builder),
            'perm' => $perm
        ]);
    }

    public static function ls(Request $request): Response
    {
        $accessPolicy = $request->targetAccessPolicy;
        if ($request->targetWebItem) {
            if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAdminActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAdminActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
            else {
                if (!in_array(WebItemAction::List, $request->targetWebItem->getEnabledAppActions())) {
                    if ($request->httpEventHandlers) HttpEventHandler::triggerEvent(HttpEvent::NotEnoughPerms, $request->httpEventHandlers, []);
                    return Response::badRequest();
                }

                if (!$accessPolicy) {
                    $defaultAccessPolicy = $request->targetWebItem->getAppActionAccessPolicy(WebItemAction::List);
                    if ($defaultAccessPolicy) $accessPolicy = $defaultAccessPolicy;
                }
            }
        }


        if (!$request->targetComponent) return Response::badRequest();

        $schema = Schema::get($request->targetComponent);
        $helperInstance = $schema->getItemInstance();
        $builder = $helperInstance::getQueryCaller();

        if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
            $capability = $request->loggedUser->getAdminCapability($request->targetComponent, 'ls');
        } else {
            $capability = $request->loggedUser->getAppCapability($request->targetComponent, 'ls');
        }

        if ($capability && $capability === RoleCapability::Owned) {
            $ownershipField = $schema->getOwnershipField();
            if ($ownershipField) {
                $builder->andIntegerEqual($ownershipField->getColumn(), $request->loggedUser->getId());
            }
        }
        $rawResults = $helperInstance::getMany($builder);
        $results = [];
        foreach ($rawResults as $rawResult) {
            if ($accessPolicy) {
                $rawResult->setAccessPolicy($accessPolicy, AccessPolicyEndOfLife::UntilNextRead);
            }
            $results[] = $rawResult->autoRead();
        }

        $perm = [];
        if ($request->loggedUser) {
            $perm = $request->loggedUser->attemptToGrantPermissions(
                $request->accessLevel,
                $request->targetComponent,
                $request->attemptToGrantPerms,
                null,
            );
        }

        return Response::ok([
            'results' => $results,
            'perm' => $perm
        ]);
    }
}