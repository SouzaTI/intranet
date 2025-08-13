<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$messages = [];
$status = 'success'; // Começa como sucesso, muda para 'error' se algo falhar

// --- Lógica para Alteração de Senha ---
if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $messages[] = "Para alterar a senha, todos os três campos de senha devem ser preenchidos.";
        $status = 'error';
    } elseif ($new_password !== $confirm_password) {
        $messages[] = "A nova senha e a confirmação não correspondem.";
        $status = 'error';
    } else {
        // Busca a senha atual do usuário no banco
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($current_password, $user['password'])) {
            // Senha atual está correta, atualiza para a nova
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $hashed_password, $user_id);
            if ($stmt_update->execute()) {
                $messages[] = "Senha alterada com sucesso.";
            } else {
                $messages[] = "Erro ao atualizar a senha.";
                $status = 'error';
            }
            $stmt_update->close();
        } else {
            $messages[] = "A senha atual está incorreta.";
            $status = 'error';
        }
    }
}

// --- Lógica para Alteração da Foto de Perfil ---
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed_types)) {
        $messages[] = "Formato de arquivo de imagem inválido. Apenas JPG, PNG e GIF são permitidos.";
        $status = 'error';
    } elseif ($file['size'] > $max_size) {
        $messages[] = "O arquivo de imagem é muito grande. O tamanho máximo é 2MB.";
        $status = 'error';
    } else {
        $upload_dir = __DIR__ . '/uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Remove a foto antiga se existir para não acumular lixo
        $stmt_old_photo = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt_old_photo->bind_param("i", $user_id);
        $stmt_old_photo->execute();
        $old_photo_path = $stmt_old_photo->get_result()->fetch_assoc()['profile_photo'] ?? null;
        if ($old_photo_path && file_exists(__DIR__ . '/' . $old_photo_path)) {
            unlink(__DIR__ . '/' . $old_photo_path);
        }
        $stmt_old_photo->close();

        // Cria um nome de arquivo único e seguro
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $new_filename;
        $db_path = 'uploads/profiles/' . $new_filename; // Caminho relativo para o banco

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Atualiza o caminho no banco de dados
            $stmt_update_photo = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt_update_photo->bind_param("si", $db_path, $user_id);
            if ($stmt_update_photo->execute()) {
                $_SESSION['profile_photo'] = $db_path; // Atualiza a sessão imediatamente
                $messages[] = "Foto de perfil atualizada com sucesso.";
            } else {
                $messages[] = "Erro ao salvar o caminho da foto no banco de dados.";
                $status = 'error';
            }
            $stmt_update_photo->close();
        } else {
            $messages[] = "Falha ao mover o arquivo de imagem para o destino.";
            $status = 'error';
        }
    }
}

// --- Redirecionamento Final ---
if (empty($messages) && (empty($_POST['current_password']) && empty($_FILES['profile_photo']['name']))) {
    // Se nada foi enviado, apenas redireciona de volta sem mensagem.
    header("Location: index.php?section=profile");
    exit();
}

$msg = implode(' ', $messages);
header("Location: index.php?section=profile&status=$status&msg=" . urlencode($msg));
exit();
