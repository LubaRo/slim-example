<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

$routesRegistrator = function (App $app) {
    $routeParser = $app->getRouteCollector()->getRouteParser();

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
        $users = getUsers($request);
        $data = [
            'title' => 'Users',
            'users' => $users,
            'messages' => $this->get('flash')->getMessages()
        ];
    
        return $this->get('view')->render($response, "users/list.twig", $data);
    })->setName('users');
    
    $app->get('/user/{id}', function (Request $request, Response $response, $args) {
        $id = $args['id'];
        $userData = getUser($request, $id);
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
        $userData = getUser($request, $id);
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
            'title' => 'Create user',
            'user' => []
        ];
        return $this->get('view')->render($response, "users/new.twig", $data);
    })->setName('new_user');
    
    $app->post('/users', function (Request $request, Response $response) use ($routeParser) {
        $userData = $request->getParsedBody()['user'];
        $errors = validateUser($userData);
    
        if ($errors) {
            $data = [
                'title' => 'Create user',
                'user' => $userData,
                'errors' => $errors
            ];
            return $this->get('view')->render($response, "users/new.twig", $data);
        }
    
        $newUserList = addNewUser($userData, $request);
        $usersEncoded = json_encode($newUserList);
    
        $this->get('flash')->addMessage('success', 'New user was added!');
    
        return $response
            ->withHeader('Location', $routeParser->urlFor('users'))
            ->withHeader('Set-Cookie', "users=$usersEncoded; HttpOnly; Path=/; Max-Age=432000")
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
    
        $newUserList = updateUser($userData, $request);
        $usersEncoded = json_encode($newUserList);
    
        $this->get('flash')->addMessage('success', 'User was updates!');
    
        return $response
            ->withHeader('Location', $routeParser->urlFor('users'))
            ->withHeader('Set-Cookie', "users=$usersEncoded; HttpOnly; Path=/; Max-Age=432000")
            ->withStatus(302);
    });
    
    $app->delete('/user/{id}', function (Request $request, Response $response, $args) use ($routeParser) {
        $userId = $args['id'];
        $newUserList = deleteUser($userId, $request);
    
        $usersEncoded = json_encode($newUserList);
        $this->get('flash')->addMessage('success', 'User was deleted!');
    
        return $response
            ->withHeader('Location', $routeParser->urlFor('users'))
            ->withHeader('Set-Cookie', "users=$usersEncoded; HttpOnly; Path=/; Max-Age=432000")
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
    })->setName('home');
};

return $routesRegistrator;
