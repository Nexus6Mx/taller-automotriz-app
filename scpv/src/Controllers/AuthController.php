<?php
/**
 * Controlador de autenticación
 */

namespace Controllers;

use Core\Controller;
use Models\Usuario;

class AuthController extends Controller
{
    private $usuarioModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $data = [
            'title' => 'Iniciar Sesión - ' . APP_NAME,
            'csrf_token' => $this->generateCsrfToken(),
            'flash_messages' => $this->getFlashMessages()
        ];
        
        $this->render('auth.login', $data, 'layouts.auth');
    }
    
    /**
     * Procesar login
     */
    public function login()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/login');
        }
        
        $email = $this->request->post('email');
        $password = $this->request->post('password');
        $remember = $this->request->post('remember');
        
        // Validar datos
        $errors = $this->validate([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->flash('error', 'Por favor corrige los errores en el formulario');
            $this->redirect('/login');
        }
        
        // Intentar autenticar
        $user = $this->usuarioModel->authenticate($email, $password);
        
        if ($user) {
            // Login exitoso
            $_SESSION['user'] = $user;
            $_SESSION['login_time'] = time();
            
            // Si seleccionó "recordarme"
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                // Aquí deberías guardar el token en la BD para mayor seguridad
            }
            
            $this->flash('success', 'Bienvenido, ' . $user['nombre']);
            $this->redirect('/dashboard');
            
        } else {
            // Login fallido
            $this->flash('error', 'Credenciales incorrectas');
            $this->redirect('/login');
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout()
    {
        // Limpiar sesión
        session_unset();
        session_destroy();
        
        // Limpiar cookie de recordar
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Iniciar nueva sesión para mensajes flash
        session_start();
        $this->flash('info', 'Sesión cerrada correctamente');
        
        $this->redirect('/login');
    }
    
    /**
     * Registro de nuevo usuario (solo para admins)
     */
    public function showRegister()
    {
        $this->requireRole(['admin']);
        
        $data = [
            'title' => 'Registrar Usuario - ' . APP_NAME,
            'csrf_token' => $this->generateCsrfToken(),
            'flash_messages' => $this->getFlashMessages(),
            'roles' => [
                'admin' => 'Administrador',
                'comprador' => 'Comprador',
                'proveedor' => 'Proveedor',
                'viewer' => 'Solo Lectura'
            ]
        ];
        
        $this->render('auth.register', $data);
    }
    
    /**
     * Procesar registro
     */
    public function register()
    {
        $this->requireRole(['admin']);
        
        if (!$this->request->isPost()) {
            $this->redirect('/auth/register');
        }
        
        $this->validateCsrfToken();
        
        $data = [
            'nombre' => trim($this->request->post('nombre')),
            'email' => trim($this->request->post('email')),
            'password' => $this->request->post('password'),
            'password_confirm' => $this->request->post('password_confirm'),
            'rol' => $this->request->post('rol'),
            'activo' => $this->request->post('activo') ? 1 : 0
        ];
        
        // Validar datos
        $errors = $this->validate($data, [
            'nombre' => 'required|min:2|max:100',
            'email' => 'required|email|max:150',
            'password' => 'required|min:6',
            'rol' => 'required'
        ]);
        
        // Validar confirmación de contraseña
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'][] = 'Las contraseñas no coinciden';
        }
        
        // Validar email único
        if ($this->usuarioModel->emailExists($data['email'])) {
            $errors['email'][] = 'Este email ya está registrado';
        }
        
        // Validar rol válido
        $rolesValidos = ['admin', 'comprador', 'proveedor', 'viewer'];
        if (!in_array($data['rol'], $rolesValidos)) {
            $errors['rol'][] = 'Rol no válido';
        }
        
        if (!empty($errors)) {
            $this->flash('error', 'Por favor corrige los errores en el formulario');
            $_SESSION['form_data'] = $data;
            $_SESSION['form_errors'] = $errors;
            $this->redirect('/auth/register');
        }
        
        // Crear usuario
        unset($data['password_confirm']);
        $userId = $this->usuarioModel->create($data);
        
        if ($userId) {
            $this->flash('success', 'Usuario registrado correctamente');
            $this->redirect('/usuarios');
        } else {
            $this->flash('error', 'Error al registrar usuario');
            $this->redirect('/auth/register');
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword()
    {
        $this->requireAuth();
        
        if ($this->request->isPost()) {
            $this->validateCsrfToken();
            
            $currentPassword = $this->request->post('current_password');
            $newPassword = $this->request->post('new_password');
            $confirmPassword = $this->request->post('confirm_password');
            
            $errors = $this->validate([
                'current_password' => $currentPassword,
                'new_password' => $newPassword
            ], [
                'current_password' => 'required',
                'new_password' => 'required|min:6'
            ]);
            
            if ($newPassword !== $confirmPassword) {
                $errors['confirm_password'][] = 'Las contraseñas no coinciden';
            }
            
            // Verificar contraseña actual
            $user = $this->usuarioModel->find($this->getCurrentUser()['id']);
            if (!password_verify($currentPassword, $user['password'] ?? '')) {
                $errors['current_password'][] = 'Contraseña actual incorrecta';
            }
            
            if (!empty($errors)) {
                $this->json(['errors' => $errors], 422);
            }
            
            // Actualizar contraseña
            $updated = $this->usuarioModel->update($user['id'], [
                'password' => $newPassword
            ]);
            
            if ($updated) {
                $this->json(['message' => 'Contraseña actualizada correctamente']);
            } else {
                $this->json(['error' => 'Error al actualizar contraseña'], 500);
            }
        }
        
        $data = [
            'title' => 'Cambiar Contraseña - ' . APP_NAME,
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->render('auth.change-password', $data);
    }
}