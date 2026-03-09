<?php

/** @var \PHPFramework\Application $app */

use \App\Controllers\HomeController;

const MIDDLEWARE = [
    'auth' => \PHPFramework\Middleware\Auth::class,
    'guest' => \PHPFramework\Middleware\Guest::class,
    'admin' => \PHPFramework\Middleware\Admin::class,
];

//Home Route
$app->router->get('/', [HomeController::class, 'index']);

