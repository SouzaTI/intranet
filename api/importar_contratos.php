<?php
require_once 'config.php';
require_once __DIR__ . '/api/ContratoAuth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id_sessao = (int) ($_SESSION['user_id'] ?? 0);
$eh_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$auth = new ContratoAuth($pdo_intra, $user_id_sessao, $eh_admin);

if ($user_id_sessao <= 0 || !$auth->pode('acessar_modulo') || !$auth->pode('visualizar') || !$auth->pode('criar')) {
    http_response_code(403);
    header('Location: contratos.php?erro=' . urlencode('Você não possui permissão para importar contratos.'));
    exit;
}

$csrf_token = contratoCsrfToken();
$msg_sucesso = $_GET['sucesso'] ?? '';
$msg_erro = $_GET['erro'] ?? '';
$erro_analise = '';

function impNormalizarTexto(?string $texto): string {
    $texto = trim((string) $texto);
    $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
    return $texto;
}

function impNormalizarChave(?string $texto): string {
    $texto = mb_strtoupper(impNormalizarTexto($texto), 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($ascii !== false) $texto = $ascii;
    $texto = preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? $texto;
    return trim(preg_replace('/\s+/', ' ', $texto) ?? $texto);
}

function impValorDecimal(mixed $valor): ?float {
    if ($valor === null || $valor === '') return null;
    if (is_int($valor) || is_float($valor)) return (float) $valor;
    $texto = trim((string) $valor);
    if ($texto === '') return null;
    $texto = preg_replace('/[^0-9,.-]/', '', $texto) ?? '';
    if ($texto === '') return null;
    if (str_contains($texto, ',')) {
        $texto = str_replace('.', '', $texto);
        $texto = str_replace(',', '.', $texto);
    }
    return is_numeric($texto) ? (float) $texto : null;
}

function impInteiro(mixed $valor): ?int {
    if ($valor === null || $valor === '') return null;
    if (is_numeric($valor)) return (int) round((float) $valor);
    if (preg_match('/\d+/', (string) $valor, $m)) return (int) $m[0];
    return null;
}

function impDataMysql(mixed $valor): ?string {
    if ($valor === null || $valor === '') return null;
    if (is_int($valor) || is_float($valor) || (is_string($valor) && is_numeric(trim($valor)))) {
        $serial = (int) floor((float) $valor);
        if ($serial <= 0) return null;
        return (new DateTimeImmutable('1899-12-30'))->modify('+' . $serial . ' days')->format('Y-m-d');
    }

    $texto = trim((string) $valor);
    if ($texto === '') return null;
    foreach (['!d/m/Y', '!d/m/y', '!Y-m-d', '!d-m-Y', '!m/d/Y'] as $formato) {
        $dt = DateTimeImmutable::createFromFormat($formato, $texto);
        $erros = DateTimeImmutable::getLastErrors();
        if ($dt && ($erros === false || ($erros['warning_count'] === 0 && $erros['error_count'] === 0))) {
            return $dt->format('Y-m-d');
        }
    }
    try {
        return (new DateTimeImmutable($texto))->format('Y-m-d');
    } catch (Throwable $e) {
        return null;
    }
}

function impDataBr(?string $data): string {
    if (!$data) return '—';
    try { return (new DateTimeImmutable($data))->format('d/m/Y'); }
    catch (Throwable $e) { return '—'; }
}

function impBooleanoSimNao(mixed $valor): ?int {
    $v = impNormalizarChave((string) $valor);
    if ($v === '') return null;
    if (in_array($v, ['SIM', 'S', 'YES', 'TRUE', '1'], true)) return 1;
    if (in_array($v, ['NAO', 'N', 'NO', 'FALSE', '0'], true)) return 0;
    return null;
}

function impColunaIndice(string $referencia): int {
    if (!preg_match('/^([A-Z]+)/i', $referencia, $m)) return 0;
    $letras = strtoupper($m[1]);
    $indice = 0;
    for ($i = 0, $n = strlen($letras); $i < $n; $i++) {
        $indice = ($indice * 26) + (ord($letras[$i]) - 64);
    }
    return $indice - 1;
}

function impLerXlsxNativo(string $arquivo): array {
    if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
        throw new RuntimeException('O servidor precisa da extensão PHP ZIP (ou PhpSpreadsheet) para ler arquivos .xlsx.');
    }

    $zip = new ZipArchive();
    if ($zip->open($arquivo) !== true) throw new RuntimeException('Não foi possível abrir a planilha XLSX.');

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);
        if ($xml) {
            $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($xml->xpath('//x:si') ?: [] as $si) {
                $si->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $partes = [];
                foreach ($si->xpath('.//x:t') ?: [] as $t) $partes[] = (string) $t;
                $sharedStrings[] = implode('', $partes);
            }
        }
    }

    $nomeSheet = 'xl/worksheets/sheet1.xml';
    if ($zip->locateName($nomeSheet) === false) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nome = $zip->getNameIndex($i);
            if ($nome && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $nome)) {
                $nomeSheet = $nome;
                break;
            }
        }
    }

    $sheetRaw = $zip->getFromName($nomeSheet);
    $zip->close();
    if ($sheetRaw === false) throw new RuntimeException('Nenhuma aba válida foi encontrada na planilha.');

    $sheetXml = simplexml_load_string($sheetRaw);
    if (!$sheetXml) throw new RuntimeException('Não foi possível interpretar a primeira aba da planilha.');
    $sheetXml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $linhas = [];
    foreach ($sheetXml->xpath('//x:sheetData/x:row') ?: [] as $row) {
        $row->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $linha = [];
        $maiorColuna = -1;
        foreach ($row->xpath('x:c') ?: [] as $cell) {
            $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $ref = (string) ($cell['r'] ?? '');
            $col = impColunaIndice($ref);
            $tipo = (string) ($cell['t'] ?? '');
            $valor = null;

            if ($tipo === 'inlineStr') {
                $partes = [];
                foreach ($cell->xpath('.//x:is//x:t') ?: [] as $t) $partes[] = (string) $t;
                $valor = implode('', $partes);
            } else {
                $v = $cell->xpath('x:v');
                $bruto = ($v && isset($v[0])) ? (string) $v[0] : '';
                if ($tipo === 's') $valor = $sharedStrings[(int) $bruto] ?? '';
                elseif ($tipo === 'b') $valor = $bruto === '1';
                elseif ($bruto === '') $valor = null;
                elseif (is_numeric($bruto)) $valor = (float) $bruto;
                else $valor = $bruto;
            }

            $linha[$col] = $valor;
            $maiorColuna = max($maiorColuna, $col);
        }
        if ($maiorColuna >= 0) {
            $normalizada = [];
            for ($c = 0; $c <= $maiorColuna; $c++) $normalizada[] = $linha[$c] ?? null;
            $linhas[] = $normalizada;
        }
    }
    return $linhas;
}

function impLerXlsx(string $arquivo): array {
    $autoloads = [
        __DIR__ . '/vendor/autoload.php',
        dirname(__DIR__) . '/vendor/autoload.php',
    ];
    foreach ($autoloads as $autoload) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory') && is_file($autoload)) {
            require_once $autoload;
        }
    }

    if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($arquivo);
        $sheet = $spreadsheet->getSheet(0);
        $ultimaLinha = min((int) $sheet->getHighestDataRow(), 5000);
        $ultimaColuna = $sheet->getHighestDataColumn();
        return $sheet->rangeToArray('A1:' . $ultimaColuna . $ultimaLinha, null, true, true, false);
    }

    return impLerXlsxNativo($arquivo);
}

function impMapearCabecalhos(array $cabecalhos): array {
    $aliases = [
        'RAZAO SOCIAL' => 'fornecedor',
        'NOME FANTASIA' => 'nome_fantasia',
        'CONTATO NOME TELEFONE E MAIL' => 'contato',
        'CONTATO NOME TELEFONE EMAIL' => 'contato',
        'CODIGO SISTEMA' => 'codigo_sistema',
        'SERVICO' => 'servico_objeto',
        'CNPJ' => 'cnpj',
        'VALOR' => 'valor_planilha',
        'PRAZO' => 'prazo_planilha',
        'QUANT PAGTOS' => 'quantidade_pagamentos',
        'QUANT PAGAMENTOS' => 'quantidade_pagamentos',
        'INICIO' => 'data_inicio',
        'COMUNICADO 60 DIAS' => 'comunicado_60_dias',
        'FINAL' => 'data_vencimento',
        'RENOVACAO AUTOMATICA' => 'renovacao_automatica',
        'AVISO PREVIO' => 'aviso_previo',
        'MULTA' => 'multa',
        'CLASULA TECNICA' => 'clausula_tecnica',
        'CLAUSULA TECNICA' => 'clausula_tecnica',
        'EMPRESA' => 'empresa',
        'DEPARTAMENTO CONTRATANTE' => 'departamento',
    ];

    $mapa = [];
    foreach ($cabecalhos as $idx => $cabecalho) {
        $chave = impNormalizarChave((string) $cabecalho);
        if (isset($aliases[$chave])) $mapa[$aliases[$chave]] = $idx;
    }
    return $mapa;
}

function impCelula(array $linha, array $mapa, string $campo): mixed {
    if (!isset($mapa[$campo])) return null;
    return $linha[$mapa[$campo]] ?? null;
}

function impSepararContato(?string $contato): array {
    $contato = impNormalizarTexto($contato);
    if ($contato === '') return ['', ''];
    $telefone = '';
    if (preg_match('/(?:\+?\d{1,3}[\s.-]*)?(?:\(?\d{2}\)?[\s.-]*)?\d{4,5}[\s.-]?\d{4}/', $contato, $m)) {
        $telefone = trim($m[0]);
    }
    $nome = $telefone !== '' ? trim(str_replace($telefone, '', $contato), " \t\n\r\0\x0B-;,/") : $contato;
    if ($nome === '') $nome = $contato;
    return [$nome, $telefone];
}

function impContratoDuplicado(PDO $pdo, array $linha): ?int {
    static $stmt = null;
    if ($stmt === null) {
        $stmt = $pdo->prepare(
            "SELECT id FROM contratos
             WHERE UPPER(TRIM(fornecedor)) = UPPER(TRIM(?))
               AND UPPER(TRIM(IFNULL(cnpj,''))) = UPPER(TRIM(?))
               AND UPPER(TRIM(empresa)) = UPPER(TRIM(?))
               AND UPPER(TRIM(IFNULL(servico_objeto,''))) = UPPER(TRIM(?))
               AND IFNULL(data_inicio,'') = IFNULL(?, '')
             LIMIT 1"
        );
    }
    $stmt->execute([
        $linha['fornecedor'], $linha['cnpj'], $linha['empresa'],
        $linha['servico_objeto'], $linha['data_inicio'],
    ]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

$stmt_setores = $pdo_intra->query(
    "SELECT DISTINCT TRIM(SETOR) AS setor
       FROM matriz_comunicacao
      WHERE SETOR IS NOT NULL
        AND TRIM(SETOR) <> ''
      ORDER BY setor"
);
$setores = $stmt_setores->fetchAll(PDO::FETCH_COLUMN);
$mapa_setores = [];
foreach ($setores as $setor) $mapa_setores[impNormalizarChave($setor)] = $setor;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'analisar_planilha') {
    try {
        contratoValidarCsrf();
        if (empty($_FILES['planilha']['name']) || ($_FILES['planilha']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Selecione uma planilha .xlsx para continuar.');
        }
        if ((int) $_FILES['planilha']['size'] > 10 * 1024 * 1024) {
            throw new RuntimeException('A planilha deve ter no máximo 10 MB.');
        }
        $ext = strtolower(pathinfo($_FILES['planilha']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') throw new RuntimeException('Envie a planilha no formato .xlsx.');

        $linhas = impLerXlsx($_FILES['planilha']['tmp_name']);
        if (count($linhas) < 2) throw new RuntimeException('A planilha não possui linhas de contratos para importar.');

        $mapa = impMapearCabecalhos($linhas[0]);
        foreach (['fornecedor', 'empresa', 'departamento'] as $obrigatorio) {
            if (!isset($mapa[$obrigatorio])) throw new RuntimeException('Não encontrei a coluna obrigatória "' . $obrigatorio . '" na planilha.');
        }

        $preview = [];
        foreach (array_slice($linhas, 1) as $idx => $linha) {
            $linhaPlanilha = $idx + 2;
            $fornecedor = impNormalizarTexto((string) impCelula($linha, $mapa, 'fornecedor'));
            $empresa = impNormalizarTexto((string) impCelula($linha, $mapa, 'empresa'));
            $departamentoOriginal = mb_strtoupper(impNormalizarTexto((string) impCelula($linha, $mapa, 'departamento')), 'UTF-8');
            $servico = impNormalizarTexto((string) impCelula($linha, $mapa, 'servico_objeto'));
            $cnpj = impNormalizarTexto((string) impCelula($linha, $mapa, 'cnpj'));

            $linhaVazia = $fornecedor === '' && $empresa === '' && $departamentoOriginal === '' && $servico === '' && $cnpj === '';
            if ($linhaVazia) continue;

            [$contatoNome, $contatoTelefone] = impSepararContato((string) impCelula($linha, $mapa, 'contato'));
            $setorEncontrado = $mapa_setores[impNormalizarChave($departamentoOriginal)] ?? '';

            $item = [
                'linha' => $linhaPlanilha,
                'fornecedor' => $fornecedor,
                'nome_fantasia' => impNormalizarTexto((string) impCelula($linha, $mapa, 'nome_fantasia')),
                'contato_fornecedor_nome' => $contatoNome,
                'contato_fornecedor_telefone' => $contatoTelefone,
                'codigo_sistema' => impNormalizarTexto((string) impCelula($linha, $mapa, 'codigo_sistema')),
                'servico_objeto' => $servico,
                'cnpj' => $cnpj,
                'valor_planilha' => impValorDecimal(impCelula($linha, $mapa, 'valor_planilha')),
                'prazo_planilha' => impNormalizarTexto((string) impCelula($linha, $mapa, 'prazo_planilha')),
                'quantidade_pagamentos' => impInteiro(impCelula($linha, $mapa, 'quantidade_pagamentos')),
                'data_inicio' => impDataMysql(impCelula($linha, $mapa, 'data_inicio')),
                'data_vencimento' => impDataMysql(impCelula($linha, $mapa, 'data_vencimento')),
                'renovacao_automatica' => impBooleanoSimNao(impCelula($linha, $mapa, 'renovacao_automatica')),
                'aviso_previo' => impNormalizarTexto((string) impCelula($linha, $mapa, 'aviso_previo')),
                'multa' => impNormalizarTexto((string) impCelula($linha, $mapa, 'multa')),
                'clausula_tecnica' => impNormalizarTexto((string) impCelula($linha, $mapa, 'clausula_tecnica')),
                'empresa' => mb_strtoupper($empresa, 'UTF-8'),
                'departamento_original' => $departamentoOriginal,
                'setor_encontrado' => $setorEncontrado,
            ];
            $item['duplicado_id'] = impContratoDuplicado($pdo_intra, $item);
            $preview[] = $item;
        }

        $_SESSION['importacao_contratos_preview'] = $preview;
        $_SESSION['importacao_contratos_arquivo'] = basename($_FILES['planilha']['name']);
    } catch (Throwable $e) {
        $erro_analise = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível analisar a planilha.';
        unset($_SESSION['importacao_contratos_preview'], $_SESSION['importacao_contratos_arquivo']);
    }
}

$preview = $_SESSION['importacao_contratos_preview'] ?? [];
$nome_arquivo = $_SESSION['importacao_contratos_arquivo'] ?? '';
$total = count($preview);
$total_ajustes = count(array_filter($preview, fn($r) => empty($r['setor_encontrado']) && empty($r['duplicado_id'])));
$total_duplicados = count(array_filter($preview, fn($r) => !empty($r['duplicado_id'])));
$total_prontos = $total - $total_ajustes - $total_duplicados;

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 overflow-y-auto bg-slate-50 p-8">
    <div class="max-w-[1600px] mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-7">
            <div>
                <a href="contratos.php" class="inline-flex items-center gap-2 text-sm font-black text-slate-500 hover:text-navy-900 mb-2">← Voltar para Gestão de Contratos</a>
                <h2 class="text-3xl font-black text-navy-900 tracking-tight uppercase italic">Importar Contratos</h2>
                <p class="text-slate-500 font-medium mt-1">Carga inicial por planilha XLSX, com correção dos departamentos antes da gravação.</p>
            </div>
        </div>

        <?php if ($msg_sucesso): ?>
            <div class="mb-5 bg-emerald-50 text-emerald-700 p-4 rounded-2xl font-bold border border-emerald-100 shadow-sm"><?php echo htmlspecialchars($msg_sucesso); ?></div>
        <?php endif; ?>
        <?php if ($msg_erro): ?>
            <div class="mb-5 bg-rose-50 text-rose-700 p-4 rounded-2xl font-bold border border-rose-100 shadow-sm"><?php echo htmlspecialchars($msg_erro); ?></div>
        <?php endif; ?>
        <?php if ($erro_analise): ?>
            <div class="mb-5 bg-rose-50 text-rose-700 p-4 rounded-2xl font-bold border border-rose-100 shadow-sm"><?php echo htmlspecialchars($erro_analise); ?></div>
        <?php endif; ?>

        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-5">
                <div>
                    <p class="text-xs uppercase tracking-wider font-black text-slate-400">1. Selecionar arquivo</p>
                    <h3 class="font-black text-navy-900 text-lg mt-1">Planilha de contratos</h3>
                    <p class="text-sm text-slate-500 mt-1">O comunicado de 60 dias não é importado: o próprio módulo calcula o alerta pela data final.</p>
                </div>
                <form method="post" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 sm:items-center min-w-0">
                    <input type="hidden" name="acao" value="analisar_planilha">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input required type="file" name="planilha" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 file:font-black hover:file:bg-slate-200">
                    <button type="submit" class="shrink-0 bg-navy-900 hover:bg-navy-800 text-white font-black px-5 py-3 rounded-xl shadow-sm">Ler planilha</button>
                </form>
            </div>
        </section>

        <?php if ($preview): ?>
        <section class="mb-5 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm"><p class="text-[10px] font-black uppercase text-slate-400">Linhas encontradas</p><p class="text-2xl font-black text-navy-900"><?php echo $total; ?></p></div>
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 shadow-sm"><p class="text-[10px] font-black uppercase text-emerald-600">Prontas</p><p class="text-2xl font-black text-emerald-700"><?php echo max(0, $total_prontos); ?></p></div>
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 shadow-sm"><p class="text-[10px] font-black uppercase text-amber-600">Departamentos para ajustar</p><p class="text-2xl font-black text-amber-700"><?php echo $total_ajustes; ?></p></div>
            <div class="bg-slate-100 border border-slate-200 rounded-2xl p-4 shadow-sm"><p class="text-[10px] font-black uppercase text-slate-500">Já cadastrados</p><p class="text-2xl font-black text-slate-700"><?php echo $total_duplicados; ?></p></div>
        </section>

        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-4 text-sm text-blue-800">
            <strong><?php echo htmlspecialchars($nome_arquivo); ?></strong> analisada. Quando um departamento não existir na matriz, escolha o correto na lista. Ao corrigir um nome, o mesmo ajuste é aplicado automaticamente às outras linhas com o mesmo departamento original.
        </div>

        <form method="post" action="api/ImportarContratosController.php" id="form-importacao">
            <input type="hidden" name="acao" value="importar_planilha">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1450px]">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-3 border-b border-slate-200 text-center w-14">Importar</th>
                                <th class="px-3 py-3 border-b border-slate-200">Linha</th>
                                <th class="px-3 py-3 border-b border-slate-200">Fornecedor</th>
                                <th class="px-3 py-3 border-b border-slate-200">Serviço</th>
                                <th class="px-3 py-3 border-b border-slate-200">CNPJ</th>
                                <th class="px-3 py-3 border-b border-slate-200">Empresa</th>
                                <th class="px-3 py-3 border-b border-slate-200 min-w-[250px]">Departamento / setor</th>
                                <th class="px-3 py-3 border-b border-slate-200 text-right">Valor</th>
                                <th class="px-3 py-3 border-b border-slate-200">Prazo</th>
                                <th class="px-3 py-3 border-b border-slate-200">Início</th>
                                <th class="px-3 py-3 border-b border-slate-200">Final</th>
                                <th class="px-3 py-3 border-b border-slate-200">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <?php foreach ($preview as $i => $r):
                                $duplicado = !empty($r['duplicado_id']);
                                $setorOk = !empty($r['setor_encontrado']);
                            ?>
                            <tr class="<?php echo $duplicado ? 'bg-slate-50 opacity-70' : (!$setorOk ? 'bg-amber-50/50' : 'hover:bg-blue-50/40'); ?>">
                                <td class="px-3 py-3 text-center">
                                    <?php if ($duplicado): ?>
                                        <input type="checkbox" disabled class="rounded border-slate-300">
                                    <?php else: ?>
                                        <input type="checkbox" name="importar[<?php echo $i; ?>]" value="1" checked class="linha-check rounded border-slate-300" data-index="<?php echo $i; ?>">
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3 font-black text-slate-500">#<?php echo (int) $r['linha']; ?></td>
                                <td class="px-3 py-3"><p class="font-black text-navy-900"><?php echo htmlspecialchars($r['fornecedor'] ?: '—'); ?></p><p class="text-[10px] text-slate-400"><?php echo htmlspecialchars($r['nome_fantasia'] ?: ''); ?></p></td>
                                <td class="px-3 py-3 max-w-[220px] break-words <?php echo $r['servico_objeto'] === '' ? 'text-rose-600 font-black' : 'text-slate-700'; ?>"><?php echo htmlspecialchars($r['servico_objeto'] ?: 'Pendente'); ?></td>
                                <td class="px-3 py-3 whitespace-nowrap <?php echo $r['cnpj'] === '' ? 'text-amber-600 font-black' : ''; ?>"><?php echo htmlspecialchars($r['cnpj'] ?: 'Pendente'); ?></td>
                                <td class="px-3 py-3 font-black text-slate-700"><?php echo htmlspecialchars($r['empresa'] ?: '—'); ?></td>
                                <td class="px-3 py-3">
                                    <?php if ($setorOk): ?>
                                        <input type="hidden" name="setor[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($r['setor_encontrado']); ?>">
                                        <p class="font-black text-emerald-700"><?php echo htmlspecialchars($r['setor_encontrado']); ?></p>
                                        <p class="text-[10px] text-slate-400">Encontrado na matriz</p>
                                    <?php elseif (!$duplicado): ?>
                                        <p class="text-[10px] font-black text-amber-700 mb-1">Não encontrado: <?php echo htmlspecialchars($r['departamento_original'] ?: 'VAZIO'); ?></p>
                                        <select name="setor[<?php echo $i; ?>]" class="setor-ajuste w-full bg-white border border-amber-300 rounded-xl px-3 py-2 font-bold outline-none focus:border-amber-500"
                                                data-index="<?php echo $i; ?>" data-original="<?php echo htmlspecialchars(impNormalizarChave($r['departamento_original'])); ?>">
                                            <option value="">Selecione o setor correto...</option>
                                            <?php foreach ($setores as $setor): ?>
                                                <option value="<?php echo htmlspecialchars($setor); ?>"><?php echo htmlspecialchars($setor); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3 text-right font-black text-slate-700 whitespace-nowrap"><?php echo $r['valor_planilha'] === null ? '—' : 'R$ ' . number_format((float) $r['valor_planilha'], 2, ',', '.'); ?></td>
                                <td class="px-3 py-3 whitespace-nowrap"><?php echo htmlspecialchars($r['prazo_planilha'] ?: '—'); ?><?php echo $r['quantidade_pagamentos'] ? '<br><span class="text-[10px] text-slate-400">' . (int) $r['quantidade_pagamentos'] . ' pag.</span>' : ''; ?></td>
                                <td class="px-3 py-3 whitespace-nowrap"><?php echo impDataBr($r['data_inicio']); ?></td>
                                <td class="px-3 py-3 whitespace-nowrap"><?php echo impDataBr($r['data_vencimento']); ?></td>
                                <td class="px-3 py-3">
                                    <?php if ($duplicado): ?>
                                        <span class="inline-block bg-slate-200 text-slate-600 px-2 py-1 rounded-full text-[10px] font-black">JÁ EXISTE #<?php echo (int) $r['duplicado_id']; ?></span>
                                    <?php elseif (!$setorOk): ?>
                                        <span class="status-linha inline-block bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-[10px] font-black" data-index="<?php echo $i; ?>">AJUSTAR SETOR</span>
                                    <?php else: ?>
                                        <span class="status-linha inline-block bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full text-[10px] font-black" data-index="<?php echo $i; ?>">PRONTO</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sticky bottom-4 mt-4 bg-white/95 backdrop-blur border border-slate-200 rounded-2xl shadow-lg p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <p class="font-black text-navy-900"><span id="qtd-selecionados">0</span> contrato(s) selecionado(s)</p>
                    <p id="aviso-ajustes" class="text-xs text-slate-500 mt-0.5">Corrija os departamentos destacados antes de importar.</p>
                </div>
                <button id="btn-importar" type="submit" class="bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-black px-6 py-3 rounded-xl shadow-sm">
                    Importar contratos selecionados
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</main>

<script>
(function () {
    const form = document.getElementById('form-importacao');
    if (!form) return;

    const checks = () => Array.from(document.querySelectorAll('.linha-check'));
    const selects = () => Array.from(document.querySelectorAll('.setor-ajuste'));
    const btn = document.getElementById('btn-importar');
    const qtd = document.getElementById('qtd-selecionados');
    const aviso = document.getElementById('aviso-ajustes');

    function linhaSelecionada(index) {
        const check = document.querySelector('.linha-check[data-index="' + index + '"]');
        return !!check && check.checked;
    }

    function atualizar() {
        const selecionados = checks().filter(c => c.checked);
        const pendentes = selects().filter(s => linhaSelecionada(s.dataset.index) && !s.value);
        qtd.textContent = selecionados.length;
        btn.disabled = selecionados.length === 0 || pendentes.length > 0;
        aviso.textContent = pendentes.length > 0
            ? pendentes.length + ' linha(s) selecionada(s) ainda precisam de um setor válido.'
            : (selecionados.length > 0 ? 'Tudo certo para importar. Os registros entrarão como rascunho.' : 'Selecione ao menos uma linha.');
    }

    selects().forEach(select => {
        select.addEventListener('change', function () {
            const original = this.dataset.original;
            const valor = this.value;
            if (valor && original) {
                selects().forEach(outro => {
                    if (outro.dataset.original === original) outro.value = valor;
                });
            }
            selects().forEach(s => {
                const status = document.querySelector('.status-linha[data-index="' + s.dataset.index + '"]');
                if (!status) return;
                if (s.value) {
                    status.textContent = 'PRONTO';
                    status.className = 'status-linha inline-block bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full text-[10px] font-black';
                    status.dataset.index = s.dataset.index;
                } else {
                    status.textContent = 'AJUSTAR SETOR';
                    status.className = 'status-linha inline-block bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-[10px] font-black';
                    status.dataset.index = s.dataset.index;
                }
            });
            atualizar();
        });
    });

    checks().forEach(c => c.addEventListener('change', atualizar));
    form.addEventListener('submit', function (e) {
        atualizar();
        if (btn.disabled) e.preventDefault();
    });
    atualizar();
})();
</script>
