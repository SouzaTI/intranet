<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $categoria = trim($_POST['categoria']);
    $data_publicacao = $_POST['data_publicacao'];
    $cor = $_POST['cor'] ?? 'blue';
    $data_inicial = $_POST['data_inicial'] ?? null;
    $data_final = $_POST['data_final'] ?? null;

    $stmt = $conn->prepare("INSERT INTO informacoes (titulo, descricao, categoria, data_publicacao, cor, data_inicial, data_final) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $titulo, $descricao, $categoria, $data_publicacao, $cor, $data_inicial, $data_final);

    if ($stmt->execute()) {
        $status = 'success';
        $msg = 'Informação cadastrada com sucesso!';
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Informação Cadastrada", "Usuário {$username} cadastrou a informação: '{$titulo}' (Categoria: {$categoria}).");
        header("Location: index.php?section=info-upload&status=$status&msg=" . urlencode($msg));
        exit();
    } else {
        $status = 'error';
        $msg = 'Erro ao cadastrar a informação.';
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'N/A';
        logActivity($userId, "Erro ao Cadastrar Informação", "Usuário {$username} falhou ao cadastrar a informação: '{$titulo}' (Categoria: {$categoria}). Erro: " . $stmt->error, "error");
        header("Location: index.php?section=info-upload&status=$status&msg=" . urlencode($msg));
        exit();
    }
}
?>