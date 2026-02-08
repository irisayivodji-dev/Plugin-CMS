<?php

if (! defined('ABSPATH')) {
    exit;
}

class Cms_Connector_Shortcodes
{
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
        add_shortcode('cms_articles', [ $this, 'shortcode_articles' ]);
        add_shortcode('cms_article', [ $this, 'shortcode_article' ]);
        add_shortcode('cms_categories', [ $this, 'shortcode_categories' ]);
        add_shortcode('cms_tags', [ $this, 'shortcode_tags' ]);
        add_action('wp_enqueue_scripts', [ $this, 'maybe_enqueue_styles' ]);
    }

    private function fetch($endpoint)
    {
        $cache = Cms_Connector_Cache::get_instance();
        $data = $cache->get($endpoint);
        if (false !== $data) {
            return $data;
        }
        $api = Cms_Connector_Api::get_instance();
        $res = $api->get($endpoint, false);
        if (! empty($res['success']) && isset($res['data'])) {
            $cache->set($endpoint, $res['data']);
            return $res['data'];
        }
        return null;
    }

    public function shortcode_articles($atts)
    {
        $atts = shortcode_atts([
            'count'       => 5,
            'category_id' => '',
            'tag_id'      => '',
            'order'       => 'date',
            'layout'      => 'list',
        ], $atts, 'cms_articles');

        $data = $this->fetch('/api/v1/articles');
        if (! is_array($data)) {
            return $this->wrap_message(__('Aucun article disponible.', 'cms-headless-connector'));
        }
        $items = isset($data['articles']) ? $data['articles'] : (isset($data['data']) ? $data['data'] : []);
        if (! is_array($items)) {
            $items = [];
        }

        $category_id = absint($atts['category_id']);
        $tag_id = absint($atts['tag_id']);
        if ($category_id > 0) {
            $items = array_filter($items, function ($a) use ($category_id) {
                $cats = isset($a['categories']) ? $a['categories'] : (isset($a['category_ids']) ? $a['category_ids'] : []);
                return is_array($cats) && in_array($category_id, $cats, true);
            });
        }
        if ($tag_id > 0) {
            $items = array_filter($items, function ($a) use ($tag_id) {
                $tags = isset($a['tags']) ? $a['tags'] : (isset($a['tag_ids']) ? $a['tag_ids'] : []);
                return is_array($tags) && in_array($tag_id, $tags, true);
            });
        }
        $count = max(1, min(50, (int) $atts['count']));
        $items = array_slice(array_values($items), 0, $count);
        $order = $atts['order'];
        if ($order === 'title') {
            usort($items, function ($a, $b) {
                $t1 = isset($a['title']) ? $a['title'] : '';
                $t2 = isset($b['title']) ? $b['title'] : '';
                return strcasecmp($t1, $t2);
            });
        }
        ob_start();
        $this->render_articles($items, $atts['layout']);
        return ob_get_clean();
    }

    public function shortcode_article($atts)
    {
        $atts = shortcode_atts([
            'id'   => '',
            'slug' => '',
        ], $atts, 'cms_article');

        $id = absint($atts['id']);
        $slug = sanitize_text_field($atts['slug']);
        if ($id > 0) {
            $data = $this->fetch('/api/v1/articles/' . $id);
        } elseif ($slug !== '') {
            $data = $this->fetch('/api/v1/articles/slug/' . $slug);
        } else {
            return $this->wrap_message(__('Indiquez id ou slug pour l’article.', 'cms-headless-connector'));
        }
        if (! is_array($data) || empty($data)) {
            return $this->wrap_message(__('Article introuvable.', 'cms-headless-connector'));
        }
        $article = isset($data['article']) ? $data['article'] : (isset($data['data']) ? $data['data'] : $data);
        ob_start();
        ?>
        <div class="wp-cms-connector-article">
            <h2 class="wp-cms-connector-article-title"><?php echo esc_html(isset($article['title']) ? $article['title'] : ''); ?></h2>
            <?php if (! empty($article['excerpt'])) : ?>
                <p class="wp-cms-connector-article-excerpt"><?php echo esc_html($article['excerpt']); ?></p>
            <?php endif; ?>
            <div class="wp-cms-connector-article-content"><?php echo wp_kses_post(isset($article['content']) ? $article['content'] : ''); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_categories($atts)
    {
        $atts = shortcode_atts([
            'limit'  => 20,
            'layout' => 'list',
        ], $atts, 'cms_categories');

        $data = $this->fetch('/api/v1/categories');
        if (! is_array($data)) {
            return $this->wrap_message(__('Aucune catégorie disponible.', 'cms-headless-connector'));
        }
        $items = isset($data['categories']) ? $data['categories'] : (isset($data['data']) ? $data['data'] : []);
        if (! is_array($items)) {
            $items = [];
        }
        $limit = max(1, min(100, (int) $atts['limit']));
        $items = array_slice($items, 0, $limit);
        ob_start();
        ?>
        <ul class="wp-cms-connector-categories wp-cms-connector-layout-<?php echo esc_attr($atts['layout']); ?>">
            <?php foreach ($items as $cat) : ?>
                <li class="wp-cms-connector-category-item">
                    <?php echo esc_html(isset($cat['name']) ? $cat['name'] : ''); ?>
                    <?php if (! empty($cat['description'])) : ?>
                        <span class="wp-cms-connector-desc"> — <?php echo esc_html($cat['description']); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    public function shortcode_tags($atts)
    {
        $atts = shortcode_atts([
            'limit' => 20,
        ], $atts, 'cms_tags');

        $data = $this->fetch('/api/v1/tags');
        if (! is_array($data)) {
            return $this->wrap_message(__('Aucun tag disponible.', 'cms-headless-connector'));
        }
        $items = isset($data['tags']) ? $data['tags'] : (isset($data['data']) ? $data['data'] : []);
        if (! is_array($items)) {
            $items = [];
        }
        $limit = max(1, min(100, (int) $atts['limit']));
        $items = array_slice($items, 0, $limit);
        ob_start();
        ?>
        <ul class="wp-cms-connector-tags">
            <?php foreach ($items as $tag) : ?>
                <li class="wp-cms-connector-tag-item"><?php echo esc_html(isset($tag['name']) ? $tag['name'] : ''); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    private function render_articles(array $items, $layout)
    {
        ?>
        <ul class="wp-cms-connector-articles wp-cms-connector-layout-<?php echo esc_attr($layout); ?>">
            <?php foreach ($items as $a) : ?>
                <li class="wp-cms-connector-article-item">
                    <h3 class="wp-cms-connector-article-item-title"><?php echo esc_html(isset($a['title']) ? $a['title'] : ''); ?></h3>
                    <?php if (! empty($a['excerpt'])) : ?>
                        <p class="wp-cms-connector-article-item-excerpt"><?php echo esc_html($a['excerpt']); ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private function wrap_message($msg)
    {
        return '<div class="wp-cms-connector-message-box">' . esc_html($msg) . '</div>';
    }

    public function maybe_enqueue_styles()
    {
        global $post;
        if (! is_a($post, 'WP_Post')) {
            return;
        }
        $shortcodes = [ 'cms_articles', 'cms_article', 'cms_categories', 'cms_tags' ];
        foreach ($shortcodes as $sc) {
            if (has_shortcode($post->post_content, $sc)) {
                wp_enqueue_style(
                    'cms-connector-shortcodes',
                    CMS_CONNECTOR_URL . 'assets/css/shortcodes.css',
                    [],
                    CMS_CONNECTOR_VERSION
                );
                break;
            }
        }
    }
}
