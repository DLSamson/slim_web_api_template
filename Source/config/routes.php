<?php

/* @var \Slim\App $app */

use Api\Controllers\Api\EchoController;
use Api\Controllers\Pages\IndexController;

/* Pages */
$app->get('/', [IndexController::class, 'handle'])->setName('pages.index');
$app->get('/info', function($req, $res) { phpinfo(); return $res->withStatus(200);});

/* Api */
$app->get('/echo/{value}', [EchoController::class, 'handle']);