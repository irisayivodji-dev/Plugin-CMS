<?php

namespace App\Controllers\Api\v1\Tags;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\TagRepository;

class GetTagController extends AbstractController {
    public function process(Request $request): Response
    {
        $tagRepository = new TagRepository();
        
        $tag = $tagRepository->find($request->getSlug('id'));
        
            if(empty($tag)) {
                return new Response(json_encode([
                    'success' => false,
                    'error' => 'Tag non trouvé'
                ]), 404, ['Content-Type' => 'application/json']);
            }
            return new Response(json_encode([
                'success' => true,
                'tag' => $tag
            ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
