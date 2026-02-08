<?php

namespace App\Controllers\Admin\Tags;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\TagRepository;

class TagsController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageTags();

        $tagRepository = new TagRepository();
        $tags = $tagRepository->findAll();

        $tagsWithStats = [];
        foreach ($tags as $tag) {
            $articles = $tagRepository->getArticles($tag->id);
            $tagsWithStats[] = [
                'id' => $tag->id,
                'name' => $tag->name,
                'description' => $tag->description,
                'slug' => $tag->slug,
                'created_at' => $tag->created_at,
                'article_count' => count($articles),
            ];
        }

        $flashSuccess = \App\Lib\Auth\Session::get('flash_success');
        $flashError = \App\Lib\Auth\Session::get('flash_error');
        if ($flashSuccess) {
            \App\Lib\Auth\Session::remove('flash_success');
        }
        if ($flashError) {
            \App\Lib\Auth\Session::remove('flash_error');
        }

        $csrfToken = CsrfToken::generate();
        return $this->render('admin/tags', [
            'csrf_token' => $csrfToken,
            'tags' => $tagsWithStats,
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
        ]);
    }
}

?>
