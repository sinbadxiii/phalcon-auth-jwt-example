<?php

namespace App\Middlewares;

use Phalcon\Di\Di;
use Phalcon\Mvc\Micro\MiddlewareInterface;

use function in_array;

final class MethodMiddleware implements MiddlewareInterface
{
    private function isPost($application)
    {
        return in_array($application->request->getMethod(), ['POST', 'PUT']);
    }

    private function isApplicationJson($application)
    {
        return ($application->request->getHeader('Content-Type') === 'application/json');
    }

    public function call(\Phalcon\Mvc\Micro $application)
    {
        if ($this->isPost($application) &&
            !$this->isApplicationJson($application)) {

                $response = Di::getDefault()->get("response");
                $response->setStatusCode(400);
                $response->setJsonContent("Only 'application/json' is accepted for Content-Type in POST requests", JSON_UNESCAPED_UNICODE);

                if (true !== $response->isSent()) {
                    return $response->send();
                }
        }

        return true;
    }
}
