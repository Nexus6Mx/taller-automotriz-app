<?php
/**
 * Modelo de Cotización
 */

namespace Models;

use Core\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    
    protected $fillable = [
        'folio',
        'titulo',
        'descripcion',
        'usuario_solicitante_id',
        'estado',
        'fecha_limite',
        'fecha_evaluacion',
        'moneda',
        'total_estimado',
        'observaciones'
    ];
    
    /**
     * Generar folio único
     */
    public function generarFolio()
    {
        $year = date('Y');
        $sql = "SELECT COUNT(*) + 1 as siguiente FROM {$this->table} WHERE folio LIKE ?";
        $result = $this->db->fetch($sql, ["COT-{$year}-%"]);
        
        return sprintf("COT-%s-%03d", $year, $result['siguiente']);
    }
    
    /**
     * Crear cotización con folio automático
     */
    public function create($data)
    {
        if (empty($data['folio'])) {
            $data['folio'] = $this->generarFolio();
        }
        
        return parent::create($data);
    }
    
    /**
     * Obtener cotizaciones por estado
     */
    public function getByEstado($estado)
    {
        $sql = "
            SELECT c.*, u.nombre as solicitante_nombre 
            FROM {$this->table} c
            JOIN usuarios u ON c.usuario_solicitante_id = u.id
            WHERE c.estado = ?
            ORDER BY c.created_at DESC
        ";
        
        return $this->db->fetchAll($sql, [$estado]);
    }
    
    /**
     * Obtener cotización con detalles completos
     */
    public function getWithDetails($id)
    {
        $sql = "
            SELECT 
                c.*,
                u.nombre as solicitante_nombre,
                u.email as solicitante_email,
                COUNT(DISTINCT ci.id) as total_items,
                COUNT(DISTINCT cp.proveedor_id) as proveedores_invitados,
                COUNT(DISTINCT o.id) as ofertas_recibidas
            FROM {$this->table} c
            JOIN usuarios u ON c.usuario_solicitante_id = u.id
            LEFT JOIN cotizacion_items ci ON c.id = ci.cotizacion_id
            LEFT JOIN cotizacion_proveedores cp ON c.id = cp.cotizacion_id
            LEFT JOIN ofertas o ON c.id = o.cotizacion_id
            WHERE c.id = ?
            GROUP BY c.id
        ";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Obtener items de una cotización
     */
    public function getItems($id)
    {
        $sql = "
            SELECT 
                ci.*,
                p.nombre as producto_nombre,
                cat.nombre as categoria_nombre
            FROM cotizacion_items ci
            LEFT JOIN productos p ON ci.producto_id = p.id
            LEFT JOIN categorias cat ON p.categoria_id = cat.id
            WHERE ci.cotizacion_id = ?
            ORDER BY ci.id
        ";
        
        return $this->db->fetchAll($sql, [$id]);
    }
    
    /**
     * Obtener proveedores invitados
     */
    public function getProveedoresInvitados($id)
    {
        $sql = "
            SELECT 
                cp.*,
                p.nombre as proveedor_nombre,
                p.email as proveedor_email,
                p.telefono as proveedor_telefono,
                u.nombre as invitado_por_nombre,
                o.id as oferta_id,
                o.estado as oferta_estado,
                o.total as oferta_total
            FROM cotizacion_proveedores cp
            JOIN proveedores p ON cp.proveedor_id = p.id
            JOIN usuarios u ON cp.invitado_por = u.id
            LEFT JOIN ofertas o ON cp.cotizacion_id = o.cotizacion_id AND cp.proveedor_id = o.proveedor_id
            WHERE cp.cotizacion_id = ?
            ORDER BY cp.fecha_invitacion
        ";
        
        return $this->db->fetchAll($sql, [$id]);
    }
    
    /**
     * Cambiar estado de cotización
     */
    public function cambiarEstado($id, $nuevoEstado, $observaciones = null)
    {
        $data = ['estado' => $nuevoEstado];
        
        if ($observaciones) {
            $data['observaciones'] = $observaciones;
        }
        
        if ($nuevoEstado === 'evaluacion') {
            $data['fecha_evaluacion'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Verificar si folio existe
     */
    public function folioExists($folio, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE folio = ?";
        $params = [$folio];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Obtener estadísticas de cotizaciones
     */
    public function getEstadisticas($fechaInicio = null, $fechaFin = null)
    {
        $sql = "
            SELECT 
                estado,
                COUNT(*) as cantidad,
                AVG(total_estimado) as promedio_estimado,
                SUM(total_estimado) as total_estimado
            FROM {$this->table}
        ";
        
        $params = [];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " WHERE created_at BETWEEN ? AND ?";
            $params = [$fechaInicio, $fechaFin];
        }
        
        $sql .= " GROUP BY estado";
        
        return $this->db->fetchAll($sql, $params);
    }
}