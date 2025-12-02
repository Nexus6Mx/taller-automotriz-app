<?php
/**
 * Controlador de Reportes
 */

namespace Controllers;

use Core\Controller;
use Models\Cotizacion;
use Models\Proveedor;
use Models\Usuario;

class ReporteController extends Controller
{
    private $cotizacionModel;
    private $proveedorModel;
    private $usuarioModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin']); // Solo administradores
        
        $this->cotizacionModel = new Cotizacion();
        $this->proveedorModel = new Proveedor();
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Dashboard de reportes
     */
    public function index()
    {
        try {
            // Estadísticas generales usando solo columnas que existen
            $stats = [
                'total_cotizaciones' => $this->getTotalCotizaciones(),
                'total_proveedores' => $this->getTotalProveedores(),
                'total_usuarios' => $this->getTotalUsuarios(),
                'cotizaciones_mes' => $this->getCotizacionesMes(),
                'valor_total_mes' => $this->getValorTotalMes(),
                'cotizaciones_por_estado' => $this->getCotizacionesPorEstado(),
                'proveedores_activos' => $this->getProveedoresActivos()
            ];
        } catch (\Exception $e) {
            error_log("Error en reportes: " . $e->getMessage());
            $stats = [
                'total_cotizaciones' => 0,
                'total_proveedores' => 0,
                'total_usuarios' => 0,
                'cotizaciones_mes' => 0,
                'valor_total_mes' => 0,
                'cotizaciones_por_estado' => [],
                'proveedores_activos' => 0
            ];
        }
        
        $data = [
            'title' => 'Reportes y Estadísticas - ' . APP_NAME,
            'stats' => $stats,
            'user' => $this->getCurrentUser()
        ];
        
        $this->render('reportes.index', $data);
    }
    
    private function getTotalCotizaciones()
    {
        $sql = "SELECT COUNT(*) as total FROM cotizaciones";
        $result = $this->cotizacionModel->query($sql)->fetch();
        return $result['total'];
    }
    
    private function getTotalProveedores()
    {
        $sql = "SELECT COUNT(*) as total FROM proveedores";
        $result = $this->proveedorModel->query($sql)->fetch();
        return $result['total'];
    }
    
    private function getTotalUsuarios()
    {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1";
        $result = $this->usuarioModel->query($sql)->fetch();
        return $result['total'];
    }
    
    private function getCotizacionesMes()
    {
        $sql = "SELECT COUNT(*) as total FROM cotizaciones WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
        $result = $this->cotizacionModel->query($sql)->fetch();
        return $result['total'];
    }
    
    private function getValorTotalMes()
    {
        // Usar columna 'total' que sí existe en la tabla actual
        $sql = "SELECT SUM(total) as total FROM cotizaciones WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
        $result = $this->cotizacionModel->query($sql)->fetch();
        return $result['total'] ?? 0;
    }
    
    private function getCotizacionesPorEstado()
    {
        $sql = "SELECT estado, COUNT(*) as cantidad FROM cotizaciones GROUP BY estado";
        return $this->cotizacionModel->query($sql)->fetchAll();
    }
    
    private function getProveedoresActivos()
    {
        $sql = "SELECT COUNT(*) as total FROM proveedores WHERE activo = 1";
        $result = $this->proveedorModel->query($sql)->fetch();
        return $result['total'];
    }
}
