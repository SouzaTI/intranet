<?php
// get_vaga_details.php
header('Content-Type: application/json');
require_once 'conexao.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da vaga não fornecido.']);
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT id, titulo, descricao, requisitos, setor, data_publicacao, status FROM vagas_internas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($vaga = $result->fetch_assoc()) {
    // Formata a data para exibição
    $vaga['data_publicacao_formatada'] = date('d/m/Y', strtotime($vaga['data_publicacao']));
    echo json_encode(['success' => true, 'data' => $vaga]);
} else {
    echo json_encode(['success' => false, 'message' => 'Vaga não encontrada.']);
}

$stmt->close();
$conn->close();
?>