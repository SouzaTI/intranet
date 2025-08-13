<?php
session_start();
require 'conexao.php';
require_once 'log_activity.php'; // Adicionado para log

header('Content-Type: application/json');

$usuario_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $departamento = $_POST['departamento'] ?? '';
    $nivel_acesso = $_POST['nivel_acesso'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    // Pega o setor_id. Se estiver vazio ou não for um número, converte para NULL.
    $setor_id = !empty($_POST['setor_id']) && is_numeric($_POST['setor_id']) ? intval($_POST['setor_id']) : null;

    if (isset($_FILES['arquivo'])) {
        if ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            logActivity($usuario_id, 'Erro no Upload', "Erro de upload do PHP: " . $_FILES['arquivo']['error'], 'error');
            echo json_encode(['success' => false, 'message' => 'Erro no upload: ' . $_FILES['arquivo']['error']]);
            exit();
        }
        $nome_arquivo = uniqid() . '_' . basename($_FILES['arquivo']['name']);
        $destino = __DIR__ . '/uploads/' . $nome_arquivo;
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0777, true);
        }
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            // Adiciona o campo setor_id ao INSERT
            $stmt = $conn->prepare("INSERT INTO arquivos (titulo, tipo, nome_arquivo, departamento, nivel_acesso, descricao, usuario_id, setor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            // Adiciona o bind para setor_id (são 8 parâmetros agora, o último é um inteiro)
            $stmt->bind_param("ssssssii", $titulo, $tipo, $nome_arquivo, $departamento, $nivel_acesso, $descricao, $usuario_id, $setor_id);
            $stmt->execute();
            logActivity($usuario_id, 'Upload de Arquivo', "Arquivo: {$nome_arquivo} | Título: {$titulo}");
            echo json_encode(['success' => true]);
            exit();
        } else {
            logActivity($usuario_id, 'Erro no Upload', "Falha ao mover arquivo: {$nome_arquivo}", 'error');
            echo json_encode(['success' => false, 'message' => 'Falha ao mover arquivo para uploads.']);
            exit();
        }
    }
    logActivity($usuario_id, 'Erro no Upload', 'Nenhum arquivo enviado no formulário', 'error');
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    exit();
}
logActivity(null, 'Erro no Upload', 'Requisição inválida para upload.php', 'error');
echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
exit();
?>