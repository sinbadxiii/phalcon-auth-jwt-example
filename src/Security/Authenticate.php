<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Access\Auth;
use App\Security\Access\Jwt;
use App\Security\Access\Guest;
use Sinbadxiii\PhalconAuth\Access\Authenticate as AuthMiddleware;

/**
 * Class Authenticate
 * @package App\Security
 */
class Authenticate extends AuthMiddleware
{
    protected array $accessList = [
        'auth'  => Auth::class,
        'jwt'   => Jwt::class,
        'guest' => Guest::class
    ];
}
