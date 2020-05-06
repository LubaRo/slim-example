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

// Define app routes
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Slim says you HELLO!");
    return $response;
});

$app->get('/users', function (Request $request, Response $response, $args) {
    $users = App\Generator::generateUsers(10);
    $userRows = array_map(function ($user) {
        return "<tr>"
            . "<td>{$user['id']}</td>"
            . "<td>{$user['name']}</td>"
            . "<td>{$user['phone']}</td>"
            . "<td>{$user['address']}</td>"
            . "</tr>";
    }, $users);
    $userTableData = implode("\n", $userRows);
    $tableHeader = <<<EOT
    <tr>
        <th>ID</th>
        <th>NAME</th>
        <th>PHONE</th>
        <th>ADDRESS</th>
    </tr>
EOT;
    $response->getBody()->write("<table>{$tableHeader}{$userTableData}</table>");
    return $response;
});

$app->post('/users', function (Request $request, Response $response) {
    $response->getBody()->write('POST /users');
    return $response;
});

$app->get('/companies', function (Request $request, Response $response) {
    $companiesData = App\Generator::generateCompanies(100);
    $params = $request->getQueryParams();

    $page = $params['page'] ?? 1;
    $per = $params['per'] ?? 5;
    $offset = ($page - 1) * $per;

    $requestedData = array_slice($companiesData, $offset, $per);
    $data = array_reduce($requestedData, function ($acc, $company) {
        $acc[] = json_encode($company, JSON_PRETTY_PRINT);
        return $acc;
    }, []);

    $response->getBody()->write(implode("<br>", $data));
    return $response;
});

// Run app
$app->run();
