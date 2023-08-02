<?php

namespace Lkt\Http;

use Lkt\Templates\Template;

class SiteMap
{
    public static function getResponse(): Response
    {
        $r = [];
        foreach (Router::getGETRoutes() as $route) {
            if (!$route->isOnlyForLoggedUsers() && $route->hasSiteMapConfig()) {
                $config = $route->getSiteMapConfig();
                $r[$config->getLocation()] = $config->toString();
            }
        }

        ksort($r);

        return Response::ok(
            Template::file(__DIR__ . '/../resources/phtml/sitemap.phtml')->setData(['routes' => $r])
        )->setContentTypeTextXML();
    }

    public static function dispatch(): void
    {
        static::getResponse()->sendHeaders()->sendContent();
        die();
    }
}