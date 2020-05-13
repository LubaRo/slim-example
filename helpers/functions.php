<?php

use App\Validator;

function getUsers()
{
    $filePath = ROOT_DIR . 'storage/data/users.json';
    $fileData = file_get_contents($filePath);
    $users = json_decode($fileData, true);

    return $users;
}

function saveUsers($data)
{
    $filePath = ROOT_DIR . 'storage/data/users.json';
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

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

function validateUser($userData)
{
    $validator = new Validator();
    return $validator->validate($userData);
}

function createUser($userData)
{
    $allUsers = getUsers();
    $lastUser = collect($allUsers)->last();
    $userData['id'] = $lastUser ? $lastUser['id'] + 1 : 1;

    $allUsers[] = $userData;
    saveUsers($allUsers);
    return true;
}

function getUser($id)
{
    $users = getUsers();
    return collect($users)->firstWhere('id', $id);
}
