<?php

namespace Lkt\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lkt\Http\Routes\AbstractRoute;
use function FastRoute\simpleDispatcher;

class Router
{
    /** @var AbstractRoute[] */
    protected static array $routes = [];

    public static function addRoute(AbstractRoute $route, string $router = 'default'): void
    {
        if (!isset(static::$routes[$router])) {
            static::$routes[$router] = [];
        }
        static::$routes[$router][] = $route;
    }

    public static function dispatch(string $router = 'default'): Response
    {
        /** @var AbstractRoute[] $routes */
        $routes = static::$routes[$router];
        $dispatcher = simpleDispatcher(function(RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route->getMethod(), $route->getRoute(), $route->getHandler());
            }
        });

        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return Response::notFound();
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                return Response::methodNotAllowed();
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = [...$routeInfo[2], ...static::getRequestVars()];

                $response = call_user_func($handler, $vars);
                if ($response instanceof Response) {
                    return $response;
                }
                break;
        }

        return Response::forbidden();
    }

    private static function getRequestVars(): array
    {
        $requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $params = [];
        
        // Merge variables
        $request = [];
        switch ($requestMethod) {
            case 'get':
                if (count($_REQUEST) > 0) {
                    foreach ($_REQUEST as $key => $val) {
                        $params[$key] = $val;
                    }
                }
                break;

            case 'post':
                if (count($_REQUEST) > 0) {
                    foreach ($_REQUEST as $key => $val) {
                        $params[$key] = $val;
                    }
                }
                $content = file_get_contents('php://input');
                if (strlen($content) > 0) {
                    $request = json_decode($content, true);
                    if ($request === null) {
                        $request = [];
                        parse_str($content, $request);
                    }
                } else {
                    parse_str($content, $request);
                }
                break;

            case 'put':
            case 'delete':
                $content = file_get_contents('php://input');
                if (strlen($content) > 0) {
                    $request = json_decode($content, true);
                    if ($request === null) {
                        $request = [];
                        parse_str($content, $request);
                    }
                } else {
                    parse_str($content, $request);
                }
                break;
        }
        foreach ($request as $key => $requestVar) {
            if ($requestVar[0] === '[' && $requestVar[strlen($requestVar) - 1] === ']') {
                $request[$key] = json_decode($requestVar, true);
            }
        }


        $params = array_merge($params, $request);

        // Unescape json data
        if (count($params) > 0) {
            foreach ($params as &$uri) {
                if (!is_array($uri)) {
                    $uri = stripcslashes($uri);
                }
            }
        }

        return $params;
    }
}