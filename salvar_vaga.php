<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Erro desconhecido.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vaga_id = trim($_POST['vaga_id'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $descricao = strip_tags(trim($_POST['descricao'] ?? ''));
    $requisitos = strip_tags(trim($_POST['requisitos'] ?? ''));

    if (empty($titulo) || empty($setor)) {
        $response['message'] = 'Título e Setor são campos obrigatórios.';
        echo json_encode($response);
        exit();
    }

    if (!empty($vaga_id)) {
        // Atualizar vaga existente
        $stmt = $conn->prepare("UPDATE vagas SET titulo = ?, setor = ?, descricao = ?, requisitos = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssssi", $titulo, $setor, $descricao, $requisitos, $vaga_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Vaga atualizada com sucesso!';
            } else {
                $response['message'] = 'Erro ao atualizar vaga: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Erro na preparação da query de atualização: ' . $conn->error;
        }
    } else {
        // Inserir nova vaga
        $data_publicacao = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO vagas (titulo, setor, descricao, requisitos, data_publicacao) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $titulo, $setor, $descricao, $requisitos, $data_publicacao);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Vaga salva com sucesso!';
            } else {
                $response['message'] = 'Erro ao salvar vaga: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Erro na preparação da query de inserção: ' . $conn->error;
        }
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

$conn->close();
echo json_encode($response);
?>
