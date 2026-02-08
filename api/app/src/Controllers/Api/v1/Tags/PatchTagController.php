<?php

namespace App\Controllers\Api\v1\Tags;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\TagRepository;
use App\Entities\Tag;

class PatchTagController extends AbstractController {
    public function process(Request $request): Response
    {
        $payload = $request->getPayload();
        $contentType = $request->getHeaders()['Content-Type'] ?? null;
        $data = json_decode($payload, true);
        
        $tagRepository = new TagRepository();
        $tag = $tagRepository->find($request->getSlug('id'));
        if(empty($tag)) {
            return new Response(json_encode(['error' => 'not found']), 404, ['Content-Type' => 'application/json']);
        }

        if(isset($data['name'])) {
            $tag->name = $data['name'];
            $tag->generateSlug();
        }

        if(isset($data['description'])) {
            $tag->description = $data['description'];
        }

        $tag->updated_at = date('Y-m-d H:i:s');
        $tagRepository->update($tag);
        return new Response(json_encode([
            'success' => true,
            'message' => 'Tag mise à jour',
            'tag' => $tag
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
