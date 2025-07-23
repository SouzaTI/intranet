<?php
session_start();
require 'conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $departamento = $_POST['departamento'] ?? '';
    $nivel_acesso = $_POST['nivel_acesso'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $usuario_id = $_SESSION['user_id'] ?? null;

    if (isset($_FILES['arquivo'])) {
        if ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erro no upload: ' . $_FILES['arquivo']['error']]);
            exit();
        }
        $nome_arquivo = uniqid() . '_' . basename($_FILES['arquivo']['name']);
        $destino = __DIR__ . '/uploads/' . $nome_arquivo;
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0777, true);
        }
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            $stmt = $conn->prepare("INSERT INTO arquivos (titulo, tipo, nome_arquivo, departamento, nivel_acesso, descricao, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $titulo, $tipo, $nome_arquivo, $departamento, $nivel_acesso, $descricao, $usuario_id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao mover arquivo para uploads.']);
            exit();
        }
    }
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    exit();
}
echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
exit();
?>