<?php
session_start();
$conn = new mysqli("localhost", "root", "", "intranet");

$token = $_GET['token'] ?? '';
$error = null;
$success = null;
$user_info = null;

if ($token) {
    // Busca o token válido e o nome de usuário
    $stmt = $conn->prepare("SELECT pr.id, pr.user_id, u.username FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
    $stmt->close();

    if (!$user_info) {
        $error = "Token inválido, expirado ou já utilizado.";
    }
} else {
    $error = "Nenhum token fornecido. Por favor, use o link enviado para o seu email.";
}

// Se o formulário for enviado e o token for válido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_info) {
    $new_password = $_POST['password'] ?? '';
    if (strlen($new_password) < 4) {
        $error = "A nova senha deve ter pelo menos 4 caracteres.";
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        // Atualiza a senha do usuário
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $user_info['user_id']);
        $stmt->execute();
        $stmt->close();

        // Marca o token como usado para prevenir reutilização
        $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_info['id']);
        $stmt->execute();
        $stmt->close();

        $success = "Senha redefinida com sucesso! Você já pode retornar e fazer login com sua nova senha.";
        // Limpa as informações do usuário para não exibir mais o formulário
        $user_info = null; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Intranet Comercial Souza</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <!-- Cabeçalho -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-900">Redefinir Senha</h3>
                    <p class="text-sm text-gray-500">
                        <?php if($user_info): ?>
                            Olá, <?= htmlspecialchars($user_info['username']) ?>. Defina sua nova senha.
                        <?php else: ?>
                            Siga as instruções para continuar.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulário -->
        <form method="POST" action="" class="p-6 space-y-4">
            <?php if ($error): ?>
                <div class="mb-2 text-red-600 text-sm text-center font-semibold bg-red-50 rounded-lg py-2 px-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-2 text-green-700 text-sm text-center font-semibold bg-green-50 rounded-lg py-2 px-3"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($user_info && !$success): ?>
                <!-- Campo de Senha -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nova Senha</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pl-11"
                            placeholder="Digite a nova senha"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="currentColor" viewBox="0 0 20 20">
                           <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
                <!-- Botão de Redefinir -->
                <button 
                    type="submit"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]"
                >
                    Salvar Nova Senha
                </button>
            <?php endif; ?>

            <!-- Botão Voltar -->
            <a 
                href="login.php" 
                class="w-full block bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
            >
                Voltar para o Login
            </a>
        </form>
    </div>
</body>
</html>
