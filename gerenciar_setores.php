<?php
session_start();
require_once 'conexao.php';

// Apenas admins ou 'god' podem gerenciar setores
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    // Redireciona para a página inicial com erro
    header("Location: index.php?status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

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
            $stmt->execute();
            $stmt->close();
        }
        $stmt_check->close();

    } elseif ($action === 'delete' && !empty($_POST['setor_id'])) {
        $setor_id = intval($_POST['setor_id']);
        $stmt = $conn->prepare("DELETE FROM setores WHERE id = ?");
        $stmt->bind_param("i", $setor_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Redireciona de volta para a seção de configurações na página principal
header("Location: index.php#settings");
exit();