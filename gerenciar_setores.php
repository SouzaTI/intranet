<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php';

// Apenas admins ou 'god' podem gerenciar setores
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    // Redireciona para a página inicial com erro
    header("Location: index.php?status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $loggedInUserId = $_SESSION['user_id'];

    if ($action === 'add' && !empty($_POST['nome_setor'])) {
        $nome_setor = trim($_POST['nome_setor']);

        // Verifica se o setor já existe para evitar duplicatas
        $stmt_check = $conn->prepare("SELECT id FROM setores WHERE nome = ?");
        $stmt_check->bind_param("s", $nome_setor);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO setores (nome) VALUES (?)");
            $stmt->bind_param("s", $nome_setor);
            if ($stmt->execute()) {
                $new_setor_id = $stmt->insert_id;
                logActivity($loggedInUserId, 'Adicionou novo setor', "Setor: {$nome_setor} (ID: {$new_setor_id})");
            } else {
                logActivity($loggedInUserId, 'Erro ao adicionar setor', "Tentativa para setor: {$nome_setor}", 'error');
            }
            $stmt->close();
        }
        $stmt_check->close();

    } elseif ($action === 'delete' && !empty($_POST['setor_id'])) {
        $setor_id = intval($_POST['setor_id']);
        // Primeiro, busca o nome do setor para o log
        $stmt_get_name = $conn->prepare("SELECT nome FROM setores WHERE id = ?");
        $stmt_get_name->bind_param("i", $setor_id);
        $stmt_get_name->execute();
        $result = $stmt_get_name->get_result();
        $setor_nome = $result->fetch_assoc()['nome'] ?? 'ID: ' . $setor_id;
        $stmt_get_name->close();

        $stmt = $conn->prepare("DELETE FROM setores WHERE id = ?");
        $stmt->bind_param("i", $setor_id);
        if ($stmt->execute()) {
            logActivity($loggedInUserId, 'Excluiu o setor', "Setor: {$setor_nome} (ID: {$setor_id})");
        } else {
            logActivity($loggedInUserId, 'Erro ao excluir o setor', "Tentativa para setor: {$setor_nome} (ID: {$setor_id})", 'error');
        }
        $stmt->close();
    }
}

// Redireciona de volta para a seção de configurações na página principal
header("Location: index.php#settings");
exit();