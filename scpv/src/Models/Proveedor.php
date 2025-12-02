<?php
/**
 * Modelo de Proveedor
 */

namespace Models;

use Core\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    
    protected $fillable = [
        'usuario_id',
        'razon_social',
        'rfc',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'contacto_nombre',
        'contacto_telefono',
        'contacto_email',
        'calificacion',
        'activo'
    ];
    
    /**
     * Obtener proveedores activos
     */
    public function getActivos()
    {
        return $this->where('activo', 1);
    }
    
    /**
     * Buscar proveedores por nombre
     */
    public function buscarPorNombre($nombre)
    {
        $sql = "SELECT * FROM {$this->table} WHERE razon_social LIKE ? AND activo = 1 ORDER BY razon_social";
        return $this->db->fetchAll($sql, ['%' . $nombre . '%']);
    }
    
    /**
     * Verificar si RFC existe
     */
    public function rfcExists($rfc, $excludeId = null)
    {
        if (empty($rfc)) return false;
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE rfc = ?";
        $params = [$rfc];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Obtener proveedores con mejor calificación
     */
    public function getTopRated($limit = 10)
    {
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY calificacion DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Actualizar calificación promedio
     */
    public function updateCalificacion($id)
    {
        $sql = "
            UPDATE {$this->table} 
            SET calificacion = (
                SELECT COALESCE(AVG(calificacion), 0) 
                FROM ofertas 
                WHERE proveedor_id = ? AND calificacion IS NOT NULL
            ) 
            WHERE id = ?
        ";
        
        return $this->db->execute($sql, [$id, $id]);
    }
    
    /**
     * Obtener estadísticas del proveedor
     */
    public function getEstadisticas($id)
    {
        $sql = "
            SELECT 
                p.razon_social as nombre,
                p.calificacion,
                0 as total_ofertas,
                0 as ofertas_aceptadas,
                0 as promedio_ofertas,
                NULL as ultima_oferta
            FROM {$this->table} p
            WHERE p.id = ?
        ";
        
        return $this->db->fetch($sql, [$id]);
    }
}