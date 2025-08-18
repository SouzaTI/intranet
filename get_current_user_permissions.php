<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não logado.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Busca role e setor_id
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

// Adiciona seções padrão que todos os usuários podem ver
$default_sections = ['dashboard', 'about', 'faq', 'profile'];
$allowed_sections = array_merge($allowed_sections, $default_sections);
$allowed_sections = array_unique($allowed_sections);

// Ensure $allowed_sections is always treated as an array for json_encode
// This is a redundant check given the code above, but adds robustness against unexpected types.
if (!is_array($allowed_sections)) {
    $allowed_sections = [];
}

echo json_encode([
    'role' => $user['role'],
    'setor_id' => $user['setor_id'],
    'sections' => array_values($allowed_sections) // Use array_values to ensure a numerically indexed array for JSON
]);
?>