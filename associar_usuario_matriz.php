<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php';

// Apenas admins ou 'god' podem fazer isso
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $matriz_id = filter_input(INPUT_POST, 'matriz_id', FILTER_VALIDATE_INT);

    if ($user_id && $matriz_id) {
        // Assumindo que a coluna na tabela 'users' se chama 'matriz_comunicacao_id'
        $stmt = $conn->prepare("UPDATE users SET matriz_comunicacao_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $matriz_id, $user_id);

        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Associou usuário à matriz', "Usuário ID: {$user_id} associado à Matriz ID: {$matriz_id}");
            header("Location: index.php?section=settings&tab=users&status=success&msg=" . urlencode("Usuário associado com sucesso."));
        } else {
            logActivity($_SESSION['user_id'], 'Erro ao associar usuário', "Tentativa para Usuário ID: {$user_id} com Matriz ID: {$matriz_id}", 'error');
            header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Erro ao associar usuário."));
        }
        $stmt->close();
    } else {
        header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Dados inválidos. ID do usuário ou do contato da matriz não fornecido."));
    }
    exit();
}