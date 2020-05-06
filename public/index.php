<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

$app = AppFactory::create();

// Redirect and rewrite all URLs that end in a '/' to the non-trailing '/' equivalent
$app->add(function (Request $request, RequestHandler $handler) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if ($path != '/' && substr($path, -1) == '/') {
        // recursively remove slashes when its more than 1 slash
        $path = rtrim($path, '/');

        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath($path);
        
        if ($request->getMethod() == 'GET') {
            $response = new Response();
            return $response
                ->withHeader('Location', (string) $uri)
                ->withStatus(301);
        } else {
            $request = $request->withUri($uri);
        }
    }

    return $handler->handle($request);
});

// Add Error Handling Middleware
$app->addErrorMiddleware(false, true, true);


// Define app routes
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello from Slim!");
    return $response;
});

$app->get('/users', function (Request $request, Response $response, $args) {
    $response->getBody()->write('GET /users');
    return $response;
});

$app->post('/users', function (Request $request, Response $response) {
    $response->getBody()->write('POST /users');
    return $response;
});


// Run app
$app->run();
