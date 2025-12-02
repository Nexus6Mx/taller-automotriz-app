<?php
/**
 * Controlador base con funcionalidades comunes
 */

namespace Core;

abstract class Controller
{
    protected $request;
    
    public function __construct()
    {
        $this->request = new Request();
        $this->checkSession();
    }
    
    /**
     * Renderizar vista
     */
    protected function view($view, $data = [])
    {
        extract($data);
        
        $viewFile = SRC_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new \Exception("Vista no encontrada: {$view}");
        }
    }
    
    /**
     * Renderizar vista con layout
     */
    protected function render($view, $data = [], $layout = 'layouts.app')
    {
        $data['content'] = $this->getViewContent($view, $data);
        $this->view($layout, $data);
    }
    
    /**
     * Obtener contenido de vista sin renderizar
     */
    protected function getViewContent($view, $data = [])
    {
        ob_start();
        extract($data);
        
        $viewFile = SRC_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new \Exception("Vista no encontrada: {$view}");
        }
        
        return ob_get_clean();
    }
    
    /**
     * Respuesta JSON
     */
    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redireccionar
     */
    protected function redirect($url, $statusCode = 302)
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    /**
     * Verificar autenticación
     */
    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }
    
    /**
     * Verificar rol específico
     */
    protected function requireRole($roles)
    {
        $this->requireAuth();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        $userRole = $_SESSION['user']['rol'] ?? null;
        
        if (!in_array($userRole, $roles)) {
            $this->json(['error' => 'Acceso denegado'], 403);
        }
    }
    
    /**
     * Verificar si está autenticado
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
    
    /**
     * Obtener usuario actual
     */
    protected function getCurrentUser()
    {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Validar token CSRF
     */
    protected function validateCsrfToken()
    {
        $token = $this->request->post('_token');
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            $this->json(['error' => 'Token CSRF inválido'], 422);
        }
    }
    
    /**
     * Generar token CSRF
     */
    protected function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verificar sesión y timeout
     */
    private function checkSession()
    {
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Agregar mensaje flash
     */
    protected function flash($type, $message)
    {
        $_SESSION['flash'][$type][] = $message;
    }
    
    /**
     * Obtener mensajes flash
     */
    protected function getFlashMessages()
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
    
    /**
     * Validar datos de entrada
     */
    protected function validate($data, $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRules = explode('|', $fieldRules);
            
            foreach ($fieldRules as $rule) {
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $ruleValue] = explode(':', $rule, 2);
                } else {
                    $ruleName = $rule;
                    $ruleValue = null;
                }
                
                $error = $this->validateRule($field, $value, $ruleName, $ruleValue);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar regla específica
     */
    private function validateRule($field, $value, $rule, $ruleValue = null)
    {
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    return "El campo {$field} es obligatorio";
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "El campo {$field} debe ser un email válido";
                }
                break;
                
            case 'min':
                if ($value && strlen($value) < $ruleValue) {
                    return "El campo {$field} debe tener al menos {$ruleValue} caracteres";
                }
                break;
                
            case 'max':
                if ($value && strlen($value) > $ruleValue) {
                    return "El campo {$field} no debe exceder {$ruleValue} caracteres";
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return "El campo {$field} debe ser numérico";
                }
                break;
        }
        
        return null;
    }
}