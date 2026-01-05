<?php

namespace Lkt\Http\Routes;

use Lkt\Http\Enums\RouteMethod;

class PostRoute extends AbstractRoute
{
    protected RouteMethod $method = RouteMethod::Post;
}