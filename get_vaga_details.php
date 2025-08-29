<?php
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da vaga não fornecido.']);
    exit;
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT id, titulo, setor, descricao, requisitos FROM vagas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($vaga = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $vaga]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vaga não encontrada.']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
