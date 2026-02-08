<?php


namespace App\Controllers\Api\v1\Users;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\UserRepository;

class GetUserController extends AbstractController {
    public function process(Request $request): Response
    {
        $userRepository = new UserRepository();

        $user = $userRepository->find($request->getSlug('id'));

        if(empty($user)) {
            return new Response(json_encode(['error' => 'not found']), 404, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode($user), 200, ['Content-Type' => 'application/json']);
    }
    
}

?>
