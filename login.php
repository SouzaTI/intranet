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
            position: relative; /* Para o overlay */
        }
        /* Overlay para escurecer a imagem de fundo */
        body::before {
            content: '';
            position: fixed; /* Fixado para cobrir a tela inteira */
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(37, 76, 144, 0.5); /* Azul escuro da intranet, semi-transparente */
            backdrop-filter: blur(2px); /* Efeito de desfoque (opcional, visual moderno) */
            z-index: 0;
        }
        .login-container {
            position: fixed; /* Fixa o painel na tela */
            right: 0; /* Alinha à direita */
            top: 0;
            height: 100%; /* Ocupa toda a altura */
            width: 420px; /* Largura da barra lateral */
            background: rgba(255, 255, 255, 0.98); /* Fundo um pouco mais opaco */
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2); /* Sombra à esquerda */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            box-sizing: border-box; /* Garante que o padding não aumente a largura */
            text-align: center;
            color: #254c90;
            z-index: 1;
        }
        .welcome-text {
            position: relative;
            z-index: 1;
            color: white;
            padding: 0 5%; /* Padding lateral para o texto não encostar nas bordas */
            padding-right: 420px; /* Adiciona espaço para o painel de login não sobrepor */
            box-sizing: border-box; /* Garante que o padding seja calculado corretamente na largura total */
            height: 100vh; /* Ocupa a altura toda da tela */
            display: flex;
            flex-direction: column; /* Empilha os blocos de texto */
            justify-content: center; /* Centraliza o conteúdo principal verticalmente */
            align-items: center; /* Centraliza o conteúdo horizontalmente no espaço disponível */
            text-align: center; /* Alinha o texto dos parágrafos */
        }
        .welcome-text h1 {
            font-size: 3.5rem; /* 56px */
            font-weight: bold;
            line-height: 1.2;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.6);
        }
        .welcome-text .welcome-subtitle {
            font-size: 1.25rem; /* 20px */
            line-height: 1.6;
            margin-top: 1.5rem; /* 24px */
            max-width: 550px; /* Limita a largura para melhor leitura */
            text-shadow: 1px 1px 6px rgba(0,0,0,0.7);
            font-weight: 400;
        }
        .welcome-text .footer-text {
            margin-top: auto; /* Empurra o rodapé para o final do container flex */
            padding-bottom: 40px; /* Espaçamento da parte inferior */
            font-size: 0.875rem; /* 14px */
            opacity: 0.8;
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
            margin-bottom: 20px;
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
        @media (max-width: 768px) { /* Aumentei o breakpoint para tablets */
            body {
                /* Reativa o flexbox para centralizar no mobile */
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-image: none;
                background: #254c90;
            }
            .welcome-text {
                display: none; /* Esconde o texto de boas-vindas */
            }
            .login-container {
                position: relative; /* Volta ao posicionamento normal */
                right: auto; top: auto; /* Reseta posicionamento fixo */
                height: auto; /* Altura automática */
                width: 100%;
                max-width: 380px;
                border-radius: 12px; /* Readiciona o radius */
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-text">
        <div>
            <h1>Bem-vindo(a) à<br>Intranet Comercial Souza</h1>
        </div>
        <p class="footer-text">Todos os direitos reservados à Comercial Souza © 2025</p>
    </div>
    <div class="login-container">
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
