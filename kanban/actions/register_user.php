<?php
// actions/register_user.php

$name = $data['name'] ?? '';
$password = $data['password'] ?? '';

if (empty($name) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El nombre y la contraseña no pueden estar vacíos.']);
    exit;
}

// 1. Verificar si el usuario ya existe
$stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['status' => 'error', 'message' => 'El nombre de usuario ya está en uso.']);
} else {
    // 2. Hashear la contraseña de forma segura
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Insertar el nuevo usuario en la base de datos
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO users (name, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $password_hash);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Usuario registrado con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el usuario.']);
    }
}
?>