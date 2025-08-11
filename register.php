<?php
session_start();
$conn = new mysqli("localhost", "root", "", "intranet");

// Função para remover acentos e caracteres especiais
function remover_acentos($str) {
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    $str = preg_replace('/[^A-Za-z0-9 _-]/', '', $str);
    return $str;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = remover_acentos($conn->real_escape_string($_POST['username']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department = remover_acentos($conn->real_escape_string($_POST['department']));

    // Verifica se o username já existe
    $check = $conn->query("SELECT id FROM users WHERE username='$username' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $error = "Já existe uma conta com este nome de usuário.";
    } else {
        $sql = "INSERT INTO users (username, password, department, role) 
                VALUES ('$username', '$password', '$department', 'user')";
        if ($conn->query($sql) === TRUE) {
            // Apenas insere o usuário e redireciona para o login (não faz login automático)
            header("Location: login.php?cadastro=ok");
            exit();
        } else {
            $error = "Erro ao criar conta: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Criar Nova Conta</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('img/fachada_souza.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative; /* Para o overlay */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        /* Overlay para escurecer a imagem de fundo */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(37, 76, 144, 0.5); /* Azul escuro da intranet, semi-transparente */
            backdrop-filter: blur(2px); /* Efeito de desfoque (opcional, visual moderno) */
            z-index: 0;
        }
        .register-container {
            position: relative;
            width: 450px;
            max-width: 90%;
            background: rgba(255, 255, 255, 0.98); /* Fundo um pouco mais opaco */
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            padding: 40px;
            text-align: center;
            color: #254c90;
            z-index: 1;
            border-radius: 12px;
        }
        .register-container img {
            width: 200px;
            max-width: 90%;
            margin-bottom: 20px;
        }
        .register-container h2 {
            color: #0052a5;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .register-container input {
            width: 100%;
            padding: 15px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .register-container input:focus {
            border-color: #0052a5;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 82, 165, 0.5);
        }
        .register-container button {
            width: 100%;
            padding: 15px;
            background: #0052a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .register-container button:hover {
            background: #003d7a;
        }
        .register-container button:active {
            transform: scale(0.98);
        }
        .register-container .login-link {
            margin-top: 20px;
            display: block;
            color: #0052a5;
            text-decoration: underline;
            font-size: 15px;
        }
        .error-message {
            color: #b91c1c;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .support-text {
            margin-top: 24px;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            body {
                background-image: none;
                background: #254c90;
            }
            .register-container {
                width: 100%;
                max-width: 380px;
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <img src="img/logo.svg" alt="Logo">
        <h2>Criar Nova Conta</h2>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Nome de Usuário" required>
            <input type="password" name="password" placeholder="Senha" required>
            <input type="text" name="department" placeholder="Departamento">
            <button type="submit">Criar Conta</button>
        </form>
        <a href="login.php" class="login-link">Já tem conta? Entrar</a>
        <div class="support-text">
            Caso não consiga criar sua conta ou tenha dúvidas, entre em contato com o suporte técnico.
        </div>
    </div>
</body>
</html>