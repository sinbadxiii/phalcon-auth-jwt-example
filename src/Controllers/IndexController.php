<?php

declare(strict_types=1);

namespace App\Controllers;

use ControllerBase;
use function var_dump;

class IndexController extends ControllerBase
{
    public function onConstruct()
    {
        $this->auth->access("jwt");
    }

    public function indexAction()
    {
//        $payload = $this->auth->payload();
//        $data = [
//            'success' => true,
//            "user" => $this->auth->user(),
//            "payload" => $payload,
//            'sub' => $payload["sub"],
//            'aud' => $payload("aud"),
//            "iat" => date("d.m.Y H:i:s", $payload->get("iat")),
//            "exp" => date("d.m.Y H:i:s", $payload->get("exp"))
//        ];

var_dump($payload = $this->auth->payload());exit;
        $this->response->setJsonContent($data);
        if (!$this->response->isSent()) {
            $this->response->send();
        }
//        var_dump($this->router->getMatchedRoute());exit;
    }

}

