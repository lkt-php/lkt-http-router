<?php

namespace Lkt\Http;

class SiteMap
{
    public static function getResponse(): Response
    {
        $routes = [];
        foreach (Router::getGETRoutes() as $route) {
            if (!$route->isOnlyForLoggedUsers() && !$route->isAdminRoute() && $route->hasSiteMapConfig()) {
                $config = $route->getSiteMapConfig();
                $routes[$config->getLocation()] = $config->toString();
            }
        }

        ksort($routes);

        $siteMap = ["<?xml version='1.0' encoding='UTF-8'?>", "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">"];
        foreach ($routes as $route) $siteMap[] = $route;
        $siteMap[] = "</urlset>";

        return Response::ok(implode('', $siteMap))->setContentTypeTextXML();
    }

    public static function dispatch(): void
    {
        static::getResponse()->sendHeaders()->sendContent();
        die();
    }
}