<?php

        namespace App\Controllers\Api\v1\Tags;

        use App\Lib\Http\Request;
        use App\Lib\Http\Response;
        use App\Lib\Controllers\AbstractController;
        use App\Repositories\TagRepository;
        use App\Entities\Tag;

class PostTagController extends AbstractController {
    public function process(Request $request): Response
    {
        $data = json_decode($request->getPayload(), true);
        if (empty($data['name'])) {
            return new Response(json_encode(['error' => 'Le nom est requis']), 400, ['Content-Type' => 'application/json']);
        }

        $tag = new Tag();
        $tag->name = $data['name'];
        $tag->description = $data['description'] ?? null;
        $tag->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag->name)));
        $tag->created_at = date('Y-m-d H:i:s');
        $tag->updated_at = date('Y-m-d H:i:s');

        $repo = new TagRepository();
        // Vérifier si la catégorie existe déjà (par nom ou slug)
        if ($repo->findBySlug($tag->slug)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Le tag existe déjà'
            ]), 409, ['Content-Type' => 'application/json']);
        }
        $repo->create($tag);

        return new Response(json_encode([
            'success' => true,
            'message' => 'Tag créée',
            'tag' => $tag
        ]), 201, ['Content-Type' => 'application/json']);
    }
}
