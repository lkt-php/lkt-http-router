<?php

namespace Lkt\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lkt\Factory\Schemas\Schema;
use Lkt\Http\Enums\AccessLevel;
use Lkt\Http\Networking\Networking;
use Lkt\Http\Routes\AbstractRoute;
use Lkt\Http\Routes\GetRoute;
use Lkt\Users\Instances\LktUser;
use Lkt\Users\Interfaces\SessionUserInterface;
use function FastRoute\simpleDispatcher;

class Router
{
    protected static array $routes = [];

    protected static $loggedUserChecker = null;
    protected static $loggedUserGetter = null;

    protected static Response|null $forceResponse = null;
    protected static mixed $currentRoute = null;
    protected static string $loggedUserComponent = '';

    protected static bool $ensureLoggedUser = true;

    protected static Request|null $request = null;

    /** @var Notification[] */
    protected static array $pendingNotifications = [];

    public static function addPendingNotification(Notification $toast): void
    {
        static::$pendingNotifications[] = $toast;
    }

    public static function getRequest(): Request|null
    {
        return static::$request;
    }

    public static function setLoggedUserChecker(callable $checker): void
    {
        static::$loggedUserChecker = $checker;
    }

    public static function setLoggedUserGetter(callable $getter): void
    {
        static::$loggedUserGetter = $getter;
    }

    public static function setLoggedUserComponent(string $component): void
    {
        static::$loggedUserComponent = $component;
    }

    public static function setEnsureLoggedUser(bool $status = true): void
    {
        static::$ensureLoggedUser = $status;
    }

    public static function getRouteLoggedUser(AbstractRoute $route): ?SessionUserInterface
    {
        if (static::$loggedUserComponent !== '' && Schema::exists(static::$loggedUserComponent)) {
            $schema = Schema::get(static::$loggedUserComponent);
            $aux = $schema->getItemInstance();
            return $aux::getSignedInUser();
        }
        if (Schema::exists('lkt-user')) {
            return LktUser::getSignedInUser();
        }
        return null;
    }

    public static function addRoute(AbstractRoute $route, string $router = 'default'): void
    {
        if (!isset(static::$routes[$router])) {
            static::$routes[$router] = [];
        }
        static::$routes[$router][$route->getRouterIndex()] = $route;
    }

    /**
     * @param string $router
     * @return AbstractRoute[]
     */
    public static function getRoutes(string $router = 'default'): array
    {
        if (!isset(static::$routes[$router])) {
            static::$routes[$router] = [];
        }
        return static::$routes[$router];
    }

    /**
     * @param string $router
     * @return AbstractRoute[]
     */
    public static function getGETRoutes(string $router = 'default'): array
    {
        return array_filter(static::getRoutes($router), function ($route) {
            return $route instanceof GetRoute;
        });
    }

    public static function forceGlobalResponse(Response $response): void
    {
        static::$forceResponse = $response;
    }

    public static function dispatch(): void
    {
        $response = static::getResponse();
        $response->sendHeaders()->sendContent();
        die();
    }

    public static function getResponse(): Response
    {
        $router = 'default';
        if (static::$forceResponse instanceof Response) {
            return static::$forceResponse;
        }

        /** @var AbstractRoute[] $routes */
        $routes = static::$routes[$router];
        $dispatcher = simpleDispatcher(function (RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route->getMethod(), $route->getRoute(), [
                    'handler' => $route->getHandler(),
                    'route' => $route,
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

        static::$currentRoute = $routeInfo[1];

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

                /** @var AbstractRoute $route */
                $route = $config['route'];

                $loggedUserChecker = $route->getLoggedUserChecker();
                $accessCheckers = $route->getAccessCheckers();
                $vars = [...$routeInfo[2], ...static::getRequestVars()];

                $request = new Request(
                    $vars,
                    $route,
                    static::$ensureLoggedUser,
                );

                static::$request = $request;

                if (!$request->hasValidAccess) return Response::forbidden();

                $loggedCheckResponse = static::ensureValidAccessChecker($loggedUserChecker, $request, $accessCheckers);
                if ($loggedCheckResponse instanceof Response) return $loggedCheckResponse;

                // Handle response
                $handler = $config['handler'];

                // Version migration helper
                $method = new \ReflectionMethod($handler[0], $handler[1]);

                if ($method->getParameters()[0]?->getType()?->getName() === 'Lkt\Http\Request') {
                    $response = call_user_func($handler, $request);

                } elseif ($method->getParameters()[0]?->getType()?->getName() === 'array' || $method->getParameters()[0]?->getType() === null) {
                    $response = call_user_func($handler, $request->params);
                } else {
                    $response = call_user_func($handler, $request);
                }

                if ($response instanceof Response) {
                    $responseData = $response->getResponseData();
                    if (is_array($responseData) && count(static::$pendingNotifications) > 0) {
                        $responseData['notifications'] = [];
                        foreach (static::$pendingNotifications as $toast) {
                            $responseData['notifications'][] = $toast->toArray();
                        }
                        $response->setResponseData($responseData);
                    }
                    return $response;
                }
                break;
        }

        return Response::forbidden();
    }

    private static function runAccessChecker(callable $checker, array $vars = []): ?Response
    {
        $result = call_user_func($checker, $vars);

        if ($result instanceof Response) return $result;

        if ($result === false) return Response::forbidden();

        return null;
    }

    public static function getRequestVars(): array
    {
        $requestMethod = Networking::getRequestMethod();
        $params = [];

        // Merge variables
        $request = [];
        switch ($requestMethod) {
            case 'get':
                if (count($_REQUEST) > 0) {
                    foreach ($_REQUEST as $key => $val) $params[$key] = $val;
                }
                break;

            case 'post':
                if (count($_REQUEST) > 0) {
                    foreach ($_REQUEST as $key => $val) $params[$key] = $val;
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

    protected static function ensureValidAccessChecker($loggedUserChecker, Request $request, $accessCheckers): ?Response
    {
        if (!is_callable($loggedUserChecker) && is_callable(static::$loggedUserChecker)) {
            $loggedUserChecker = static::$loggedUserChecker;
        }

        // Check if logged is user
        if (($request->accessLevel === AccessLevel::OnlyLoggedUsers || $request->accessLevel === AccessLevel::OnlyNotLoggedUsers) && is_callable($loggedUserChecker)) {
            $userIsLogged = call_user_func($loggedUserChecker, $request->params);

            if ($userIsLogged instanceof Response) return $userIsLogged;

            if ($request->accessLevel === AccessLevel::OnlyLoggedUsers && $userIsLogged !== true) {
                return Response::forbidden();
            }

            if ($request->accessLevel === AccessLevel::OnlyNotLoggedUsers && $userIsLogged === true) {
                return Response::notFound();
            }
        }

        // Check custom access checkers
        if (count($accessCheckers) > 0) {
            foreach ($accessCheckers as $accessChecker) {
                $checked = static::runAccessChecker($accessChecker, $request->params);
                if ($checked instanceof Response) return $checked;
            }
        }

        return null;
    }

    public static function getCurrentRoute()
    {
        return static::$currentRoute;
    }
}