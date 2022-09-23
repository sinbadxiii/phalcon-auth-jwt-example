<?php

namespace App\Middlewares;

use Phalcon\Mvc\Micro\MiddlewareInterface;
use Phalcon\Mvc\Micro;

use function in_array;

class AuthMiddleware implements MiddlewareInterface
{
    public function call(Micro $application)
    {
        $authService = $application->getDI()->get("auth");

        if ($access = $authService->getAccess()) {
            $excepts = $access->getExceptActions();

            $uri = $application->getDI()->get("request")->getURI(true);

            if (!in_array($uri, $excepts)) {
                try {
                     $authService->parseToken()->checkOrFail();
                } catch (\Throwable $t) {
                    $responseService = $application->getDI()->get("response");
                    $responseService->setStatusCode(401, 'Unauthorized');
                    $responseService->setJsonContent(
                        [
                            "error" => "Unauthorized: " . $t->getMessage(),
                            "code" => 401
                        ]
                    );
                    if (!$responseService->isSent()) {
                        $responseService->send();
                    }
                }
            }
        }

        return true;
    }
}
