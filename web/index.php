<?php

ini_set('display_errors', 0);

require_once __DIR__.'/../vendor/autoload.php';
require __DIR__.'/php/functions.php';

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/dev.php';
require __DIR__.'/../src/controllers.php';
$app['monolog']->debug('LOGTEST');
$app->run();
