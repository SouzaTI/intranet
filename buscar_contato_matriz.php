<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

// Apenas usuários logados podem buscar
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$searchTerm = $_GET['term'] ?? '';

if (strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("SELECT id, nome, setor, email FROM matriz_comunicacao WHERE nome LIKE ? OR email LIKE ? LIMIT 10");
$likeTerm = "%{$searchTerm}%";
$stmt->bind_param("ss", $likeTerm, $likeTerm);
$stmt->execute();
$result = $stmt->get_result();
$contacts = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($contacts);
?>