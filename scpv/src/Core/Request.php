<?php
/**
 * Clase para manejar las peticiones HTTP
 */

namespace Core;

class Request
{
    private $method;
    private $uri;
    private $params;
    private $body;
    
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $this->parseUri();
        $this->params = $_GET;
        $this->body = $this->parseBody();
    }
    
    public function getMethod()
    {
        return $this->method;
    }
    
    public function getUri()
    {
        return $this->uri;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getParam($key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
    
    public function getBody()
    {
        return $this->body;
    }
    
    public function get($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    public function post($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
    
    public function file($key)
    {
        return $_FILES[$key] ?? null;
    }
    
    public function isPost()
    {
        return $this->method === 'POST';
    }
    
    public function isGet()
    {
        return $this->method === 'GET';
    }
    
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    private function parseUri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remover query string
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        
        return rtrim($uri, '/') ?: '/';
    }
    
    private function parseBody()
    {
        if ($this->method === 'POST') {
            return $_POST;
        }
        
        $input = file_get_contents('php://input');
        
        if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            return json_decode($input, true) ?: [];
        }
        
        parse_str($input, $data);
        return $data;
    }
}