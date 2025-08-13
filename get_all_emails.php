<?php
session_start();
require_once 'conexao.php';

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

// --- Lógica de Filtro ---
$setor_filtro = $_GET['setor'] ?? null;

$sql = "SELECT email FROM matriz_comunicacao WHERE email IS NOT NULL AND email != '' AND email LIKE '%@%'";
$params = [];
$types = '';

if (!empty($setor_filtro)) {
    $sql .= " AND setor = ?";
    $params[] = $setor_filtro;
    $types .= 's';
}

$stmt = $conn->prepare($sql);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}

$emails = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
}

// Junta os e-mails em uma única string, separados por vírgula e espaço
$email_string = implode(', ', $emails);

echo json_encode(['emails' => $email_string]);
