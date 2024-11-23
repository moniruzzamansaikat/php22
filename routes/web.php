<?php

use Php22\Controllers\ProductController;
use Php22\Router;
use Php22\Controllers\UserController;

$router = new Router();

// Example routes
$router->addRoute('GET', '/products', [ProductController::class, 'index']);
$router->addRoute('GET', '/users', [UserController::class, 'index']);
$router->addRoute('GET', '/users/create', [UserController::class, 'create']);
$router->addRoute('POST', '/users', [UserController::class, 'store']);

// Dispatch the router
$router->dispatch();
