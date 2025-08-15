<?php
session_start();
require_once 'conexao.php';

// Garante que o usuário esteja logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Atualiza o status do tour para o usuário no banco de dados
$stmt = $conn->prepare("UPDATE users SET has_completed_tour = 1 WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Também atualiza a sessão para que o tour não inicie novamente se a página for recarregada
    $_SESSION['show_tour'] = false;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao atualizar o status do tour.']);
}

$stmt->close();