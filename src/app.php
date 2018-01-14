<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());

// RAPORTOWANIE BLEDÓW DO SENTRY

$app->register(new Moriony\Silex\Provider\SentryServiceProvider, array(
    'sentry.options' => array(
        'dsn' => 'https://818d00a586b74d758a01a267c9535cc0:0d231d6ef60a4d2a8a9b6201882cb96c@sentry.io/271425',
    )
));

$app->error(function (\Exception $e, $code) use($app) {
    // ...
    $client = $app['sentry'];
    $client->captureException($e);
    // ...
});

$errorHandler = $app['sentry.error_handler'];
$errorHandler->registerExceptionHandler();
$errorHandler->registerErrorHandler();
$errorHandler->registerShutdownFunction();

// ROZSZERZENIE STYLI
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});

return $app;
