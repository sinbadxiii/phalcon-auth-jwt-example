<?php
declare(strict_types=1);

use App\Middlewares\AuthMiddleware;
use App\Middlewares\MethodMiddleware;
use App\Middlewares\ResponseMiddleware;
use Phalcon\Di\FactoryDefault;
use Phalcon\Events\Manager;

error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/src');

require_once(BASE_PATH . '/vendor/autoload.php');

try {
    /**
     * The FactoryDefault Dependency Injector automatically registers
     * the services that provide a full stack framework.
     */
    $di = new FactoryDefault();

    /**
     * Read services
     */
    include APP_PATH . '/config/services.php';

    /**
     * Get config service for use in inline setup below
     */
    $config = $di->getConfig();

    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Micro($di);

    $eventsManager = new Manager();

    $eventsManager->attach('micro', new AuthMiddleware());
    $application->before(new AuthMiddleware());

    $eventsManager->attach('micro', new MethodMiddleware());
    $application->before(new MethodMiddleware());

    $eventsManager->attach('micro', new ResponseMiddleware());
    $application->after(new ResponseMiddleware());

    $application->post(
        "/auth/logout",
        function () {
            $this->auth->logout();

            return ['message' => 'Successfully logged out'];
        }
    );

    $application->post(
        "/auth/refresh",
        function () {
            $token = $this->auth->refresh();

            return $token->toResponse();
        }
    );

    $application->post(
        '/auth/login',
        function () {

            $credentials = [
                'email' => $this->request->getJsonRawBody()->email,
                'password' => $this->request->getJsonRawBody()->password
            ];

            $this->auth->claims(['aud' => [
                $this->request->getURI()
            ]]);

            if (! $token = $this->auth->attempt($credentials)) {
                return ['error' => 'Unauthorized'];
            }

            return $token->toResponse();
        }
    );

    $application->get(
        '/',
        function () {

            return [
                'message' => 'hello, my friend'
            ];
        }
    );

    $application->handle($_SERVER['REQUEST_URI']);
} catch (\Exception $e) {
    echo $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
