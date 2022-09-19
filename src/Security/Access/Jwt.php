<?php

namespace App\Security\Access;

use Sinbadxiii\PhalconAuth\Access\AbstractAccess;
use Sinbadxiii\PhalconAuth\Exception;

class Jwt extends AbstractAccess
{
    /**
     * @return bool
     */
    public function allowedIf(): bool
    {
        return $this->auth->parser()->hasToken();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function redirectTo()
    {
        throw new Exception("JWT: Invalid Token.");
    }
}