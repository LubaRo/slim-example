<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
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
        'auto_reload' => true
    ];
    return Twig::create(TEMPLATES_DIR_PATH, $params);
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(TwigMiddleware::createFromContainer($app));
$app->add(MethodOverrideMiddleware::class);

$routeParser = $app->getRouteCollector()->getRouteParser();

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

$app->get('/companies', function (Request $request, Response $response) {
    $params = $request->getQueryParams();
    $page = $params['page'] ?? 1;
    $per = $params['per'] ?? 10;
    $offset = ($page - 1) * $per;

    list($companies, $paging) = getCompanies($offset, $per, $page);
    if (!$companies) {
        $newResponse = $response->withStatus(NOT_FOUND);
        return $this->get('view')->render($newResponse, "not_found.twig", ['title' => 'Not found']);
    }
    $data = [
        'title' => 'COMPANIES',
        'companies' => $companies,
        'paging' => $paging
    ];

    return $this->get('view')->render($response, "companies/companies_list.twig", $data);
})->setName('companies');

$app->get('/company/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $companyData = getCompany($id);

    if (!$companyData) {
        $newResponse = $response->withStatus(NOT_FOUND);
        return $this->get('view')->render($newResponse, "not_found.twig", ['title' => 'Not found']);
    }
    $data = [
        'title' => "Company: {$companyData['name']}",
        'company' => $companyData
    ];
    return $this->get('view')->render($response, "companies/company.twig", $data);
})->setName('company');

/* Hack - need to fix */
$app->get('/images/{img}', function (Request $request, Response $response, array $args) {
    $imageName = $args['img'];
    $imagePath = IMAGES_DIR .  $imageName;

    $image = @file_get_contents($imagePath);
    if ($image === false) {
        $response->getBody()->write("Could not find '$imageName'.");
        return $response->withStatus(404);
    };
    $response->getBody()->write($image);
    return $response->withHeader('Content-Type', 'image/jpeg');
})->setName('images');

/* Hack - need to fix */
$app->get('/styles/{file}', function (Request $request, Response $response, array $args) {
    $fileName = $args['file'];
    $filePath = ROOT_DIR . 'public/styles/' . $fileName;

    $content = @file_get_contents($filePath);
    if ($content === false) {
        $response->getBody()->write("Could not open '$fileName'.");
        return $response->withStatus(404);
    };
    $response->getBody()->write($content);
    return $response->withHeader('Content-Type', 'text/css');
})->setName('styles');

$app->get('/users', function (Request $request, Response $response, $args) {
    $data = [
        'title' => 'Users',
        'users' => getUsers(),
        'messages' => $this->get('flash')->getMessages()
    ];

    return $this->get('view')->render($response, "users/list.twig", $data);
})->setName('users');

$app->get('/user/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $userData = getUser($id);
    if (!$userData) {
        $newResponse = $response->withStatus(NOT_FOUND);
        return $this->get('view')->render($newResponse, "not_found.twig", ['title' => 'Not found']);
    }
    $data = [
        'title' => 'User profile',
        'user' => $userData
    ];

    return $this->get('view')->render($response, "users/show.twig", $data);
})->setName('user');

$app->get('/users/{id}/edit', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $userData = getUser($id);
    if (!$userData) {
        $newResponse = $response->withStatus(NOT_FOUND);
        return $this->get('view')->render($newResponse, "not_found.twig", ['title' => 'Not found']);
    }
    $data = [
        'title' => 'User profile',
        'user' => $userData
    ];

    return $this->get('view')->render($response, "users/edit.twig", $data);
})->setName('edit_user');

$app->get('/users/new', function (Request $request, Response $response, $args) {
    $data = [
        'title' => 'Create user'
    ];
    return $this->get('view')->render($response, "users/new.twig", $data);
})->setName('new_user');

$app->post('/users', function (Request $request, Response $response) use ($routeParser) {
    $userData = $request->getParsedBody()['user'];
    $errors = validateUser($userData);

    if ($errors) {
        $data = [
            'title' => 'Create user',
            'errors' => $errors
        ];
        return $this->get('view')->render($response, "users/new.twig", $data);
    }

    createUser($userData);
    $this->get('flash')->addMessage('success', 'New user was added!');

    return $response
        ->withHeader('Location', $routeParser->urlFor('users'))
        ->withStatus(302);
});

$app->patch('/users', function (Request $request, Response $response) use ($routeParser) {
    $userData = $request->getParsedBody()['user'];
    $errors = validateUser($userData);

    if ($errors) {
        $data = [
            'user' => $userData,
            'title' => 'Updating user',
            'errors' => $errors
        ];
        return $this->get('view')->render($response->withStatus(422), "users/edit.twig", $data);
    }

    updateUser($userData);
    $this->get('flash')->addMessage('success', 'User was updates!');

    return $response
        ->withHeader('Location', $routeParser->urlFor('users'))
        ->withStatus(302);
});

$app->delete('/user/{id}', function (Request $request, Response $response, $args) use ($routeParser) {
    $id = $args['id'];
    deleteUser($id);
    $this->get('flash')->addMessage('success', 'User was deleted!');

    return $response
        ->withHeader('Location', $routeParser->urlFor('users'))
        ->withStatus(302);
})->setName('delete_user');

$app->get('/', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    $search = $params['search'] ?? '';
    $foundData = findCompany($search);

    $templateData = [
        'title' => 'HOME',
        'search' => htmlspecialchars($search),
        'foundData' => $foundData
    ];
    return $this->get('view')->render($response, "index.twig", $templateData);
});

// Run app
$app->run();
