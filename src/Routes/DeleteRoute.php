<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Enums\RouteMethod;

class DeleteRoute extends AbstractRoute
{
    protected RouteMethod $method = RouteMethod::Delete;
}