<?php
// api/auth/verify.php

/**
 * Verifica un token de autorización contra la base de datos.
 *
 * @param PDO $db Conexión a la base de datos.
 * @param string $token El token Bearer extraído de los encabezados.
 * @return int|false El ID del usuario si el token es válido y no ha expirado, o false en caso contrario.
 */
function verifyToken($db, $token) {
    if (empty($token)) {
        return false;
    }

    try {
        $query = "SELECT user_id FROM sessions WHERE token = :token AND expires_at > NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$session['user_id'];
        }

        return false;

    } catch (PDOException $e) {
        // En caso de un error de base de datos, no se puede verificar el token.
        return false;
    }
}
?>
