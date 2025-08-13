<?php
session_start();
require_once 'conexao.php';

// Apenas admins ou 'god' podem gerenciar sistemas
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: index.php?status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Ação para adicionar um novo sistema
    if ($action === 'add' && !empty($_POST['nome']) && !empty($_POST['link'])) {
        $nome = trim($_POST['nome']);
        $link = trim($_POST['link']);
        // Usa o ícone fornecido ou o padrão se estiver vazio
        $icon_class = !empty(trim($_POST['icon_class'])) ? trim($_POST['icon_class']) : 'fas fa-external-link-alt';
        // Se o departamento não for selecionado (valor vazio), salva como NULL no banco
        $departamento = !empty($_POST['departamento']) ? trim($_POST['departamento']) : null;

        $stmt = $conn->prepare("INSERT INTO sistemas_externos (nome, link, icon_class, departamento) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $link, $icon_class, $departamento);
        if ($stmt->execute()) {
            $status = "success";
            $msg = "Atalho adicionado com sucesso!";
        } else {
            $status = "error";
            $msg = "Erro ao adicionar o atalho.";
        }
        $stmt->close();

    // Ação para deletar um sistema
    } elseif ($action === 'delete' && !empty($_POST['sistema_id'])) {
        $sistema_id = intval($_POST['sistema_id']);
        $stmt = $conn->prepare("DELETE FROM sistemas_externos WHERE id = ?");
        $stmt->bind_param("i", $sistema_id);
        if ($stmt->execute()) {
            $status = "success";
            $msg = "Atalho excluído com sucesso!";
        } else {
            $status = "error";
            $msg = "Erro ao excluir o atalho.";
        }
        $stmt->close();
    }
}

// Redireciona de volta para a aba de acesso nas configurações
header("Location: index.php?section=settings&tab=acesso&status=$status&msg=" . urlencode($msg));
exit();
?>