<?php
session_start();
require_once 'log_activity.php'; // Incluído do arquivo antigo
$conn = new mysqli("localhost", "root", "", "intranet");

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Query do arquivo antigo para buscar o setor
    $stmt = $conn->prepare("
        SELECT u.*, s.nome as setor_nome 
        FROM users u 
        LEFT JOIN setores s ON u.setor_id = s.id 
        WHERE u.username = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Login OK
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_photo'] = $user['profile_photo'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['setor_id'] = $user['setor_id']; // Adicionado do arquivo antigo
            $_SESSION['setor_nome'] = $user['setor_nome']; // Adicionado do arquivo antigo

            // **NOVO**: Verifica se o tour deve ser exibido
            $_SESSION['show_tour'] = ($user['has_completed_tour'] == 0);

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

            logActivity($user['id'], 'Login bem-sucedido', "Usuário: " . $user['username']); // Adicionado do arquivo antigo
            header("Location: index.php");
            exit();
        } else {
            $error = "Senha incorreta.";
            logActivity(null, 'Tentativa de Login Falha', "Senha incorreta para o usuário: " . $username, 'error'); // Adicionado do arquivo antigo
        }
    } else {
        $error = "Usuário não encontrado.";
        logActivity(null, 'Tentativa de Login Falha', "Usuário não encontrado: " . $username, 'error'); // Adicionado do arquivo antigo
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intranet Comercial Souza</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        .slide-up { animation: slideUp 0.3s ease-out; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-2">
    <!-- Tela Principal de Boas-vindas -->
    <div id="welcomeScreen" class="w-full max-w-md md:max-w-lg mx-auto">
        <div class="glass-effect rounded-2xl p-4 md:p-6 text-center text-white shadow-2xl">
            <!-- Logo -->
            <div class="mb-4 md:mb-6">
                <div class="w-32 h-32 mx-auto mb-2 flex items-center justify-center">
                    <img src="img/logo.svg" alt="Logo Comercial Souza" class="w-full h-full object-contain" />
                </div>
                <div class="w-20 md:w-24 h-1 bg-white bg-opacity-50 mx-auto rounded-full"></div>
            </div>
            <!-- Texto de Boas-vindas -->
            <div class="mb-5">
                <h2 class="text-lg md:text-xl font-semibold mb-2 text-white">
                    Bem-vindo(a) à Intranet Comercial Souza
                </h2>
                <p class="text-sm md:text-base text-white text-opacity-90 leading-relaxed max-w-xl mx-auto mb-1">
                    Acesse facilmente documentos, comunicados, informações e serviços internos.<br>
                    Seu ambiente digital seguro, moderno e centralizado.<br>
                    <span class="font-medium">Faça login para começar!</span>
                </p>
            </div>
            <!-- Botão de Acesso -->
            <button 
                onclick="openLoginModal()"
                class="bg-white text-blue-700 font-semibold px-5 py-2 rounded-xl text-base hover:bg-opacity-90 transition-all duration-300 transform hover:scale-105 shadow-lg mb-4"
            >
                Acessar Intranet
            </button>
            <!-- Informações Adicionais -->
            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2 text-xs text-white text-opacity-80">
                <div class="flex items-center justify-center space-x-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>Ambiente Seguro</span>
                </div>
                <div class="flex items-center justify-center space-x-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                    </svg>
                    <span>Acesso Centralizado</span>
                </div>
                <div class="flex items-center justify-center space-x-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Disponível 24/7</span>
                </div>
            </div>
            <!-- Informações da Empresa -->
            <div class="mt-5 pt-4 border-t border-white border-opacity-30">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                    <a href="https://www.comercialsouzaatacado.com.br/" target="_blank" 
                       class="bg-white bg-opacity-10 hover:bg-opacity-20 border border-white border-opacity-30 rounded-xl p-2 transition-all duration-300 transform hover:scale-105 group">
                        <div class="flex items-center justify-center space-x-1 text-white">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <div class="text-left">
                                <h4 class="font-semibold text-xs underline">Nosso Site</h4>
                            </div>
                        </div>
                    </a>
                    <a href="https://maps.google.com/?q=Estrada+Vovó+Carolina,+1519,+Guaianazes,+São+Paulo,+SP,+08473-370" target="_blank"
                       class="bg-white bg-opacity-10 hover:bg-opacity-20 border border-white border-opacity-30 rounded-xl p-2 transition-all duration-300 transform hover:scale-105 group">
                        <div class="flex items-center justify-center space-x-1 text-white">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div class="text-left">
                                <h4 class="font-semibold text-xs underline">Localização</h4>
                            </div>
                        </div>
                    </a>
                    <a href="https://www.comercialsouzaatacado.com.br/central-de-atendimento/" target="_blank"
                       class="bg-white bg-opacity-10 hover:bg-opacity-20 border border-white border-opacity-30 rounded-xl p-2 transition-all duration-300 transform hover:scale-105 group">
                        <div class="flex items-center justify-center space-x-1 text-white">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-left">
                                <h4 class="font-semibold text-xs underline">Informações</h4>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="mt-2 pt-2 border-t border-white border-opacity-20 text-center">
                    <p class="text-xs text-white text-opacity-70">
                        © 2025 Comercial Souza Atacado Alimentício. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de Login -->
    <div id="loginModal" class="fixed inset-0 modal-overlay hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full slide-up">
            <!-- Cabeçalho do Modal -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Login</h3>
                            <p class="text-sm text-gray-500">Acesse sua conta</p>
                        </div>
                    </div>
                    <button 
                        onclick="closeLoginModal()"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Formulário de Login -->
            <form method="POST" class="p-6">
                <?php if ($error): ?>
                    <div class="mb-4 text-red-600 text-sm text-center font-semibold"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <div class="space-y-4">
                    <!-- Campo de Usuário -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Usuário
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="username" 
                                name="username"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pl-11"
                                placeholder="Digite seu usuário"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            >
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <!-- Campo de Senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Senha
                        </label>
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
                    <!-- Lembrar-me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600">Lembrar-me</span>
                        </label>
                        <a href="esqueci_senha.php" class="text-sm text-blue-600 hover:text-blue-500 transition-colors">
                            Esqueceu a senha?
                        </a>
                    </div>
                </div>
                <!-- Botões -->
                <div class="mt-6 space-y-3">
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]"
                    >
                        Entrar na Intranet
                    </button>
                    <a 
                        href="register.php"
                        class="w-full block bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
                    >
                        Criar nova conta
                    </a>
                    <button 
                        type="button"
                        onclick="closeLoginModal()"
                        class="w-full bg-gray-50 text-gray-500 py-3 px-4 rounded-lg font-medium hover:bg-gray-100 transition-colors"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
            <!-- Rodapé do Modal -->
            <div class="px-6 py-4 bg-gray-50 rounded-b-2xl">
                <p class="text-xs text-gray-500 text-center">
                    Problemas para acessar? Entre em contato com o suporte técnico.
                </p>
            </div>
        </div>
    </div>
    <script>
        function openLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex', 'fade-in');
            document.body.style.overflow = 'hidden';
        }
        function closeLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex', 'fade-in');
            document.body.style.overflow = 'auto';
        }
        // Fechar modal ao clicar fora dele
        document.getElementById('loginModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginModal();
            }
        });
        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginModal();
            }
        });
        // Abrir modal automaticamente se houve erro de login
        <?php if ($error): ?>
        window.onload = openLoginModal;
        <?php endif; ?>
    </script>
</body>
</html>
