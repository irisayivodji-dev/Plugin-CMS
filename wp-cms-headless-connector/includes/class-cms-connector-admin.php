<?php

if (! defined('ABSPATH')) {
    exit;
}

class Cms_Connector_Admin
{
    const PAGE_SLUG = 'cms-headless-connector';
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', [ $this, 'register_menu' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('wp_ajax_wp_cms_connector_login', [ $this, 'ajax_login' ]);
        add_action('wp_ajax_wp_cms_connector_logout', [ $this, 'ajax_logout' ]);
        add_action('wp_ajax_wp_cms_connector_flush_cache', [ $this, 'ajax_flush_cache' ]);
        add_action('wp_ajax_wp_cms_connector_update_article', [ $this, 'ajax_update_article' ]);
    }

    public function register_settings()
    {
        register_setting('wp_cms_connector_settings', 'wp_cms_connector_base_url', [
            'type'              => 'string',
            'sanitize_callback' => function ($v) {
                $v = esc_url_raw(trim($v));
                return $v ? $v : 'http://localhost:8079';
            },
        ]);
        register_setting('wp_cms_connector_settings', 'wp_cms_connector_cache_duration', [
            'type'              => 'integer',
            'default'           => 300,
            'sanitize_callback' => function ($v) {
                return max(0, (int) $v);
            },
        ]);
    }

    public function register_menu()
    {
        add_menu_page(
            __('CMS Headless', 'cms-headless-connector'),
            __('CMS Headless', 'cms-headless-connector'),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_page' ],
            'dashicons-rest-api',
            30
        );
    }

    public function enqueue_assets($hook)
    {
        if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }
        wp_enqueue_style(
            'cms-connector-admin',
            CMS_CONNECTOR_URL . 'assets/css/admin.css',
            [],
            CMS_CONNECTOR_VERSION
        );
        wp_enqueue_script(
            'cms-connector-admin',
            CMS_CONNECTOR_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            CMS_CONNECTOR_VERSION,
            true
        );
        wp_localize_script('cms-connector-admin', 'wpCmsConnector', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wp_cms_connector_admin'),
        ]);
    }

    public function render_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $api = Cms_Connector_Api::get_instance();
        $cache = Cms_Connector_Cache::get_instance();
        $base_url = $api->get_base_url();
        $connected = $api->is_connected();
        $token = $api->get_display_token();
        $cache_duration = $cache->get_duration();
        ?>
        <div class="wrap wp-cms-connector-admin">
            <h1><?php esc_html_e('CMS Headless — Connexion et contenu', 'cms-headless-connector'); ?></h1>

            <?php $this->render_settings_section($base_url, $cache_duration); ?>

            <div class="wp-cms-connector-section">
                <h2 class="wp-cms-connector-section-title"><?php esc_html_e('1. Connexion à l’API', 'cms-headless-connector'); ?></h2>
                <?php if ($connected) : ?>
                    <div class="wp-cms-connector-box wp-cms-connector-box--success">
                        <p><strong><?php esc_html_e('Connecté.', 'cms-headless-connector'); ?></strong></p>
                        <p>
                            <label for="wp-cms-connector-token"><?php esc_html_e('Token de sécurité (non éditable)', 'cms-headless-connector'); ?></label>
                            <input type="text" id="wp-cms-connector-token" class="wp-cms-connector-token-input" value="<?php echo esc_attr($token); ?>" readonly />
                        </p>
                        <p>
                            <button type="button" id="wp-cms-connector-logout" class="button button-secondary"><?php esc_html_e('Déconnexion', 'cms-headless-connector'); ?></button>
                        </p>
                    </div>
                <?php else : ?>
                    <div class="wp-cms-connector-box">
                        <form id="wp-cms-connector-login-form" class="wp-cms-connector-form">
                            <p>
                                <label for="wp-cms-connector-login"><?php esc_html_e('Login (email)', 'cms-headless-connector'); ?></label>
                                <input type="text" id="wp-cms-connector-login" name="login" class="regular-text" required />
                            </p>
                            <p>
                                <label for="wp-cms-connector-password"><?php esc_html_e('Mot de passe', 'cms-headless-connector'); ?></label>
                                <input type="password" id="wp-cms-connector-password" name="password" class="regular-text" required />
                            </p>
                            <p>
                                <label for="wp-cms-connector-secret-key"><?php esc_html_e('Clé secrète (optionnel)', 'cms-headless-connector'); ?></label>
                                <input type="text" id="wp-cms-connector-secret-key" name="secret_key" class="regular-text" />
                            </p>
                            <p>
                                <button type="submit" class="button button-primary"><?php esc_html_e('Connexion', 'cms-headless-connector'); ?></button>
                                <span id="wp-cms-connector-login-message" class="wp-cms-connector-message" aria-live="polite"></span>
                            </p>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="wp-cms-connector-section">
                <h2 class="wp-cms-connector-section-title"><?php esc_html_e('2. Shortcodes disponibles', 'cms-headless-connector'); ?></h2>
                <?php $this->render_shortcodes_doc(); ?>
            </div>

            <div class="wp-cms-connector-section">
                <h2 class="wp-cms-connector-section-title"><?php esc_html_e('3. Contenu reçu depuis l’API', 'cms-headless-connector'); ?></h2>
                <?php $this->render_content_preview(); ?>
            </div>

            <div class="wp-cms-connector-section">
                <h2 class="wp-cms-connector-section-title"><?php esc_html_e('4. Cache API (bonus)', 'cms-headless-connector'); ?></h2>
                <?php $this->render_cache_section(); ?>
            </div>

            <div class="wp-cms-connector-section">
                <h2 class="wp-cms-connector-section-title"><?php esc_html_e('5. Modifier le contenu du CMS depuis WordPress (bonus)', 'cms-headless-connector'); ?></h2>
                <?php $this->render_edit_section(); ?>
            </div>
        </div>
        <?php
    }

    private function render_settings_section($base_url, $cache_duration)
    {
        ?>
        <div class="wp-cms-connector-section wp-cms-connector-settings-box">
            <h2 class="wp-cms-connector-section-title"><?php esc_html_e('Réglages', 'cms-headless-connector'); ?></h2>
            <form method="post" action="options.php" class="wp-cms-connector-form-inline">
                <?php settings_fields('wp_cms_connector_settings'); ?>
                <p>
                    <label for="wp_cms_connector_base_url"><?php esc_html_e('URL de base du CMS', 'cms-headless-connector'); ?></label>
                    <input type="url" id="wp_cms_connector_base_url" name="wp_cms_connector_base_url" value="<?php echo esc_attr($base_url); ?>" class="regular-text" />
                </p>
                <p>
                    <label for="wp_cms_connector_cache_duration"><?php esc_html_e('Durée du cache (secondes)', 'cms-headless-connector'); ?></label>
                    <input type="number" id="wp_cms_connector_cache_duration" name="wp_cms_connector_cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="0" step="1" style="width:100px" />
                </p>
                <?php submit_button(__('Enregistrer', 'cms-headless-connector')); ?>
            </form>
        </div>
        <?php
    }

    private function render_shortcodes_doc()
    {
        $shortcodes = [
            [
                'name'        => 'cms_articles',
                'description' => __('Affiche une liste d’articles du CMS.', 'cms-headless-connector'),
                'params'      => [
                    'count'       => __('Nombre d’articles (défaut : 5).', 'cms-headless-connector'),
                    'category_id' => __('Filtrer par ID de catégorie (optionnel).', 'cms-headless-connector'),
                    'tag_id'      => __('Filtrer par ID de tag (optionnel).', 'cms-headless-connector'),
                    'order'       => __('Ordre : date ou title (défaut : date).', 'cms-headless-connector'),
                    'layout'      => __('Mise en page : list ou grid.', 'cms-headless-connector'),
                ],
                'example'     => '[cms_articles count="5" order="date" layout="list"]',
            ],
            [
                'name'        => 'cms_article',
                'description' => __('Affiche un seul article par ID ou par slug.', 'cms-headless-connector'),
                'params'      => [
                    'id'   => __('ID de l’article (prioritaire si présent).', 'cms-headless-connector'),
                    'slug' => __('Slug de l’article (ex. mon-article).', 'cms-headless-connector'),
                ],
                'example'     => '[cms_article slug="mon-article"]',
            ],
            [
                'name'        => 'cms_categories',
                'description' => __('Affiche la liste des catégories du CMS.', 'cms-headless-connector'),
                'params'      => [
                    'limit'  => __('Nombre max de catégories (défaut : 20).', 'cms-headless-connector'),
                    'layout' => __('list ou tags.', 'cms-headless-connector'),
                ],
                'example'     => '[cms_categories limit="10" layout="tags"]',
            ],
            [
                'name'        => 'cms_tags',
                'description' => __('Affiche la liste des tags du CMS.', 'cms-headless-connector'),
                'params'      => [
                    'limit' => __('Nombre max de tags (défaut : 20).', 'cms-headless-connector'),
                ],
                'example'     => '[cms_tags limit="15"]',
            ],
        ];
        ?>
        <div class="wp-cms-connector-box wp-cms-connector-shortcodes-doc">
            <table class="wp-cms-connector-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Shortcode', 'cms-headless-connector'); ?></th>
                        <th><?php esc_html_e('Description', 'cms-headless-connector'); ?></th>
                        <th><?php esc_html_e('Paramètres', 'cms-headless-connector'); ?></th>
                        <th><?php esc_html_e('Exemple', 'cms-headless-connector'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shortcodes as $sc) : ?>
                        <tr>
                            <td><code>[<?php echo esc_html($sc['name']); ?>]</code></td>
                            <td><?php echo esc_html($sc['description']); ?></td>
                            <td>
                                <ul class="ul-disc">
                                    <?php foreach ($sc['params'] as $param => $desc) : ?>
                                        <li><code><?php echo esc_html($param); ?></code> : <?php echo esc_html($desc); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td><code><?php echo esc_html($sc['example']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_content_preview()
    {
        $api = Cms_Connector_Api::get_instance();
        $cache = Cms_Connector_Cache::get_instance();
        $endpoints = [
            '/api/v1/articles'   => __('Articles', 'cms-headless-connector'),
            '/api/v1/categories' => __('Catégories', 'cms-headless-connector'),
            '/api/v1/tags'       => __('Tags', 'cms-headless-connector'),
        ];
        ?>
        <div class="wp-cms-connector-box">
            <p class="description"><?php esc_html_e('Données renvoyées par l’API (lecture seule). Le cache est utilisé si activé.', 'cms-headless-connector'); ?></p>
            <?php
            foreach ($endpoints as $endpoint => $label) {
                $data = $cache->get($endpoint);
                if (false === $data) {
                    $res = $api->get($endpoint, false);
                    $data = $res['success'] ? $res['data'] : null;
                    if ($res['success'] && is_array($data)) {
                        $cache->set($endpoint, $data);
                    }
                }
                echo '<h3>' . esc_html($label) . '</h3>';
                if (is_array($data)) {
                    echo '<pre class="wp-cms-connector-pre">' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                } else {
                    echo '<p class="wp-cms-connector-error">' . esc_html__('Aucune donnée ou API inaccessible. Vérifiez l’URL de base et que le CMS est démarré.', 'cms-headless-connector') . '</p>';
                }
            }
            ?>
        </div>
        <?php
    }

    private function render_cache_section()
    {
        ?>
        <div class="wp-cms-connector-box">
            <p><?php esc_html_e('La durée du cache est dans Réglages ci-dessus. Bouton pour vider le cache.', 'cms-headless-connector'); ?></p>
            <p>
                <button type="button" id="wp-cms-connector-flush-cache" class="button button-secondary"><?php esc_html_e('Vider le cache', 'cms-headless-connector'); ?></button>
                <span id="wp-cms-connector-flush-message" class="wp-cms-connector-message" aria-live="polite"></span>
            </p>
        </div>
        <?php
    }

    private function render_edit_section()
    {
        $api = Cms_Connector_Api::get_instance();
        if (! $api->is_connected()) {
            echo '<p class="wp-cms-connector-notice">' . esc_html__('Connectez-vous à l’API pour modifier le contenu du CMS.', 'cms-headless-connector') . '</p>';
            return;
        }
        $res = $api->get('/api/v1/articles', false);
        $articles = [];
        if (! empty($res['data']) && is_array($res['data'])) {
            $articles = isset($res['data']['articles']) ? $res['data']['articles'] : $res['data'];
        }
        ?>
        <div class="wp-cms-connector-box">
            <form id="wp-cms-connector-edit-article-form" class="wp-cms-connector-form">
                <p>
                    <label for="wp-cms-connector-edit-article-id"><?php esc_html_e('Article à modifier', 'cms-headless-connector'); ?></label>
                    <select id="wp-cms-connector-edit-article-id" name="article_id" required>
                        <option value="">— <?php esc_html_e('Choisir', 'cms-headless-connector'); ?> —</option>
                        <?php foreach ($articles as $a) : ?>
                            <option value="<?php echo esc_attr((string) $a['id']); ?>"><?php echo esc_html(isset($a['title']) ? $a['title'] : '#' . $a['id']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="wp-cms-connector-edit-title"><?php esc_html_e('Titre', 'cms-headless-connector'); ?></label>
                    <input type="text" id="wp-cms-connector-edit-title" name="title" class="large-text" />
                </p>
                <p>
                    <label for="wp-cms-connector-edit-content"><?php esc_html_e('Contenu', 'cms-headless-connector'); ?></label>
                    <textarea id="wp-cms-connector-edit-content" name="content" class="large-text" rows="5"></textarea>
                </p>
                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Enregistrer les modifications', 'cms-headless-connector'); ?></button>
                    <span id="wp-cms-connector-edit-message" class="wp-cms-connector-message" aria-live="polite"></span>
                </p>
            </form>
        </div>
        <?php
    }

    public function ajax_login()
    {
        check_ajax_referer('wp_cms_connector_admin', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error([ 'message' => __('Non autorisé.', 'cms-headless-connector') ]);
        }
        $login = isset($_POST['login']) ? sanitize_text_field(wp_unslash($_POST['login'])) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
        if ($login === '' || $password === '') {
            wp_send_json_error([ 'message' => __('Login et mot de passe requis.', 'cms-headless-connector') ]);
        }
        $api = Cms_Connector_Api::get_instance();
        $result = $api->login($login, $password, $secret_key);
        if ($result['success']) {
            wp_send_json_success([ 'message' => $result['message'], 'token' => $api->get_display_token() ]);
        }
        wp_send_json_error([ 'message' => $result['message'] ]);
    }

    public function ajax_logout()
    {
        check_ajax_referer('wp_cms_connector_admin', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error();
        }
        Cms_Connector_Api::get_instance()->logout();
        wp_send_json_success();
    }

    public function ajax_flush_cache()
    {
        check_ajax_referer('wp_cms_connector_admin', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error();
        }
        Cms_Connector_Cache::get_instance()->flush();
        wp_send_json_success([ 'message' => __('Cache vidé.', 'cms-headless-connector') ]);
    }

    public function ajax_update_article()
    {
        check_ajax_referer('wp_cms_connector_admin', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error([ 'message' => __('Non autorisé.', 'cms-headless-connector') ]);
        }
        $article_id = isset($_POST['article_id']) ? absint($_POST['article_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        if ($article_id <= 0) {
            wp_send_json_error([ 'message' => __('Article invalide.', 'cms-headless-connector') ]);
        }
        $body = [];
        if ($title !== '') {
            $body['title'] = $title;
        }
        if ($content !== '') {
            $body['content'] = $content;
        }
        if (empty($body)) {
            wp_send_json_error([ 'message' => __('Rien à modifier.', 'cms-headless-connector') ]);
        }
        $api = Cms_Connector_Api::get_instance();
        $result = $api->patch('/api/v1/articles/' . $article_id, $body);
        if ($result['success']) {
            Cms_Connector_Cache::get_instance()->flush();
            wp_send_json_success([ 'message' => __('Article mis à jour.', 'cms-headless-connector') ]);
        }
        wp_send_json_error([ 'message' => isset($result['message']) ? $result['message'] : __('Erreur API.', 'cms-headless-connector') ]);
    }
}
