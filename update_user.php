<?php
session_start();

$conn = new mysqli("localhost", "root", "", "intranet");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Garante que a pasta uploads existe antes do upload
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Atualizar informações do usuário
    if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['department'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $department = $conn->real_escape_string($_POST['department']);

        // Upload da foto de perfil
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $newName = 'uploads/profile_' . $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['profile_photo']['tmp_name'], $newName);

            // Atualiza no banco e na sessão
            $conn->query("UPDATE users SET profile_photo='$newName' WHERE id=$user_id");
            $_SESSION['profile_photo'] = $newName;
        }

        $sql = "UPDATE users SET username='$username', email='$email', phone='$phone', department='$department' WHERE id='$user_id'";

        $conn->query($sql);
    }

    // Excluir conta
    if (isset($_POST['delete_account'])) {
        $sql = "DELETE FROM users WHERE id='$user_id'";
        $conn->query($sql);
        // Opcional: session_destroy();
    }

    header("Location: intranet.php");
    exit();
}

$conn->close();
?>