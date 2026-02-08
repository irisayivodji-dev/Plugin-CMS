<?php

namespace App\Controllers\Errors;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;

class ForbiddenController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $response = $this->render('errors/403');
        $response->setStatus(403);
        return $response;
    }
}

?>
