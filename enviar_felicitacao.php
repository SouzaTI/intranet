<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php';

// Definir o cabeçalho como JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

// Verificar se o ID do destinatário foi enviado via POST
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do destinatário não fornecido.']);
    exit();
}

$recipient_id = (int)$_POST['user_id'];
$sender_id = (int)$_SESSION['user_id'];
$sender_name = $_SESSION['username'];

// Evitar que o usuário se parabenize
if ($recipient_id === $sender_id) {
    echo json_encode(['success' => false, 'message' => 'Você não pode parabenizar a si mesmo.']);
    exit();
}

// Criar a mensagem de notificação padronizada
$message = $sender_name . " te desejou feliz aniversário!";
$link = '#'; // Link opcional, pode levar ao perfil do usuário no futuro

// Verificar se uma felicitação já foi enviada para evitar duplicatas no mesmo ano (usando sender_id e type)
$sql_check = "SELECT id FROM notificacoes WHERE user_id = ? AND sender_id = ? AND type = 'birthday' AND YEAR(data_criacao) = YEAR(CURDATE())";
$stmt_check = $conn->prepare($sql_check);

if ($stmt_check) {
    $stmt_check->bind_param("ii", $recipient_id, $sender_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Você já enviou uma felicitação para este usuário este ano.']);
        exit();
    }
    $stmt_check->close();
} else {
    // Log de erro se a preparação da verificação falhar
    error_log("Erro ao preparar a consulta de verificação de duplicatas: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
    exit();
}

// Inserir a nova notificação no banco de dados, agora com sender_id e type
$sql_insert = "INSERT INTO notificacoes (user_id, sender_id, type, mensagem, link, lida, data_criacao) VALUES (?, ?, 'birthday', ?, ?, 0, NOW())";
$stmt_insert = $conn->prepare($sql_insert);

if ($stmt_insert) {
    $stmt_insert->bind_param("iiss", $recipient_id, $sender_id, $message, $link);
    if ($stmt_insert->execute()) {
        // Log da atividade bem-sucedida
        logActivity($sender_id, 'Enviou Felicitação', "Enviou uma felicitação de aniversário para o usuário ID: " . $recipient_id);
        echo json_encode(['success' => true, 'message' => 'Felicitação enviada com sucesso!']);
    } else {
        // Log de erro na execução
        error_log("Erro ao executar a inserção da notificação: " . $stmt_insert->error);
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar a felicitação.']);
    }
    $stmt_insert->close();
} else {
    // Log de erro na preparação da inserção
    error_log("Erro ao preparar a consulta de inserção de notificação: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}

$conn->close();
?>
