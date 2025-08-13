<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

header('Content-Type: application/json');

// Apenas admins ou 'god' podem atualizar
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $column = $_POST['column'] ?? '';
    $value = $_POST['value'] ?? '';

    // Validação básica
    if (empty($id) || empty($column)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit();
    }

    // Lista de colunas permitidas para evitar SQL Injection no nome da coluna
    $allowed_columns = ['nome', 'setor', 'email', 'ramal'];
    if (!in_array($column, $allowed_columns)) {
        echo json_encode(['success' => false, 'message' => 'Coluna inválida.']);
        exit();
    }

    // Usamos backticks no nome da coluna, que é seguro pois validamos na lista acima
    $sql = "UPDATE matriz_comunicacao SET `$column` = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $value, $id);

    if ($stmt->execute()) {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Matriz de Comunicação Atualizada", "Usuário {$username} atualizou a coluna '{$column}' para '{$value}' para o funcionário ID: {$id}.");
        echo json_encode(['success' => true]);
    } else {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Erro ao Atualizar Matriz de Comunicação", "Usuário {$username} falhou ao atualizar a coluna '{$column}' para '{$value}' para o funcionário ID: {$id}. Erro: " . $stmt->error, "error");
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o banco de dados.']);
    }
    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
exit();