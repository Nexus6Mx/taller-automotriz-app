<?php
/**
 * Controlador de Usuarios
 */

namespace Controllers;

use Core\Controller;
use Models\Usuario;

class UsuarioController extends Controller
{
    private $usuarioModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin']); // Solo administradores
        
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        $usuarios = $this->usuarioModel->all();
        
        $data = [
            'title' => 'Usuarios - ' . APP_NAME,
            'usuarios' => $usuarios,
            'user' => $this->getCurrentUser(),
            'flash_messages' => $this->getFlashMessages()
        ];
        
        $this->render('usuarios.index', $data);
    }
    
    /**
     * Mostrar formulario para crear usuario
     */
    public function create()
    {
        $data = [
            'title' => 'Nuevo Usuario - ' . APP_NAME,
            'user' => $this->getCurrentUser(),
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->render('usuarios.create', $data);
    }
    
    /**
     * Guardar nuevo usuario
     */
    public function store()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/usuarios');
        }
        
        // Validar datos
        $nombre = $this->request->post('nombre');
        $email = $this->request->post('email');
        $password = $this->request->post('password');
        $rol = $this->request->post('rol');
        
        $errors = $this->validate([
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'rol' => $rol
        ], [
            'nombre' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'rol' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->flash('error', 'Por favor completa todos los campos requeridos');
            $this->redirect('/usuarios/create');
        }
        
        // Verificar si el email ya existe
        if ($this->usuarioModel->emailExists($email)) {
            $this->flash('error', 'El email ya está registrado');
            $this->redirect('/usuarios/create');
        }
        
        // Crear usuario
        $usuarioId = $this->usuarioModel->create([
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'rol' => $rol,
            'activo' => 1
        ]);
        
        if ($usuarioId) {
            $this->flash('success', 'Usuario creado exitosamente');
            $this->redirect('/usuarios');
        } else {
            $this->flash('error', 'Error al crear el usuario');
            $this->redirect('/usuarios/create');
        }
    }
    
    /**
     * Mostrar formulario para editar usuario
     */
    public function edit($id)
    {
        $usuario = $this->usuarioModel->find($id);
        
        if (!$usuario) {
            $this->flash('error', 'Usuario no encontrado');
            $this->redirect('/usuarios');
        }
        
        $data = [
            'title' => 'Editar Usuario - ' . APP_NAME,
            'usuario' => $usuario,
            'user' => $this->getCurrentUser(),
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->render('usuarios.edit', $data);
    }
    
    /**
     * Actualizar usuario
     */
    public function update($id)
    {
        if (!$this->request->isPost()) {
            $this->redirect('/usuarios');
        }
        
        $nombre = $this->request->post('nombre');
        $email = $this->request->post('email');
        $rol = $this->request->post('rol');
        $activo = $this->request->post('activo', 1);
        
        $data = [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol,
            'activo' => $activo
        ];
        
        // Si se proporciona nueva contraseña
        $password = $this->request->post('password');
        if (!empty($password)) {
            $data['password'] = $password;
        }
        
        if ($this->usuarioModel->update($id, $data)) {
            $this->flash('success', 'Usuario actualizado exitosamente');
        } else {
            $this->flash('error', 'Error al actualizar el usuario');
        }
        
        $this->redirect('/usuarios');
    }
    
    /**
     * Eliminar usuario (desactivar)
     */
    public function delete($id)
    {
        $currentUser = $this->getCurrentUser();
        
        // No permitir que se elimine a sí mismo
        if ($id == $currentUser['id']) {
            $this->flash('error', 'No puedes eliminar tu propio usuario');
            $this->redirect('/usuarios');
        }
        
        if ($this->usuarioModel->update($id, ['activo' => 0])) {
            $this->flash('success', 'Usuario desactivado exitosamente');
        } else {
            $this->flash('error', 'Error al desactivar el usuario');
        }
        
        $this->redirect('/usuarios');
    }
}
