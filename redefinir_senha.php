<?php
session_start();
$conn = new mysqli("localhost", "root", "", "intranet");

$token = $_GET['token'] ?? '';
$error = null;
$success = null;

if ($token) {
    // Busca o token válido
    $stmt = $conn->prepare("SELECT pr.id, pr.user_id, u.username FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        $error = "Token inválido ou expirado.";
    }
} else {
    $error = "Token ausente.";
}

// Se formulário enviado e token válido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($reset['user_id'])) {
    $new_password = $_POST['password'] ?? '';
    if (strlen($new_password) < 4) {
        $error = "A senha deve ter pelo menos 4 caracteres.";
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        // Atualiza a senha
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $reset['user_id']);
        $stmt->execute();
        $stmt->close();

        // Marca o token como usado
        $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $stmt->bind_param("i", $reset['id']);
        $stmt->execute();
        $stmt->close();

        $success = "Senha redefinida com sucesso! Você já pode fazer login.";
    }
}
?>
<!-- ...HTML visual igual ao login/cadastro... -->
<form method="POST" class="p-6 space-y-4">
    <?php if ($error): ?>
        <div class="mb-2 text-red-600 text-sm text-center font-semibold bg-red-50 rounded-lg py-2"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="mb-2 text-green-700 text-sm text-center font-semibold bg-green-50 rounded-lg py-2"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!$success && !$error): ?>
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
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
    <button 
        type="submit"
        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]"
    >
        Redefinir senha
    </button>
    <?php endif; ?>
    <a 
        href="login1.php"
        class="w-full block bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
    >
        Voltar ao login
    </a>
</form>