<?php
function log_audit($db, $actor_user_id, $action, $target_type = null, $target_id = null, $details = null) {
    try {
        $stmt = $db->prepare("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, details) VALUES (:actor, :action, :target_type, :target_id, :details)");
        $stmt->bindParam(':actor', $actor_user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':target_type', $target_type);
        $stmt->bindParam(':target_id', $target_id);
        $stmt->bindParam(':details', $details);
        $stmt->execute();
    } catch (Exception $e) {
        // No hacer nada si falla el log para no afectar la operación principal
    }
}
?>
