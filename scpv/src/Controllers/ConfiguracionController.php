<?php
/**
 * Controlador de Configuración
 */

namespace Controllers;

use Core\Controller;

class ConfiguracionController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin']); // Solo administradores
    }
    
    /**
     * Página de configuración
     */
    public function index()
    {
        $data = [
            'title' => 'Configuración del Sistema - ' . APP_NAME,
            'user' => $this->getCurrentUser(),
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->render('configuracion.index', $data);
    }
}
