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

    protected static array $accessCheckers = [];

    protected static $loggedUserChecker = null;

    public static function setLoggedUserChecker(callable $checker): void
    {
        static::$loggedUserChecker = $checker;
    }

    public static function setAccessChecker(string $name, callable $checker): void
    {
        static::$accessCheckers[$name] = $checker;
    }

    public static function addRoute(AbstractRoute $route, string $router = 'default'): void
    {
        if (!isset(static::$routes[$router])) {
            static::$routes[$router] = [];
        }
        static::$routes[$router][] = $route;
    }

    public static function dispatch(string $router = 'default'): void
    {
        $response = static::getResponse($router);
        $response->sendHeaders()->sendContent();
        die();
    }

    public static function getResponse(string $router = 'default'): Response
    {
        /** @var AbstractRoute[] $routes */
        $routes = static::$routes[$router];
        $dispatcher = simpleDispatcher(function (RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route->getMethod(), $route->getRoute(), [
                    'handler' => $route->getHandler(),
                    'loggedUserChecker' => $route->getLoggedUserChecker(),
                    'onlyLoggedUsers' => $route->isOnlyForLoggedUsers(),
                    'onlyNotLoggedUsers' => $route->isOnlyForNotLoggedUsers(),
                    'accessCheckers' => $route->getAccessCheckers(),
                ]);
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
                $config = $routeInfo[1];
                $loggedUserChecker = $config['loggedUserChecker'];
                $isOnlyForLoggedUsers = $config['onlyLoggedUsers'];
                $isOnlyNotForLoggedUsers = $config['onlyNotLoggedUsers'];
                $accessCheckers = $config['accessCheckers'];
                $vars = [...$routeInfo[2], ...static::getRequestVars()];

                if (!is_callable($loggedUserChecker) && is_callable(static::$loggedUserChecker)) {
                    $loggedUserChecker = static::$loggedUserChecker;
                }

                // Check if logged is user
                if (($isOnlyForLoggedUsers || $isOnlyNotForLoggedUsers) && is_callable($loggedUserChecker)) {
                    $userIsLogged = call_user_func($loggedUserChecker, $vars);

                    if ($isOnlyForLoggedUsers && $userIsLogged !== true) {
                        return Response::forbidden();
                    }

                    if ($isOnlyNotForLoggedUsers && $userIsLogged === true) {
                        return Response::notFound();
                    }
                }

                // Check custom access checkers
                if (count($accessCheckers) > 0) {
                    foreach ($accessCheckers as $accessChecker) {
                        $checked = static::runAccessChecker($accessChecker, $vars);
                        if ($checked instanceof Response) {
                            return $checked;
                        }
                    }
                }

                // Handle response
                $handler = $config['handler'];

                $response = call_user_func($handler, $vars);
                if ($response instanceof Response) {
                    return $response;
                }
                break;
        }

        return Response::forbidden();
    }

    private static function runAccessChecker(string $name, array $vars = []): ?Response
    {
        if (!isset(static::$accessCheckers[$name])) {
            return null;
        }
        $result = call_user_func(static::$accessCheckers[$name], $vars);

        if ($result instanceof Response) {
            return $result;
        }

        if ($result === false) {
            return Response::forbidden();
        }

        return null;
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

    private static function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public static function getBearerToken(): ?string
    {
        $headers = static::getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public static function getTokenHeader(): ?string
    {
        if (isset($_SERVER['HTTP_TOKEN'])) {
            return trim($_SERVER['HTTP_TOKEN']);
        }
        return null;
    }
}