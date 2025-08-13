<?php
session_start();
require_once 'conexao.php';

// Apenas admins ou 'god' podem adicionar
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    // Redireciona para a página inicial com uma mensagem de erro
    header("Location: index.php?section=matriz_comunicacao&status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e sanitiza os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ramal = trim($_POST['ramal'] ?? '');

    // Validação dos campos obrigatórios
    if (empty($nome) || empty($setor)) {
        header("Location: index.php?section=matriz_comunicacao&status=error&msg=" . urlencode("Nome e Setor são campos obrigatórios."));
        exit();
    }

    // Prepara a query de inserção
    $sql = "INSERT INTO matriz_comunicacao (nome, setor, email, ramal) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssss", $nome, $setor, $email, $ramal);
        if ($stmt->execute()) {
            header("Location: index.php?section=matriz_comunicacao&status=success&msg=" . urlencode("Funcionário adicionado com sucesso!"));
        } else {
            header("Location: index.php?section=matriz_comunicacao&status=error&msg=" . urlencode("Erro ao salvar no banco de dados."));
        }
        $stmt->close();
    }
}
exit();