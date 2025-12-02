<?php
// actions/get_board_data.php

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$board_id = $_GET['board_id'] ?? null;

// Si no se proporciona un board_id, busca el primer tablero disponible.
if (!$board_id) {
    $stmt = $conn->prepare("SELECT id FROM boards ORDER BY id LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($board = $result->fetch_assoc()) {
        $board_id = $board['id'];
    } else {
        // No hay tableros en el sistema. Devuelve una estructura vacía.
        echo json_encode(['status' => 'success', 'data' => ['board_id' => null, 'columns' => [], 'users' => [], 'tags' => []]]);
        exit;
    }
    $stmt->close();
}

// Se elimina la verificación de propiedad para permitir el acceso a todos.

// --- Carga de datos del tablero ---
$response_data = ['board_id' => (int)$board_id, 'columns' => [], 'users' => [], 'tags' => []];

// Cargar usuarios y etiquetas (siguen siendo globales por ahora)
$users_query = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
while ($row = $users_query->fetch_assoc()) { $response_data['users'][] = $row; }
$tags_query = $conn->query("SELECT * FROM tags ORDER BY name ASC");
while ($row = $tags_query->fetch_assoc()) { $response_data['tags'][] = $row; }

// Cargar columnas SÓLO para el tablero actual
$columns_stmt = $conn->prepare("SELECT * FROM `columns` WHERE board_id = ? ORDER BY position ASC");
$columns_stmt->bind_param("i", $board_id);
$columns_stmt->execute();
$columns_result = $columns_stmt->get_result();

while ($column = $columns_result->fetch_assoc()) {
    $column['tasks'] = [];
    $tasks_stmt = $conn->prepare("SELECT t.*, u.name as user_name FROM tasks t LEFT JOIN users u ON t.user_id = u.id WHERE t.column_id = ? ORDER BY t.position ASC");
    $tasks_stmt->bind_param("i", $column['id']);
    $tasks_stmt->execute();
    $tasks_result = $tasks_stmt->get_result();
    while ($task = $tasks_result->fetch_assoc()) {
        $task_id = $task['id'];
        $task['subtasks'] = []; $task['attachments'] = []; $task['comments'] = []; $task['tags'] = []; $task['activity_log'] = [];
        
        // Las siguientes consultas anidadas (N+1) son ineficientes, pero se mantienen por ahora para no romper la lógica existente.
        $subtask_stmt = $conn->prepare("SELECT * FROM subtasks WHERE task_id = ?");
        $subtask_stmt->bind_param("i", $task_id); $subtask_stmt->execute(); $subtasks_result = $subtask_stmt->get_result();
        while ($subtask = $subtasks_result->fetch_assoc()) { $task['subtasks'][] = $subtask; }
        $subtask_stmt->close();

        $att_stmt = $conn->prepare("SELECT * FROM attachments WHERE task_id = ?");
        $att_stmt->bind_param("i", $task_id); $att_stmt->execute(); $atts_result = $att_stmt->get_result();
        while ($att = $atts_result->fetch_assoc()) { $task['attachments'][] = $att; }
        $att_stmt->close();

        $com_stmt = $conn->prepare("SELECT c.*, u.name as user_name FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.task_id = ?");
        $com_stmt->bind_param("i", $task_id); $com_stmt->execute(); $coms_result = $com_stmt->get_result();
        while ($com = $coms_result->fetch_assoc()) { $task['comments'][] = $com; }
        $com_stmt->close();

        $tag_stmt = $conn->prepare("SELECT t.* FROM tags t JOIN task_tags tt ON t.id = tt.tag_id WHERE tt.task_id = ?");
        $tag_stmt->bind_param("i", $task_id); $tag_stmt->execute(); $tags_result = $tag_stmt->get_result();
        while ($tag = $tags_result->fetch_assoc()) { $task['tags'][] = $tag; }
        $tag_stmt->close();

        $act_stmt = $conn->prepare("SELECT a.*, u.name as user_name FROM activity_log a LEFT JOIN users u ON a.user_id = u.id WHERE a.task_id = ? ORDER BY a.activity_date DESC LIMIT 10");
        $act_stmt->bind_param("i", $task_id); $act_stmt->execute(); $acts_result = $act_stmt->get_result();
        while ($act = $acts_result->fetch_assoc()) { $task['activity_log'][] = $act; }
        $act_stmt->close();

        $column['tasks'][] = $task;
    }
    $tasks_stmt->close();
    $response_data['columns'][] = $column;
}
$columns_stmt->close();

echo json_encode(['status' => 'success', 'data' => $response_data]);
?>