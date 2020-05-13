<?php

define('ROOT_DIR', __DIR__ . '/../');
define('TEMPLATES_DIR_PATH', ROOT_DIR . 'templates');
define('CACHE_DIR', ROOT_DIR . 'storage/cache/');
define('IMAGES_DIR', ROOT_DIR . 'public/images/');
define('NOT_FOUND', 404);

function prettyPrint($data)
{
    print("<pre>" . print_r($data, true) . "</pre>");
}
