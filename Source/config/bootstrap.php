<?php

use Api\Core\Factories\ResponseFactory;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;

/* Folders */
if(!is_dir(ROOT_PATH.'/log'))
    mkdir(ROOT_PATH.'/log');

if(!is_dir(ROOT_PATH.'/cache/templates'))
    mkdir(ROOT_PATH.'/cache/templates');

if(!is_dir(ROOT_PATH.'/templates'))
    mkdir(ROOT_PATH.'/templates');

/* Container */
$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions([
    LoggerInterface::class => DI\factory(function() {
        return new Logger('main', [new StreamHandler(ROOT_PATH.'/log/main.log', Logger::INFO)]);
    }),
    ValidatorInterface::class => DI\factory(function() {
        return (new ValidatorBuilder())->getValidator();
    }),
    Fenom::class => DI\factory(function () {
        return Fenom::factory(ROOT_PATH.'/templates', ROOT_PATH.'/cache/templates',
            Fenom::AUTO_RELOAD | Fenom::AUTO_STRIP | Fenom::AUTO_ESCAPE
        );
    }),
    'env' => function() {
        $dotenv = Dotenv::createImmutable(ROOT_PATH.'/.env');
        return $dotenv->safeLoad();
    },
]);

/* Container and Eloquent */
try {
    $container = $containerBuilder->build();
    $eloquent = new Capsule();
    $env = $container->get('env');
    $eloquent->addConnection([
        'driver' => 'mysql',
        'host' => $env['DB_HOST'],
        'database' => $env['DB_NAME'],
        'username' => $env['DB_USERNAME'],
        'password' => $env['DB_PASSWORD'],
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => $env['DB_PREFIX'] ?: '',
    ]);
    $eloquent->setAsGlobal();

} catch (Throwable $error) {
    echo $error->getTraceAsString();
}

/* Slim App */
AppFactory::setContainer($container);
$app = AppFactory::create();
$error_middleware = $app->addErrorMiddleware(true, true, true,
    $container->get(LoggerInterface::class));

$error_middleware->setErrorHandler(HttpNotFoundException::class, function () {
    return ResponseFactory::Make('<h1>404. Not found<h1><h2>Slim Web Api</h2>')
        ->Custom(404, 'Not found');
});