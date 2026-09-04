<?php
declare(strict_types=1);

require_once 'config.php';
require_once __DIR__ . '/services/EmailAssinaturaService.php';

if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$usuarioId = (int)$_SESSION['user_id'];
$usuarioAdmin = !empty($_SESSION['is_admin']);
if (!$usuarioAdmin) {
    $stmtAdmin = $pdo_intra->prepare('SELECT is_admin FROM usuarios_permissoes WHERE usuario_id = ?');
    $stmtAdmin->execute([$usuarioId]);
    $usuarioAdmin = (int)$stmtAdmin->fetchColumn() === 1;
}
if (!$usuarioAdmin) { http_response_code(403); exit('Acesso permitido somente para administradores.'); }

if (empty($_SESSION['csrf_assinaturas_config'])) $_SESSION['csrf_assinaturas_config'] = bin2hex(random_bytes(32));
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_assinaturas_config'], $token)) {
        $erro = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
    } else {
        $horas = max(1, min(720, (int)($_POST['horas_para_lembrete'] ?? 24)));
        try {
            $pdo_intra->prepare("INSERT INTO assinatura_configuracoes
                (id, notificar_novo_pendente, notificar_documento_assinado,
                 notificar_lembrete_pendente, notificar_fluxo_concluido,
                 horas_para_lembrete, atualizado_por)
                VALUES (1, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                 notificar_novo_pendente = VALUES(notificar_novo_pendente),
                 notificar_documento_assinado = VALUES(notificar_documento_assinado),
                 notificar_lembrete_pendente = VALUES(notificar_lembrete_pendente),
                 notificar_fluxo_concluido = VALUES(notificar_fluxo_concluido),
                 horas_para_lembrete = VALUES(horas_para_lembrete),
                 atualizado_por = VALUES(atualizado_por)")
                ->execute([
                    isset($_POST['notificar_novo_pendente']) ? 1 : 0,
                    isset($_POST['notificar_documento_assinado']) ? 1 : 0,
                    isset($_POST['notificar_lembrete_pendente']) ? 1 : 0,
                    isset($_POST['notificar_fluxo_concluido']) ? 1 : 0,
                    $horas,
                    $usuarioId,
                ]);
            registrarLog($pdo_intra, 'CONFIGUROU NOTIFICACOES', 'Administrador atualizou as notificações do Portal de Assinaturas.');
            $mensagem = 'Configurações salvas. Os próximos eventos já usarão estas regras.';
        } catch (Throwable $e) {
            error_log('Assinaturas/configuracoes/salvar: ' . $e->getMessage());
            $erro = 'Não foi possível salvar. Confirme se o SQL de configuração foi executado.';
        }
    }
}

$config = (new EmailAssinaturaService())->obterConfiguracoes($pdo_intra);
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-10">
  <div class="max-w-3xl mx-auto">
    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-2">Portal de Assinaturas</p>
    <h1 class="text-3xl font-black text-slate-900 mb-2">Notificações por e-mail</h1>
    <p class="text-sm text-slate-500 mb-7">Defina quais comunicações automáticas serão enviadas. Desativar um aviso não interfere na assinatura, no hash ou na auditoria.</p>

    <?php if ($mensagem): ?><div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-sm"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 font-bold text-sm"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <form method="post" class="bg-white border border-slate-200 rounded-[2rem] p-6 md:p-8 shadow-sm space-y-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_assinaturas_config']) ?>">
      <?php
      $opcoes = [
        'notificar_novo_pendente' => ['Novo documento pendente', 'Avisa quando uma etapa é liberada para o usuário.', 'Essencial'],
        'notificar_documento_assinado' => ['Documento assinado', 'Confirma ao assinante e atualiza o criador do envelope.', 'Recomendado'],
        'notificar_lembrete_pendente' => ['Lembrete de pendência', 'Relembra o usuário quando o documento permanece sem assinatura.', 'Opcional'],
        'notificar_fluxo_concluido' => ['Fluxo concluído', 'Entrega o resumo final e os PDFs assinados aos destinatários.', 'Essencial'],
      ];
      foreach ($opcoes as $campo => [$titulo, $descricao, $nivel]): ?>
        <label class="flex items-start gap-4 p-4 rounded-2xl border border-slate-200 hover:border-blue-300 cursor-pointer">
          <input type="checkbox" name="<?= $campo ?>" value="1" class="mt-1 h-5 w-5" <?= (int)$config[$campo] === 1 ? 'checked' : '' ?>>
          <span class="flex-1"><span class="block font-black text-slate-900"><?= htmlspecialchars($titulo) ?></span><span class="block text-sm text-slate-500 mt-1"><?= htmlspecialchars($descricao) ?></span></span>
          <span class="text-[10px] uppercase tracking-wider font-black px-2 py-1 rounded-lg bg-slate-100 text-slate-500"><?= htmlspecialchars($nivel) ?></span>
        </label>
      <?php endforeach; ?>

      <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
        <label for="horas" class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">Enviar lembrete após</label>
        <div class="flex items-center gap-3"><input id="horas" name="horas_para_lembrete" type="number" min="1" max="720" value="<?= (int)$config['horas_para_lembrete'] ?>" class="w-28 p-3 rounded-xl border border-slate-200 bg-white font-bold"><span class="text-sm text-slate-500">horas de pendência</span></div>
        <p class="text-xs text-slate-400 mt-2">Depois do primeiro lembrete, o sistema envia no máximo um novo aviso a cada 24 horas.</p>
      </div>

      <div class="flex flex-col-reverse sm:flex-row gap-3 pt-3">
        <a href="minhas_assinaturas.php" class="flex-1 text-center py-4 rounded-xl border border-slate-200 text-slate-500 font-black uppercase text-xs tracking-wider">Voltar</a>
        <button class="flex-1 py-4 rounded-xl bg-slate-900 text-white font-black uppercase text-xs tracking-wider">Salvar configurações</button>
      </div>
    </form>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
