<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagem = null;
    $status = 'error';
    $msg = 'Nenhuma imagem válida foi enviada ou ocorreu um erro.';

    $userId = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'N/A';

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $nomeImagem = uniqid('carrossel_').'.'.$ext;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], 'uploads/'.$nomeImagem)) {
                $imagem = $nomeImagem;
            } else {
                $msg = 'Falha ao mover o arquivo para a pasta de uploads.';
                logActivity($userId, "Erro Upload Carrossel", "Usuário {$username} falhou ao mover a imagem para uploads: {$nomeImagem}.", "error");
            }
        } else {
            $msg = 'Formato de arquivo não permitido. Use JPG, PNG ou GIF.';
            logActivity($userId, "Erro Upload Carrossel", "Usuário {$username} tentou fazer upload de imagem com formato não permitido: {$ext}.", "error");
        }
    } else {
        logActivity($userId, "Erro Upload Carrossel", "Usuário {$username} tentou fazer upload de imagem para carrossel, mas nenhum arquivo válido foi enviado.", "error");
    }

    if ($imagem) {
        $stmt = $conn->prepare("INSERT INTO carrossel_imagens (imagem) VALUES (?)");
        $stmt->bind_param("s", $imagem);
        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'Imagem adicionada ao carrossel com sucesso!';
            logActivity($userId, "Imagem Carrossel Adicionada", "Usuário {$username} adicionou a imagem {$imagem} ao carrossel.");
        } else {
            $msg = 'Erro ao salvar imagem no banco de dados.';
            logActivity($userId, "Erro DB Carrossel", "Usuário {$username} falhou ao salvar imagem {$imagem} no DB. Erro: " . $stmt->error, "error");
        }
    }
    header("Location: index.php?section=upload&status=$status&msg=" . urlencode($msg));
    exit();
}
?>