<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

header('Content-Type: application/json');

// Apenas admins ou 'god' podem atualizar o status
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sugestao_id = $_POST['sugestao_id'] ?? 0;
    $novo_status = $_POST['novo_status'] ?? '';

    if (empty($sugestao_id) || empty($novo_status)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE sugestoes SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $sugestao_id);

    if ($stmt->execute()) {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Status de Sugestão Atualizado", "Usuário {$username} atualizou o status da sugestão ID: {$sugestao_id} para: {$novo_status}.");
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
    } else {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Erro ao Atualizar Status de Sugestão", "Usuário {$username} falhou ao atualizar o status da sugestão ID: {$sugestao_id} para: {$novo_status}. Erro: " . $stmt->error, "error");
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status.']);
    }
    $stmt->close();
}