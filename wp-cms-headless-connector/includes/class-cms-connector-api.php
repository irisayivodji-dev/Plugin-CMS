<?php

if (! defined('ABSPATH')) {
    exit;
}

class Cms_Connector_Api
{
    private $base_url;
    private $cookies = [];
    private $display_token = '';
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
        $this->base_url = rtrim(get_option('wp_cms_connector_base_url', 'http://localhost:8079'), '/');
        $saved = get_option('wp_cms_connector_cookies', []);
        if (is_array($saved)) {
            $this->cookies = $saved;
        }
        $this->display_token = get_option('wp_cms_connector_token', '');
    }

    public function set_base_url($url)
    {
        $this->base_url = rtrim(esc_url_raw($url), '/');
    }

    public function get_base_url()
    {
        return $this->base_url;
    }

    public function is_connected()
    {
        return ! empty($this->cookies) || ! empty($this->display_token);
    }

    public function get_display_token()
    {
        return $this->display_token;
    }

    public function login($login, $password, $secret_key = '')
    {
        $url = $this->base_url . '/api/v1/auth/login';
        $body = [
            'email'    => $login,
            'password' => $password,
        ];
        if ($secret_key !== '') {
            $body['secret_key'] = $secret_key;
        }

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'cookies' => [],
        ]);

        $code = wp_remote_retrieve_response_code($response);
        $res_body = wp_remote_retrieve_body($response);
        $data = json_decode($res_body, true);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        if ($code >= 200 && $code < 300) {
            $cookies = wp_remote_retrieve_cookies($response);
            $cookie_strings = [];
            foreach ($cookies as $cookie) {
                $cookie_strings[] = $cookie->name . '=' . $cookie->value;
            }
            $this->cookies = $cookie_strings;
            $token = isset($data['token']) ? $data['token'] : wp_generate_password(32, true);
            $this->display_token = $token;
            update_option('wp_cms_connector_cookies', $this->cookies);
            update_option('wp_cms_connector_token', $this->display_token);
            return [
                'success' => true,
                'message' => __('Connexion réussie.', 'cms-headless-connector'),
                'data'    => [ 'token' => $this->display_token ],
            ];
        }

        $message = __('Identifiants incorrects ou API indisponible.', 'cms-headless-connector');
        if (is_array($data) && ! empty($data['message'])) {
            $message = sanitize_text_field($data['message']);
        }
        return [
            'success' => false,
            'message' => $message,
        ];
    }

    public function logout()
    {
        $url = $this->base_url . '/api/v1/auth/logout';
        wp_remote_post($url, [
            'timeout' => 10,
            'headers' => [ 'Accept' => 'application/json' ],
            'cookies' => $this->parse_cookies_for_request(),
        ]);
        $this->cookies = [];
        $this->display_token = '';
        delete_option('wp_cms_connector_cookies');
        delete_option('wp_cms_connector_token');
    }

    private function parse_cookies_for_request()
    {
        $parsed = [];
        foreach ($this->cookies as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $parsed[trim($parts[0])] = trim($parts[1]);
            }
        }
        $out = [];
        foreach ($parsed as $name => $value) {
            $out[] = new WP_Http_Cookie([ 'name' => $name, 'value' => $value ]);
        }
        return $out;
    }

    public function get($endpoint, $use_auth = false)
    {
        $url = $this->base_url . $endpoint;
        $args = [
            'timeout' => 15,
            'headers' => [ 'Accept' => 'application/json' ],
        ];
        if ($use_auth && ! empty($this->cookies)) {
            $args['cookies'] = $this->parse_cookies_for_request();
        }
        $response = wp_remote_get($url, $args);
        return $this->parse_response($response);
    }

    public function patch($endpoint, $body = [])
    {
        $url = $this->base_url . $endpoint;
        $args = [
            'timeout' => 15,
            'method'  => 'PATCH',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ];
        if (! empty($this->cookies)) {
            $args['cookies'] = $this->parse_cookies_for_request();
        }
        $response = wp_remote_request($url, $args);
        return $this->parse_response($response);
    }

    public function post($endpoint, $body = [])
    {
        $url = $this->base_url . $endpoint;
        $args = [
            'timeout' => 15,
            'method'  => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ];
        if (! empty($this->cookies)) {
            $args['cookies'] = $this->parse_cookies_for_request();
        }
        $response = wp_remote_request($url, $args);
        return $this->parse_response($response);
    }

    private function parse_response($response)
    {
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $ok = $code >= 200 && $code < 300;
        $out = [ 'success' => $ok, 'code' => $code ];
        if ($ok) {
            $out['data'] = $data;
        } else {
            $out['message'] = is_array($data) && ! empty($data['message'])
                ? sanitize_text_field($data['message'])
                : __('Erreur API.', 'cms-headless-connector');
        }
        return $out;
    }
}
