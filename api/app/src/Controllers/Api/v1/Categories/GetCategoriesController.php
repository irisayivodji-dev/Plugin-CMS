<?php

namespace App\Controllers\Api\v1\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\CategoryRepository;

class GetCategoriesController extends AbstractController {
    public function process(Request $request): Response
    {
        $categoryRepository = new CategoryRepository();
        // Par défaut, on ne retourne que les articles publiés
        $categories = $categoryRepository->findAll();
        return new Response(json_encode([
            'success' => true,
            'categories' => $categories
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
