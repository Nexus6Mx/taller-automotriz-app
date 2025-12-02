<?php
// api/orders/invoice_recipients.php

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../config/database.php';
require_once '../utils/cors.php';
require_once '../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

// Safe header extraction with polyfill for getallheaders
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = $authHeader ? str_replace('Bearer ', '', $authHeader) : '';
// Fallbacks: allow token via cookie, query string or JSON body (shared hosting may strip Authorization headers)
if (!$token) {
    if (!empty($_COOKIE['authToken'])) {
        $token = $_COOKIE['authToken'];
    }
}
if (!$token) {
    if (!empty($_GET['token'])) {
        $token = $_GET['token'];
    } else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $j = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($j['token'])) {
                $token = $j['token'];
            }
        }
    }
}

    $user = verifyToken($db, $token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(["message" => "Acceso no autorizado."]);
        exit();
    }
    $user_id = $user['id'];
    $user_active = isset($user['active']) ? $user['active'] : true;
    if (!$user_active) {
        http_response_code(403);
        echo json_encode(["message"=>"Usuario desactivado."]);
        exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Ensure table exists (in case migration wasn't applied)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS invoice_recipients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_invoice_recipients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar la tabla de destinatarios: ' . $e->getMessage()]);
    exit;
}

// Determine admin user id by role
$adminUserId = null;
try {
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'Administrador' ORDER BY id ASC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $adminUserId = (int)$row['id'];
} catch (Exception $e) {
    // ignore; will fall back to current user
}

$user_role = isset($user['role']) ? $user['role'] : 'Operador';
$isAdmin = ($user_role === 'Administrador');

if ($method === 'GET') {
    // Always read from the admin list if available; else fallback to current user
    $targetUserId = $adminUserId ?: $user_id;
    try {
        $stmt = $db->prepare('SELECT email FROM invoice_recipients WHERE user_id = :user_id ORDER BY id ASC');
        $stmt->execute(['user_id' => $targetUserId]);
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al obtener correos.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $emails,
        'managedBy' => $adminUserId ? 'admin' : 'user'
    ]);
    exit;
}

if ($method === 'POST' || $method === 'PUT') {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo un administrador puede modificar los correos de facturación.']);
        exit;
    }
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);

    if (!$payload || !isset($payload['emails']) || !is_array($payload['emails'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato inválido. Enviar { "emails": ["correo1", "correo2"] }']);
        exit;
    }

    $emails = array_values(array_unique(array_map('trim', $payload['emails'])));
    $validEmails = [];
    foreach ($emails as $email) {
        if ($email === '') {
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => "Correo inválido: $email"]);
            exit;
        }
        $validEmails[] = $email;
    }

    if (empty($validEmails)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe proporcionar al menos un correo válido.']);
        exit;
    }

    try {
        $db->beginTransaction();

    // Write to admin user list (shared for whole account); if no admin found, use current user
    $targetUserId = $adminUserId ?: $user_id;
        $deleteStmt = $db->prepare('DELETE FROM invoice_recipients WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $targetUserId]);

        $insertStmt = $db->prepare('INSERT INTO invoice_recipients (user_id, email) VALUES (:user_id, :email)');
        foreach ($validEmails as $email) {
            $insertStmt->execute([
                'user_id' => $targetUserId,
                'email' => $email
            ]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar correos: ' . $e->getMessage()]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Correos de facturación actualizados.', 'data' => $validEmails]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
