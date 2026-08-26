<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/auth_check.php';

global $pdo_totvs;

$podeVisualizarWinthor =
    (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)
    || (isset($_SESSION['pode_gerenciar_acessos']) && $_SESSION['pode_gerenciar_acessos'] === true);

if (!$podeVisualizarWinthor && !empty($_SESSION['user_id'])) {
    $stmtPermissaoWinthor = $pdo_intra->prepare(
        "SELECT 1
           FROM usuarios_grupos UG
           JOIN grupos_intranet G ON G.id = UG.grupo_id
          WHERE UG.usuario_id = ?
            AND G.pode_visualizar_winthor = 1
          LIMIT 1"
    );
    $stmtPermissaoWinthor->execute([(int) $_SESSION['user_id']]);
    $podeVisualizarWinthor = (bool) $stmtPermissaoWinthor->fetchColumn();
}

if (!$podeVisualizarWinthor) {
    http_response_code(403);
    header('Location: index.php?erro=' . urlencode('Você não possui permissão para visualizar o Acompanhamento WinThor.'));
    exit;
}

if (empty($_SESSION['csrf_winthor'])) {
    $_SESSION['csrf_winthor'] = bin2hex(random_bytes(32));
}

// Histórico individual carregado pelo modal, sem recarregar a página.
if (isset($_GET['ajax']) && $_GET['ajax'] === 'historico') {
    header('Content-Type: application/json; charset=utf-8');
    $usuarioId = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
    if (!$usuarioId) {
        http_response_code(400);
        echo json_encode(['erro' => 'Usuário inválido']);
        exit;
    }

    $stmt = $pdo_totvs->prepare("\n        SELECT
            H.data_inicio,
            H.data_final,
            H.duracao_segundos,
            H.codrotina_oracle,
            COALESCE(R.nome, 'Rotina não identificada') AS nomerotina,
            R.nome_modulo
        FROM win_historico_acessos H
        LEFT JOIN win_rotinas R ON R.id = H.rotina_id
        WHERE H.usuario_id = ?
        ORDER BY H.data_final DESC
        LIMIT 30
    ");
    $stmt->execute([$usuarioId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

// Administradores podem escolher quem entra no acompanhamento.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if (!hash_equals($_SESSION['csrf_winthor'], $_POST['csrf'] ?? '')) {
        http_response_code(419);
        die('Sessão expirada. Atualize a página.');
    }
    if (empty($_SESSION['is_admin'])) {
        http_response_code(403);
        die('Acesso negado.');
    }

    if ($_POST['acao'] === 'alternar_monitoramento') {
        $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
        $monitorar = filter_input(INPUT_POST, 'monitorar', FILTER_VALIDATE_INT);
        if ($usuarioId && in_array($monitorar, [0, 1], true)) {
            $stmt = $pdo_totvs->prepare("UPDATE win_usuarios SET monitorar = ? WHERE id = ?");
            $stmt->execute([$monitorar, $usuarioId]);
        }
    }
    header('Location: acompanhamento_winthor.php');
    exit;
}

$sql = "
    SELECT
        U.id,
        U.matricula_oracle,
        COALESCE(NULLIF(U.nome_exibicao, ''), U.nome_oracle) AS nome,
        U.monitorar,
        S.nome AS setor,
        A.ultimo_acesso,
        COALESCE(A.total_acessos, 0) AS total_acessos,
        COALESCE(A.acessos_hoje, 0) AS acessos_hoje,
        A.ultima_rotina,
        COALESCE(T.rotinas_abertas, 0) AS rotinas_abertas,
        T.rotinas_agora,
        T.em_rotina_desde,
        T.usuario_rede_atual,
        T.divergencia_identidade
    FROM win_usuarios U
    LEFT JOIN win_setores S ON S.id = U.setor_id
    LEFT JOIN (
        SELECT
            H.usuario_id,
            MAX(H.data_final) AS ultimo_acesso,
            COUNT(*) AS total_acessos,
            SUM(DATE(H.data_final) = CURDATE()) AS acessos_hoje,
            SUBSTRING_INDEX(
                GROUP_CONCAT(
                    CONCAT(H.codrotina_oracle, ' - ', COALESCE(R.nome, 'Rotina não identificada'))
                    ORDER BY H.data_final DESC SEPARATOR '||'
                ), '||', 1
            ) AS ultima_rotina
        FROM win_historico_acessos H
        LEFT JOIN win_rotinas R ON R.id = H.rotina_id
        GROUP BY H.usuario_id
    ) A ON A.usuario_id = U.id
    LEFT JOIN (
        SELECT
            SS.usuario_id,
            COUNT(*) AS rotinas_abertas,
            GROUP_CONCAT(
                DISTINCT CONCAT(SS.codrotina_extraida, ' - ', COALESCE(RR.nome, 'Rotina não identificada'))
                ORDER BY SS.data_hora_login SEPARATOR ' | '
            ) AS rotinas_agora,
            MIN(SS.data_hora_login) AS em_rotina_desde,
            GROUP_CONCAT(DISTINCT SS.usuario_rede ORDER BY SS.usuario_rede SEPARATOR ' | ') AS usuario_rede_atual,
            MAX(SS.divergencia_identidade) AS divergencia_identidade
        FROM win_sessoes_atuais SS
        LEFT JOIN win_rotinas RR ON RR.id = SS.rotina_id
        GROUP BY SS.usuario_id
    ) T ON T.usuario_id = U.id
    ORDER BY
        U.monitorar DESC,
        (T.rotinas_abertas > 0) DESC,
        A.ultimo_acesso DESC,
        nome
";
$usuarios = $pdo_totvs->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$agora = new DateTimeImmutable();
$hoje = $agora->format('Y-m-d');
$cards = [
    'monitorados' => 0,
    'online' => 0,
    'hoje' => 0,
    'atencao' => 0,
    'nunca' => 0,
    'sem_hoje' => 0,
    'ignorados' => 0,
];

foreach ($usuarios as &$usuario) {
    $usuario['dias_sem_acesso'] = null;
    if ((int)$usuario['rotinas_abertas'] > 0) {
        $usuario['status'] = 'EM ROTINA AGORA';
        $usuario['status_key'] = 'online';
    } elseif (empty($usuario['ultimo_acesso'])) {
        $usuario['status'] = 'NUNCA ACESSOU';
        $usuario['status_key'] = 'nunca';
    } else {
        $ultimo = new DateTimeImmutable($usuario['ultimo_acesso']);
        $dias = (int)$ultimo->setTime(0, 0)->diff($agora->setTime(0, 0))->format('%a');
        $usuario['dias_sem_acesso'] = $dias;
        if ($ultimo->format('Y-m-d') === $hoje) {
            $usuario['status'] = 'ACESSOU HOJE';
            $usuario['status_key'] = 'hoje';
        } elseif ($dias >= 2) {
            $usuario['status'] = $dias . ' DIAS SEM ACESSAR';
            $usuario['status_key'] = 'atencao';
        } else {
            $usuario['status'] = 'SEM ACESSO HOJE';
            $usuario['status_key'] = 'sem_hoje';
        }
    }

    if ((int)$usuario['monitorar'] === 1) {
        $cards['monitorados']++;
        if ($usuario['status_key'] === 'online') $cards['online']++;
        if (in_array($usuario['status_key'], ['online', 'hoje'], true)) $cards['hoje']++;
        if ($usuario['status_key'] === 'atencao') $cards['atencao']++;
        if ($usuario['status_key'] === 'nunca') $cards['nunca']++;
        if ($usuario['status_key'] === 'sem_hoje') $cards['sem_hoje']++;
    } else {
        $cards['ignorados']++;
    }
}
unset($usuario);

$setores = $pdo_totvs->query("SELECT nome FROM win_setores WHERE ativo = 1 ORDER BY nome")
    ->fetchAll(PDO::FETCH_COLUMN);

$ultimaColeta = $pdo_totvs->query("\n    SELECT status, finalizada_em, mensagem
    FROM win_coletas
    ORDER BY id DESC LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
$ultimaColetaFormatada = !empty($ultimaColeta['finalizada_em'])
    ? (new DateTimeImmutable($ultimaColeta['finalizada_em']))->format('d/m/Y H:i:s')
    : 'Ainda não registrada';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<style>
    .tabela-winthor-scroll {
        height: clamp(420px, 52vh, 680px);
        overflow: auto;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #e2e8f0;
    }
    .tabela-winthor-scroll::-webkit-scrollbar { width: 9px; height: 9px; }
    .tabela-winthor-scroll::-webkit-scrollbar-track { background: #e2e8f0; }
    .tabela-winthor-scroll::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
        border: 2px solid #e2e8f0;
    }
    .tabela-winthor-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 20;
        background: #f8fafc;
        box-shadow: 0 1px 0 #e2e8f0;
    }
    .visao-winthor-btn {
        white-space: nowrap;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        transition: .18s ease;
    }
    .visao-winthor-btn:hover { border-color: #93c5fd; color: #1d4ed8; }
    .visao-winthor-btn.ativo {
        color: #fff;
        background: #172554;
        border-color: #172554;
        box-shadow: 0 8px 20px rgba(23, 37, 84, .18);
    }
</style>

<main class="flex-1 overflow-y-auto bg-slate-100">
    <div class="max-w-[1800px] mx-auto px-4 py-6 lg:px-8 lg:py-8 space-y-6">
        <section class="rounded-[2rem] bg-gradient-to-r from-navy-900 via-navy-800 to-blue-900 text-white p-6 lg:p-8 shadow-xl overflow-hidden relative">
            <div class="absolute -right-20 -top-24 w-72 h-72 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="relative flex flex-col xl:flex-row xl:items-end justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full <?= $cards['online'] > 0 ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500' ?>"></span>
                        <span class="text-[10px] uppercase font-black tracking-[0.25em] text-blue-300">Implantação WinThor</span>
                    </div>
                    <h1 class="text-2xl lg:text-4xl font-black tracking-tight">Acompanhamento de utilização</h1>
                    <p class="mt-2 text-sm text-slate-300 max-w-3xl">Acompanhe quem está usando o WinThor agora, quem utilizou hoje e quais pessoas precisam de atenção.</p>
                </div>
                <div class="bg-white/10 border border-white/10 rounded-2xl px-5 py-4 backdrop-blur-sm min-w-[280px]">
                    <p class="text-[9px] font-black uppercase tracking-widest text-blue-300">Última coleta registrada</p>
                    <p id="ultimaColetaWinthor" class="mt-1 text-sm font-bold"><?= htmlspecialchars($ultimaColetaFormatada) ?></p>
                    <p id="mensagemColetaWinthor" class="text-[10px] text-slate-400 mt-1"><?= htmlspecialchars($ultimaColeta['mensagem'] ?? '') ?></p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 xl:grid-cols-5 gap-3 lg:gap-5">
            <?php
            $listaCards = [
                ['monitorados', 'Monitorados', $cards['monitorados'], 'bg-blue-600', 'Usuários incluídos'],
                ['online', 'Em rotina agora', $cards['online'], 'bg-emerald-500', 'Coletor em tempo real'],
                ['hoje', 'Acessaram hoje', $cards['hoje'], 'bg-cyan-500', 'Com atividade no dia'],
                ['atencao', 'Atenção', $cards['atencao'], 'bg-amber-500', '2 dias ou mais'],
                ['nunca', 'Nunca acessaram', $cards['nunca'], 'bg-slate-500', 'Sem histórico'],
            ];
            foreach ($listaCards as [$chaveCard, $titulo, $valor, $cor, $subtitulo]):
            ?>
                <article class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm relative overflow-hidden">
                    <span class="absolute left-0 top-0 bottom-0 w-1.5 <?= $cor ?>"></span>
                    <p class="text-[10px] uppercase tracking-widest font-black text-slate-400"><?= $titulo ?></p>
                    <p data-card-winthor="<?= $chaveCard ?>" class="text-3xl lg:text-4xl font-black text-navy-900 mt-2"><?= (int)$valor ?></p>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1"><?= $subtitulo ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
            <header class="p-5 lg:p-6 border-b border-slate-100 flex flex-col xl:flex-row gap-4 xl:items-center xl:justify-between">
                <div>
                    <h2 id="tituloVisaoWinthor" class="text-lg font-black text-navy-900">Em rotina agora</h2>
                    <p class="text-xs text-slate-400 mt-1">Clique em uma linha para consultar as 30 atividades mais recentes.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full xl:w-auto">
                    <input id="buscaWinthor" type="search" placeholder="Buscar pessoa ou matrícula..." class="w-full xl:w-72 rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-semibold outline-none focus:ring-2 focus:ring-blue-500/20">
                    <select id="setorWinthor" class="rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-semibold outline-none">
                        <option value="">Todos os setores</option>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?= htmlspecialchars(mb_strtolower((string)$setor)) ?>"><?= htmlspecialchars((string)$setor) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input id="statusWinthor" type="hidden" value="online">
                </div>
            </header>

            <nav class="px-5 py-4 border-b border-slate-100 bg-slate-50/70 overflow-x-auto" aria-label="Visões do acompanhamento">
                <div class="flex items-center gap-2 min-w-max">
                    <button type="button" class="visao-winthor-btn ativo rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-wider" data-visao="online" data-titulo="Em rotina agora">Em rotina agora <span data-contador-winthor="online" class="ml-1 opacity-70"><?= (int)$cards['online'] ?></span></button>
                    <button type="button" class="visao-winthor-btn rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-wider" data-visao="online,hoje" data-titulo="Pessoas que usaram hoje">Usaram hoje <span data-contador-winthor="hoje" class="ml-1 opacity-70"><?= (int)$cards['hoje'] ?></span></button>
                    <button type="button" class="visao-winthor-btn rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-wider" data-visao="atencao" data-titulo="Pessoas que precisam de atenção">Atenção <span data-contador-winthor="atencao" class="ml-1 opacity-70"><?= (int)$cards['atencao'] ?></span></button>
                    <button type="button" class="visao-winthor-btn rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-wider" data-visao="sem_hoje" data-titulo="Sem utilização hoje">Sem acesso hoje <span data-contador-winthor="sem_hoje" class="ml-1 opacity-70"><?= (int)$cards['sem_hoje'] ?></span></button>
                    <button type="button" class="visao-winthor-btn rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-wider" data-visao="nunca" data-titulo="Pessoas que nunca acessaram">Nunca acessaram <span data-contador-winthor="nunca" class="ml-1 opacity-70"><?= (int)$cards['nunca'] ?></span></button>
                    <button type="button" class="visao-winthor-btn rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-wider" data-visao="ignorado" data-titulo="Pessoas não acompanhadas">Não acompanhados <span data-contador-winthor="ignorados" class="ml-1 opacity-70"><?= (int)$cards['ignorados'] ?></span></button>
                    <button type="button" class="visao-winthor-btn rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-wider" data-visao="" data-titulo="Todas as pessoas">Todos <span data-contador-winthor="todos" class="ml-1 opacity-70"><?= count($usuarios) ?></span></button>
                </div>
            </nav>

            <div id="tabelaWinthorScroll" class="tabela-winthor-scroll">
                <table class="w-full min-w-[1200px] text-left">
                    <thead class="bg-slate-50">
                        <tr class="text-[9px] uppercase tracking-widest font-black text-slate-400">
                            <th class="px-5 py-4">Pessoa</th>
                            <th class="px-4 py-4">Setor</th>
                            <th class="px-4 py-4">Situação</th>
                            <th class="px-4 py-4">Rotina atual/última</th>
                            <th class="px-4 py-4">Último acesso</th>
                            <th class="px-4 py-4 text-center">Atividades hoje</th>
                            <th class="px-4 py-4 text-center">Histórico carregado</th>
                            <th class="px-5 py-4 text-right">Acompanhamento</th>
                        </tr>
                    </thead>
                    <tbody id="corpoWinthor" class="divide-y divide-slate-100">
                    <?php foreach ($usuarios as $u):
                        $ignorado = (int)$u['monitorar'] !== 1;
                        $classeStatus = [
                            'online' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'hoje' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'sem_hoje' => 'bg-slate-50 text-slate-600 border-slate-200',
                            'atencao' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'nunca' => 'bg-rose-50 text-rose-700 border-rose-200',
                        ][$u['status_key']];
                        $rotina = $u['rotinas_agora'] ?: $u['ultima_rotina'] ?: 'Sem rotina registrada';
                    ?>
                        <tr class="linha-winthor hover:bg-blue-50/40 transition-colors cursor-pointer <?= $ignorado ? 'opacity-45' : '' ?>"
                            data-busca="<?= htmlspecialchars(mb_strtolower($u['matricula_oracle'] . ' ' . $u['nome'])) ?>"
                            data-setor="<?= htmlspecialchars(mb_strtolower((string)($u['setor'] ?? ''))) ?>"
                            data-status="<?= $ignorado ? 'ignorado' : htmlspecialchars($u['status_key']) ?>"
                            onclick='abrirHistoricoWinthor(<?= (int)$u['id'] ?>, <?= htmlspecialchars(json_encode($u['nome'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)'>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-navy-900 text-white flex items-center justify-center text-xs font-black shrink-0"><?= htmlspecialchars(mb_substr($u['nome'], 0, 1)) ?></div>
                                    <div>
                                        <p class="text-xs font-black text-navy-900 uppercase"><?= htmlspecialchars($u['nome']) ?></p>
                                        <p class="text-[10px] text-slate-400 font-bold mt-0.5">Matrícula <?= (int)$u['matricula_oracle'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs font-semibold text-slate-600"><?= htmlspecialchars($u['setor'] ?: 'Não informado') ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded-full border text-[9px] font-black uppercase tracking-wider <?= $classeStatus ?>"><?= htmlspecialchars($u['status']) ?></span>
                                <?php if (!empty($u['divergencia_identidade'])): ?>
                                    <span class="block text-[9px] font-black text-rose-600 mt-1">VERIFICAR IDENTIDADE</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 max-w-md">
                                <p class="text-xs font-bold text-slate-700 truncate" title="<?= htmlspecialchars($rotina) ?>"><?= htmlspecialchars($rotina) ?></p>
                                <?php if ($u['usuario_rede_atual']): ?><p class="text-[9px] text-blue-500 font-black mt-1">REDE: <?= htmlspecialchars($u['usuario_rede_atual']) ?></p><?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-xs font-semibold text-slate-600 whitespace-nowrap"><?= $u['ultimo_acesso'] ? date('d/m/Y H:i:s', strtotime($u['ultimo_acesso'])) : 'Nunca' ?></td>
                            <td class="px-4 py-4 text-center text-sm font-black text-navy-900"><?= (int)$u['acessos_hoje'] ?></td>
                            <td class="px-4 py-4 text-center text-sm font-black text-navy-900"><?= (int)$u['total_acessos'] ?></td>
                            <td class="px-5 py-4 text-right" onclick="event.stopPropagation()">
                                <?php if (!empty($_SESSION['is_admin'])): ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_winthor']) ?>">
                                        <input type="hidden" name="acao" value="alternar_monitoramento">
                                        <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="monitorar" value="<?= $ignorado ? 1 : 0 ?>">
                                        <button class="px-3 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider <?= $ignorado ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 hover:bg-rose-50 hover:text-rose-600' ?>">
                                            <?= $ignorado ? 'Acompanhar' : 'Não acompanhar' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-[9px] font-black text-slate-400 uppercase"><?= $ignorado ? 'Ignorado' : 'Ativo' ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="semResultadosWinthor" class="hidden p-12 text-center text-sm font-bold text-slate-400">Nenhum usuário encontrado com os filtros selecionados.</div>
            </div>
        </section>
    </div>
</main>

<div id="modalHistoricoWinthor" class="hidden fixed inset-0 z-[2500] bg-navy-900/60 backdrop-blur-sm p-4 items-center justify-center">
    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col">
        <header class="bg-navy-900 text-white px-6 py-5 flex items-center justify-between">
            <div>
                <p class="text-[9px] text-blue-300 font-black uppercase tracking-[0.2em]">Histórico individual</p>
                <h3 id="tituloHistoricoWinthor" class="text-lg font-black mt-1"></h3>
            </div>
            <button onclick="fecharHistoricoWinthor()" class="text-2xl text-white/50 hover:text-white">&times;</button>
        </header>
        <div id="conteudoHistoricoWinthor" class="p-5 overflow-y-auto space-y-3"></div>
    </div>
</div>

<script>
const buscaWinthor = document.getElementById('buscaWinthor');
const setorWinthor = document.getElementById('setorWinthor');
const statusWinthor = document.getElementById('statusWinthor');
const tabelaWinthorScroll = document.getElementById('tabelaWinthorScroll');

function filtrarWinthor() {
    const busca = buscaWinthor.value.toLowerCase().trim();
    const setor = setorWinthor.value;
    const status = statusWinthor.value;
    const statusAceitos = status ? status.split(',') : [];
    let visiveis = 0;
    document.querySelectorAll('.linha-winthor').forEach(linha => {
        const mostrar = (!busca || linha.dataset.busca.includes(busca)) &&
            (!setor || linha.dataset.setor === setor) &&
            (!status || statusAceitos.includes(linha.dataset.status));
        linha.classList.toggle('hidden', !mostrar);
        if (mostrar) visiveis++;
    });
    document.getElementById('semResultadosWinthor').classList.toggle('hidden', visiveis !== 0);
}

[buscaWinthor, setorWinthor].forEach(el => el.addEventListener('input', filtrarWinthor));

const filtrosSalvos = JSON.parse(sessionStorage.getItem('filtrosWinthor') || '{}');
buscaWinthor.value = filtrosSalvos.busca || '';
setorWinthor.value = filtrosSalvos.setor || '';
statusWinthor.value = sessionStorage.getItem('visaoWinthor') ?? 'online';

function atualizarVisaoWinthor() {
    const botaoAtivo = [...document.querySelectorAll('.visao-winthor-btn')]
        .find(botao => botao.dataset.visao === statusWinthor.value);
    document.querySelectorAll('.visao-winthor-btn').forEach(botao => {
        botao.classList.toggle('ativo', botao === botaoAtivo);
    });
    document.getElementById('tituloVisaoWinthor').textContent = botaoAtivo?.dataset.titulo || 'Pessoas acompanhadas';
}

document.querySelectorAll('.visao-winthor-btn').forEach(botao => {
    botao.addEventListener('click', () => {
        statusWinthor.value = botao.dataset.visao;
        sessionStorage.setItem('visaoWinthor', statusWinthor.value);
        sessionStorage.setItem('scrollWinthor', '0');
        tabelaWinthorScroll.scrollTop = 0;
        atualizarVisaoWinthor();
        filtrarWinthor();
    });
});

atualizarVisaoWinthor();
filtrarWinthor();

tabelaWinthorScroll.scrollTop = Number(sessionStorage.getItem('scrollWinthor') || 0);
tabelaWinthorScroll.addEventListener('scroll', () => {
    sessionStorage.setItem('scrollWinthor', String(tabelaWinthorScroll.scrollTop));
}, { passive: true });

[buscaWinthor, setorWinthor].forEach(el => el.addEventListener('input', () => {
    sessionStorage.setItem('filtrosWinthor', JSON.stringify({
        busca: buscaWinthor.value,
        setor: setorWinthor.value
    }));
}));

function duracaoHumana(segundos) {
    segundos = Number(segundos || 0);
    if (segundos < 60) return segundos + 's';
    const min = Math.floor(segundos / 60);
    if (min < 60) return min + 'min';
    return Math.floor(min / 60) + 'h ' + (min % 60) + 'min';
}

function dataBr(data) {
    if (!data) return '-';
    return new Date(data.replace(' ', 'T')).toLocaleString('pt-BR');
}

function escaparHtml(valor) {
    return String(valor ?? '').replace(/[&<>'"]/g, caractere => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[caractere]);
}

async function abrirHistoricoWinthor(usuarioId, nome) {
    const modal = document.getElementById('modalHistoricoWinthor');
    const conteudo = document.getElementById('conteudoHistoricoWinthor');
    document.getElementById('tituloHistoricoWinthor').textContent = nome;
    conteudo.innerHTML = '<div class="py-16 text-center text-sm font-black text-blue-600 animate-pulse">Carregando histórico...</div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    try {
        const resposta = await fetch(`acompanhamento_winthor.php?ajax=historico&usuario_id=${usuarioId}`);
        const dados = await resposta.json();
        if (!Array.isArray(dados) || dados.length === 0) {
            conteudo.innerHTML = '<div class="py-16 text-center text-sm font-bold text-slate-400">Nenhuma atividade registrada.</div>';
            return;
        }
        conteudo.innerHTML = dados.map(item => `
            <article class="border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:border-blue-300 transition-colors">
                <div>
                    <p class="text-xs font-black text-navy-900">${escaparHtml(item.codrotina_oracle)} - ${escaparHtml(item.nomerotina)}</p>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1">${escaparHtml(item.nome_modulo || 'Módulo não informado')}</p>
                </div>
                <div class="md:text-right">
                    <p class="text-xs font-bold text-slate-600">${dataBr(item.data_inicio)} → ${dataBr(item.data_final)}</p>
                    <p class="text-[10px] font-black text-blue-600 mt-1">Duração: ${duracaoHumana(item.duracao_segundos)}</p>
                </div>
            </article>
        `).join('');
    } catch (erro) {
        conteudo.innerHTML = '<div class="py-16 text-center text-sm font-bold text-rose-500">Não foi possível carregar o histórico.</div>';
    }
}

function fecharHistoricoWinthor() {
    const modal = document.getElementById('modalHistoricoWinthor');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('modalHistoricoWinthor').addEventListener('click', event => {
    if (event.target.id === 'modalHistoricoWinthor') fecharHistoricoWinthor();
});

let atualizandoWinthor = false;

async function atualizarDadosWinthor() {
    if (document.hidden || atualizandoWinthor) return;
    atualizandoWinthor = true;
    try {
        const resposta = await fetch(window.location.href, {
            cache: 'no-store',
            headers: { 'X-Requested-With': 'painel-winthor' }
        });
        if (!resposta.ok) return;

        const documentoNovo = new DOMParser().parseFromString(await resposta.text(), 'text/html');
        const corpoNovo = documentoNovo.getElementById('corpoWinthor');
        if (!corpoNovo) return;

        const posicaoScroll = tabelaWinthorScroll.scrollTop;
        document.getElementById('corpoWinthor').innerHTML = corpoNovo.innerHTML;

        ['ultimaColetaWinthor', 'mensagemColetaWinthor'].forEach(id => {
            const atual = document.getElementById(id);
            const novo = documentoNovo.getElementById(id);
            if (atual && novo) atual.textContent = novo.textContent;
        });

        document.querySelectorAll('[data-card-winthor]').forEach(atual => {
            const chave = atual.dataset.cardWinthor;
            const novo = documentoNovo.querySelector(`[data-card-winthor="${chave}"]`);
            if (novo) atual.textContent = novo.textContent;
        });

        document.querySelectorAll('[data-contador-winthor]').forEach(atual => {
            const chave = atual.dataset.contadorWinthor;
            const novo = documentoNovo.querySelector(`[data-contador-winthor="${chave}"]`);
            if (novo) atual.textContent = novo.textContent;
        });

        filtrarWinthor();
        tabelaWinthorScroll.scrollTop = posicaoScroll;
    } catch (erro) {
        console.warn('Atualização do painel indisponível:', erro);
    } finally {
        atualizandoWinthor = false;
    }
}

// Atualiza somente os dados. A página, os filtros e o scroll não recarregam.
setInterval(atualizarDadosWinthor, 5000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
