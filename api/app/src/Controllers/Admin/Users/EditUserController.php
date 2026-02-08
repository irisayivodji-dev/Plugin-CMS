<?php

namespace App\Controllers\Admin\Users;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\UserRepository;

class EditUserController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageUsers();

        $userId = (int) $request->getSlug('id');
        $userRepository = new UserRepository();
        $user = $userRepository->find($userId);

        if (empty($user)) {
            \App\Lib\Auth\Session::set('flash_error', 'Utilisateur non trouvé');
            return Response::redirect('/admin/users');
        }

        // Vérifier si c'est une requête PATCH (via _method) ou POST
        $postData = $request->getPost();
        $isPatch = $request->getMethod() === 'PATCH' || 
                   ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'PATCH');
        
        if ($isPatch) {
            return $this->handlePatch($request, $user, $userRepository);
        }

        // GET - Afficher le formulaire
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/users/edit', [
            'csrf_token' => $csrfToken,
            'user' => $user,
            'errors' => [],
            'old' => []
        ]);
    }

    private function handlePatch(Request $request, $user, UserRepository $userRepository): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], $request->getPost(), $user);
        }

        $data = $request->getPost();
        $errors = $this->validate($data, $user->id, $userRepository);

        if (!empty($errors)) {
            return $this->renderWithErrors($errors, $data, $user);
        }

        // Mettre à jour l'utilisateur
        if (isset($data['email'])) {
            $user->email = trim($data['email']);
        }
        if (isset($data['firstname'])) {
            $user->firstname = trim($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $user->lastname = trim($data['lastname']);
        }
        if (isset($data['role'])) {
            $user->role = $data['role'];
        }
        if (!empty($data['password'] ?? '')) {
            $user->hashPassword($data['password']);
        }
        $user->updated_at = date('Y-m-d H:i:s');

        $userRepository->update($user);

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Utilisateur modifié avec succès');

        return Response::redirect('/admin/users');
    }

    private function validate(array $data, int $userId, UserRepository $userRepository): array
    {
        $errors = [];

        // Email
        if (isset($data['email'])) {
            if (empty(trim($data['email']))) {
                $errors['email'] = 'L\'email est requis';
            } elseif (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format d\'email invalide';
            } else {
                // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
                $existingUser = $userRepository->findByEmail(trim($data['email']));
                if ($existingUser !== null && $existingUser->id !== $userId) {
                    $errors['email'] = 'Cet email est déjà utilisé';
                }
            }
        }

        // Prénom
        if (isset($data['firstname'])) {
            if (empty(trim($data['firstname']))) {
                $errors['firstname'] = 'Le prénom est requis';
            } elseif (strlen(trim($data['firstname'])) > 255) {
                $errors['firstname'] = 'Le prénom ne doit pas dépasser 255 caractères';
            }
        }

        // Nom
        if (isset($data['lastname'])) {
            if (empty(trim($data['lastname']))) {
                $errors['lastname'] = 'Le nom est requis';
            } elseif (strlen(trim($data['lastname'])) > 255) {
                $errors['lastname'] = 'Le nom ne doit pas dépasser 255 caractères';
            }
        }

        // Mot de passe (optionnel lors de l'édition)
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
            }
        }

        // Rôle
        if (isset($data['role']) && !in_array($data['role'], ['admin', 'editor', 'author'])) {
            $errors['role'] = 'Rôle invalide';
        }

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old, $user): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/users/edit', [
            'csrf_token' => $csrfToken,
            'user' => $user,
            'errors' => $errors,
            'old' => $old
        ]);
    }
}

?>
