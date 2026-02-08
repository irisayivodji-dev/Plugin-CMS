<?php

namespace App\Controllers\Admin\Users;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Entities\User;
use App\Repositories\UserRepository;

class CreateUserController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageUsers();

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        // GET - Afficher le formulaire
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/users/create', [
            'csrf_token' => $csrfToken,
            'errors' => [],
            'old' => []
        ]);
    }

    private function handlePost(Request $request): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], $request->getPost());
        }

        $data = $request->getPost();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            return $this->renderWithErrors($errors, $data);
        }

        $userRepository = new UserRepository();

        // Vérifier si l'email existe déjà
        $existingUser = $userRepository->findByEmail(trim($data['email']));
        if ($existingUser !== null) {
            return $this->renderWithErrors(['email' => 'Cet email est déjà utilisé'], $data);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->email = trim($data['email']);
        $user->firstname = trim($data['firstname']);
        $user->lastname = trim($data['lastname']);
        $user->role = $data['role'] ?? 'author';
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        $user->hashPassword($data['password']);

        $user->id = $userRepository->save($user);

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Utilisateur créé avec succès');

        return Response::redirect('/admin/users');
    }

    private function validate(array $data): array
    {
        $errors = [];

        // Email
        if (empty(trim($data['email'] ?? ''))) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide';
        }

        // Prénom
        if (empty(trim($data['firstname'] ?? ''))) {
            $errors['firstname'] = 'Le prénom est requis';
        } elseif (strlen(trim($data['firstname'])) > 255) {
            $errors['firstname'] = 'Le prénom ne doit pas dépasser 255 caractères';
        }

        // Nom
        if (empty(trim($data['lastname'] ?? ''))) {
            $errors['lastname'] = 'Le nom est requis';
        } elseif (strlen(trim($data['lastname'])) > 255) {
            $errors['lastname'] = 'Le nom ne doit pas dépasser 255 caractères';
        }

        // Mot de passe
        if (empty($data['password'] ?? '')) {
            $errors['password'] = 'Le mot de passe est requis';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        // Rôle
        if (isset($data['role']) && !in_array($data['role'], ['admin', 'editor', 'author'])) {
            $errors['role'] = 'Rôle invalide';
        }

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/users/create', [
            'csrf_token' => $csrfToken,
            'errors' => $errors,
            'old' => $old
        ]);
    }
}

?>
