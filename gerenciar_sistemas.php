<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php';

// Apenas admins ou 'god' podem gerenciar sistemas
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: index.php?status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $loggedInUserId = $_SESSION['user_id'];

    // Ação para adicionar um novo sistema
    if ($action === 'add' && !empty($_POST['nome']) && !empty($_POST['link'])) {
        $nome = trim($_POST['nome']);
        $link = trim($_POST['link']);
        // Usa o ícone fornecido ou o padrão se estiver vazio
        $icon_class = !empty(trim($_POST['icon_class'])) ? trim($_POST['icon_class']) : 'fas fa-external-link-alt';
        
        // Pega o setor_id do formulário
        $setor_id = filter_input(INPUT_POST, 'setor_id', FILTER_VALIDATE_INT);
        if ($setor_id === false || $setor_id === 0) {
            $setor_id = null;
        }

        // Busca o nome do setor para a coluna 'departamento' (para compatibilidade)
        $departamento = null;
        if ($setor_id) {
            $stmt_setor = $conn->prepare("SELECT nome FROM setores WHERE id = ?");
            $stmt_setor->bind_param("i", $setor_id);
            $stmt_setor->execute();
            $departamento = $stmt_setor->get_result()->fetch_assoc()['nome'] ?? null;
            $stmt_setor->close();
        }

        $stmt = $conn->prepare("INSERT INTO sistemas_externos (nome, link, icon_class, departamento, setor_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nome, $link, $icon_class, $departamento, $setor_id);
        if ($stmt->execute()) {
            $new_system_id = $stmt->insert_id;
            logActivity($loggedInUserId, 'Adicionou novo atalho de sistema', "Sistema: {$nome} (ID: {$new_system_id})");
            $status = "success";
            $msg = "Atalho adicionado com sucesso!";
        } else {
            logActivity($loggedInUserId, 'Erro ao adicionar atalho de sistema', "Tentativa para sistema: {$nome}", 'error');
            $status = "error";
            $msg = "Erro ao adicionar o atalho.";
        }
        $stmt->close();

    // Ação para deletar um sistema
    } elseif ($action === 'delete' && !empty($_POST['sistema_id'])) {
        $sistema_id = intval($_POST['sistema_id']);
        
        // Busca o nome do sistema para o log
        $stmt_get_name = $conn->prepare("SELECT nome FROM sistemas_externos WHERE id = ?");
        $stmt_get_name->bind_param("i", $sistema_id);
        $stmt_get_name->execute();
        $result = $stmt_get_name->get_result();
        $sistema_nome = $result->fetch_assoc()['nome'] ?? 'ID: ' . $sistema_id;
        $stmt_get_name->close();

        $stmt = $conn->prepare("DELETE FROM sistemas_externos WHERE id = ?");
        $stmt->bind_param("i", $sistema_id);
        if ($stmt->execute()) {
            logActivity($loggedInUserId, 'Excluiu atalho de sistema', "Sistema: {$sistema_nome} (ID: {$sistema_id})");
            $status = "success";
            $msg = "Atalho excluído com sucesso!";
        } else {
            logActivity($loggedInUserId, 'Erro ao excluir atalho de sistema', "Tentativa para sistema: {$sistema_nome} (ID: {$sistema_id})", 'error');
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