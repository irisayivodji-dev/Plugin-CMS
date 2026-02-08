<?php
namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleVersionRepository;

class GetArticleVersionController extends AbstractController {
    public function process(Request $request): Response
    {
        $versionId = (int)($request->getUrlParams()['versionId'] ?? 0);
        $repo = new ArticleVersionRepository();
        $version = $repo->find($versionId);
        if (!$version) {
            return new Response(json_encode([
                'error' => 'Version non trouvée'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        return new Response(json_encode($version), 200, ['Content-Type' => 'application/json']);
    }
}
