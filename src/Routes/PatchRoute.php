<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Enums\RouteMethod;

class PatchRoute extends AbstractRoute
{
    protected RouteMethod $method = RouteMethod::Patch;
}