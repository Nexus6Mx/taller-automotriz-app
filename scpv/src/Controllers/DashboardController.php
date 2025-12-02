<?php
/**
 * Controlador del Dashboard
 */

namespace Controllers;

use Core\Controller;
use Models\Cotizacion;
use Models\Proveedor;
use Models\Usuario;

class DashboardController extends Controller
{
    private $cotizacionModel;
    private $proveedorModel;
    private $usuarioModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        
        $this->cotizacionModel = new Cotizacion();
        $this->proveedorModel = new Proveedor();
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Dashboard principal
     */
    public function index()
    {
        $user = $this->getCurrentUser();
        
        try {
            // Obtener estadísticas según el rol
            $stats = $this->getStatsForRole($user['rol']);
            
            // Obtener actividad reciente
            $recentActivity = $this->getRecentActivity($user['rol']);
        } catch (\Exception $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            $stats = [];
            $recentActivity = [];
        }
        
        $data = [
            'title' => 'Dashboard - ' . APP_NAME,
            'user' => $user,
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'flash_messages' => $this->getFlashMessages()
        ];
        
        $this->render('dashboard.index', $data);
    }
    
    /**
     * Obtener estadísticas según rol
     */
    private function getStatsForRole($role)
    {
        $stats = [];
        
        switch ($role) {
            case 'admin':
            case 'comprador':
                $stats = [
                    'cotizaciones_activas' => $this->getCotizacionesActivas(),
                    'cotizaciones_por_estado' => $this->getCotizacionesPorEstado(),
                    'proveedores_activos' => $this->getProveedoresActivos(),
                    'total_cotizaciones_mes' => $this->getTotalCotizacionesMes(),
                    'valor_promedio_cotizaciones' => $this->getValorPromedioCotizaciones()
                ];
                break;
                
            case 'proveedor':
                $stats = [
                    'invitaciones_pendientes' => $this->getInvitacionesPendientes(),
                    'ofertas_enviadas' => $this->getOfertasEnviadas(),
                    'ofertas_aceptadas' => $this->getOfertasAceptadas(),
                    'calificacion_promedio' => $this->getCalificacionPromedio()
                ];
                break;
                
            default:
                $stats = [
                    'cotizaciones_publicas' => $this->getCotizacionesPublicas(),
                    'total_proveedores' => $this->getTotalProveedores()
                ];
        }
        
        return $stats;
    }
    
    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity($role)
    {
        switch ($role) {
            case 'admin':
            case 'comprador':
                return $this->getRecentCotizaciones();
                
            case 'proveedor':
                return $this->getRecentInvitaciones();
                
            default:
                return $this->getRecentCotizacionesPublicas();
        }
    }
    
    // Métodos para estadísticas de admin/comprador
    private function getCotizacionesActivas()
    {
        // Usar estados que existen en tu BD
        $sql = "SELECT COUNT(*) as count FROM cotizaciones WHERE estado IN ('enviada', 'recibida', 'aprobada')";
        $result = $this->cotizacionModel->query($sql)->fetch();
        return $result['count'];
    }
    
    private function getCotizacionesPorEstado()
    {
        $sql = "SELECT estado, COUNT(*) as count FROM cotizaciones GROUP BY estado";
        return $this->cotizacionModel->query($sql)->fetchAll();
    }
    
    private function getProveedoresActivos()
    {
        $sql = "SELECT COUNT(*) as count FROM proveedores WHERE activo = 1";
        $result = $this->proveedorModel->query($sql)->fetch();
        return $result['count'];
    }
    
    private function getTotalCotizacionesMes()
    {
        $sql = "SELECT COUNT(*) as count FROM cotizaciones WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $result = $this->cotizacionModel->query($sql)->fetch();
        return $result['count'];
    }
    
    private function getValorPromedioCotizaciones()
    {
        // Usar columna 'total' en lugar de 'total_estimado'
        $sql = "SELECT AVG(total) as promedio FROM cotizaciones WHERE total > 0";
        $result = $this->cotizacionModel->query($sql)->fetch();
        return $result['promedio'] ? (float)$result['promedio'] : 0.00;
    }
    
    private function getRecentCotizaciones()
    {
        // Usar columna 'usuario_id' en lugar de 'usuario_solicitante_id'
        $sql = "
            SELECT c.*, u.nombre as solicitante 
            FROM cotizaciones c 
            LEFT JOIN usuarios u ON c.usuario_id = u.id 
            ORDER BY c.created_at DESC 
            LIMIT 10
        ";
        return $this->cotizacionModel->query($sql)->fetchAll();
    }
    
    // Métodos para estadísticas de proveedor
    private function getInvitacionesPendientes()
    {
        // TODO: Implementar cuando tengamos sistema de autenticación de proveedores
        return 0;
    }
    
    private function getOfertasEnviadas()
    {
        // TODO: Implementar
        return 0;
    }
    
    private function getOfertasAceptadas()
    {
        // TODO: Implementar
        return 0;
    }
    
    private function getCalificacionPromedio()
    {
        // TODO: Implementar
        return 0.0;
    }
    
    private function getRecentInvitaciones()
    {
        // TODO: Implementar
        return [];
    }
    
    // Métodos para usuarios con acceso limitado
    private function getCotizacionesPublicas()
    {
        // Usar estado 'enviada' que sí existe
        $sql = "SELECT COUNT(*) as count FROM cotizaciones WHERE estado = 'enviada'";
        $result = $this->cotizacionModel->query($sql)->fetch();
        return $result['count'];
    }
    
    private function getTotalProveedores()
    {
        $sql = "SELECT COUNT(*) as count FROM proveedores WHERE activo = 1";
        $result = $this->proveedorModel->query($sql)->fetch();
        return $result['count'];
    }
    
    private function getRecentCotizacionesPublicas()
    {
        // Usar solo columnas que existen: folio, estado, created_at
        $sql = "
            SELECT c.* 
            FROM cotizaciones c 
            WHERE c.estado = 'enviada'
            ORDER BY c.created_at DESC 
            LIMIT 10
        ";
        return $this->cotizacionModel->query($sql)->fetchAll();
    }
}