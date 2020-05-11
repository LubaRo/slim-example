<?php

function getUsers()
{
    $filePath = ROOT_DIR . 'storage/users.json';
    $fileData = file_get_contents($filePath);
    $users = json_decode($fileData, true);

    return $users;
}

function saveUsers($data)
{
    $filePath = ROOT_DIR . 'storage/users.json';
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}
