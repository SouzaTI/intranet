<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

// Apenas admins ou 'god' podem alterar permissões
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $role = $_POST['role'] ?? 'user';
    $setor_id = filter_input(INPUT_POST, 'setor_id', FILTER_VALIDATE_INT);
    $empresa = trim($_POST['empresa'] ?? 'Comercial Souza');
    $sections = $_POST['sections'] ?? [];

    if ($user_id === false || empty($role)) {
        header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Dados inválidos."));
        exit();
    }

    // Se o setor_id não for um inteiro válido (ou for vazio/zero), define como NULL
    if ($setor_id === false || $setor_id === 0) {
        $setor_id = null;
    }

    // Busca o nome do setor para manter a coluna 'department' atualizada (compatibilidade)
    $department_name = null;
    if ($setor_id !== null) {
        $stmt_setor = $conn->prepare("SELECT nome FROM setores WHERE id = ?");
        $stmt_setor->bind_param("i", $setor_id);
        $stmt_setor->execute();
        $department_name = $stmt_setor->get_result()->fetch_assoc()['nome'] ?? null;
        $stmt_setor->close();
    }

    // 1. Atualizar a 'role', 'setor_id' e 'department' na tabela 'users'
    $stmt_update_user = $conn->prepare("UPDATE users SET role = ?, setor_id = ?, department = ?, empresa = ? WHERE id = ?");
    $stmt_update_user->bind_param("sissi", $role, $setor_id, $department_name, $empresa, $user_id);
    $stmt_update_user->execute();
    $stmt_update_user->close();

    // 2. Limpar permissões de seção antigas
    $stmt_delete = $conn->prepare("DELETE FROM user_sections WHERE user_id = ?");
    $stmt_delete->bind_param("i", $user_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 3. Inserir novas permissões de seção (apenas se o role for 'user')
    if ($role === 'user' && !empty($sections)) {
        $stmt_insert = $conn->prepare("INSERT INTO user_sections (user_id, section_name) VALUES (?, ?)");
        foreach ($sections as $section_name) {
            $stmt_insert->bind_param("is", $user_id, $section_name);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
    }

    // Busca o username do usuário que teve as permissões alteradas para o log
    $stmt_get_username = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_get_username->bind_param("i", $user_id);
    $stmt_get_username->execute();
    $target_username = $stmt_get_username->get_result()->fetch_assoc()['username'] ?? 'N/A';
    $stmt_get_username->close();

    logActivity($_SESSION['user_id'] ?? null, 'Permissões de Usuário Atualizadas', "Usuário alvo: {$target_username} (ID: {$user_id}) | Role: {$role} | Seções: " . implode(', ', $sections));

    header("Location: index.php?section=settings&tab=users&status=success&msg=" . urlencode("Permissões atualizadas com sucesso!"));
    exit();
}

header("Location: index.php?section=settings&tab=users");
exit();