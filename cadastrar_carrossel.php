<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagem = null;
    $status = 'error';
    $msg = 'Nenhuma imagem válida foi enviada ou ocorreu um erro.';

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $nomeImagem = uniqid('carrossel_').'.'.$ext;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], 'uploads/'.$nomeImagem)) {
                $imagem = $nomeImagem;
            } else {
                $msg = 'Falha ao mover o arquivo para a pasta de uploads.';
            }
        } else {
            $msg = 'Formato de arquivo não permitido. Use JPG, PNG ou GIF.';
        }
    }

    if ($imagem) {
        $stmt = $conn->prepare("INSERT INTO carrossel_imagens (imagem) VALUES (?)");
        $stmt->bind_param("s", $imagem);
        if ($stmt->execute()) {
            $status = 'success';
            $msg = 'Imagem adicionada ao carrossel com sucesso!';
        }
    }
    header("Location: index.php?section=upload&status=$status&msg=" . urlencode($msg));
    exit();
}
?>