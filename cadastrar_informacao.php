<?php
require_once 'conexao.php';

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
        header("Location: index.php?info=sucesso");
        exit();
    } else {
        header("Location: index.php?info=erro");
        exit();
    }
}
?>