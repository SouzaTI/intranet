<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/services/EmailAssinaturaService.php';
header('Content-Type: application/json; charset=utf-8');

function responder(bool $ok, string $msg, int $http = 200, array $extra = []): never
{
    http_response_code($http);
    echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    responder(false, 'Acesso negado.', 403);
}

$titulo = trim((string) ($_POST['titulo'] ?? ''));
$tipo = (string) ($_POST['tipo_fluxo'] ?? 'sequencial');
$criador = (int) $_SESSION['user_id'];
$emails = trim((string) ($_POST['emails_finalizacao'] ?? ''));
$assinantes = array_values(array_filter(array_map('intval', (array) ($_POST['assinantes'] ?? []))));
$ordensInformadas = array_map('intval', (array) ($_POST['ordem'] ?? []));

if ($titulo === '' || mb_strlen($titulo) > 255) responder(false, 'Informe um título válido.', 422);
if (!in_array($tipo, ['sequencial', 'paralelo'], true)) responder(false, 'Tipo de fluxo inválido.', 422);
if (!$assinantes) responder(false, 'Adicione pelo menos um assinante.', 422);
if (count($assinantes) !== count(array_unique($assinantes))) responder(false, 'O mesmo usuário não pode aparecer duas vezes.', 422);

$ordens = [];
foreach ($assinantes as $i => $uid) {
    $ordens[] = $tipo === 'sequencial' ? ($ordensInformadas[$i] ?? $i + 1) : 1;
}
if ($tipo === 'sequencial') {
    sort($ordens);
    if ($ordens !== range(1, count($assinantes))) responder(false, 'A ordem deve ser sequencial, sem repetições.', 422);
}

$listaEmails = [];
foreach (preg_split('/[,;\r\n]+/', $emails, -1, PREG_SPLIT_NO_EMPTY) as $email) {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responder(false, "E-mail inválido: {$email}", 422);
    $listaEmails[strtolower($email)] = $email;
}
$emailsNormalizados = implode(',', array_values($listaEmails));

$files = $_FILES['pdfs'] ?? $_FILES['pdf'] ?? null;
if (!$files) responder(false, 'Adicione pelo menos um PDF.', 422);
if (!is_array($files['name'])) {
    foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $campo) $files[$campo] = [$files[$campo]];
}
if (count($files['name']) > 20) responder(false, 'Limite de 20 PDFs por envelope.', 422);

$uploadBase = dirname(__DIR__) . '/uploads/assinaturas';
if (!is_dir($uploadBase) && !mkdir($uploadBase, 0750, true)) responder(false, 'Não foi possível preparar o armazenamento.', 500);

$finfo = new finfo(FILEINFO_MIME_TYPE);
$preparados = [];
foreach ($files['name'] as $i => $nome) {
    $erro = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
    $tmp = (string) ($files['tmp_name'][$i] ?? '');
    $tamanho = (int) ($files['size'][$i] ?? 0);
    if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) responder(false, "Falha no upload de {$nome}.", 422);
    if ($tamanho <= 0 || $tamanho > 25 * 1024 * 1024) responder(false, "{$nome}: limite de 25 MB por arquivo.", 422);
    if ($finfo->file($tmp) !== 'application/pdf' || file_get_contents($tmp, false, null, 0, 5) !== '%PDF-') {
        responder(false, "{$nome} não é um PDF válido.", 422);
    }
    $preparados[] = ['tmp' => $tmp, 'nome' => basename((string) $nome)];
}

$movidos = [];
$pdo_intra->beginTransaction();
try {
    $stmtEnv = $pdo_intra->prepare("INSERT INTO sistemas_assinaturas
        (titulo, arquivo_path, arquivo_hash, tipo_fluxo, criado_por, emails_finalizacao, status)
        VALUES (?, '', ?, ?, ?, ?, 'em_andamento')");
    $hashEnvelope = hash('sha256', $titulo . microtime(true) . random_bytes(16));
    $stmtEnv->execute([$titulo, $hashEnvelope, $tipo, $criador, $emailsNormalizados ?: null]);
    $envelopeId = (int) $pdo_intra->lastInsertId();

    $dirEnvelope = $uploadBase . '/' . $envelopeId;
    if (!mkdir($dirEnvelope, 0750, true) && !is_dir($dirEnvelope)) throw new RuntimeException('Falha ao criar diretório do envelope.');

    $stmtDoc = $pdo_intra->prepare("INSERT INTO assinatura_documentos
        (envelope_id, nome_original, arquivo_original_path, arquivo_atual_path, hash_original, hash_atual, status)
        VALUES (?, ?, ?, ?, ?, ?, 'em_assinatura')");
    $primeiroPath = '';
    $hashes = [];
    foreach ($preparados as $i => $arquivo) {
        $nomeDisco = sprintf('%02d_%s.pdf', $i + 1, bin2hex(random_bytes(12)));
        $destino = $dirEnvelope . '/' . $nomeDisco;
        if (!move_uploaded_file($arquivo['tmp'], $destino)) throw new RuntimeException('Falha ao armazenar ' . $arquivo['nome']);
        $movidos[] = $destino;
        $relativo = 'uploads/assinaturas/' . $envelopeId . '/' . $nomeDisco;
        $hash = hash_file('sha256', $destino);
        $hashes[] = $hash;
        if ($primeiroPath === '') $primeiroPath = $relativo;
        $stmtDoc->execute([$envelopeId, $arquivo['nome'], $relativo, $relativo, $hash, $hash]);
    }

    $hashEnvelope = hash('sha256', implode('|', $hashes));
    $pdo_intra->prepare('UPDATE sistemas_assinaturas SET arquivo_path = ?, arquivo_hash = ? WHERE id = ?')
        ->execute([$primeiroPath, $hashEnvelope, $envelopeId]);

    $stmtFluxo = $pdo_intra->prepare("INSERT INTO assinaturas_fluxo
        (fk_assinatura, glpi_user_id, ordem, status) VALUES (?, ?, ?, ?)");
    foreach ($assinantes as $i => $uid) {
        $ordem = $tipo === 'sequencial' ? ($ordensInformadas[$i] ?? $i + 1) : 1;
        $status = $tipo === 'paralelo' || $ordem === 1 ? 'pendente' : 'aguardando';
        $stmtFluxo->execute([$envelopeId, $uid, $ordem, $status]);
    }

    $pdo_intra->prepare("INSERT INTO assinatura_eventos
        (envelope_id, glpi_user_id, evento, descricao, ip_origem, user_agent)
        VALUES (?, ?, 'CRIADO', ?, ?, ?)")
        ->execute([$envelopeId, $criador, 'Envelope criado com ' . count($preparados) . ' documento(s).', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);

    $pdo_intra->commit();

    $falhasEmail = 0;
    $stmtAtivos = $pdo_intra->prepare("SELECT glpi_user_id FROM assinaturas_fluxo WHERE fk_assinatura = ? AND status = 'pendente'");
    $stmtAtivos->execute([$envelopeId]);
    $emailService = new EmailAssinaturaService();
    foreach ($stmtAtivos->fetchAll(PDO::FETCH_COLUMN) as $usuarioPendente) {
        try {
            $emailService->enviarNovoPendente($pdo_intra, $pdo_glpi, $envelopeId, (int)$usuarioPendente);
        } catch (Throwable $emailErro) {
            $falhasEmail++;
            error_log('Assinaturas/novo-pendente: ' . $emailErro->getMessage());
            $pdo_intra->prepare("INSERT INTO assinatura_eventos (envelope_id, glpi_user_id, evento, descricao, ip_origem)
                VALUES (?, ?, 'ERRO', ?, ?)")->execute([$envelopeId, (int)$usuarioPendente, 'E-mail NOVO_PENDENTE falhou: ' . mb_substr($emailErro->getMessage(), 0, 400), $_SERVER['REMOTE_ADDR'] ?? null]);
        }
    }

    responder(true, $falhasEmail ? 'Envelope criado. Uma ou mais notificações por e-mail falharam.' : 'Envelope criado e assinantes notificados.', 201, [
        'envelope_id' => $envelopeId, 'falhas_email' => $falhasEmail,
    ]);
} catch (Throwable $e) {
    if ($pdo_intra->inTransaction()) $pdo_intra->rollBack();
    foreach ($movidos as $arquivo) if (is_file($arquivo)) @unlink($arquivo);
    error_log('Assinaturas/cadastrar: ' . $e->getMessage());
    responder(false, 'Não foi possível criar o envelope.', 500);
}
