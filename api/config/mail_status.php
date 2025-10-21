<?php
// api/config/mail_status.php
// Admin-only endpoint to inspect mail transport configuration and environment

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

// Extract Authorization header safely (shared hosting may vary)
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = $authHeader ? str_replace('Bearer ', '', $authHeader) : '';

$user = verifyToken($db, $token);
if (!$user) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

$role = isset($user['role']) ? $user['role'] : 'Operador';
if ($role !== 'Administrador') {
    http_response_code(403);
    echo json_encode(["message" => "Solo administradores pueden consultar esta información."]);
    exit();
}

// Load config if present
$configFile = __DIR__ . '/mail.php';
$exampleFile = __DIR__ . '/mail.example.php';
$mailCfg = null;
$configSource = 'default';
if (file_exists($configFile)) {
    $mailCfg = require $configFile;
    $configSource = 'mail.php';
} elseif (file_exists($exampleFile)) {
    $mailCfg = require $exampleFile;
    $configSource = 'mail.example.php';
}

$transport = is_array($mailCfg) && !empty($mailCfg['transport']) ? strtolower($mailCfg['transport']) : 'mail';
$smtp = $mailCfg['smtp'] ?? [];
$phpMailerPath = __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
$phpMailerAvailable = file_exists($phpMailerPath);

$result = [
    'configSource' => $configSource,
    'configPresent' => file_exists($configFile),
    'transport' => $transport,
    'phpMailerAvailable' => $phpMailerAvailable,
    'smtp' => [
        'host' => $smtp['host'] ?? null,
        'port' => $smtp['port'] ?? null,
        'secure' => $smtp['secure'] ?? null,
        'from' => [
            'address' => $smtp['from']['address'] ?? null,
            'name' => $smtp['from']['name'] ?? null,
        ],
        // Intentionally omit username/password for security
    ],
];

echo json_encode(['success' => true, 'data' => $result]);
