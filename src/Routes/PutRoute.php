<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Enums\RouteMethod;

class PutRoute extends AbstractRoute
{
    protected RouteMethod $method = RouteMethod::Put;
}