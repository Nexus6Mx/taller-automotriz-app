<?php
// actions/logout.php

// Destruye toda la información de la sesión.
session_unset();
session_destroy();

echo json_encode(['status' => 'success', 'message' => 'Sesión cerrada.']);
?>