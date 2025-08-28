<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de usuário inválido.']);
    exit();
}

// 1. Buscar o matriz_comunicacao_id do usuário
$stmt_user = $conn->prepare("SELECT matriz_comunicacao_id FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$stmt_user->close();

if ($user_data && $user_data['matriz_comunicacao_id']) {
    $matriz_id = $user_data['matriz_comunicacao_id'];

    // 2. Buscar os detalhes do contato na matriz_comunicacao
    $stmt_matriz = $conn->prepare("SELECT id, nome, setor, email, ramal FROM matriz_comunicacao WHERE id = ?");
    $stmt_matriz->bind_param("i", $matriz_id);
    $stmt_matriz->execute();
    $result_matriz = $stmt_matriz->get_result();
    $contact_data = $result_matriz->fetch_assoc();
    $stmt_matriz->close();

    if ($contact_data) {
        echo json_encode($contact_data);
    } else {
        // ID associado não encontrado na matriz (pode ter sido deletado)
        echo json_encode(null);
    }
} else {
    // Usuário não tem contato associado
    echo json_encode(null);
}
?>