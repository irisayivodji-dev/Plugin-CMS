<?php

namespace App\Controllers\Api\v1\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\CategoryRepository;
use App\Entities\Category;

class PostCategoryController extends AbstractController {
    public function process(Request $request): Response
    {
        $data = json_decode($request->getPayload(), true);
        if (empty($data['name'])) {
            return new Response(json_encode(['error' => 'Le nom est requis']), 400, ['Content-Type' => 'application/json']);
        }

        $category = new Category();
        $category->name = $data['name'];
        $category->description = $data['description'] ?? null;
        $category->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $category->name)));
        $category->created_at = date('Y-m-d H:i:s');
        $category->updated_at = date('Y-m-d H:i:s');

        $repo = new CategoryRepository();
        // Vérifier si la catégorie existe déjà (par nom ou slug)
        if ($repo->findBySlug($category->slug)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'La catégorie existe déjà'
            ]), 409, ['Content-Type' => 'application/json']);
        }
        $repo->create($category);

        return new Response(json_encode([
            'success' => true,
            'message' => 'Catégorie créée',
            'category' => $category
        ]), 201, ['Content-Type' => 'application/json']);
    }
}
