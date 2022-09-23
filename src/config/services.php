<?php
declare(strict_types=1);

use App\Security\Authenticate;
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
use Sinbadxiii\PhalconAuthJWT\Blacklist;
use Sinbadxiii\PhalconAuthJWT\Builder;
use Sinbadxiii\PhalconAuthJWT\Http\Parser\Chains\AuthHeaders;
use Sinbadxiii\PhalconAuthJWT\Http\Parser\Chains\InputSource;
use Sinbadxiii\PhalconAuthJWT\Http\Parser\Chains\QueryString;
use Sinbadxiii\PhalconAuthJWT\Http\Parser\Parser;
use Sinbadxiii\PhalconAuthJWT\JWT;
use Sinbadxiii\PhalconAuthJWT\Manager as JWTManager;


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

$di->setShared("jwt", function () {

    $configJwt = $this->getConfig()->path('jwt');

    $providerJwt = $configJwt->providers->jwt;

    $builder = new Builder();

    $builder->lockSubject($configJwt->lock_subject)
        ->setTTL($configJwt->ttl)
        ->setRequiredClaims($configJwt->required_claims->toArray())
        ->setLeeway($configJwt->leeway)
        ->setMaxRefreshPeriod($configJwt->max_refresh_period);

    $parser = new Parser($this->getRequest(), [
        new AuthHeaders,
        new QueryString,
        new InputSource,
    ]);

    $providerStorage = $configJwt->providers->storage;

    $blacklist = new Blacklist(new $providerStorage($this->getCache()));

    $blacklist->setGracePeriod($configJwt->blacklist_grace_period);

    $manager = new JWTManager(new $providerJwt(
        $configJwt->secret,
        $configJwt->algo,
        $configJwt->keys->toArray()
    ), $blacklist, $builder);

    $manager->setBlacklistEnabled((bool) $configJwt->blacklist_enabled);

    return new JWT($builder, $manager, $parser);
});


$di->setShared("auth", function () {

    $security = $this->getSecurity();

    $adapter     = new \Sinbadxiii\PhalconAuth\Adapter\Model($security);
    $adapter->setModel(App\Models\User::class);

    $guard = new \Sinbadxiii\PhalconAuthJWT\Guard\JWTGuard(
        $adapter,
        $this->getJwt(),
        $this->getRequest(),
        $this->getEventsManager(),
    );

    $manager = new Manager();
    $manager->addGuard("jwt", $guard);
    $manager->setDefaultGuard($guard);

    $manager->setAccess(new \App\Security\Access\Jwt());
    $manager->except("/auth/login");

    return $manager;
});


$di->setShared("cache", function () {

    $configCache = $this->getConfig()->path("cache");

    $serializerFactory = new SerializerFactory();
    $adapterFactory    = new AdapterFactory($serializerFactory);

    $adapter           = $adapterFactory->newInstance(
        $configCache->default, $configCache->options->toArray(),
    );

    return new Cache($adapter);
});
