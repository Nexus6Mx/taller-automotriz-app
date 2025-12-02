<?php
/**
 * Modelo de Usuario
 */

namespace Models;

use Core\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol',
        'activo'
    ];
    
    protected $hidden = [
        'password'
    ];
    
    /**
     * Encriptar contraseña antes de guardar
     */
    public function create($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        return parent::create($data);
    }
    
    /**
     * Actualizar con encriptación de contraseña
     */
    public function update($id, $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        
        return parent::update($id, $data);
    }
    
    /**
     * Autenticar usuario
     */
    public function authenticate($email, $password)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? AND activo = 1";
        $user = $this->db->fetch($sql, [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Usuario autenticado correctamente
            // (No actualizamos ultimo_acceso porque esa columna no existe en la BD actual)
            
            return $this->hideFields($user);
        }
        
        return false;
    }
    
    /**
     * Obtener usuarios por rol
     */
    public function getByRole($role)
    {
        return $this->where('rol', $role);
    }
    
    /**
     * Verificar si email existe
     */
    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
}