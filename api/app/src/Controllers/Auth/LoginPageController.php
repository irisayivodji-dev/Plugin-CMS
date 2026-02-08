<?php

namespace App\Controllers\Auth;

use App\Lib\Http\Response;
use App\Lib\Http\Request;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;

class LoginPageController extends AbstractController
{
    public function process(Request $request): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('auth/login', ['csrf_token' => $csrfToken]);
    }
}
