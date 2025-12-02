<?php
/**
 * Controlador de Cotizaciones
 */

namespace Controllers;

use Core\Controller;
use Models\Cotizacion;
use Models\Proveedor;

class CotizacionController extends Controller
{
    private $cotizacionModel;
    private $proveedorModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        
        $this->cotizacionModel = new Cotizacion();
        $this->proveedorModel = new Proveedor();
    }
    
    /**
     * Listar todas las cotizaciones
     */
    public function index()
    {
        $user = $this->getCurrentUser();
        
        // Obtener cotizaciones según el rol
        if ($user['rol'] === 'admin' || $user['rol'] === 'comprador') {
            $cotizaciones = $this->cotizacionModel->all();
        } else {
            // Para proveedores, mostrar solo las que tienen invitación
            $cotizaciones = [];
        }
        
        $data = [
            'title' => 'Cotizaciones - ' . APP_NAME,
            'cotizaciones' => $cotizaciones,
            'user' => $user,
            'flash_messages' => $this->getFlashMessages()
        ];
        
        $this->render('cotizaciones.index', $data);
    }
    
    /**
     * Mostrar formulario para crear cotización
     */
    public function create()
    {
        $this->requireRole(['admin', 'comprador']);
        
        $proveedores = $this->proveedorModel->where('activo', 1);
        
        $data = [
            'title' => 'Nueva Cotización - ' . APP_NAME,
            'proveedores' => $proveedores,
            'user' => $this->getCurrentUser(),
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->render('cotizaciones.create', $data);
    }
    
    /**
     * Guardar nueva cotización
     */
    public function store()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/cotizaciones');
        }
        
        $this->requireRole(['admin', 'comprador']);
        
        $user = $this->getCurrentUser();
        
        // Validar datos
        $titulo = $this->request->post('titulo');
        $descripcion = $this->request->post('descripcion');
        $fecha_limite = $this->request->post('fecha_limite');
        $presupuesto_estimado = $this->request->post('presupuesto_estimado');
        
        $errors = $this->validate([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'fecha_limite' => $fecha_limite
        ], [
            'titulo' => 'required',
            'descripcion' => 'required',
            'fecha_limite' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->flash('error', 'Por favor completa todos los campos requeridos');
            $this->redirect('/cotizaciones/create');
        }
        
        // Generar folio único
        $folio = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Crear cotización
        $cotizacionId = $this->cotizacionModel->create([
            'folio' => $folio,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'fecha_limite' => $fecha_limite,
            'presupuesto_estimado' => $presupuesto_estimado ?? 0,
            'total_estimado' => $presupuesto_estimado ?? 0,
            'usuario_solicitante_id' => $user['id'],
            'estado' => 'borrador',
            'activo' => 1
        ]);
        
        if ($cotizacionId) {
            $this->flash('success', 'Cotización creada exitosamente');
            $this->redirect('/cotizaciones/' . $cotizacionId);
        } else {
            $this->flash('error', 'Error al crear la cotización');
            $this->redirect('/cotizaciones/create');
        }
    }
    
    /**
     * Mostrar detalle de cotización
     */
    public function show($id)
    {
        $cotizacion = $this->cotizacionModel->find($id);
        
        if (!$cotizacion) {
            $this->flash('error', 'Cotización no encontrada');
            $this->redirect('/cotizaciones');
        }
        
        $data = [
            'title' => 'Cotización ' . $cotizacion['folio'] . ' - ' . APP_NAME,
            'cotizacion' => $cotizacion,
            'user' => $this->getCurrentUser()
        ];
        
        $this->render('cotizaciones.show', $data);
    }
}
