<?php
session_start();
$conn = new mysqli("localhost", "root", "", "intranet");

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Usar prepared statements para prevenir SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_photo'] = $user['profile_photo'];
 
            // Carrega as permissões de seção do usuário
            $user_id = $user['id'];
            $allowed_sections = [];
            $stmt_sections = $conn->prepare("SELECT section_name FROM user_sections WHERE user_id = ?");
            $stmt_sections->bind_param("i", $user_id);
            $stmt_sections->execute();
            $result_sections = $stmt_sections->get_result();
            while ($row = $result_sections->fetch_assoc()) {
                $allowed_sections[] = $row['section_name'];
            }
            $_SESSION['allowed_sections'] = $allowed_sections;

            header("Location: index.php");
            exit();
        } else {
            $error = "Senha incorreta.";
        }
    } else {
        $error = "Usuário não encontrado.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Entrar na Conta</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #254c90;
        }
        .login-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 350px;
            position: relative;
        }
        .login-container img {
            width: 200px;
            max-width: 90%;
            margin-bottom: 20px;
        }
        .login-container h2 {
            color: #0052a5;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .login-container input {
            width: calc(100% - 20px);
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .login-container input:focus {
            border-color: #0052a5;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 82, 165, 0.5);
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #0052a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .login-container button:hover {
            background: #003d7a;
        }
        .login-container button:active {
            transform: scale(0.98);
        }
        .login-container .register-link {
            margin-top: 16px;
            display: block;
            color: #0052a5;
            text-decoration: underline;
            font-size: 15px;
        }
        @media (max-width: 600px) {
            body {
                background-image: none;
                background: #254c90;
            }
            .login-container {
                width: 100%;
                max-width: 320px;
                padding: 16px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="img/logo.svg" alt="Logo">
        <h2>Entrar na Conta</h2>
        <?php if (!empty($error)): ?>
            <div style="color: red; margin-bottom: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Nome de Usuário" required>
            <input type="password" name="password" placeholder="Senha" required>
            <button type="submit">ENTRAR</button>
        </form>
        <a href="register.php" class="register-link">Criar nova conta</a>
    </div>
</body>
</html>
