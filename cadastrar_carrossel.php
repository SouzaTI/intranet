<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'god'])) {
    header("Location: index.php?status=error&msg=Acesso negado.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagem'])) {
    $target_dir = "uploads/";
    $image_name = 'carrossel_' . uniqid() . '_' . basename($_FILES["imagem"]["name"]);
    $target_file = $target_dir . $image_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Verifica se o arquivo é uma imagem
    $check = getimagesize($_FILES["imagem"]["tmp_name"]);
    if ($check === false) {
        header("Location: index.php?section=info-upload&status=error&msg=O arquivo não é uma imagem.");
        exit();
    }

    // Verifica o tamanho do arquivo
    if ($_FILES["imagem"]["size"] > 5000000) { // 5MB
        header("Location: index.php?section=info-upload&status=error&msg=O arquivo é muito grande.");
        exit();
    }

    // Permite certos formatos de arquivo
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        header("Location: index.php?section=info-upload&status=error&msg=Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.");
        exit();
    }

    if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
        // Insere no banco de dados
        $stmt = $conn->prepare("INSERT INTO carrossel_imagens (imagem, data_upload) VALUES (?, NOW())");
        $stmt->bind_param("s", $image_name);

        if ($stmt->execute()) {
            header("Location: index.php?section=info-upload&status=success&msg=Imagem do carrossel adicionada com sucesso.");
        } else {
            header("Location: index.php?section=info-upload&status=error&msg=Erro ao salvar a imagem no banco de dados.");
        }
        $stmt->close();
    } else {
        header("Location: index.php?section=info-upload&status=error&msg=Erro ao fazer upload da imagem.");
    }
} else {
    header("Location: index.php?section=info-upload&status=error&msg=Requisição inválida.");
}

$conn->close();
?>