<?php

use App\Validator;
use Slim\Psr7\Cookies;

function getAllCompanies()
{
    return App\Generator::generateCompanies(100);
}
function getCompanies($offset, $amount, $page = 1)
{
    $fullList = getAllCompanies();
    $companies = array_slice($fullList, $offset, $amount);

    $paging = [
        'total' => ceil(sizeof($fullList) / $amount),
        'current' => $page
    ];
    return [$companies, $paging];
}

function getCompany($id)
{
    $companies = getAllCompanies();
    return collect($companies)->firstWhere('id', $id);
}
function findCompany($search)
{
    if (!$search) {
        return [];
    }
    $companies = getAllCompanies();

    $needle = strtolower($search);
    $foundCompanies = array_filter($companies, function ($company) use ($needle) {
        $foundPosition = strpos(strtolower($company['name']), $needle);
        return $foundPosition === false ? false : true;
    });

    return $foundCompanies;
}

function getUsers($request)
{
    $cookies = Cookies::parseHeader($request->getHeader('Cookie'));
    $usersCookie = $cookies['users'] ?? '';
    $usersDecoded = json_decode($usersCookie, true);

    return $usersDecoded;
}

function validateUser($userData)
{
    $validator = new Validator();
    return $validator->validate($userData);
}

function getUser($request, $id)
{
    $users = getUsers($request);
    return collect($users)->firstWhere('id', $id);
}


function saveUsers($data)
{
    $filePath = ROOT_DIR . 'storage/data/users.json';
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

function addNewUser($userData, $request)
{
    $allUsers = getUsers($request);
    $lastUser = collect($allUsers)->last();

    $userData['id'] = $lastUser ? $lastUser['id'] + 1 : 1;
    $allUsers[] = $userData;

    return $allUsers;
}

function updateUser($data, $request)
{
    $id = $data['id'];

    $newUsers = array_map(function($elem) use ($id, $data) {
        if ($elem['id'] == $id) {
            $elem = array_merge($elem, $data);
        }
        return $elem;
    }, getUsers($request));

    return $newUsers;
}

function deleteUser($id, $request)
{
    $newUsers = array_filter(getUsers($request), fn($elem) => $elem['id'] != $id);
    return $newUsers;
}
