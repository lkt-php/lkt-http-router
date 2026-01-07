<?php

namespace Lkt\Http;

use Lkt\Factory\Schemas\Enums\AccessPolicyEndOfLife;
use Lkt\Http\Enums\AccessLevel;

class BasicHttpHandler
{
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
            // Test perms which must be tested before granted
            foreach ($request->attemptToGrantPerms as $i => $grantedPerm) {

                // Determine which perms are gonna be tested
                // (it can be the array key (if string), or (if numeric array key) the array value or an array of string at array value)
                $testedPerm = is_numeric($i) ? $grantedPerm : $i;
                $testedPerms = [];
                if (is_array($testedPerm)) $testedPerms = $testedPerm;
                else $testedPerms[] = $testedPerm;

                // Determine which perms are gonna be granted
                // It can be a single perm or an array of perms
                $grantedPerms = [];
                if (is_array($grantedPerm)) $grantedPerms = $grantedPerm;
                else $grantedPerms[] = $grantedPerm;

                // Test permissions differently if it's an admin route or not
                if ($request->accessLevel === AccessLevel::OnlyAdminUsers) {
                    foreach ($testedPerms as $testedPerm) {
                        if ($request->loggedUser->hasAdminPermission($request->targetComponent, $testedPerm, $request->targetInstance)) {
                            foreach ($grantedPerms as $grantedPerm) $perm[] = $grantedPerm;
                        }
                    }
                } else {
                    foreach ($testedPerms as $testedPerm) {
                        if ($request->loggedUser->hasAppPermission($request->targetComponent, $testedPerm, $request->targetInstance)) {
                            foreach ($grantedPerms as $grantedPerm) $perm[] = $grantedPerm;
                        }
                    }
                }
            }
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
}