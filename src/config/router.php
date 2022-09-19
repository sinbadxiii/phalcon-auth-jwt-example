<?php

use Phalcon\Mvc\Router\Group;
use Sinbadxiii\PhalconFoundationAuth\Routes as AuthRoutes;

$router = $di->getRouter(false);

$router->setDefaultNamespace("App\Controllers");

$router->addGet('/', 'Index::index')->setName("main");
$router->addGet('/admin', 'Admin::index')->setName("admin");

//$router->mount(AuthRoutes::routes());
//$router->mount(AuthRoutes::jwt());

$routerJwt = new Group(
    [
        'namespace' => 'App\Controllers\Auth'
    ]
);
$routerJwt->setPrefix("/auth");

$routerJwt->addPost("/login", 'Login::login')->setName("login");
$routerJwt->addPost("/logout", 'Login::logout')->setName("logout");
$routerJwt->addPost("/refresh", 'Login::refresh')->setName("refresh");
$routerJwt->addPost("/me", 'Login::me')->setName("me");

$router->mount($routerJwt);

$router->handle($_SERVER['REQUEST_URI']);
