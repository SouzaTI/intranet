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
    // Permitir 0 para desassociar
    $matriz_id = filter_input(INPUT_POST, 'matriz_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($user_id && $matriz_id !== false) { // Verifica se o user_id é válido e o matriz_id foi passado
        if ($matriz_id > 0) {
            // Associar a um novo contato
            $stmt = $conn->prepare("UPDATE users SET matriz_comunicacao_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $matriz_id, $user_id);
            $log_message = "Usuário ID: {$user_id} associado à Matriz ID: {$matriz_id}";
            $success_message = "Usuário associado com sucesso.";
            $error_message = "Erro ao associar usuário.";
        } else {
            // Desassociar (matriz_id é 0)
            $stmt = $conn->prepare("UPDATE users SET matriz_comunicacao_id = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $log_message = "Usuário ID: {$user_id} desassociado da Matriz.";
            $success_message = "Usuário desassociado com sucesso.";
            $error_message = "Erro ao desassociar usuário.";
        }

        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Associação de usuário à matriz', $log_message);
            header("Location: index.php?section=settings&tab=users&status=success&msg=" . urlencode($success_message));
        } else {
            logActivity($_SESSION['user_id'], 'Erro na associação de usuário', "Tentativa para Usuário ID: {$user_id}", 'error');
            header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode($error_message));
        }
        $stmt->close();
    } else {
        header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Dados inválidos."));
    }
    exit();
}