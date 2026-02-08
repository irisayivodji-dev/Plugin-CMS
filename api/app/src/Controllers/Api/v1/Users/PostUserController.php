<?php



namespace App\Controllers\Api\v1\Users;

use App\Entities\User;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\UserRepository;

class PostUserController extends AbstractController {
    public function process(Request $request): Response
    {
        $data = json_decode($request->getPayload(), true);
        
        // Validation des champs obligatoires
        if (!isset($data['email']) || !isset($data['password'])) {
            return new Response(
                json_encode(['error' => 'Email and password required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }
        
        if (!isset($data['firstname']) || empty(trim($data['firstname']))) {
            return new Response(
                json_encode(['error' => 'firstname is required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }
        
        if (!isset($data['lastname']) || empty(trim($data['lastname']))) {
            return new Response(
                json_encode(['error' => 'lastname is required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }
        
        // Validation de la longueur
        if (strlen($data['firstname']) > 255) {
            return new Response(
                json_encode(['error' => 'firstname must not exceed 255 characters']),
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
        
        $userRepository = new UserRepository();

        $user = new User();
        $user->email = trim($data['email']);
        $user->firstname = trim($data['firstname']);
        $user->lastname = trim($data['lastname']);
        $user->role = $data['role'] ?? 'author';
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        
        $user->hashPassword($data['password']);

        $user->id = $userRepository->save($user);

        return new Response(json_encode($user), 201, ['Content-Type' => 'application/json']);
    }
    
}

?>
