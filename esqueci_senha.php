<?php
session_start();
$conn = new mysqli("localhost", "root", "", "intranet");

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // Busca o usuário pelo e-mail
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        // Gera token seguro
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $user_id = $user['id'];

        // Salva o token no banco de dados
        $stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $user_id, $token, $expires);
        $stmt2->execute();
        $stmt2->close();

        // IMPORTANTE: Configure o envio de e-mail no seu servidor (php.ini)
        // e substitua os valores de placeholder abaixo.
        $reset_link = "http://localhost/intranet/redefinir_senha.php?token=$token"; // Ajuste o domínio conforme necessário
        $to = $email;
        $subject = "Recuperação de senha - Intranet Comercial Souza";
        $message = "Olá, {$user['username']}!\n\nClique no link abaixo para redefinir sua senha:\n$reset_link\n\nEste link é válido por 1 hora.";
        $headers = "From: no-reply@comercialsouza.com.br\r\n"; // Use um e-mail do seu domínio

        // A função mail() pode não funcionar em ambiente local (XAMPP) sem configuração.
        // mail($to, $subject, $message, $headers);

        // Para fins de teste em ambiente local, você pode exibir o link em vez de enviar.
        // Comente a linha mail() acima e descomente a linha abaixo para ver o link gerado.
        // $success = "Link de redefinição (para teste): $reset_link";

        $success = "Se o e-mail estiver correto, um link de redefinição foi enviado.";

    } else {
        // Mensagem genérica para não confirmar se um e-mail existe ou não no sistema.
        $success = "Se o e-mail estiver correto, um link de redefinição foi enviado.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Intranet Comercial Souza</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
        }
        .modal-effect {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-2">
    <div class="modal-effect fade-in">
        <div class="p-6 border-b border-gray-100 flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 10a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Recuperar Senha</h3>
                <p class="text-sm text-gray-500">Informe seu e-mail de cadastro</p>
            </div>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <?php if ($error): ?>
                <div class="mb-2 text-red-600 text-sm text-center font-semibold bg-red-50 rounded-lg py-2 px-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-2 text-green-700 text-sm text-center font-semibold bg-green-50 rounded-lg py-2 px-3"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <div class="relative">
                    <input 
                        type="email" 
                        id="email" 
                        name="email"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pl-11"
                        placeholder="Digite seu e-mail"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.94 6.94a1.5 1.5 0 012.12 0l4.95 4.95a1.5 1.5 0 002.12 0l4.95-4.95a1.5 1.5 0 112.12 2.12l-4.95 4.95a3.5 3.5 0 01-4.95 0L2.94 9.06a1.5 1.5 0 010-2.12z"/>
                    </svg>
                </div>
            </div>
            <button 
                type="submit"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]"
            >
                Solicitar redefinição
            </button>
            <?php endif; ?>

            <a 
                href="login.php"
                class="w-full block bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
            >
                Voltar ao login
            </a>
        </form>
        <div class="px-6 py-4 bg-gray-50 rounded-b-2xl">
            <p class="text-xs text-gray-500 text-center">
                Problemas para acessar? Entre em contato com o suporte técnico.
            </p>
        </div>
    </div>
</body>
</html>
