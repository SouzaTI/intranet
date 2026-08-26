<?php
// IMPORTADOR DE CONTRATOS V3 - 20/08/2026
// Sem descarte automático por duplicidade.
// api/ImportarContratosController.php
require_once '../config.php';
require_once __DIR__ . '/ContratoAuth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id_sessao = (int) ($_SESSION['user_id'] ?? 0);
$admin_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$eh_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$auth = new ContratoAuth($pdo_intra, $user_id_sessao, $eh_admin);

function impCtrlRedirecionar(string $tipo, string $mensagem): void {
    header('Location: ../importar_contratos.php?' . $tipo . '=' . urlencode($mensagem));
    exit;
}

function impCtrlNormalizar(string $texto): string {
    $texto = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto), 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($ascii !== false) $texto = $ascii;
    return trim(preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? $texto);
}

function impCtrlIntDias(string $texto): ?int {
    if (preg_match('/\d+/', $texto, $m)) return max(0, (int) $m[0]);
    return null;
}

function impCtrlEhNao(string $texto): bool {
    return in_array(impCtrlNormalizar($texto), ['NAO', 'N', 'SEM', 'NAO POSSUI', 'SEM MULTA', 'SEM AVISO'], true);
}

function impCtrlInferirPrazo(array $r): array {
    $prazoTexto = impCtrlNormalizar((string) ($r['prazo_planilha'] ?? ''));
    $dataFinal = $r['data_vencimento'] ?? null;

    if (str_contains($prazoTexto, 'INDETERMINADO')) {
        return ['INDETERMINADO', 1];
    }
    if ($dataFinal || preg_match('/\d+\s*(MES|MESES|ANO|ANOS|DIA|DIAS)/', $prazoTexto)) {
        return ['DETERMINADO', 0];
    }
    return [null, 0];
}

function impCtrlInferirPagamento(array $r, ?string $tipoPrazo): array {
    $qtd = isset($r['quantidade_pagamentos']) && $r['quantidade_pagamentos'] !== null ? (int) $r['quantidade_pagamentos'] : null;
    $valorParcela = isset($r['valor_planilha']) && $r['valor_planilha'] !== null ? (float) $r['valor_planilha'] : null;
    $prazoTexto = impCtrlNormalizar((string) ($r['prazo_planilha'] ?? ''));

    if ($tipoPrazo === 'INDETERMINADO') {
        return ['RECORRENTE_MENSAL', null, $valorParcela, null, 'Mensal'];
    }
    if ($qtd === 1) {
        return ['UNICO', $valorParcela, $valorParcela, 1, 'Pagamento único'];
    }
    if ($qtd !== null && $qtd >= 2) {
        $total = $valorParcela !== null ? round($valorParcela * $qtd, 2) : null;
        $periodicidade = str_contains($prazoTexto, 'MES') ? 'Mensal' : '';
        return ['PARCELADO', $total, $valorParcela, $qtd, $periodicidade];
    }

    // Sem quantidade de pagamentos, preservamos o valor como valor por pagamento,
    // mas deixamos o tipo em aberto para o responsável completar depois.
    return [null, null, $valorParcela, null, ''];
}

try {
    if ($user_id_sessao <= 0) throw new RuntimeException('Sessão inválida.', 401);
    $auth->exigir('criar');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['acao'] ?? '') !== 'importar_planilha') {
        throw new RuntimeException('Operação inválida.', 405);
    }
    contratoValidarCsrf();

    $preview = $_SESSION['importacao_contratos_preview'] ?? [];
    if (!$preview) throw new RuntimeException('A prévia da planilha expirou. Leia o arquivo novamente.');

    $selecionados = array_keys($_POST['importar'] ?? []);
    if (!$selecionados) throw new RuntimeException('Selecione pelo menos um contrato para importar.');

    $stmtSetores = $pdo_intra->query(
        "SELECT DISTINCT TRIM(SETOR) AS setor
           FROM matriz_comunicacao
          WHERE SETOR IS NOT NULL
            AND TRIM(SETOR) <> ''"
    );
    $setoresValidos = [];
    foreach ($stmtSetores->fetchAll(PDO::FETCH_COLUMN) as $setor) {
        $setoresValidos[impCtrlNormalizar($setor)] = $setor;
    }

    // INSERT explícito para que os registros importados entrem como rascunho
    // e possam ser completados pelo fluxo normal da Gestão de Contratos.
    $sql = "INSERT INTO contratos
        (fornecedor, nome_fantasia, cnpj, contato_fornecedor_nome, contato_fornecedor_telefone,
         servico_objeto, numero_contrato, codigo_sistema, clausula_tecnica, multa_carencia,
         prazo_comunicacao_cancelamento, renovacao_automatica, aviso_previo, multa_contratual, carencia_contratual,
         setor, empresa, cnpj_empresa_contratante, data_inicio, data_vencimento,
         prazo_indeterminado, recorrente, valor, valor_parcela, arquivo_path, forma_pagamento,
         quantidade_parcelas, periodicidade, indices_reajuste, centro_custo, dados_bancarios_fornecedor,
         retencoes_tributarias, condicoes_pagamento, responsavel_aprovacao_servico,
         contato_financeiro_nome, contato_financeiro_email, contato_financeiro_telefone, gestor_id,
         etapa_atual, status_fluxo, cadastro_atualizado, tipo_prazo, tipo_pagamento,
         dia_vencimento, possui_reajuste, indice_reajuste, indice_reajuste_outro,
         periodicidade_reajuste, periodicidade_reajuste_outro, mes_base_reajuste,
         possui_aviso_cancelamento, possui_multa, possui_carencia)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmtInsert = $pdo_intra->prepare($sql);
    $stmtAcesso = $pdo_intra->prepare(
        "INSERT IGNORE INTO contratos_acessos_usuarios (contrato_id, usuario_id, concedido_por) VALUES (?, ?, ?)"
    );
    $stmtHistorico = $pdo_intra->prepare(
        "INSERT INTO contratos_historico (contrato_id, etapa, acao, usuario_id) VALUES (?, 1, 'Contrato importado da planilha', ?)"
    );

    $importados = 0;
    $ignorados = 0;

    $pdo_intra->beginTransaction();
    foreach ($selecionados as $idxTexto) {
        $idx = (int) $idxTexto;
        if (!isset($preview[$idx])) {
            $ignorados++;
            continue;
        }
        $r = $preview[$idx];

        $fornecedor = trim((string) ($r['fornecedor'] ?? ''));
        $empresa = trim((string) ($r['empresa'] ?? ''));
        if ($fornecedor === '' || $empresa === '') {
            throw new RuntimeException('Linha ' . (int) ($r['linha'] ?? 0) . ': fornecedor e empresa são obrigatórios para a importação.');
        }

        $setorPost = trim((string) (($_POST['setor'][$idx] ?? '')));
        $chaveSetor = impCtrlNormalizar($setorPost);
        if ($setorPost === '' || !isset($setoresValidos[$chaveSetor])) {
            throw new RuntimeException('Linha ' . (int) ($r['linha'] ?? 0) . ': selecione um departamento/setor válido da matriz.');
        }
        $setor = $setoresValidos[$chaveSetor];

        [$tipoPrazo, $prazoIndeterminado] = impCtrlInferirPrazo($r);
        [$tipoPagamento, $valorTotal, $valorParcela, $quantidadeParcelas, $periodicidade] = impCtrlInferirPagamento($r, $tipoPrazo);
        $recorrente = $tipoPagamento === 'RECORRENTE_MENSAL' ? 1 : 0;

        $avisoRaw = trim((string) ($r['aviso_previo'] ?? ''));
        $possuiAviso = null;
        $prazoAviso = null;
        if ($avisoRaw !== '') {
            if (impCtrlEhNao($avisoRaw)) $possuiAviso = 'NAO';
            else {
                $possuiAviso = 'SIM';
                $prazoAviso = impCtrlIntDias($avisoRaw);
            }
        }

        $multaRaw = trim((string) ($r['multa'] ?? ''));
        $possuiMulta = null;
        $multaDescricao = '';
        if ($multaRaw !== '') {
            if (impCtrlEhNao($multaRaw)) $possuiMulta = 'NAO';
            else {
                $possuiMulta = 'SIM';
                $multaDescricao = $multaRaw;
            }
        }

        $renovacao = $r['renovacao_automatica'] ?? null;
        if ($tipoPrazo === 'INDETERMINADO') $renovacao = null;

        $dados = [
            $fornecedor,
            trim((string) ($r['nome_fantasia'] ?? '')),
            trim((string) ($r['cnpj'] ?? '')),
            trim((string) ($r['contato_fornecedor_nome'] ?? '')),
            trim((string) ($r['contato_fornecedor_telefone'] ?? '')),
            trim((string) ($r['servico_objeto'] ?? '')),
            '',
            mb_strtoupper(trim((string) ($r['codigo_sistema'] ?? '')), 'UTF-8'),
            trim((string) ($r['clausula_tecnica'] ?? '')),
            $multaRaw,
            $prazoAviso,
            $renovacao,
            $avisoRaw,
            $multaDescricao,
            '',
            $setor,
            mb_strtoupper($empresa, 'UTF-8'),
            '',
            $r['data_inicio'] ?: null,
            $prazoIndeterminado ? null : ($r['data_vencimento'] ?: null),
            $prazoIndeterminado,
            $recorrente,
            $valorTotal,
            $valorParcela,
            null,
            '',
            $quantidadeParcelas,
            $periodicidade,
            '', '', '', '', '', '', '', '', '',
            $user_id_sessao,
            1,
            'RASCUNHO',
            0,
            $tipoPrazo,
            $tipoPagamento,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $possuiAviso,
            $possuiMulta,
            null,
        ];

        $stmtInsert->execute($dados);
        $cid = (int) $pdo_intra->lastInsertId();
        $stmtAcesso->execute([$cid, $user_id_sessao, $user_id_sessao]);
        $stmtHistorico->execute([$cid, $user_id_sessao]);
        $importados++;
    }
    $pdo_intra->commit();

    unset($_SESSION['importacao_contratos_preview'], $_SESSION['importacao_contratos_arquivo'], $_SESSION['importacao_contratos_mapa_posicional']);

    if (function_exists('registrarLog')) {
        registrarLog(
            $pdo_intra,
            'IMPORTOU CONTRATOS',
            "Importação por planilha V3: {$importados} contrato(s) importado(s). Sem descarte automático por duplicidade.",
            $user_id_sessao,
            $admin_ip
        );
    }

    $mensagem = $importados . ' contrato(s) importado(s) como rascunho.';
    if ($ignorados) $mensagem .= ' ' . $ignorados . ' linha(s) inválida(s) foram ignorada(s).';
    impCtrlRedirecionar('sucesso', $mensagem);

} catch (Throwable $e) {
    if (isset($pdo_intra) && $pdo_intra instanceof PDO && $pdo_intra->inTransaction()) $pdo_intra->rollBack();
    $mensagem = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível concluir a importação dos contratos.';
    impCtrlRedirecionar('erro', $mensagem);
}
