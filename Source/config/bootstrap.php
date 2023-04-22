<?php

use Api\Core\Factories\ResponseFactory;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;

if(!defined('ROOT_PATH'))
    define('ROOT_PATH', dirname(__DIR__));

/* Folders */
if(!is_dir(ROOT_PATH.'/log'))
    mkdir(ROOT_PATH.'/log');

if(!is_dir(ROOT_PATH.'/cache'))
    mkdir(ROOT_PATH.'/cache');

if(!is_dir(ROOT_PATH.'/cache/templates'))
    mkdir(ROOT_PATH.'/cache/templates');

if(!is_dir(ROOT_PATH.'/cache/proxies'))
    mkdir(ROOT_PATH.'/cache/proxies');

if(!is_dir(ROOT_PATH.'/templates'))
    mkdir(ROOT_PATH.'/templates');

/* Container */
$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->enableCompilation(ROOT_PATH . '/cache');
$containerBuilder->writeProxiesToFile(true, ROOT_PATH . '/cache/proxies');

$containerBuilder->addDefinitions([
    App::class => DI\factory(function($container) {
        $app = AppFactory::createFromContainer($container);
        require ROOT_PATH.'/config/routes.php';
        return $app;
    }),
    LoggerInterface::class => DI\factory(function() {
        return new Logger('main', [new StreamHandler(ROOT_PATH.'/log/main.log', Logger::INFO)]);
    }),
    ValidatorInterface::class => DI\factory(function() {
        return (new ValidatorBuilder())->getValidator();
    }),
    Fenom::class => DI\factory(function ($container) {
        $fenom = Fenom::factory(ROOT_PATH.'/templates', ROOT_PATH.'/cache/templates',
            Fenom::AUTO_RELOAD | Fenom::AUTO_STRIP | Fenom::AUTO_ESCAPE
        );
        $fenom->addModifier('route', function($variable, $params = []) use ($container) {
            /* @var Slim\App $app */
            $app = $container->get(App::class);
            return $app->getRouteCollector()->getNamedRoute($variable)->getPattern();
        });
        return $fenom;
    }),
    'env' => function() {
        $dotenv = Dotenv::createImmutable(ROOT_PATH.'/.env');
        return $dotenv->safeLoad();
    },
]);

/* Env file */
$dotenv = Dotenv::createImmutable(ROOT_PATH);
$dotenv->safeLoad();

/* Container and Eloquent */
try {
    $container = $containerBuilder->build();
    $eloquent = new Capsule();
    $eloquent->addConnection([
        'driver' => $_ENV['DB_TYPE'],
        'host' => $_ENV['DB_HOST'],
        'database' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => !empty($_ENV['DB_PREFIX']) ? $_ENV['DB_PREFIX'] : '',
    ]);
    $eloquent->setAsGlobal();
    $eloquent->bootEloquent();

} catch (Throwable $error) {
    echo $error->getTraceAsString();
    return;
}

/* Slim App */
$app = $container->get(App::class);
$error_middleware = $app->addErrorMiddleware(true, true, true,
    $container->get(LoggerInterface::class));

$error_middleware->setErrorHandler(HttpNotFoundException::class, function () {
    return ResponseFactory::Make('
        <h1>404. Not found<h1>
        <h2>Slim Web Api</h2>
        <a href="/">Go to index page</a>
    ')->Custom(404, 'Not found');
});