<?php

namespace App\Security\Access;

use Sinbadxiii\PhalconAuth\Access\AbstractAccess;

class Jwt extends AbstractAccess
{
    /**
     * @return bool
     */
    public function allowedIf(): bool
    {
        return $this->auth->parseToken()->check();
    }
}