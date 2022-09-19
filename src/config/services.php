<?php
declare(strict_types=1);

use App\Security\Authenticate;
use App\Security\JWTAutheticate;
use Phalcon\Cache\Cache;
use Phalcon\Cache\AdapterFactory;
use Phalcon\Escaper;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Session\Adapter\Stream as SessionAdapter;
use Phalcon\Session\Manager as SessionManager;
use Phalcon\Storage\SerializerFactory;
use Phalcon\Url as UrlResolver;
use Sinbadxiii\PhalconAuth\Manager;


$di->setShared("dispatcher", function () use ($di) {
    $dispatcher = new Dispatcher();

    $eventsManager = $di->getShared('eventsManager');
    $eventsManager->attach('dispatch', new Authenticate());
    $dispatcher->setEventsManager($eventsManager);

    return $dispatcher;
});

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
});

/**
 * Setting up the view component
 */
$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines([
        '.volt' => function ($view) {
            $config = $this->getConfig();

            $volt = new VoltEngine($view, $this);

            $volt->setOptions([
                'path' => $config->application->cacheDir,
                'separator' => '_'
            ]);

            return $volt;
        },
        '.phtml' => PhpEngine::class

    ]);

    return $view;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    return new $class($params);
});


/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function () {
    $escaper = new Escaper();
    $flash = new Flash($escaper);
    $flash->setImplicitFlush(false);
    $flash->setCssClasses([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);

    return $flash;
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionManager();
    $files = new SessionAdapter();
    $session->setAdapter($files);
    $session->start();

    return $session;
});

$di->setShared("auth", function () {

    $security = $this->getSecurity();

    $adapter     = new \Sinbadxiii\PhalconAuth\Adapter\Model($security);
    $adapter->setModel(App\Models\User::class);
//    $adapter->setFileSource(__DIR__. "/users.json");
//    $adapter->setData(
//        [
//                    ["name" => "admin", "username" => "admin", 'password' => '1234', "email" => "1234@1234.ru"],
//                    ["name" => "admin1","username" => "admin1", 'password' => 'admin1', "email" => "admin1@admin.ru"],
//                ]
//    );
    $guard = new \Sinbadxiii\PhalconAuthJWT\Guards\JWTGuard(
        "web",
        $adapter
    );


    $manager = new Manager();
    $manager->addGuard("jwt", $guard);
    $manager->setDefaultGuard($guard);

    return $manager;
});

//$securityProvider = new \Sinbadxiii\PhalconFoundationAuth\Providers\SecurityProvider();
//$securityProvider->register($di);

$jwt = new \Sinbadxiii\PhalconAuthJWT\Providers\JWTServiceProvider();
$jwt->register($di);

//$cookieProvider = new \Sinbadxiii\PhalconFoundationAuth\Providers\CookiesProvider();
//$cookieProvider->register($di);

$di->setShared("cache", function () {

    $configCache = $this->getConfig()->path("cache");

    $serializerFactory = new SerializerFactory();
    $adapterFactory    = new AdapterFactory($serializerFactory);

    $adapter           = $adapterFactory->newInstance(
        $configCache->default, $configCache->options->toArray(),
    );

    return new Cache($adapter);
});
