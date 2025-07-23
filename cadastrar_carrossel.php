<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagem = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $nomeImagem = uniqid('carrossel_').'.'.$ext;
            move_uploaded_file($_FILES['imagem']['tmp_name'], 'uploads/'.$nomeImagem);
            $imagem = $nomeImagem;
        }
    }

    if ($imagem) {
        $stmt = $conn->prepare("INSERT INTO carrossel_imagens (imagem) VALUES (?)");
        $stmt->bind_param("s", $imagem);
        $stmt->execute();
    }
    header("Location: index.php?carrossel=sucesso");
    exit();
}
?>