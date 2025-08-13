<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Faça login para continuar.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['user_id'];
    $tipo = $_POST['tipo'] ?? '';
    $mensagem = trim($_POST['mensagem'] ?? '');
    $email = trim($_POST['email'] ?? '') ?: null; // Converte string vazia para NULL
    $telefone = trim($_POST['telefone'] ?? '') ?: null; // Converte string vazia para NULL

    if (empty($tipo) || empty($mensagem)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, preencha os campos obrigatórios.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO sugestoes (usuario_id, tipo, mensagem, email, telefone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $usuario_id, $tipo, $mensagem, $email, $telefone);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sua mensagem foi enviada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar sua mensagem. Tente novamente.']);
    }
    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
exit();