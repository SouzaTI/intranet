<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Erro desconhecido.'];

if (!isset($_SESSION['user_id']) || (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'god'))) {
    $response['message'] = 'Acesso negado. Você não tem permissão para realizar esta ação.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vaga_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$vaga_id) {
        $response['message'] = 'ID da vaga inválido.';
        echo json_encode($response);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM vagas WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $vaga_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Vaga excluída com sucesso!';
            } else {
                $response['message'] = 'Vaga não encontrada ou já excluída.';
            }
        } else {
            $response['message'] = 'Erro ao excluir vaga: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Erro na preparação da query: ' . $conn->error;
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

$conn->close();
echo json_encode($response);
?>