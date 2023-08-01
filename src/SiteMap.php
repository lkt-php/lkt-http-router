<?php

namespace Lkt\Http;

use Lkt\Templates\Template;

class SiteMap
{
    public static function dispatch(): void
    {
        $r = [];
        foreach (Router::getRoutes() as $route) {
            if (!$route->isOnlyForLoggedUsers() && $route->hasSiteMapConfig()) $r[] = $route->getSiteMapConfig()->toString();
        }

        Response::ok(
            Template::file(__DIR__ . '/../resources/phtml/sitemap.phtml')->setData(['routes' => $r])
        )->setContentTypeTextXML()->sendHeaders()->sendContent();
        die();
    }
}