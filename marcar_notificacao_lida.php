<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$notificacao_id = $_POST['id'] ?? null;

if (!$notificacao_id) {
    echo json_encode(['success' => false, 'error' => 'ID da notificação não fornecido.']);
    exit();
}

if ($notificacao_id === 'all') {
    // Marcar todas como lidas
    $sql = "UPDATE notificacoes SET lida = TRUE WHERE user_id = ? AND lida = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else {
    // Marcar uma específica como lida
    $sql = "UPDATE notificacoes SET lida = TRUE WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notificacao_id, $user_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao marcar notificação como lida.']);
}
?>