<?php

namespace Lkt\Http;

use Lkt\Factory\Schemas\Enums\AccessPolicyEndOfLife;
use Lkt\Factory\Schemas\Schema;

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
        if ($request->targetAccessPolicy) {
            $request->targetInstance->setAccessPolicy($request->targetAccessPolicy, AccessPolicyEndOfLife::UntilNextRead);
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
        if ($request->targetAccessPolicy) {
            $request->targetInstance->setAccessPolicy($request->targetAccessPolicy, AccessPolicyEndOfLife::UntilNextWrite);
        }
        $request->targetInstance->autoCreate($request->params);

        return Response::ok(['id' => $request->targetInstance->getId()]);
    }

    public static function up(Request $request): Response
    {
        if ($request->targetAccessPolicy) {
            $request->targetInstance->setAccessPolicy($request->targetAccessPolicy, AccessPolicyEndOfLife::UntilNextWrite);
        }
        $request->targetInstance->autoUpdate($request->params);

        return Response::ok(['id' => $request->targetInstance->getId()]);
    }

    public static function rm(Request $request): Response
    {
        $request->targetInstance->delete();
        return Response::ok();
    }

    public static function pg(Request $request): Response
    {
        $schema = Schema::get($request->targetComponent);
        $helperInstance = $schema->getItemInstance();
        $builder = $helperInstance::getQueryCaller();
        $rawResults = $helperInstance::getPage($request->page, $builder);
        $results = [];
        foreach ($rawResults as $rawResult) {
            if ($request->targetAccessPolicy) {
                $rawResult->setAccessPolicy($request->targetAccessPolicy, AccessPolicyEndOfLife::UntilNextRead);
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
            'maxPage' => $request->targetInstance::getAmountOfPages($builder),
            'perm' => $perm
        ]);
    }

    public static function ls(Request $request): Response
    {
        $schema = Schema::get($request->targetComponent);
        $helperInstance = $schema->getItemInstance();
        $builder = $helperInstance::getQueryCaller();
        $rawResults = $helperInstance::getMany($builder);
        $results = [];
        foreach ($rawResults as $rawResult) {
            if ($request->targetAccessPolicy) {
                $rawResult->setAccessPolicy($request->targetAccessPolicy, AccessPolicyEndOfLife::UntilNextRead);
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