<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

header('Content-Type: application/json');

// Apenas admins ou 'god' podem atualizar o status
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sugestao_id = $_POST['sugestao_id'] ?? 0;
    $novo_status = $_POST['novo_status'] ?? '';

    if (empty($sugestao_id) || empty($novo_status)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE sugestoes SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $sugestao_id);

    if ($stmt->execute()) {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Status de Sugestão Atualizado", "Usuário {$username} atualizou o status da sugestão ID: {$sugestao_id} para: {$novo_status}.");

        // Lógica de Notificação para o usuário que criou a sugestão
        $stmt_get_user = $conn->prepare("SELECT usuario_id FROM sugestoes WHERE id = ?");
        $stmt_get_user->bind_param("i", $sugestao_id);
        $stmt_get_user->execute();
        $result_user = $stmt_get_user->get_result();
        if ($result_user->num_rows > 0) {
            $suggestion_owner_id = $result_user->fetch_assoc()['usuario_id'];
            $notification_message = "O status da sua sugestão foi atualizado para: " . str_replace('_', ' ', $novo_status);
            $notification_link = "index.php?section=sugestoes";

            $stmt_notif = $conn->prepare("INSERT INTO notificacoes (user_id, mensagem, link) VALUES (?, ?, ?)");
            $stmt_notif->bind_param("iss", $suggestion_owner_id, $notification_message, $notification_link);
            $stmt_notif->execute();
            $stmt_notif->close();
        }
        $stmt_get_user->close();

        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
    } else {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Erro ao Atualizar Status de Sugestão", "Usuário {$username} falhou ao atualizar o status da sugestão ID: {$sugestao_id} para: {$novo_status}. Erro: " . $stmt->error, "error");
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status.']);
    }
    $stmt->close();
}