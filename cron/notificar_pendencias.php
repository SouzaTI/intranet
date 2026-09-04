<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execução permitida somente pela linha de comando.');
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/services/EmailAssinaturaService.php';

$servico = new EmailAssinaturaService();
$configuracoes = $servico->obterConfiguracoes($pdo_intra);
if ((int)$configuracoes['notificar_lembrete_pendente'] !== 1) {
    echo 'Lembretes desativados na configuração administrativa.' . PHP_EOL;
    exit(0);
}
$horasLembrete = max(1, min(720, (int)$configuracoes['horas_para_lembrete']));

$stmt = $pdo_intra->query("SELECT af.fk_assinatura, af.glpi_user_id
    FROM assinaturas_fluxo af
    INNER JOIN sistemas_assinaturas sa ON sa.id = af.fk_assinatura
    WHERE af.status = 'pendente'
      AND sa.status IN ('aguardando', 'em_andamento')
      AND af.atualizado_em <= DATE_SUB(NOW(), INTERVAL {$horasLembrete} HOUR)
      AND NOT EXISTS (
          SELECT 1
          FROM assinatura_eventos ae
          WHERE ae.envelope_id = af.fk_assinatura
            AND ae.glpi_user_id = af.glpi_user_id
            AND ae.evento = 'EMAIL_ENVIADO'
            AND ae.descricao LIKE 'LEMBRETE_PENDENTE:%'
            AND ae.criado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
      )
    ORDER BY af.atualizado_em, af.id");

$pendencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$enviados = 0;
$falhas = 0;

foreach ($pendencias as $pendencia) {
    $envelopeId = (int) $pendencia['fk_assinatura'];
    $usuarioId = (int) $pendencia['glpi_user_id'];
    try {
        $servico->enviarLembretePendente($pdo_intra, $pdo_glpi, $envelopeId, $usuarioId);
        $enviados++;
        echo "[OK] Envelope {$envelopeId}; usuário {$usuarioId}." . PHP_EOL;
    } catch (Throwable $e) {
        $falhas++;
        $descricao = 'E-mail LEMBRETE_PENDENTE falhou: ' . mb_substr($e->getMessage(), 0, 400);
        error_log('Assinaturas/lembrete: ' . $e->getMessage());
        try {
            $pdo_intra->prepare("INSERT INTO assinatura_eventos
                (envelope_id, glpi_user_id, evento, descricao)
                VALUES (?, ?, 'ERRO', ?)")->execute([$envelopeId, $usuarioId, $descricao]);
        } catch (Throwable $registroErro) {
            error_log('Assinaturas/lembrete/registro: ' . $registroErro->getMessage());
        }
        echo "[ERRO] Envelope {$envelopeId}; usuário {$usuarioId}: {$e->getMessage()}" . PHP_EOL;
    }
}

echo "Resumo: {$enviados} enviado(s), {$falhas} falha(s), " . count($pendencias) . " pendência(s) processada(s)." . PHP_EOL;
exit($falhas > 0 ? 1 : 0);
