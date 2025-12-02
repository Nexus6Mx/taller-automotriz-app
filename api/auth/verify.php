<?php
// api/auth/verify.php

/**
 * Verifica un token de autorización contra la base de datos.
 *
 * @param PDO $db Conexión a la base de datos.
 * @param string $token El token Bearer extraído de los encabezados.
 * @return int|false El ID del usuario si el token es válido y no ha expirado, o false en caso contrario.
 */
/**
 * verifyToken: verifica un token y devuelve información del usuario asociado.
 * Retorna false si el token no es válido o expiró.
 * Retorna un array asociativo con keys: id, email, role, active cuando es válido.
 * Mantiene compatibilidad: si se desea sólo el id, se puede usar ['id'] del resultado.
 */
function verifyToken($db, $token) {
    if (empty($token)) {
        return false;
    }

    try {
        // Buscar sesión válida
        $query = "SELECT s.user_id, u.email, u.role, IFNULL(u.active,1) as active FROM sessions s JOIN users u ON s.user_id = u.id WHERE s.token = :token AND s.expires_at > NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'id' => (int)$row['user_id'],
                'email' => $row['email'],
                'role' => isset($row['role']) ? $row['role'] : 'Operador',
                'active' => (int)$row['active'] === 1
            ];
        }

        return false;

    } catch (PDOException $e) {
        return false;
    }
}
?>
