# Guide : Créer un Dashboard Administratif

Ce guide vous montre comment créer un dashboard avec des statistiques et widgets.

## 📋 Table des matières

1. [Modifier le contrôleur](#1-modifier-le-contrôleur)
2. [Structure HTML du dashboard](#2-structure-html-du-dashboard)
3. [Exemples de widgets](#3-exemples-de-widgets)
4. [Styles CSS](#4-styles-css)
5. [Méthodes utiles dans les repositories](#5-méthodes-utiles-dans-les-repositories)

---

## 1. Modifier le contrôleur

### Fichier : `api/app/src/Controllers/Admin/AdminController.php`

Voici comment récupérer les statistiques et les passer à la vue :

```php
<?php

namespace App\Controllers\Admin;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Lib\Auth\AuthService;
use App\Repositories\ArticleRepository;
use App\Repositories\UserRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TagRepository;
use App\Repositories\MediaRepository;

class AdminController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        if (!\App\Lib\Auth\Session::isAuthenticated()) {
            return Response::redirect('/login');
        }

        $authService = new AuthService();
        $currentUser = $authService->getCurrentUser();
        
        // Initialiser les repositories
        $articleRepository = new ArticleRepository();
        $userRepository = new UserRepository();
        $categoryRepository = new CategoryRepository();
        $tagRepository = new TagRepository();
        $mediaRepository = new MediaRepository();

        // Récupérer les statistiques selon les permissions
        $stats = [];
        
        // Statistiques d'articles
        if ($this->canManageAllArticles()) {
            // Admin/Editor : voir toutes les statistiques
            $stats['articles'] = [
                'total' => count($articleRepository->findAll()),
                'published' => count($articleRepository->findByStatus('published')),
                'draft' => count($articleRepository->findByStatus('draft')),
                'archived' => count($articleRepository->findByStatus('archived'))
            ];
        } else {
            // Author : uniquement ses articles
            $myArticles = $articleRepository->findByAuthor($currentUser->id);
            $stats['articles'] = [
                'total' => count($myArticles),
                'published' => count(array_filter($myArticles, fn($a) => $a->status === 'published')),
                'draft' => count(array_filter($myArticles, fn($a) => $a->status === 'draft')),
                'archived' => count(array_filter($myArticles, fn($a) => $a->status === 'archived'))
            ];
        }

        // Statistiques utilisateurs (Admin uniquement)
        if ($this->isAdmin()) {
            $allUsers = $userRepository->findAll();
            $stats['users'] = [
                'total' => count($allUsers),
                'admins' => count(array_filter($allUsers, fn($u) => $u->role === 'admin')),
                'editors' => count(array_filter($allUsers, fn($u) => $u->role === 'editor')),
                'authors' => count(array_filter($allUsers, fn($u) => $u->role === 'author'))
            ];
        }

        // Statistiques catégories et tags (Admin/Editor)
        if ($this->canManageCategories()) {
            $stats['categories'] = count($categoryRepository->findAll());
            $stats['tags'] = count($tagRepository->findAll());
        }

        // Statistiques médias (tous les utilisateurs)
        $myMedia = $mediaRepository->findBy(['user_id' => $currentUser->id]);
        $stats['media'] = count($myMedia);

        // Articles récents (5 derniers)
        if ($this->canManageAllArticles()) {
            $allArticles = $articleRepository->findAll();
        } else {
            $allArticles = $articleRepository->findByAuthor($currentUser->id);
        }
        
        // Trier par date de création (plus récent en premier)
        usort($allArticles, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        $recentArticles = array_slice($allArticles, 0, 5);

        // Préparer les articles récents avec détails
        $recentArticlesData = [];
        foreach ($recentArticles as $article) {
            $author = $userRepository->find($article->author_id);
            $recentArticlesData[] = [
                'id' => $article->id,
                'title' => $article->title,
                'status' => $article->status,
                'author_name' => $author ? ($author->firstname . ' ' . $author->lastname) : 'Inconnu',
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at
            ];
        }

        $csrfToken = CsrfToken::generate();
        
        return $this->render('admin/dashboard', [
            'csrf_token' => $csrfToken,
            'stats' => $stats,
            'recent_articles' => $recentArticlesData,
            'current_user' => $currentUser,
            'is_admin' => $this->isAdmin(),
            'can_manage_all' => $this->canManageAllArticles(),
            'can_manage_categories' => $this->canManageCategories()
        ]);
    }
}
```

---

## 2. Structure HTML du dashboard

### Fichier : `api/app/views/admin/dashboard.html`

Voici une structure complète avec des widgets :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Back-office - Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/dist/css/main.css">
</head>
<body class="dashboard">
    <?php require __DIR__ . '/../components/sidebar.html'; ?>

    <div class="dashboard__container">
        <!-- En-tête -->
        <div class="dashboard__header">
            <div>
                <h1 class="dashboard__title">Tableau de bord</h1>
                <p class="dashboard__subtitle">
                    Bienvenue, <?= htmlspecialchars($current_user->firstname ?? 'Utilisateur') ?> !
                </p>
            </div>
            <div class="dashboard__actions">
                <a href="/admin/articles/create" class="button button--primary">
                    + Nouvel article
                </a>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="stats-grid">
            <!-- Carte Articles -->
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--blue">
                    📝
                </div>
                <div class="stat-card__content">
                    <h3 class="stat-card__label">Articles</h3>
                    <p class="stat-card__value"><?= $stats['articles']['total'] ?? 0 ?></p>
                    <div class="stat-card__details">
                        <span>Publiés: <?= $stats['articles']['published'] ?? 0 ?></span>
                        <span>Brouillons: <?= $stats['articles']['draft'] ?? 0 ?></span>
                    </div>
                </div>
            </div>

            <!-- Carte Utilisateurs (Admin uniquement) -->
            <?php if (isset($is_admin) && $is_admin && isset($stats['users'])): ?>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--green">
                    👥
                </div>
                <div class="stat-card__content">
                    <h3 class="stat-card__label">Utilisateurs</h3>
                    <p class="stat-card__value"><?= $stats['users']['total'] ?? 0 ?></p>
                    <div class="stat-card__details">
                        <span>Admins: <?= $stats['users']['admins'] ?? 0 ?></span>
                        <span>Éditeurs: <?= $stats['users']['editors'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Carte Catégories (Admin/Editor) -->
            <?php if (isset($can_manage_categories) && $can_manage_categories && isset($stats['categories'])): ?>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--purple">
                    📂
                </div>
                <div class="stat-card__content">
                    <h3 class="stat-card__label">Catégories</h3>
                    <p class="stat-card__value"><?= $stats['categories'] ?? 0 ?></p>
                    <a href="/admin/categories" class="stat-card__link">Voir toutes</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Carte Tags (Admin/Editor) -->
            <?php if (isset($can_manage_categories) && $can_manage_categories && isset($stats['tags'])): ?>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--orange">
                    🏷️
                </div>
                <div class="stat-card__content">
                    <h3 class="stat-card__label">Tags</h3>
                    <p class="stat-card__value"><?= $stats['tags'] ?? 0 ?></p>
                    <a href="/admin/tags" class="stat-card__link">Voir tous</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Carte Médias -->
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--red">
                    🖼️
                </div>
                <div class="stat-card__content">
                    <h3 class="stat-card__label">Médias</h3>
                    <p class="stat-card__value"><?= $stats['media'] ?? 0 ?></p>
                    <a href="/admin/media" class="stat-card__link">Voir la médiathèque</a>
                </div>
            </div>
        </div>

        <!-- Grille principale : Articles récents + Actions rapides -->
        <div class="dashboard__grid">
            <!-- Colonne gauche : Articles récents -->
            <div class="dashboard__widget">
                <div class="widget">
                    <div class="widget__header">
                        <h2 class="widget__title">Articles récents</h2>
                        <a href="/admin/articles" class="widget__link">Voir tout</a>
                    </div>
                    <div class="widget__content">
                        <?php if (empty($recent_articles)): ?>
                            <p class="widget__empty">Aucun article pour le moment.</p>
                        <?php else: ?>
                            <ul class="article-list">
                                <?php foreach ($recent_articles as $article): ?>
                                <li class="article-list__item">
                                    <div class="article-list__info">
                                        <h3 class="article-list__title">
                                            <a href="/admin/articles/edit/<?= $article['id'] ?>">
                                                <?= htmlspecialchars($article['title']) ?>
                                            </a>
                                        </h3>
                                        <div class="article-list__meta">
                                            <span class="badge badge--<?= $article['status'] === 'published' ? 'success' : ($article['status'] === 'draft' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($article['status']) ?>
                                            </span>
                                            <span class="article-list__author">
                                                Par <?= htmlspecialchars($article['author_name']) ?>
                                            </span>
                                            <span class="article-list__date">
                                                <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne droite : Actions rapides -->
            <div class="dashboard__widget">
                <div class="widget">
                    <div class="widget__header">
                        <h2 class="widget__title">Actions rapides</h2>
                    </div>
                    <div class="widget__content">
                        <div class="quick-actions">
                            <a href="/admin/articles/create" class="quick-action">
                                <span class="quick-action__icon">✏️</span>
                                <span class="quick-action__label">Créer un article</span>
                            </a>
                            <a href="/admin/media/upload" class="quick-action">
                                <span class="quick-action__icon">📤</span>
                                <span class="quick-action__label">Uploader un média</span>
                            </a>
                            <?php if (isset($can_manage_categories) && $can_manage_categories): ?>
                            <a href="/admin/categories/create" class="quick-action">
                                <span class="quick-action__icon">📂</span>
                                <span class="quick-action__label">Nouvelle catégorie</span>
                            </a>
                            <a href="/admin/tags/create" class="quick-action">
                                <span class="quick-action__icon">🏷️</span>
                                <span class="quick-action__label">Nouveau tag</span>
                            </a>
                            <?php endif; ?>
                            <?php if (isset($is_admin) && $is_admin): ?>
                            <a href="/admin/users/create" class="quick-action">
                                <span class="quick-action__icon">👤</span>
                                <span class="quick-action__label">Nouvel utilisateur</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Déconnexion -->
        <div class="dashboard__footer">
            <form method="POST" action="/api/v1/auth/logout">
                <input type="hidden" name="csrf_token" value="<?= isset($csrf_token) ? htmlspecialchars($csrf_token) : '' ?>">
                <button type="submit" class="button button--danger">
                    Se déconnecter
                </button>
            </form>
        </div>
    </div>
</body>
</html>
```

---

## 3. Exemples de widgets supplémentaires

### Widget "Articles en attente de publication" (pour Editor/Admin)

```html
<?php if (isset($can_manage_all) && $can_manage_all): ?>
<div class="dashboard__widget">
    <div class="widget widget--warning">
        <div class="widget__header">
            <h2 class="widget__title">Articles en attente</h2>
        </div>
        <div class="widget__content">
            <?php 
            $pendingArticles = array_filter($recent_articles, fn($a) => $a['status'] === 'draft');
            if (empty($pendingArticles)): ?>
                <p class="widget__empty">Aucun article en attente.</p>
            <?php else: ?>
                <ul class="article-list">
                    <?php foreach ($pendingArticles as $article): ?>
                    <li class="article-list__item">
                        <a href="/admin/articles/edit/<?= $article['id'] ?>">
                            <?= htmlspecialchars($article['title']) ?>
                        </a>
                        <form method="POST" action="/admin/articles/publish/<?= $article['id'] ?>" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="button button--small button--success">
                                Publier
                            </button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
```

### Widget "Mes statistiques" (pour Author)

```html
<?php if (!isset($can_manage_all) || !$can_manage_all): ?>
<div class="dashboard__widget">
    <div class="widget">
        <div class="widget__header">
            <h2 class="widget__title">Mes statistiques</h2>
        </div>
        <div class="widget__content">
            <div class="my-stats">
                <div class="my-stats__item">
                    <span class="my-stats__label">Total d'articles</span>
                    <span class="my-stats__value"><?= $stats['articles']['total'] ?? 0 ?></span>
                </div>
                <div class="my-stats__item">
                    <span class="my-stats__label">Publiés</span>
                    <span class="my-stats__value"><?= $stats['articles']['published'] ?? 0 ?></span>
                </div>
                <div class="my-stats__item">
                    <span class="my-stats__label">Brouillons</span>
                    <span class="my-stats__value"><?= $stats['articles']['draft'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
```

---

## 4. Styles CSS

### Fichier : `api/assets/main.scss` (ou votre fichier SCSS)

Ajoutez ces styles pour le dashboard :

```scss
// Grille de statistiques
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

// Carte de statistique
.stat-card {
    border-radius: 8px;
    
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    &__icon {
        font-size: 2.5rem;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #f0f0f0;

        &--blue { background: #e3f2fd; }
        &--green { background: #e8f5e9; }
        &--purple { background: #f3e5f5; }
        &--orange { background: #fff3e0; }
        &--red { background: #ffebee; }
    }

    &__content {
        flex: 1;
        background:var(--blue-200)
    }

    &__label {
        font-size: 0.875rem;
        color: #666;
        margin: 0 0 0.5rem 0;
        font-weight: 500;
    }

    &__value {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        margin: 0;
        line-height: 1;
    }

    &__details {
        display: flex;
        gap: 1rem;
        margin-top: 0.5rem;
        font-size: 0.75rem;
        color: #999;
    }

    &__link {
        display: inline-block;
        margin-top: 0.5rem;
        color: #007bff;
        text-decoration: none;
        font-size: 0.875rem;

        &:hover {
            text-decoration: underline;
        }
    }
}

// Grille principale du dashboard
.dashboard__grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;

    @media (max-width: 768px) {
        grid-template-columns: 1fr;
    }
}

// Widget générique
.widget {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;

    &__header {
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    &__title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    &__link {
        color: #007bff;
        text-decoration: none;
        font-size: 0.875rem;

        &:hover {
            text-decoration: underline;
        }
    }

    &__content {
        padding: 1.5rem;
    }

    &__empty {
        color: #999;
        text-align: center;
        padding: 2rem;
        margin: 0;
    }

    &--warning {
        border-left: 4px solid #ffc107;
    }
}

// Liste d'articles
.article-list {
    list-style: none;
    padding: 0;
    margin: 0;

    &__item {
        padding: 1rem 0;
        border-bottom: 1px solid #eee;

        &:last-child {
            border-bottom: none;
        }
    }

    &__title {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;

        a {
            color: #333;
            text-decoration: none;

            &:hover {
                color: #007bff;
            }
        }
    }

    &__meta {
        display: flex;
        gap: 1rem;
        align-items: center;
        font-size: 0.875rem;
        color: #666;
    }

    &__author,
    &__date {
        color: #999;
    }
}

// Actions rapides
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: background 0.2s;

    &:hover {
        background: #e9ecef;
    }

    &__icon {
        font-size: 1.5rem;
    }

    &__label {
        font-weight: 500;
    }
}

// Badges
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;

    &--success {
        background: #d4edda;
        color: #155724;
    }

    &--warning {
        background: #fff3cd;
        color: #856404;
    }

    &--secondary {
        background: #e2e3e5;
        color: #383d41;
    }
}

// Mes statistiques
.my-stats {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.my-stats__item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.my-stats__label {
    color: #666;
}

.my-stats__value {
    font-weight: bold;
    font-size: 1.25rem;
    color: #333;
}

// En-tête du dashboard
.dashboard__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #eee;
}

.dashboard__title {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}

.dashboard__subtitle {
    margin: 0;
    color: #666;
}

.dashboard__actions {
    display: flex;
    gap: 1rem;
}

.dashboard__footer {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
    text-align: right;
}
```

---

## 5. Méthodes utiles dans les repositories

### Ajouter des méthodes de comptage dans `ArticleRepository.php`

```php
// Compter les articles par statut
public function countByStatus(string $status): int
{
    $sql = "SELECT COUNT(*) as count FROM {$this->getTable()} WHERE status = :status";
    $stmt = $this->db->getConnexion()->prepare($sql);
    $stmt->execute(['status' => $status]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return (int) $result['count'];
}

// Compter tous les articles
public function countAll(): int
{
    $sql = "SELECT COUNT(*) as count FROM {$this->getTable()}";
    $stmt = $this->db->getConnexion()->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return (int) $result['count'];
}

// Récupérer les articles récents (limite)
public function findRecent(int $limit = 5): array
{
    $sql = "SELECT * FROM {$this->getTable()} ORDER BY created_at DESC LIMIT :limit";
    $stmt = $this->db->getConnexion()->prepare($sql);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(\PDO::FETCH_CLASS, Article::class);
    return $stmt->fetchAll();
}
```

### Ajouter des méthodes dans `UserRepository.php`

```php
// Compter les utilisateurs par rôle
public function countByRole(string $role): int
{
    $sql = "SELECT COUNT(*) as count FROM {$this->getTable()} WHERE role = :role";
    $stmt = $this->db->getConnexion()->prepare($sql);
    $stmt->execute(['role' => $role]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return (int) $result['count'];
}

// Compter tous les utilisateurs
public function countAll(): int
{
    $sql = "SELECT COUNT(*) as count FROM {$this->getTable()}";
    $stmt = $this->db->getConnexion()->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return (int) $result['count'];
}
```

---

## 🎯 Résumé des étapes

1. **Modifier `AdminController.php`** : Ajouter la récupération des statistiques
2. **Modifier `dashboard.html`** : Ajouter la structure HTML avec les widgets
3. **Ajouter les styles CSS** : Dans votre fichier SCSS principal
4. **Optionnel** : Ajouter des méthodes dans les repositories pour optimiser les requêtes

---

## 💡 Conseils

- **Performance** : Utilisez `COUNT()` en SQL plutôt que `count()` en PHP pour de grandes quantités
- **Sécurité** : Toujours utiliser `htmlspecialchars()` pour afficher les données utilisateur
- **Responsive** : Utilisez des media queries pour adapter le dashboard sur mobile
- **Permissions** : Vérifiez toujours les permissions avant d'afficher les statistiques sensibles

Bon développement ! 🚀
