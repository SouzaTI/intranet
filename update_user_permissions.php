<?php
session_start();
require_once 'conexao.php';

// Apenas admins ou 'god' podem alterar permissões
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'];
    $sections = $_POST['sections'] ?? [];

    if (empty($user_id) || empty($role)) {
        header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Dados inválidos."));
        exit();
    }

    // 1. Atualizar a 'role' na tabela 'users'
    $stmt_role = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt_role->bind_param("si", $role, $user_id);
    $stmt_role->execute();
    $stmt_role->close();

    // 2. Limpar permissões de seção antigas
    $stmt_delete = $conn->prepare("DELETE FROM user_sections WHERE user_id = ?");
    $stmt_delete->bind_param("i", $user_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 3. Inserir novas permissões de seção
    if (!empty($sections)) {
        $stmt_insert = $conn->prepare("INSERT INTO user_sections (user_id, section_name) VALUES (?, ?)");
        foreach ($sections as $section_name) {
            $stmt_insert->bind_param("is", $user_id, $section_name);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
    }

    header("Location: index.php?section=settings&tab=users&status=success&msg=" . urlencode("Permissões atualizadas com sucesso!"));
    exit();
}

header("Location: index.php?section=settings&tab=users");
exit();