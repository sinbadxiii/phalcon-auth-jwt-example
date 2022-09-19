<?php

declare(strict_types=1);

namespace App\Controllers;

use ControllerBase;

class AdminController extends ControllerBase
{
    public function indexAction()
    {
        var_dump($this->auth->id());exit;
    }

}

