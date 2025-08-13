<?php
session_start();
$conn = new mysqli("localhost", "root", "", "intranet");

// Função para remover acentos e caracteres especiais
function remover_acentos($str) {
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    $str = preg_replace('/[^A-Za-z0-9 _@.-]/', '', $str);
    return $str;
}

$error = null;
$success = null;
$username = '';
$department = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = remover_acentos(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $department = remover_acentos(trim($_POST['department'] ?? ''));
    $email = remover_acentos(trim($_POST['email'] ?? ''));

    // Verifica se o username ou email já existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Já existe uma conta com este nome de usuário ou e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "E-mail inválido.";
    } elseif (strlen($username) < 3 || strlen($password) < 4) {
        $error = "Usuário e senha precisam ter pelo menos 3 e 4 caracteres.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt_insert = $conn->prepare("INSERT INTO users (username, password, department, email, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt_insert->bind_param("ssss", $username, $hash, $department, $email);
        if ($stmt_insert->execute()) {
            $success = "Conta criada com sucesso! Você já pode fazer login.";
            // Limpa os campos após sucesso
            $username = '';
            $department = '';
            $email = '';
        } else {
            $error = "Erro ao criar conta: " . $conn->error;
        }
        $stmt_insert->close();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Intranet Comercial Souza</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
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
        .fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-2">
    <div class="modal-effect fade-in">
        <div class="p-6 border-b border-gray-100 flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Criar nova conta</h3>
                <p class="text-sm text-gray-500">Preencha os campos para se cadastrar</p>
            </div>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?php if ($error): ?>
                <div class="mb-2 text-red-600 text-sm text-center font-semibold bg-red-50 rounded-lg py-2"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="mb-2 text-green-700 text-sm text-center font-semibold bg-green-50 rounded-lg py-2"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="username" 
                        name="username"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pl-11"
                        placeholder="Digite seu usuário"
                        value="<?= htmlspecialchars($username) ?>"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
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
                        value="<?= htmlspecialchars($email) ?>"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.94 6.94a1.5 1.5 0 012.12 0l4.95 4.95a1.5 1.5 0 002.12 0l4.95-4.95a1.5 1.5 0 112.12 2.12l-4.95 4.95a3.5 3.5 0 01-4.95 0L2.94 9.06a1.5 1.5 0 010-2.12z"/>
                    </svg>
                </div>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pl-11"
                        placeholder="Digite sua senha"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <div>
                <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="department" 
                        name="department"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pl-11"
                        placeholder="Digite seu departamento"
                        value="<?= htmlspecialchars($department) ?>"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 7a4 4 0 118 0 4 4 0 01-8 0zM2 13a6 6 0 0112 0v1a1 1 0 01-1 1H3a1 1 0 01-1-1v-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <button 
                type="submit"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]"
            >
                Criar Conta
            </button>
            <a 
                href="login.php"
                class="w-full block bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
            >
                Voltar para Login
            </a>
        </form>
    </div>
</body>
</html>