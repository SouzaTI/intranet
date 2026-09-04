<?php
require_once 'config.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim((string) ($_POST['pin'] ?? ''));
    $confirmacao = trim((string) ($_POST['confirmacao'] ?? ''));
    if (!preg_match('/^\d{4}$/', $pin)) $mensagem = 'O PIN precisa ter exatamente 4 números.';
    elseif ($pin !== $confirmacao) $mensagem = 'A confirmação não corresponde ao PIN.';
    else {
        $hash = password_hash($pin, PASSWORD_DEFAULT);
        $pdo_intra->prepare("INSERT INTO usuarios_permissoes (usuario_id, assinatura_pin)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE assinatura_pin = VALUES(assinatura_pin)")
            ->execute([(int) $_SESSION['user_id'], $hash]);
        registrarLog($pdo_intra, 'CONFIGUROU PIN', 'Usuário cadastrou ou alterou o PIN de assinatura.');
        header('Location: minhas_assinaturas.php?pin=ok'); exit;
    }
}
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-10">
    <div class="max-w-lg mx-auto bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm">
        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-2">Assinaturas</p>
        <h1 class="text-2xl font-black text-navy-900 mb-3">Configurar PIN</h1>
        <p class="text-sm text-slate-500 mb-6">Crie quatro números que serão solicitados para confirmar suas assinaturas.</p>
        <?php if ($mensagem): ?><div class="mb-4 p-3 rounded-xl bg-rose-50 text-rose-600 font-bold text-sm"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
        <form method="post" class="space-y-4">
            <div><label class="block text-xs font-black uppercase text-slate-400 mb-2">Novo PIN</label><input name="pin" type="password" inputmode="numeric" maxlength="4" required pattern="\d{4}" class="w-full p-4 rounded-xl bg-slate-50 border border-slate-200 text-center text-2xl tracking-[.5em]"></div>
            <div><label class="block text-xs font-black uppercase text-slate-400 mb-2">Confirmar PIN</label><input name="confirmacao" type="password" inputmode="numeric" maxlength="4" required pattern="\d{4}" class="w-full p-4 rounded-xl bg-slate-50 border border-slate-200 text-center text-2xl tracking-[.5em]"></div>
            <button class="w-full py-4 rounded-xl bg-navy-900 text-white font-black uppercase text-xs tracking-widest">Salvar PIN</button>
        </form>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
