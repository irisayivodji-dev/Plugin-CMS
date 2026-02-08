<?php

namespace App\Lib\Http;

class Response {
    private string $content;
    private int $status;
    private array $headers;

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }
    public function setContent(string $content): void {
        $this->content = $content;
    }

    public function getContent(): string {
        return $this->content;
    }
    
    public function setStatus(int $status): void{
        $this->status = $status;
    }
    
    public function getStatus(): int {
        return $this->status;
    }
    
    public function setHeaders(array $headers): void{
        $this->headers = $headers;
    }

    public function addHeader(string $name, string $value): void{
        $this->headers[$name] = $value;
    }
    
    public function getHeaders(): array {
        return $this->headers;
    }

    public function getHeadersAsString(): string {
        $headersAsString = '';
        foreach($this->getHeaders() as $headerName => $headerValue) {
            $headersAsString .= "$headerName: $headerValue\n";
        }

        return $headersAsString;
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self(
            '',
            $status,
            ['Location' => $url]
        );
    }

}


?>
