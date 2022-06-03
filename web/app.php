<?php

use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

$environment = getenv('ENVIRONMENT');
if ($environment === 'local') {
    opcache_reset();
    apcu_clear_cache();
}

$enableDebug = $environment !== 'prod';
if ($enableDebug) {
    Debug::enable();
}

$kernel = new AppKernel($environment, $enableDebug);
if (in_array($environment, ['prod',  'demo', 'stage'])) {
    $kernel = new AppCache($kernel);
}

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
