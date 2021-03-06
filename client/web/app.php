<?php

use Symfony\Component\HttpFoundation\Request;

function empty_shutdown() {
    if (!headers_sent()) {
        http_response_code(500);
        echo file_get_contents(__DIR__ . '/error.html');
    }
}

register_shutdown_function('empty_shutdown');

$loader = require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel(getenv('SYMFONY_ENV'), false);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
