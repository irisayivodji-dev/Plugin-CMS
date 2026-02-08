<?php


namespace App\Controllers\Api\v1\Users;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\UserRepository;

class GetUsersController extends AbstractController {
    public function process(Request $request): Response
    {
        $userRepository = new UserRepository();

        $users = $userRepository->findAll();

        return new Response(json_encode($users), 200, ['Content-Type' => 'application/json']);
    }
    
}

?>
