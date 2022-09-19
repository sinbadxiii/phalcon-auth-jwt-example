<?php

/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/src');

use \Sinbadxiii\PhalconAuthJWT\Claims;

return new \Phalcon\Config\Config([
    'database' => [
        'adapter'     => 'Mysql',
        'host'        => 'localhost',
        'username'    => 'root',
        'password'    => '1993',
        'dbname'      => 'auth-test',
        'charset'     => 'utf8',
    ],
    'application' => [
        'appDir'         => APP_PATH . '/',
        'controllersDir' => APP_PATH . '/Controllers/',
        'modelsDir'      => APP_PATH . '/Models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'pluginsDir'     => APP_PATH . '/plugins/',
        'libraryDir'     => APP_PATH . '/library/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/',
    ],
    'auth' => [
        'defaults' => [
            'guard' => 'web',
            'passwords' => 'users',
        ],

        'guards' => [
            'web' => [
                'driver' => 'session',
                'provider' => 'admin_users',
            ],
            'api' => [
                'driver' => 'jwt',
                'provider' => 'users',
            ],
        ],
        'providers' => [
//            'admin_users' => [
//                'driver' => 'file',
//                'path'  => __DIR__ . "/users.json",
//                'passsword_crypted' => false
//            ],

            'admin_users' => [
                'driver' => 'model',
                'model'  => \App\Models\Users::class,
            ],
        ],
        'hash' => [
            'method' => 'sha1'
        ],
    ],
    'cache' => [
        'default' => 'redis',
        'options' => [
            'options' => [
                'defaultSerializer' => 'Json',
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
                'lifetime' => 3600,
            ],
        ]
    ],
    'jwt' => [

        'secret' => "ekCxR#7T*`m}F9]dn(?-~_xJeq'd7J>@",

        'keys' => [
            'public' => "env('JWT_PUBLIC_KEY')",
            'private' => "env('JWT_PRIVATE_KEY')",
            'passphrase' => "env('JWT_PASSPHRASE')",
        ],

        'ttl' => 30,

        'max_refresh_period' => null,

        'algo' => 'HS512',

        'required_claims' => [
            Claims\Issuer::NAME,
            Claims\IssuedAt::NAME,
            Claims\Expiration::NAME,
            Claims\Subject::NAME,
            Claims\JwtId::NAME,
        ],

        'lock_subject' => true,

        'leeway' => 0,

        'blacklist_enabled' => true,

        'blacklist_grace_period' => 0,

        'decrypt_cookies' => false,

        'providers' => [
//            'jwt' => \Sinbadxiii\PhalconAuthJWT\Providers\JWT\Lcobucci::class,
            'jwt' => \Sinbadxiii\PhalconAuthJWT\Providers\JWT\Phalcon::class,
            'storage' => \Sinbadxiii\PhalconAuthJWT\Providers\Storage\Cache::class,
        ],
        ]
]);
