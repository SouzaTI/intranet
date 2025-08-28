<?php
session_start();
require_once 'conexao.php'; // Adjust path if necessary

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in and has admin/god role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    $response['message'] = 'Acesso negado.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userIdToDelete = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);

    if (empty($userIdToDelete)) {
        $response['message'] = 'ID do usuário inválido.';
        echo json_encode($response);
        exit();
    }

    // Prevent self-deletion
    if ($userIdToDelete == $_SESSION['user_id']) {
        $response['message'] = 'Você não pode excluir seu próprio usuário.';
        echo json_encode($response);
        exit();
    }

    // Prepare and execute the delete statement
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userIdToDelete);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Log the activity
                require_once 'log_activity.php';
                logActivity($_SESSION['user_id'], 'Excluiu Usuário', "Usuário com ID: {$userIdToDelete} foi excluído.");

                $response['success'] = true;
                $response['message'] = 'Usuário excluído com sucesso!';
            } else {
                $response['message'] = 'Nenhum usuário encontrado com o ID fornecido.';
            }
        } else {
            $response['message'] = 'Erro ao excluir usuário: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Erro na preparação da query: ' . $conn->error;
    }
} else {
    $response['message'] = 'Requisição inválida.';
}

echo json_encode($response);
$conn->close();
?>