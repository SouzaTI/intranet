<?php
require_once 'config.php';

// BUG 1 FIX: evita conflito quando a sessão já foi iniciada pelo config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Acesso negado: Usuário não autenticado.");
}

// ── Modo Assinaturas: resolve o arquivo pelo banco e valida participação ──
if (isset($_GET['assinatura_doc_id']) || isset($_GET['path'])) {
    $usuarioId = (int) $_SESSION['user_id'];
    $docId = filter_input(INPUT_GET, 'assinatura_doc_id', FILTER_VALIDATE_INT);
    $pathInformado = (string) ($_GET['path'] ?? '');
    if ($docId) {
        $stmt = $pdo_intra->prepare("SELECT ad.*, sa.criado_por
            FROM assinatura_documentos ad JOIN sistemas_assinaturas sa ON sa.id = ad.envelope_id
            WHERE ad.id = ?");
        $stmt->execute([$docId]);
    } else {
        $stmt = $pdo_intra->prepare("SELECT ad.*, sa.criado_por
            FROM assinatura_documentos ad JOIN sistemas_assinaturas sa ON sa.id = ad.envelope_id
            WHERE ad.arquivo_original_path = ? OR ad.arquivo_atual_path = ? LIMIT 1");
        $stmt->execute([$pathInformado, $pathInformado]);
    }
    $assinaturaDoc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$assinaturaDoc) {
        http_response_code(404);
        die('Documento de assinatura não encontrado.');
    }
    $stmtAcesso = $pdo_intra->prepare("SELECT COUNT(*) FROM assinaturas_fluxo
        WHERE fk_assinatura = ? AND glpi_user_id = ?");
    $stmtAcesso->execute([$assinaturaDoc['envelope_id'], $usuarioId]);
    $podeAcessar = (int) $assinaturaDoc['criado_por'] === $usuarioId
        || (int) $stmtAcesso->fetchColumn() > 0
        || !empty($_SESSION['is_admin']);
    if (!$podeAcessar) {
        http_response_code(403);
        die('Acesso negado ao documento.');
    }

    $usarOriginal = ($_GET['versao'] ?? '') === 'original';
    $pathRelativo = $usarOriginal ? $assinaturaDoc['arquivo_original_path'] : $assinaturaDoc['arquivo_atual_path'];
    $pathReal = realpath(__DIR__ . '/' . ltrim($pathRelativo, '/\\'));
    $dirPermitido = realpath(__DIR__ . '/uploads/assinaturas/');
    if (!$pathReal || !$dirPermitido || ($pathReal !== $dirPermitido && $dirPermitido !== dirname($pathReal)
        && strncmp($pathReal, $dirPermitido . DIRECTORY_SEPARATOR, strlen($dirPermitido) + 1) !== 0)) {
        http_response_code(403);
        die('Caminho de documento inválido.');
    }
    if (!is_file($pathReal)) {
        http_response_code(404);
        die('Arquivo não encontrado.');
    }

    $modo = $_GET['modo'] ?? 'visualizar';
    $nome = preg_replace('/[^A-Za-z0-9._-]/u', '_', $assinaturaDoc['nome_original']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($modo === 'baixar' ? 'attachment' : 'inline') . '; filename="' . $nome . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    if (ob_get_length()) ob_clean();
    flush();
    readfile($pathReal);
    exit;
}

// ── Modo original: serve por ID (docs_fluxo_simples) ─────────────────────
$doc_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$doc_id) die("Documento inválido.");

$stmt = $pdo_intra->prepare("SELECT * FROM docs_fluxo_simples WHERE id = ?");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) die("Arquivo não encontrado.");

$stmt_ti = $pdo_intra->prepare("
    SELECT COUNT(*) FROM usuarios_grupos ug
    JOIN grupos_intranet g ON ug.grupo_id = g.id
    WHERE ug.usuario_id = ? AND g.nome = 'FACILITIES & T.I'
");
$stmt_ti->execute([$_SESSION['user_id']]);
$is_ti   = ($stmt_ti->fetchColumn() > 0);
$is_admin = ($_SESSION['is_admin'] ?? false);
$is_dono  = ($doc['usuario_id'] == $_SESSION['user_id']);
$eh_aprovado      = ($doc['status'] === 'Aprovado');
$setor_doc        = strtoupper($doc['setor_origem']);
$pertence_ao_setor = (
    $setor_doc === ($_SESSION['setor_principal'] ?? '') ||
    in_array($setor_doc, $_SESSION['pastas_extras'] ?? [])
);
// Adicionamos a condição: OU o setor do documento é 'GERAL'
$eh_geral = ($setor_doc === 'GERAL');

$pode_acessar = ($is_admin || $is_ti || $is_dono || ($eh_aprovado && ($pertence_ao_setor || $eh_geral)));

if (!$pode_acessar) {
    http_response_code(403);
    die("Acesso negado: Você não possui permissão para este recurso.");
}

$caminho_arquivo = __DIR__ . '/uploads_fluxo/' . $doc['nome_arquivo'];
if (!file_exists($caminho_arquivo)) die("Erro: Arquivo não localizado.");

$extensao    = strtolower(pathinfo($caminho_arquivo, PATHINFO_EXTENSION));
$mime        = $extensao === 'pdf' ? 'application/pdf' : mime_content_type($caminho_arquivo);
$pode_baixar = ($is_admin || $is_dono || $eh_aprovado);
$modo        = $_GET['modo'] ?? 'visualizar';
$nome_limpo  = preg_replace('/[^A-Za-z0-9\-]/', '_', $doc['titulo'] ?? 'Documento_Oficial');
$nome_dl     = $nome_limpo . '_V' . ($doc['versao_atual'] ?? '1') . '.' . $extensao;

// =====================================================================
// 🔥 NOVA LÓGICA: TELA AMIGÁVEL PARA ARQUIVOS NÃO SUPORTADOS (Word/Excel)
// =====================================================================
$formatos_navegador = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'ogg'];

if ($modo === 'visualizar' && !in_array($extensao, $formatos_navegador)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visualização Indisponível</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
        <style>body { font-family: 'Inter', sans-serif; }</style>
    </head>
    <body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
        <div class="bg-white p-10 rounded-[2rem] shadow-xl border border-slate-200 text-center max-w-md w-full">
            <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-4xl mx-auto mb-6">
                <?= ($extensao === 'docx' || $extensao === 'doc') ? '📝' : '📊' ?>
            </div>
            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter mb-2">Formato não suportado na web</h2>
            <p class="text-sm text-slate-500 font-medium mb-8">O navegador não consegue exibir arquivos <b>.<?= $extensao ?></b> nativamente. Baixe o documento para visualizá-lo.</p>
            
            <a href="serve_documento.php?id=<?= $doc_id ?>&modo=baixar" 
               class="flex items-center justify-center gap-3 w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-blue-600/20 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Fazer Download Seguro
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// =====================================================================

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($modo === 'baixar' && $pode_baixar ? 'attachment' : 'inline') . '; filename="' . $nome_dl . '"');
header('X-Content-Type-Options: nosniff');
if (ob_get_length()) ob_clean();
flush();
readfile($caminho_arquivo);
exit;
