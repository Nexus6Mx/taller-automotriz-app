<?php
session_start();

require_once 'config.php';
require_once 'functions.php';

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    error_log("[API ERROR] {$errstr} in {$errfile}:{$errline}");
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno en el servidor.',
        'code' => $errno
    ]);
    exit;
});

set_exception_handler(function ($exception) {
    error_log('[API EXCEPTION] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno en el servidor.'
    ]);
    exit;
});

function get_request_payload(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $_REQUEST ?? [];
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || $rawBody === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    $errorMessage = 'JSON inválido en la solicitud.';
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMessage .= ' ' . json_last_error_msg();
        error_log('JSON decode error: ' . json_last_error_msg() . ' | Payload: ' . $rawBody);
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    exit;
}

// El manejo de subida de archivos se queda aquí, pero ahora también estará protegido.
if (isset($_POST['action']) && $_POST['action'] === 'upload_attachment') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
        exit;
    }
    
    header('Content-Type: application/json');
    $taskId = $_POST['task_id'] ?? null;
    $userId = $_SESSION['user_id'];
    $response = ['status' => 'error', 'message' => 'Solicitud inválida.'];

    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
        $response['message'] = "Error de servidor: El directorio '{$upload_dir}' no existe o no tiene permisos de escritura.";
        echo json_encode($response);
        exit;
    }
    if (isset($_FILES['attachmentFile']) && $taskId) {
        if ($_FILES['attachmentFile']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Error desconocido al subir el archivo.';
        } else {
            $fileTmpPath = $_FILES['attachmentFile']['tmp_name'];
            $fileName = basename($_FILES['attachmentFile']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $response['message'] = 'Error: Tipo de archivo no permitido.';
                echo json_encode($response);
                exit;
            }

            $dest_path = $upload_dir . md5(time() . $fileName) . '.' . $fileExtension;
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $conn = db_connect($servername, $username, $password, $dbname, $dbport);
                $stmt = $conn->prepare("INSERT INTO attachments (task_id, file_name, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $taskId, $fileName, $dest_path);
                if ($stmt->execute()) {
                    log_activity($conn, $taskId, $userId, "adjuntó el archivo: " . $fileName);
                    $response = ['status' => 'success', 'message' => 'File uploaded.', 'file' => ['id' => $stmt->insert_id, 'file_name' => $fileName, 'file_path' => $dest_path]];
                } else { $response['message'] = 'Failed to save file info.'; }
                $stmt->close(); $conn->close();
            } else { $response['message'] = 'Error moving file.'; }
        }
    }
    echo json_encode($response); exit;
}

// --- LÓGICA PRINCIPAL DE LA API ---
header('Content-Type: application/json');
$data = get_request_payload();
$action = $_REQUEST['action'] ?? ($data['action'] ?? '');
if (empty($action)) {
    http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Action parameter missing.']); exit;
}

$public_actions = ['login', 'register_user'];
if (!in_array($action, $public_actions) && !isset($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']); exit;
}

$action_file = __DIR__ . '/actions/' . $action . '.php';

if (file_exists($action_file)) {
    try {
        $conn = db_connect($servername, $username, $password, $dbname, $dbport);

        require $action_file;
        $conn->close();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => "Action '$action' not found"]);
}
exit;
?>