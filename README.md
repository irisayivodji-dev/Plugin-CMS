# Projet semestriel — CMS Headless + Plugin WordPress

**Auteur : Iris AYIVODJI**

Ce dépôt contient :
- **api/** — CMS headless (PHP, PostgreSQL, Docker)
- **wp-cms-headless-connector/** — Plugin WordPress pour communiquer avec l’API (connexion, shortcodes, consultation et édition du contenu)

---

## Tester le plugin

1. **Lancer l’API** (à la racine du dépôt) :
   ```bash
   cd api
   docker-compose up -d --build
   ```
   API disponible sur http://localhost:8079.

2. **Lancer WordPress avec le plugin** :
   ```bash
   cd wp-cms-headless-connector
   docker compose up -d
   ```
   WordPress sur http://localhost:8000.

3. Dans WordPress : **Extensions** → activer **CMS Headless Connector**. Puis **CMS Headless** → **Réglages** → URL du CMS : **`http://host.docker.internal:8079`** → Enregistrer. **Connexion** : `admin@cms.local` / `admin123`.

---

## Plugin CMS Headless Connector

- Connexion à l’API (login, mot de passe, clé secrète optionnelle), token affiché, déconnexion.
- Shortcodes : `[cms_articles]`, `[cms_article]`, `[cms_categories]`, `[cms_tags]` (voir la page d’admin pour les paramètres).
- Consultation du contenu API (articles, catégories, tags).
- Bonus : cache (durée + vider), modification d’articles depuis WordPress.

**Installation manuelle** : copier le dossier `wp-cms-headless-connector` dans `wp-content/plugins/` puis activer le plugin. Configurer l’URL du CMS (ex. `http://localhost:8079` ou `http://host.docker.internal:8079` si WordPress est en Docker).


