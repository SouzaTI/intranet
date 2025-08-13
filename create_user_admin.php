<?php
session_start();
require_once 'conexao.php';

// Apenas admins ou 'god' podem criar usuários
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $setor_id = filter_input(INPUT_POST, 'setor_id', FILTER_VALIDATE_INT);

    // Validação
    if (empty($username) || empty($password) || $setor_id === false || !in_array($role, ['user', 'admin'])) {
        header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Todos os campos são obrigatórios."));
        exit();
    }

    // Verifica se o usuário já existe
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Este nome de usuário já está em uso."));
        exit();
    }
    $stmt_check->close();

    // Hash da senha
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Busca o nome do setor para a coluna 'department' (compatibilidade)
    $department_name = null;
    if ($setor_id) {
        $stmt_setor = $conn->prepare("SELECT nome FROM setores WHERE id = ?");
        $stmt_setor->bind_param("i", $setor_id);
        $stmt_setor->execute();
        $department_name = $stmt_setor->get_result()->fetch_assoc()['nome'] ?? null;
        $stmt_setor->close();
    }

    // Insere o novo usuário no banco de dados
    $stmt_insert = $conn->prepare("INSERT INTO users (username, password, role, setor_id, department) VALUES (?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("sssis", $username, $hashed_password, $role, $setor_id, $department_name);

    if ($stmt_insert->execute()) {
        header("Location: index.php?section=settings&tab=users&status=success&msg=" . urlencode("Usuário criado com sucesso."));
    } else {
        header("Location: index.php?section=settings&tab=users&status=error&msg=" . urlencode("Erro ao criar o usuário."));
    }
    $stmt_insert->close();
    exit();
}

// Redireciona se o acesso for direto (GET)
header("Location: index.php?section=settings&tab=users");
exit();

