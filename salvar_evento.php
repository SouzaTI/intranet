<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php';

header('Content-Type: application/json');

// Apenas admins podem salvar eventos
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID do evento não fornecido.']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM eventos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Evento Excluído', "ID do evento: {$id}");
        echo json_encode(['success' => true, 'message' => 'Evento excluído com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir o evento.']);
    }
    $stmt->close();
    $conn->close();
    exit();
}

// Ação de salvar (criar/editar)
$id = $_POST['evento_id'] ?? null;
$titulo = $_POST['titulo'] ?? '';
$descricao = $_POST['descricao'] ?? null;
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = $_POST['data_fim'] ?? null;
$cor = $_POST['cor'] ?? '#3788d8';
$criado_por = $_SESSION['user_id'];

if (empty($titulo) || empty($data_inicio)) {
    echo json_encode(['success' => false, 'message' => 'Título e data de início são obrigatórios.']);
    exit();
}

// Se data_fim estiver vazia, define como NULL
if (empty($data_fim)) {
    $data_fim = null;
}

if (empty($id)) {
    // Criar novo evento
    $stmt = $conn->prepare("INSERT INTO eventos (titulo, descricao, data_inicio, data_fim, cor, criado_por) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $titulo, $descricao, $data_inicio, $data_fim, $cor, $criado_por);
    $log_action = 'Evento Criado';
    $log_details = "Título: {$titulo}";
} else {
    // Atualizar evento existente
    $stmt = $conn->prepare("UPDATE eventos SET titulo = ?, descricao = ?, data_inicio = ?, data_fim = ?, cor = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $titulo, $descricao, $data_inicio, $data_fim, $cor, $id);
    $log_action = 'Evento Atualizado';
    $log_details = "ID: {$id}, Título: {$titulo}";
}

if ($stmt->execute()) {
    logActivity($criado_por, $log_action, $log_details);
    echo json_encode(['success' => true, 'message' => 'Evento salvo com sucesso!']);
} else {
    logActivity($criado_por, 'Erro ao Salvar Evento', $log_details, 'error');
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar o evento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
