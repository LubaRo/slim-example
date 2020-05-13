<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Validator;

$container = new Container();
AppFactory::setContainer($container);

$container->set('view', function() {
    $params = [
        'cache' => CACHE_DIR . 'twig/',
        'auto_reload' => true
    ];
    return Twig::create(TEMPLATES_DIR_PATH, $params);
});

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(TwigMiddleware::createFromContainer($app));

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
$routeParser = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response, $args) use ($routeParser) {
    $links = [
        ['name' => 'Users', 'path' => $routeParser->urlFor('users')],
        ['name' => 'Companies list', 'path' => $routeParser->urlFor('companies')]
    ];
    $renderer = $this->get('view');

    $params = $request->getQueryParams();
    $search = $params['search'] ?? '';

    $searchData = ['test', 'goal', 'php', 'slim', 'OOP', 'manifest'];
    $foundData = [];
    if ($search) {
        $foundData = array_filter($searchData, function ($elem) use ($search) {
            return strpos($elem, $search);
        });
    }
    $templateData = [
        'title' => 'HOME',
        'links' => $links,
        'search' => htmlspecialchars($search),
        'foundData' => $foundData
    ];

    return $renderer->render($response, "index.twig", $templateData);
});

$app->get('/users', function (Request $request, Response $response, $args) {
    $renderer = $this->get('view');
    $users = getUsers();
    $data = ['title' => 'Users', 'users' => $users];

    return $renderer->render($response, "users.twig", $data);
})->setName('users');;

$app->get('/users/new', function (Request $request, Response $response, $args) {
    $renderer = $this->get('view');
    $data = ['title' => 'Create user'];

    return $renderer->render($response, "users_new.twig", $data);
})->setName('newUser');

$app->post('/users', function (Request $request, Response $response) use ($routeParser) {
    $validator = new Validator;
    $renderer = $this->get('view');
    $request = $request->getParsedBody();
    $userData = $request['user'];
    $errors = $validator->validate($userData);

    if ($errors) {
        $data = [
            'title' => 'Create user',
            'errors' => $errors
        ];
        return $renderer->render($response, "users_new.twig", $data);
    }

    $allUsers = getUsers();
    $lastUser = collect($allUsers)->last();
    $userData['id'] = $lastUser ? $lastUser['id'] + 1 : 1;

    $allUsers[] = $userData;
    saveUsers($allUsers);

    return $response
        ->withHeader('Location',$routeParser->urlFor('users'))
        ->withStatus(302);
});

$app->get('/companies', function (Request $request, Response $response) {
    $companiesFullList = App\Generator::generateCompanies(100);
    $params = $request->getQueryParams();

    $page = $params['page'] ?? 1;
    $per = $params['per'] ?? 10;
    $offset = ($page - 1) * $per;

    $companies = array_slice($companiesFullList, $offset, $per);
    $renderer = $this->get('view');
    if (!$companies) {
        $renderer->addAttribute('title', 'Not found');
        $newResponse = $response->withStatus(NOT_FOUND);
        return $renderer->render($newResponse, "not_found.phtml");
    }

    $paging = [
        'total' => ceil(sizeof($companiesFullList) / $per),
        'current' => $page
    ];

    $data = [
        'title' => 'COMPANIES',
        'companies' => $companies,
        'paging' => $paging
    ];

    return $renderer->render($response, "companies_list.twig", $data);
})->setName('companies');

$app->get('/company/{id}', function (Request $request, Response $response, array $args) {
    $companyId = $args['id'];
    $companiesList = App\Generator::generateCompanies(100);
    $companyData = collect($companiesList)->firstWhere('id', $companyId);
    $renderer = $this->get('view');

    if (!$companyData) {
        $newResponse = $response->withStatus(NOT_FOUND);
        return $renderer->render($newResponse, "not_found.twig", ['title' => 'Not found']);
    }

    $data = [
        'title' => "Company: {$companyData['name']}",
        'company' => $companyData
    ];
    return $renderer->render($response, "company.twig", $data);
})->setName('company');

/* Hack - need to fix */
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
})->setName('images');
/* Hack - need to fix */
$app->get('/styles/{file}', function (Request $request, Response $response, array $args) {
    $fileName = $args['file'];
    $filePath = ROOT_DIR . 'public/styles/' . $fileName;

    $content = @file_get_contents($filePath);
    if ($content === false) {;
        $response->getBody()->write("Could not open '$fileName'.");
        return $response->withStatus(404);
    };
    $response->getBody()->write($content);
    return $response->withHeader('Content-Type', 'text/css');
})->setName('styles');

// Run app
$app->run();
