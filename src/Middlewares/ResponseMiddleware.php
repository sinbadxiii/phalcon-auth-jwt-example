<?php

namespace App\Middlewares;

use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\MiddlewareInterface;

class ResponseMiddleware implements MiddlewareInterface
{
    public function call(Micro $application)
    {
        $response = $application->response;
        $response
            ->setJsonContent($application->getReturnedValue(), JSON_UNESCAPED_UNICODE);

        if (true !== $response->isSent()) {
          $response->send();
        }

        return true;
    }
}