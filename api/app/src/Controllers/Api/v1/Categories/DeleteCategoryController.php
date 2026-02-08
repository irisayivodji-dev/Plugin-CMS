<?php

namespace App\Controllers\Api\v1\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\CategoryRepository;

class DeleteCategoryController extends AbstractController {
    public function process(Request $request): Response
    {
        $categoryRepository = new CategoryRepository();
        
        $category = $categoryRepository->find($request->getSlug('id'));
        
        if(empty($category)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Catégorie non trouvée'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        
        $categoryRepository->remove($category);
        
        return new Response(json_encode([
            'success' => true,
            'message' => 'Catégorie supprimée',
            'category_id' => $category->id
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
