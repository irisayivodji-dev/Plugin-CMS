<?php

namespace App\Controllers\Api\v1\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\CategoryRepository;

class PatchCategoryController extends AbstractController {
    public function process(Request $request): Response
    {
        $payload = $request->getPayload();
        $contentType = $request->getHeaders()['Content-Type'] ?? null;
        $data = json_decode($payload, true);
        
        $categoryRepository = new CategoryRepository();
        $category = $categoryRepository->find($request->getSlug('id'));
        if(empty($category)) {
            return new Response(json_encode(['error' => 'not found']), 404, ['Content-Type' => 'application/json']);
        }

        if(isset($data['name'])) {
            $category->name = $data['name'];
            $category->generateSlug();
        }

        if(isset($data['description'])) {
            $category->description = $data['description'];
        }

        $category->updated_at = date('Y-m-d H:i:s');
        $categoryRepository->update($category);

        return new Response(json_encode([
            'success' => true,
            'message' => 'Catégorie mise à jour',
            'category' => $category
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
