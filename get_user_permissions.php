<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

// Apenas admins ou 'god' podem ver as permissões
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

if (!isset($_GET['user_id'])) {
    echo json_encode(['error' => 'ID de usuário não fornecido.']);
    exit();
}

$user_id = intval($_GET['user_id']);

// Busca role
$stmt_user = $conn->prepare("SELECT role, setor_id FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

if (!$user) {
    echo json_encode(['error' => 'Usuário não encontrado.']);
    exit();
}

// Busca seções permitidas
$allowed_sections = [];
$stmt_sections = $conn->prepare("SELECT section_name FROM user_sections WHERE user_id = ?");
$stmt_sections->bind_param("i", $user_id);
$stmt_sections->execute();
$result_sections = $stmt_sections->get_result();
while ($row = $result_sections->fetch_assoc()) {
    $allowed_sections[] = $row['section_name'];
}

echo json_encode([
    'role' => $user['role'],
    'setor_id' => $user['setor_id'],
    'sections' => $allowed_sections
]);
?>