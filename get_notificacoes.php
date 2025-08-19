<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Busca as últimas 20 notificações para o usuário, com as não lidas primeiro
$sql = "SELECT id, mensagem, link, lida, data_criacao FROM notificacoes WHERE user_id = ? ORDER BY lida ASC, data_criacao DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notificacoes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notificacoes[] = $row;
    }
}

echo json_encode(['success' => true, 'notifications' => $notificacoes]);
?>