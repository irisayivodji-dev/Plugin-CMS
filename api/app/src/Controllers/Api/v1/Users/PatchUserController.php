<?php

namespace App\Controllers\Api\v1\Users;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\UserRepository;

class PatchUserController extends AbstractController {
    public function process(Request $request): Response
    {
        $data = json_decode($request->getPayload(), true);
        
        $userRepository = new UserRepository();

        $user = $userRepository->find($request->getSlug('id'));

        if(empty($user)) {
            return new Response(json_encode(['error' => 'not found']), 404, ['Content-Type' => 'application/json']);
        }
        
        if (isset($data['email'])) {
            $user->email = trim($data['email']);
        }
        
        if (isset($data['firstname'])) {
            if (empty(trim($data['firstname']))) {
                return new Response(
                    json_encode(['error' => 'firstname cannot be empty']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            if (strlen($data['firstname']) > 255) {
                return new Response(
                    json_encode(['error' => 'firstname must not exceed 255 characters']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $user->firstname = trim($data['firstname']);
        }
        
        if (isset($data['lastname'])) {
            if (empty(trim($data['lastname']))) {
                return new Response(
                    json_encode(['error' => 'lastname cannot be empty']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            if (strlen($data['lastname']) > 255) {
                return new Response(
                    json_encode(['error' => 'lastname must not exceed 255 characters']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $user->lastname = trim($data['lastname']);
        }
        
        if (isset($data['password'])) {
            $user->hashPassword($data['password']);
        }
        
        if (isset($data['role'])) {
            $user->role = $data['role'];
        }
        
        $user->updated_at = date('Y-m-d H:i:s');

        $userRepository->update($user);

        return new Response(json_encode($user), 200, ['Content-Type' => 'application/json']);
    }
    
}

?>
