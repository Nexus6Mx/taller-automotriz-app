<?php
/**
 * Controlador principal/home
 */

namespace Controllers;

use Core\Controller;

class HomeController extends Controller
{
    /**
     * Página de inicio
     */
    public function index()
    {
        // Si está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $data = [
            'title' => APP_NAME,
            'version' => APP_VERSION
        ];
        
        $this->view('home.index', $data);
    }
}