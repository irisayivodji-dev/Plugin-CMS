<?php

namespace App\Lib\Http;

class Request {
    private string $uri;
    private string $path;
    private string $method;
    private array $headers;
    private array $slugs;
    private array $urlParams;
    private string $payload;

    public function __construct() {
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->path = parse_url($this->uri, PHP_URL_PATH);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->headers = getallheaders();
        $this->urlParams = $_GET;
        $this->payload = file_get_contents('php://input');
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function addSlug(string $key, string $value): self {
        $this->slugs[$key] = $value;
        
        return $this;
    }

    public function getSlugs(): array {
        return $this->slugs;
    }

    public function getSlug(string $key): string {
        if(!isset($this->slugs[$key])) {
            return '';
        }
        
        return $this->slugs[$key];
    }

    public function getUrlParams(): array {
        return $this->urlParams;
    }
    
    public function getHeaders(): array {
        return $this->headers;
    }

    public function getPayload(): string {
        return $this->payload;
    }

    public function getPost(): array
    {
        return $_POST;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }
}
