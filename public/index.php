<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
use Slim\Views\PhpRenderer;
use DI\Container;

const ROOT_DIR = __DIR__ . '/../';
const TEMPLATES_DIR_PATH = ROOT_DIR . 'templates';
const IMAGES_DIR = ROOT_DIR . 'images/';
const NOT_FOUND = 404;

// Create Container using PHP-DI
$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$container->set('renderService', function () {
    return new PhpRenderer(TEMPLATES_DIR_PATH, ['title' => 'Default title']);
});

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

// Define app routes
$app->get('/', function (Request $request, Response $response, $args) {
    $links = [
        ['name' => 'Users', 'path' => '/users'],
        ['name' => 'Companies list', 'path' => '/companies']
    ];
    $renderer = $this->get('renderService');
    $renderer->addAttribute('title', 'HOME');
    $renderer->setLayout("layout.phtml");

    return $renderer->render($response, "index.phtml", ['links' => $links]);
});

$app->get('/users', function (Request $request, Response $response, $args) {
    $renderer = $this->get('renderService');
    $renderer->addAttribute('title', 'Users');
    $renderer->setLayout("layout.phtml");
    $users = App\Generator::generateUsers(10);

    return $renderer->render($response, "users_list.phtml", ['users' => $users]);
});

$app->post('/users', function (Request $request, Response $response) {
    $response->getBody()->write('POST /users');
    return $response;
});

$app->get('/companies', function (Request $request, Response $response) {
    $companiesFullList = App\Generator::generateCompanies(100);
    $params = $request->getQueryParams();

    $page = $params['page'] ?? 1;
    $per = $params['per'] ?? 10;
    $offset = ($page - 1) * $per;

    $companies = array_slice($companiesFullList, $offset, $per);
    $renderer = $this->get('renderService');
    if (!$companies) {
        $renderer->addAttribute('title', 'Not found');
        $newResponse = $response->withStatus(NOT_FOUND);
        return $renderer->render($newResponse, "not_found.phtml");
    }
    $renderer->addAttribute('title', 'Companies');
    $renderer->setLayout("layout.phtml");

    $paging = [
        'total' => ceil(sizeof($companiesFullList) / $per),
        'current' => $page
    ];

    $templateData = [
        'companies' => $companies,
        'paging' => $paging
    ];

    return $renderer->render($response, "companies_list.phtml", $templateData);
});

$app->get('/company/{id}', function (Request $request, Response $response, array $args) {
    $companyId = $args['id'];
    $companiesList = App\Generator::generateCompanies(100);
    $companyData = collect($companiesList)->firstWhere('id', $companyId);
    $renderer = $this->get('renderService');

    if (!$companyData) {
        $renderer->addAttribute('title', 'Not found');
        $newResponse = $response->withStatus(NOT_FOUND);
        return $renderer->render($newResponse, "not_found.phtml");
    }

    $renderer->addAttribute('title', "Company: {$companyData['name']}");
    $renderer->setLayout("layout.phtml");

    return $renderer->render($response, "company.phtml", ['company' => $companyData]);
});

$app->get('/images/{img}', function (Request $request, Response $response, array $args) {
    $imageName = $args['img'];
    $imagePath = IMAGES_DIR .  $imageName;

    $image = @file_get_contents($imagePath);
    if ($image === false) {;
        $response->getBody()->write("Could not find '$imageName'.");
        return $response->withStatus(404);
    };
    $response->getBody()->write($image);
    return $response->withHeader('Content-Type', 'image/jpeg');
});

// Run app
$app->run();
