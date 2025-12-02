<?php
/**
 * Aplicación principal del sistema
 */

namespace Core;

class Application
{
    private $router;
    private $request;
    
    public function __construct()
    {
        $this->request = new Request();
        $this->router = new Router();
        $this->setupSession();
        $this->registerRoutes();
    }
    
    public function run()
    {
        try {
            $this->router->dispatch($this->request);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }
    
    private function setupSession()
    {
        // Solo configurar la sesión si no está ya iniciada
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Verificar timeout de sesión
            if (isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
                session_unset();
                session_destroy();
                if (!headers_sent()) {
                    session_start();
                }
            }
            
            $_SESSION['last_activity'] = time();
        }
    }
    
    private function registerRoutes()
    {
        // Rutas públicas
        $this->router->get('/', 'Controllers\HomeController@index');
        $this->router->get('/login', 'Controllers\AuthController@showLogin');
        $this->router->post('/login', 'Controllers\AuthController@login');
        $this->router->get('/logout', 'Controllers\AuthController@logout');
        
        // Rutas protegidas
        $this->router->get('/dashboard', 'Controllers\DashboardController@index');
        $this->router->get('/cotizaciones', 'Controllers\CotizacionController@index');
        $this->router->get('/cotizaciones/create', 'Controllers\CotizacionController@create');
        $this->router->post('/cotizaciones', 'Controllers\CotizacionController@store');
        $this->router->get('/cotizaciones/{id}', 'Controllers\CotizacionController@show');
        
        $this->router->get('/proveedores', 'Controllers\ProveedorController@index');
        $this->router->get('/proveedores/create', 'Controllers\ProveedorController@create');
        $this->router->post('/proveedores', 'Controllers\ProveedorController@store');
        $this->router->get('/proveedores/{id}/edit', 'Controllers\ProveedorController@edit');
        $this->router->post('/proveedores/{id}/update', 'Controllers\ProveedorController@update');
        $this->router->post('/proveedores/{id}/delete', 'Controllers\ProveedorController@delete');
        $this->router->get('/proveedores/{id}', 'Controllers\ProveedorController@show');
        
        $this->router->get('/productos', 'Controllers\ProductoController@index');
        $this->router->get('/productos/create', 'Controllers\ProductoController@create');
        $this->router->post('/productos', 'Controllers\ProductoController@store');
        $this->router->get('/productos/{id}/edit', 'Controllers\ProductoController@edit');
        $this->router->post('/productos/{id}/update', 'Controllers\ProductoController@update');
        $this->router->post('/productos/{id}/delete', 'Controllers\ProductoController@delete');
        
        $this->router->get('/categorias', 'Controllers\CategoriaController@index');
        $this->router->get('/categorias/create', 'Controllers\CategoriaController@create');
        $this->router->post('/categorias', 'Controllers\CategoriaController@store');
        $this->router->get('/categorias/{id}/edit', 'Controllers\CategoriaController@edit');
        $this->router->post('/categorias/{id}/update', 'Controllers\CategoriaController@update');
        $this->router->post('/categorias/{id}/delete', 'Controllers\CategoriaController@delete');
        
        $this->router->get('/usuarios', 'Controllers\UsuarioController@index');
        $this->router->get('/usuarios/create', 'Controllers\UsuarioController@create');
        $this->router->post('/usuarios', 'Controllers\UsuarioController@store');
        $this->router->get('/usuarios/{id}/edit', 'Controllers\UsuarioController@edit');
        $this->router->post('/usuarios/{id}/update', 'Controllers\UsuarioController@update');
        $this->router->post('/usuarios/{id}/delete', 'Controllers\UsuarioController@delete');
        
        $this->router->get('/reportes', 'Controllers\ReporteController@index');
        $this->router->get('/configuracion', 'Controllers\ConfiguracionController@index');
        
        $this->router->get('/perfil', 'Controllers\PerfilController@index');
        $this->router->post('/perfil/update', 'Controllers\PerfilController@update');
        $this->router->get('/perfil/change-password', 'Controllers\PerfilController@showChangePassword');
        $this->router->post('/perfil/change-password', 'Controllers\PerfilController@changePassword');
    }
    
    private function handleError($e)
    {
        error_log('Error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
        
        if (APP_ENV === 'development') {
            echo '<pre>' . $e->getMessage() . '</pre>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            include SRC_PATH . '/Views/errors/500.php';
        }
    }
}