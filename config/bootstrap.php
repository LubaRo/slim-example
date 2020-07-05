<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;

session_start();

$container = new Container();
AppFactory::setContainer($container);

$container->set('view', function () {
    $params = [
        'cache' => CACHE_DIR . 'twig/',
        'auto_reload' => true,
        'debug' => true
    ];
    $twig = Twig::create(TEMPLATES_DIR_PATH, $params);

    //add the debug extention for ability to dump variables
    $twig->addExtension(new \Twig\Extension\DebugExtension());

    return $twig;
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(TwigMiddleware::createFromContainer($app));
$app->add(MethodOverrideMiddleware::class);

$routesRegistrator = require __DIR__ . '/routes.php';
$routesRegistrator($app);

return $app;
