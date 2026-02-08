<?php

    namespace App\Controllers\Api\v1\Tags;

    use App\Lib\Http\Request;
    use App\Lib\Http\Response;
    use App\Lib\Controllers\AbstractController;
    use App\Repositories\TagRepository;

class GetTagsController extends AbstractController {
    public function process(Request $request): Response
    {
        $tagRepository = new TagRepository();
        // Par défaut, on ne retourne que les articles publiés
        $tags = $tagRepository->findAll();
        return new Response(json_encode([
            'success' => true,
            'tags' => $tags
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
