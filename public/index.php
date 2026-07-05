<?php

declare(strict_types=1);

use Core\Env;
use Core\ErrorHandler;
use Core\Router;
use Core\Session;

require __DIR__ . '/../vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');
ErrorHandler::register(Env::bool('APP_DEBUG', false));

/*
|--------------------------------------------------------------------------
| Start session
|--------------------------------------------------------------------------
*/
Session::start();

/*
|--------------------------------------------------------------------------
| Bootstrap application + get DI container
|--------------------------------------------------------------------------
*/
$container = require __DIR__ . '/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/
$router = new Router();

/*
|--------------------------------------------------------------------------
| Register routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

/*
|--------------------------------------------------------------------------
| Dispatch request
|--------------------------------------------------------------------------
*/
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

$router->dispatch(
    $method,
    $uri,
    $container
);
