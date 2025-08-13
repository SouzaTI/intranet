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
            $_SESSION['department'] = $user['department']; // Adiciona o departamento à sessão
 
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
            min-height: 100vh;
            min-width: 100vw;
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        /* Remova o overlay de transparência */
        body::before {
            display: none;
        }
        .login-container {
            position: relative;
            z-index: 1;
            width: 520px;              /* aumente a largura */
            max-width: 98vw;
            background: #fff;          /* fundo branco sólido */
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 56px 48px 36px 48px; /* mais espaço interno */
            box-sizing: border-box;
            text-align: center;
            color: #254c90;
        }
        .login-container .welcome-header h1 {
            font-size: 2.4rem;
            font-weight: bold;
            color: #1d3870;
            margin-bottom: 18px;
            line-height: 1.15;
            letter-spacing: -1px;
        }
        .login-container .welcome-header p {
            font-size: 1.15rem;
            color: #374151;
            margin-bottom: 32px;
            margin-top: 0;
            line-height: 1.7;
            font-weight: 400;
        }
        .login-container img {
            width: 110px;
            max-width: 60%;
            margin-bottom: 24px;
            margin-top: 8px;
        }
        .login-container h2 {
            color: #0052a5;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .login-container input {
            width: 100%;
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
            width: 80%;
            max-width: 260px;
            padding: 15px;
            background: #0052a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 19px;
            font-weight: bold;
            transition: background 0.3s ease;
            margin-top: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(37,76,144,0.08);
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
        .footer-text {
            margin-top: 24px;
            font-size: 12px;
            color: #6b7280;
        }
        .hidden {
            display: none;
        }
        @media (max-width: 700px) {
            .login-container {
                width: 100vw;
                max-width: 99vw;
                padding: 24px 6px 18px 6px;
                border-radius: 10px;
            }
            .login-container .welcome-header h1 {
                font-size: 1.3rem;
            }
            .login-container img {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="img/logo.svg" alt="Logo">
        <div class="welcome-header">
            <h1>Bem-vindo à Intranet Comercial Souza</h1>
            <p>
                Seu portal centralizado para acesso a documentos, comunicados, informações e serviços internos.<br>
                Ambiente seguro, moderno e exclusivo para colaboradores.<br>
                <span style="color:#1d3870;font-weight:600;">Use suas credenciais para acessar o sistema.</span>
            </p>
        </div>
        <div id="login-intro">
            <button id="access-btn">Acessar Sistema</button>
        </div>
        <div id="login-form-wrapper" class="hidden w-full">
            <h2>Entrar na Conta</h2>
            <?php if (!empty($error)): ?>
                <div style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="w-full">
                <input type="text" name="username" placeholder="Nome de Usuário" required>
                <input type="password" name="password" placeholder="Senha" required>
                <button type="submit">Entrar</button>
            </form>
            <a href="register.php" class="register-link">Criar nova conta</a>
        </div>
        <p class="footer-text">© 2025 Comercial Souza – Todos os direitos reservados</p>
    </div>

    <script>
        const accessBtn = document.getElementById('access-btn');
        const loginIntro = document.getElementById('login-intro');
        const loginFormWrapper = document.getElementById('login-form-wrapper');

        accessBtn.addEventListener('click', function() {
            loginIntro.classList.add('hidden');
            loginFormWrapper.classList.remove('hidden');
        });

        // Se houver um erro de login (a página recarregou), mostra o formulário diretamente
        <?php if (!empty($error)): ?>
            loginIntro.classList.add('hidden');
            loginFormWrapper.classList.remove('hidden');
        <?php endif; ?>
    </script>
</body>
</html>
