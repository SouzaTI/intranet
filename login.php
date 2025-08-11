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
            background: url('img/fachada_souza.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        /* Overlay para escurecer a imagem de fundo */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(37, 76, 144, 0.5); /* Azul escuro da intranet, semi-transparente */
            backdrop-filter: blur(2px); /* Efeito de desfoque (opcional, visual moderno) */
            z-index: 0;
        }
        .login-container {
            position: fixed;
            right: 0;
            top: 0;
            height: 100%;
            width: 450px;
            max-width: 90%;
            background: rgba(255, 255, 255, 0.98); /* Fundo um pouco mais opaco */
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            box-sizing: border-box;
            text-align: center;
            color: #254c90;
            z-index: 1;
        }
        .login-container .welcome-header h1 {
            font-size: 2rem; /* 32px */
            font-weight: bold;
            color: #1d3870;
            margin-bottom: 24px;
            line-height: 1.3;
        }
        /* Estilos para a nova introdução no painel de login */
        #login-intro {
            width: 100%;
            text-align: center;
        }
        #login-intro .description {
            font-size: 1rem;
            line-height: 1.6;
            color: #374151;
            margin-bottom: 2rem;
        }
        .hidden {
            display: none;
        }
        .login-container img {
            width: 200px;
            max-width: 90%;
            margin-bottom: 24px;
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
        @media (max-width: 768px) {
            body {
                /* Re-add flex to center on mobile */
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-image: none;
                background: #254c90;
            }
            .login-container {
                position: relative; /* Revert fixed positioning */
                right: auto; top: auto; /* Reset */
                height: auto; /* Reset */
                width: 100%;
                max-width: 380px;
                padding: 30px;
                border-radius: 12px; /* Add back */
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Novo cabeçalho de boas-vindas -->
        <div class="welcome-header">
            <h1>Bem-vindo(a) à<br>Intranet Comercial Souza</h1>
        </div>
        <img src="img/logo.svg" alt="Logo">

        <!-- Etapa 1: Introdução e Botão Acessar -->
        <div id="login-intro">
            <p class="description">
                Seu portal centralizado para acesso a documentos, informações, comunicados e serviços da Comercial Souza.
            </p>
            <button id="access-btn">ACESSAR</button>
        </div>

        <!-- Etapa 2: Formulário de Login (Oculto) -->
        <div id="login-form-wrapper" class="hidden w-full">
            <h2>Entrar na Conta</h2>
            <?php if (!empty($error)): ?>
                <div style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="w-full">
                <input type="text" name="username" placeholder="Nome de Usuário" required>
                <input type="password" name="password" placeholder="Senha" required>
                <button type="submit">ENTRAR</button>
            </form>
            <a href="register.php" class="register-link">Criar nova conta</a>
        </div>

        <!-- Rodapé -->
        <p class="footer-text">Todos os direitos reservados à Comercial Souza © 2025</p>
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
