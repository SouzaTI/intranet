<?php
session_start();
require_once 'conexao.php';

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
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status.']);
    }
    $stmt->close();
}