# About this package

This package is designed to handle HTTP request.

All `Response` objects used by the `Router` are described in [lkt/http-response](https://github.com/lekrat/lkt-http-response).

This package implements [nikic/FastRoute](https://github.com/nikic/FastRoute) so all dynamic params for routes are available. 

# Installation

```shell
composer require lkt/http-router
```

# Register routes

Register a new route is as easy as instantiate a class. It takes two arguments: the full route and a [callable](https://www.php.net/manual/en/language.types.callable.php) with the `Route` handler.

```php
use Lkt\Http\Routes\GetRoute;
use Lkt\Http\Routes\PostRoute;
use Lkt\Http\Routes\PutRoute;
use Lkt\Http\Routes\PatchRoute;
use Lkt\Http\Routes\DeleteRoute;

GetRoute::register('/blog/{id}', [YourController::class, 'handler']); // Route only can be accessed by GET
PostRoute::register('/blog', [YourController::class, 'handler']); // Route only can be accessed by POST
PutRoute::register('/blog/{id}', [YourController::class, 'handler']); // Route only can be accessed by PUT
PatchRoute::register('/blog/{id}', [YourController::class, 'handler']); // Route only can be accessed by PATCH
DeleteRoute::register('/blog/{id}', [YourController::class, 'handler']); // Route only can be accessed by DELETE
```

Keep in mind a route can't be declared twice.

# Routes visibility

All routes have two alternative constructors to control if it's available in a public or private way: `onlyLoggedUsers` and `onlyNotLoggedUsers`

```php
use Lkt\Http\Routes\GetRoute;
use Lkt\Http\Routes\PostRoute;
use Lkt\Http\Routes\PutRoute;
use Lkt\Http\Routes\PatchRoute;
use Lkt\Http\Routes\DeleteRoute;

GetRoute::onlyLoggedUsers('/blog/{id}', [YourController::class, 'handler']);
PostRoute::onlyLoggedUsers('/blog', [YourController::class, 'handler']);
PutRoute::onlyLoggedUsers('/blog/{id}', [YourController::class, 'handler']);
PatchRoute::onlyLoggedUsers('/blog/{id}', [YourController::class, 'handler']);
DeleteRoute::onlyLoggedUsers('/blog/{id}', [YourController::class, 'handler']);

GetRoute::onlyNotLoggedUsers('/public-blog/{id}', [YourController::class, 'handler']);
PostRoute::onlyNotLoggedUsers('/public-blog', [YourController::class, 'handler']);
PutRoute::onlyNotLoggedUsers('/public-blog/{id}', [YourController::class, 'handler']);
PatchRoute::onlyNotLoggedUsers('/public-blog/{id}', [YourController::class, 'handler']);
DeleteRoute::onlyNotLoggedUsers('/public-blog/{id}', [YourController::class, 'handler']);
```

# Determine if user is logged in

`Router` includes a method which accepts a [callable](https://www.php.net/manual/en/language.types.callable.php) function to check if user is logged in or not.

This function must return a `bool` value (`true` means user is logged).

This way, `Router` doesn't modify your logic.

```php
use Lkt\Http\Router;

// With a function
Router::setLoggedUserChecker(function(){
    // Do your stuff
    return true;
});

// With a callable array
Router::setLoggedUserChecker([YourLoginController::class, 'yourLoginCheckerMethod']);
```

# Specify a custom login checker for a `Route`

If you have a context where can exist many ways to get logged in, you can specify a custom login checker:

```php
use Lkt\Http\Routes\GetRoute;

GetRoute::onlyLoggedUsers('/blog', [YourController::class, 'handler'])
    ->setLoggedUserChecker([YourLoginController::class, 'yourSpecificLoginCheckerMethod']);
```

# Restricting even more the access to a route

Every `Route` can add some access checkers to determine if it's accessible:


```php
use Lkt\Http\Routes\GetRoute;

GetRoute::onlyLoggedUsers('/blog', [YourController::class, 'handler'])
    ->addAccessChecker([YourLoginController::class, 'checkThisUserIsAdmin']);
```

The access checker handler will receive an array with all request variables in it and can have some different returns:

- Returning a `Response` instance will make `Router` dispatch this Response.
- `false`, which means user is not allowed and `Router` will dispatch a forbidden response
- `void`, the access checker doesn't limit the access to data and `Router` will work as usually

# Routes handlers

`Router` expects all routes returns a `Response` instance (see [lkt/http-response](https://github.com/lekrat/lkt-http-response)).

Each handler will receive as first argument an array with all request variables in it.

An example of YourController.php:
```php
use Lkt\Http\Response;

class YourController {
    public static function index(array $params = []): Response
    {
        return Response::ok(['message' => 'everything ok!']);
    }
}


// ...
// And that method would be mapped this way:
GetRoute::onlyLoggedUsers('/blog', [YourController::class, 'index']);
```

# Route resolving

In your `index.php`, simple add this:

```php
use Lkt\Http\Router;

Router::dispatch();
```

The `dispatch` method automatically detects if you're sending a JSON, HTML, a file, ... and disposes the right headers.

Also, the `dispatch` method will end the script execution.

If you have an existing routing engine and want to migrate step by step, you can get the response and works with it. 
Take a look to [lkt/http-response](https://github.com/lekrat/lkt-http-response) to know what can be done with the `Response` instance, for example, how to send HTTP headers.

```php
use Lkt\Http\Router;

Router::getResponse();
```

# Globally force a response

This feature is useful if you want to return a maintenance status, or block a given IP address, or any situation in which you need to send the same `Response` no matter which route is accessed.

```php

use Lkt\Http\Router;
use Lkt\Http\Response;

$response = Response::serviceUnavailable('Server under maintenance');

Router::forceGlobalResponse($response);
```

# Token detection

`Router` can give you the HTTP_TOKEN or a Bearer Token. Both methods return null if the token is undefined.

```php

use Lkt\Http\Router;

Router::getBearerToken();
Router::getTokenHeader();
```