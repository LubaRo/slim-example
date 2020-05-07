<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

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
    $body = <<<EOT
    <h1>Slim says you HELLO!</h1>
    <p>You can follow by these links:</p>
    <ul>
        <li><a href="/users">Users</a></li>
        <li><a href="/companies">Companies</a></li>
        <li><a href="/courses/5">5 course</a></li>
    </ul>
EOT;
    $response->getBody()->write($body);
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
    $per = $params['per'] ?? 10;
    $offset = ($page - 1) * $per;

    $requestedData = array_slice($companiesData, $offset, $per);
    $data = array_reduce($requestedData, function ($acc, $company) {
        $acc[] = "<a href=\"\\company\\{$company['id']}\">{$company['name']}</a>";
        return $acc;
    }, []);

    $response->getBody()->write(implode("<br>", $data));
    return $response;
});

$app->get('/company/{id}', function (Request $request, Response $response, array $args) {
    $companyId = $args['id'];
    $companiesList = App\Generator::generateCompanies(100);
    $companyData = collect($companiesList)->firstWhere('id', $companyId);

    if (!$companyData) {
        $response->getBody()->write("Page not found.. :(");
        return $response->withStatus(404);
    }
    $data = '';
    foreach ($companyData as $key => $value) {
        $data .= "<p><b>$key:</b> $value</p>";
    }
    $response->getBody()->write($data);
    return $response;
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Course id: {$id}");
    return $response;
});

// Run app
$app->run();
