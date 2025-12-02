<?php
/**
 * Controlador de Proveedores
 */

namespace Controllers;

use Core\Controller;
use Models\Proveedor;

class ProveedorController extends Controller
{
    private $proveedorModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        
        $this->proveedorModel = new Proveedor();
    }
    
    /**
     * Listar todos los proveedores
     */
    public function index()
    {
        $this->requireRole(['admin', 'comprador']);
        
        $proveedores = $this->proveedorModel->all();
        
        $data = [
            'title' => 'Proveedores - ' . APP_NAME,
            'proveedores' => $proveedores,
            'user' => $this->getCurrentUser(),
            'flash_messages' => $this->getFlashMessages()
        ];
        
        $this->render('proveedores.index', $data);
    }
    
    /**
     * Mostrar formulario para crear proveedor
     */
    public function create()
    {
        $this->requireRole(['admin', 'comprador']);
        
        $data = [
            'title' => 'Nuevo Proveedor - ' . APP_NAME,
            'user' => $this->getCurrentUser(),
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->render('proveedores.create', $data);
    }
    
    /**
     * Guardar nuevo proveedor
     */
    public function store()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/proveedores');
        }
        
        $this->requireRole(['admin', 'comprador']);
        
        // Validar datos
        $nombre = $this->request->post('nombre');
        $rfc = $this->request->post('rfc');
        $email = $this->request->post('email');
        $telefono = $this->request->post('telefono');
        $direccion = $this->request->post('direccion');
        $contacto_principal = $this->request->post('contacto_principal');
        
        $errors = $this->validate([
            'nombre' => $nombre,
            'rfc' => $rfc,
            'email' => $email
        ], [
            'nombre' => 'required',
            'rfc' => 'required',
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            $this->flash('error', 'Por favor completa todos los campos requeridos');
            $this->redirect('/proveedores/create');
        }
        
        // Crear proveedor usando columnas que existen en la BD
        $proveedorId = $this->proveedorModel->create([
            'razon_social' => $nombre,
            'rfc' => $rfc,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'contacto_nombre' => $contacto_principal,
            'contacto_telefono' => $telefono,
            'contacto_email' => $email,
            'activo' => 1
        ]);
        
        if ($proveedorId) {
            $this->flash('success', 'Proveedor registrado exitosamente');
            $this->redirect('/proveedores');
        } else {
            $this->flash('error', 'Error al registrar el proveedor');
            $this->redirect('/proveedores/create');
        }
    }
    
    /**
     * Mostrar detalles de un proveedor
     */
    public function show($id)
    {
        $this->requireRole(['admin', 'comprador']);
        
        $proveedor = $this->proveedorModel->find($id);
        
        if (!$proveedor) {
            $this->flash('error', 'Proveedor no encontrado');
            $this->redirect('/proveedores');
        }
        
        $data = [
            'title' => 'Detalles del Proveedor - ' . APP_NAME,
            'proveedor' => $proveedor,
            'user' => $this->getCurrentUser()
        ];
        
        $this->render('proveedores.show', $data);
    }
    
    /**
     * Mostrar formulario para editar proveedor
     */
    public function edit($id)
    {
        $this->requireRole(['admin', 'comprador']);
        
        $proveedor = $this->proveedorModel->find($id);
        
        if (!$proveedor) {
            $this->flash('error', 'Proveedor no encontrado');
            $this->redirect('/proveedores');
        }
        
        $data = [
            'title' => 'Editar Proveedor - ' . APP_NAME,
            'proveedor' => $proveedor,
            'user' => $this->getCurrentUser(),
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->render('proveedores.edit', $data);
    }
    
    /**
     * Actualizar proveedor
     */
    public function update($id)
    {
        if (!$this->request->isPost()) {
            $this->redirect('/proveedores');
        }
        
        $this->requireRole(['admin', 'comprador']);
        
        $proveedor = $this->proveedorModel->find($id);
        
        if (!$proveedor) {
            $this->flash('error', 'Proveedor no encontrado');
            $this->redirect('/proveedores');
        }
        
        // Validar y actualizar datos usando columnas que existen en la BD
        $data = [
            'razon_social' => $this->request->post('razon_social') ?? $this->request->post('nombre'),
            'rfc' => $this->request->post('rfc'),
            'email' => $this->request->post('email'),
            'telefono' => $this->request->post('telefono'),
            'direccion' => $this->request->post('direccion'),
            'ciudad' => $this->request->post('ciudad'),
            'estado' => $this->request->post('estado'),
            'codigo_postal' => $this->request->post('codigo_postal'),
            'contacto_nombre' => $this->request->post('contacto_nombre') ?? $this->request->post('contacto_principal'),
            'contacto_telefono' => $this->request->post('contacto_telefono') ?? $this->request->post('telefono'),
            'contacto_email' => $this->request->post('contacto_email') ?? $this->request->post('email')
        ];
        
        $success = $this->proveedorModel->update($id, $data);
        
        if ($success) {
            $this->flash('success', 'Proveedor actualizado exitosamente');
            $this->redirect('/proveedores');
        } else {
            $this->flash('error', 'Error al actualizar el proveedor');
            $this->redirect('/proveedores/' . $id . '/edit');
        }
    }
    
    /**
     * Eliminar proveedor (desactivar)
     */
    public function delete($id)
    {
        if (!$this->request->isPost()) {
            $this->redirect('/proveedores');
        }
        
        $this->requireRole(['admin']);
        
        $proveedor = $this->proveedorModel->find($id);
        
        if (!$proveedor) {
            $this->flash('error', 'Proveedor no encontrado');
            $this->redirect('/proveedores');
        }
        
        // Desactivar en lugar de eliminar
        $success = $this->proveedorModel->update($id, ['activo' => 0]);
        
        if ($success) {
            $this->flash('success', 'Proveedor desactivado exitosamente');
        } else {
            $this->flash('error', 'Error al desactivar el proveedor');
        }
        
        $this->redirect('/proveedores');
    }
}
