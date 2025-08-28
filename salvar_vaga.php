<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// --- DEBUG: Log $_POST content ---
file_put_contents('debug_salvar_vaga.log', "\n---" . date('Y-m-d H:i:s') . "---\n" . print_r($_POST, true), FILE_APPEND);
// --- END DEBUG ---

$response = ['success' => false, 'message' => 'Erro desconhecido.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit();
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $descricao = strip_tags(trim($_POST['descricao'] ?? ''));
    $requisitos = strip_tags(trim($_POST['requisitos'] ?? ''));
    $data_publicacao = date('Y-m-d'); // A data é gerada no servidor

    // Validação básica
    if (empty($titulo) || empty($setor)) {
        $response['message'] = 'Título e Setor são campos obrigatórios.';
        echo json_encode($response);
        exit();
    }

    // Prepara a inserção no banco de dados
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
        $response['message'] = 'Erro na preparação da query: ' . $conn->error;
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

$conn->close();
echo json_encode($response);
?>