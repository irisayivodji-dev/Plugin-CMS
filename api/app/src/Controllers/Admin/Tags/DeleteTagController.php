<?php

namespace App\Controllers\Admin\Tags;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\TagRepository;

class DeleteTagController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageTags();

        $tagId = (int) $request->getSlug('id');
        $tagRepository = new TagRepository();
        $tag = $tagRepository->find($tagId);
        if (empty($tag)) {
            \App\Lib\Auth\Session::set('flash_error', 'Tag non trouvé');
            return Response::redirect('/admin/tags');
        }

        // Récupérer le nombre d'articles associés à ce tag
        $articles = $tagRepository->getArticles($tagId);
        $articleCount = count($articles);

        // Vérifier si c'est une requête DELETE 
        $postData = $request->getPost();
        $isDelete = $request->getMethod() === 'DELETE' || 
                    ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'DELETE');
        
        if ($isDelete || $request->getMethod() === 'POST') {
            // Vérifier CSRF pour les requêtes POST
            if ($request->getMethod() === 'POST') {
                $csrfToken = $request->post('csrf_token');
                if (!CsrfToken::validate($csrfToken ?? '')) {
                    \App\Lib\Auth\Session::set('flash_error', 'Token CSRF invalide');
                    return Response::redirect('/admin/tags');
                }
            }

            $tagRepository->remove($tag);
            \App\Lib\Auth\Session::set('flash_success', 'Tag supprimé avec succès');
            return Response::redirect('/admin/tags');
        }

        // GET - Afficher la page de confirmation
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/tags/delete', [
            'csrf_token' => $csrfToken,
            'tag' => $tag,
            'article_count' => $articleCount
        ]);
    }
}

?>
