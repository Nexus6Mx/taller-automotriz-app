<?php
/**
 * Controlador de Perfil de Usuario
 */

namespace Controllers;

use Core\Controller;
use Models\Usuario;

class PerfilController extends Controller
{
    private $usuarioModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Mostrar perfil del usuario actual
     */
    public function index()
    {
        $user = $this->getCurrentUser();
        
        $data = [
            'title' => 'Mi Perfil - ' . APP_NAME,
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
            'flash_messages' => $this->getFlashMessages()
        ];
        
        $this->render('perfil.index', $data);
    }
    
    /**
     * Actualizar perfil
     */
    public function update()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/perfil');
        }
        
        $user = $this->getCurrentUser();
        
        $nombre = $this->request->post('nombre');
        $email = $this->request->post('email');
        
        // Validar datos
        $errors = $this->validate([
            'nombre' => $nombre,
            'email' => $email
        ], [
            'nombre' => 'required',
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            $this->flash('error', 'Por favor completa todos los campos correctamente');
            $this->redirect('/perfil');
        }
        
        // Verificar si el email ya existe (excepto el propio)
        if ($email !== $user['email'] && $this->usuarioModel->emailExists($email, $user['id'])) {
            $this->flash('error', 'El email ya está en uso por otro usuario');
            $this->redirect('/perfil');
        }
        
        // Actualizar datos
        $updateData = [
            'nombre' => $nombre,
            'email' => $email
        ];
        
        if ($this->usuarioModel->update($user['id'], $updateData)) {
            // Actualizar sesión
            $_SESSION['user']['nombre'] = $nombre;
            $_SESSION['user']['email'] = $email;
            
            $this->flash('success', 'Perfil actualizado exitosamente');
        } else {
            $this->flash('error', 'Error al actualizar el perfil');
        }
        
        $this->redirect('/perfil');
    }
    
    /**
     * Mostrar formulario cambiar contraseña
     */
    public function showChangePassword()
    {
        $data = [
            'title' => 'Cambiar Contraseña - ' . APP_NAME,
            'user' => $this->getCurrentUser(),
            'csrf_token' => $this->generateCsrfToken(),
            'flash_messages' => $this->getFlashMessages()
        ];
        
        $this->render('perfil.change-password', $data);
    }
    
    /**
     * Procesar cambio de contraseña
     */
    public function changePassword()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/perfil/change-password');
        }
        
        $user = $this->getCurrentUser();
        
        $currentPassword = $this->request->post('current_password');
        $newPassword = $this->request->post('new_password');
        $confirmPassword = $this->request->post('confirm_password');
        
        // Validar campos
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->flash('error', 'Todos los campos son obligatorios');
            $this->redirect('/perfil/change-password');
        }
        
        // Verificar que las contraseñas nuevas coincidan
        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'Las contraseñas nuevas no coinciden');
            $this->redirect('/perfil/change-password');
        }
        
        // Validar longitud mínima
        if (strlen($newPassword) < 6) {
            $this->flash('error', 'La contraseña debe tener al menos 6 caracteres');
            $this->redirect('/perfil/change-password');
        }
        
        // Obtener usuario completo de la BD
        $usuarioDB = $this->usuarioModel->find($user['id']);
        
        // Verificar contraseña actual
        if (!password_verify($currentPassword, $usuarioDB['password'])) {
            $this->flash('error', 'La contraseña actual es incorrecta');
            $this->redirect('/perfil/change-password');
        }
        
        // Actualizar contraseña
        if ($this->usuarioModel->update($user['id'], ['password' => $newPassword])) {
            $this->flash('success', 'Contraseña actualizada exitosamente');
            $this->redirect('/perfil');
        } else {
            $this->flash('error', 'Error al actualizar la contraseña');
            $this->redirect('/perfil/change-password');
        }
    }
}
