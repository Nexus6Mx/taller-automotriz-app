<?php
/**
 * Router para manejar las rutas de la aplicación
 */

namespace Core;

class Router
{
    private $routes = [];
    
    public function get($uri, $action)
    {
        $this->routes['GET'][$uri] = $action;
    }
    
    public function post($uri, $action)
    {
        $this->routes['POST'][$uri] = $action;
    }
    
    public function put($uri, $action)
    {
        $this->routes['PUT'][$uri] = $action;
    }
    
    public function delete($uri, $action)
    {
        $this->routes['DELETE'][$uri] = $action;
    }
    
    public function dispatch(Request $request)
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        
        // Buscar ruta exacta
        if (isset($this->routes[$method][$uri])) {
            return $this->callAction($this->routes[$method][$uri], []);
        }
        
        // Buscar rutas con parámetros
        foreach ($this->routes[$method] ?? [] as $route => $action) {
            if ($params = $this->matchRoute($route, $uri)) {
                return $this->callAction($action, $params);
            }
        }
        
        // No se encontró la ruta
        $this->handleNotFound();
    }
    
    private function matchRoute($route, $uri)
    {
        // Convertir {id} a expresión regular
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $route);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remover la coincidencia completa
            return $matches;
        }
        
        return false;
    }
    
    private function callAction($action, $params)
    {
        if (strpos($action, '@') !== false) {
            [$controller, $method] = explode('@', $action);
            
            if (!class_exists($controller)) {
                throw new \Exception("Controlador {$controller} no encontrado");
            }
            
            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Método {$method} no encontrado en {$controller}");
            }
            
            return call_user_func_array([$controllerInstance, $method], $params);
        }
        
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }
        
        throw new \Exception("Acción no válida: {$action}");
    }
    
    private function handleNotFound()
    {
        header('HTTP/1.1 404 Not Found');
        include SRC_PATH . '/Views/errors/404.php';
        exit;
    }
}