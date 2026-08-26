<?php
/**
 * MÓDULO: Sistemas de Navegação / NOC
 *
 * Este arquivo é incluído pelo index_teste.php.
 * Ele reutiliza a sessão, $pdo_intra e $user_id_logado já carregados no index.
 *
 * Contém:
 * - RBAC e leitura de sistemas_lista;
 * - categorias temporárias do NOC;
 * - HTML do modal;
 * - CSS específico;
 * - JavaScript específico da navegação.
 */
?>

<div id="modalSistemas" class="fixed inset-0 z-[1000] hidden items-center justify-center p-4 backdrop-blur-xl bg-navy-900/40 transition-all duration-500">
 <div id="modalSistemasPainel" class="modal-sistemas-painel relative w-[98vw] max-w-[1750px] rounded-[2rem] p-7 animate-in zoom-in-95 duration-300 overflow-hidden">
        
        <button id="btnVoltarModal" onclick="exibirPrincipalSistemas()" class="hidden absolute top-6 left-6 text-blue-400 hover:text-white hover:scale-105 transition-all text-xs font-black flex items-center gap-2 z-30 bg-white/5 border border-white/10 px-4 py-2 rounded-xl backdrop-blur-md">
            ⬅️ VOLTAR
        </button>

        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
       
        <button onclick="fecharModalSistemas()" class="absolute top-5 right-6 text-white/30 hover:text-white transition-colors text-3xl font-light z-30">&times;</button>

         <div class="modal-sistemas-header mb-8 flex flex-col md:flex-row justify-between items-start md:items-center border-b border-cyan-400/10 pb-4 mt-4 md:mt-0">
            <div>
                <h2 id="tituloModalSistemas" class="text-white text-xl font-black tracking-tighter uppercase italic">Sistemas de Navegação</h2>
                <p id="subtituloModalSistemas" class="text-blue-400 text-[10px] font-bold uppercase tracking-widest">Sistemas e ferramentas autorizados para seu perfil</p>
            </div>
           
            <!-- Pesquisa tecnológica de sistemas -->
        <div class="pesquisa-sistemas-wrapper mt-3 md:mt-0 md:mr-8">
            <div class="pesquisa-sistemas">
                <span class="pesquisa-sistemas__icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="6"></circle>
                        <path d="M16 16L21 21"></path>
                    </svg>
                </span>

                <input
                    type="text"
                    id="inputBuscaSistemas"
                    oninput="filtrarSistemas()"
                    placeholder="Buscar sistema ou módulo..."
                    autocomplete="off"
                >

                <button
                    type="button"
                    class="pesquisa-sistemas__limpar"
                    onclick="limparBuscaSistemas()"
                    title="Limpar pesquisa"
                    aria-label="Limpar pesquisa"
                >
                    &times;
                </button>
            </div>
        </div>

        </div>

        <?php 
            $nav_sistemas_permitidos = [];
            
            // RBAC: Resgata as permissões associadas ao usuário
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
                $nav_stmt_sys = $pdo_intra->query("SELECT * FROM sistemas_lista ORDER BY nome");
                $nav_sistemas_permitidos = $nav_stmt_sys->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $nav_stmt_sys = $pdo_intra->prepare("
                    SELECT DISTINCT sl.* FROM sistemas_lista sl
                    LEFT JOIN permissoes_sistemas ps ON sl.id = ps.sistema_id AND ps.user_id = ?
                    LEFT JOIN grupos_sistemas gs ON sl.id = gs.sistema_id
                    LEFT JOIN usuarios_grupos ug ON gs.grupo_id = ug.grupo_id AND ug.usuario_id = ?
                    WHERE ps.user_id IS NOT NULL OR ug.usuario_id IS NOT NULL
                    ORDER BY sl.nome
                ");
                $nav_stmt_sys->execute([$user_id_logado, $user_id_logado]);
                $nav_sistemas_permitidos = $nav_stmt_sys->fetchAll(PDO::FETCH_ASSOC);
            }

            $nav_sistemas_raiz = [];
            $nav_sistemas_filhos = [];

            foreach ($nav_sistemas_permitidos as $nav_sys) {
                if (!empty($nav_sys['pai_id'])) {
                    $nav_sistemas_filhos[$nav_sys['pai_id']][] = $nav_sys;
                } else {
                    $nav_sistemas_raiz[] = $nav_sys;
                }
            }
            // ======================================================
            // PASSO 1 - IDENTIFICA NOC CENTRAL E SISTEMAS RAIZ
            // ======================================================

            $nav_noc_central = null;
            $nav_sistemas_raiz_por_id = [];

            foreach ($nav_sistemas_raiz as $nav_sys) {

                $nav_id_sistema = (int)$nav_sys['id'];

                // ID 52 = NOC CHAMADOS
                if ($nav_id_sistema === 52) {
                    $nav_noc_central = $nav_sys;
                    continue;
                }

                $nav_sistemas_raiz_por_id[$nav_id_sistema] = $nav_sys;
            }

            // ======================================================
// PASSO 2 - CATEGORIAS TEMPORÁRIAS DO NOVO PAINEL
// ======================================================

// Aqui usamos os IDs REAIS dos sistemas raiz.
// Nenhum pai_id do banco será alterado neste teste.

$nav_categorias_config = [

    'monitoramento' => [
        'nome'  => 'MONITORAMENTO & OBSERVABILIDADE',
        'icone' => '📡',
        'cor'   => '#22d3ee',
        'ids'   => [
            53, // Monitoramento TI
            16, // pfSense e Zabbix
            59, // Sistema Incidentes
            61, // Grafana
            62  // Controle de Atualização por Equipamento
        ]
    ],

    'infraestrutura' => [
        'nome'  => 'INFRAESTRUTURA & CONECTIVIDADE',
        'icone' => '🖥️',
        'cor'   => '#3b82f6',
        'ids'   => [
            34, // T.I.
            50, // Internet
            31, // UniFi
            32, // Incontrol
            30  // iCloud
        ]
    ],

    'gestao' => [
        'nome'  => 'GESTÃO & PROCESSOS',
        'icone' => '📊',
        'cor'   => '#a855f7',
        'ids'   => [
            20, // Estrutura de Projetos
            29, // Kanban
            39  // Ticket
        ]
    ],

    'corporativos' => [
        'nome'  => 'SISTEMAS CORPORATIVOS',
        'icone' => '💼',
        'cor'   => '#6366f1',
        'ids'   => [
            42  // TOTVS
        ]
    ],

    'facilities' => [
        'nome'  => 'FACILITIES & OPERAÇÃO',
        'icone' => '🏢',
        'cor'   => '#10b981',
        'ids'   => [
            3, // Facilities & T.I.
            1  // Recebimento
        ]
    ],

    'pessoas' => [
        'nome'  => 'PESSOAS & RH',
        'icone' => '👥',
        'cor'   => '#f59e0b',
        'ids'   => [
            11 // Gestão de Ponto
        ]
    ],

    'comunicacao' => [
        'nome'  => 'COMUNICAÇÃO & TELEFONIA',
        'icone' => '☎️',
        'cor'   => '#ec4899',
        'ids'   => [
            33, // PABX
            14  // Vivo 0800
        ]
    ]

];


// ======================================================
// MONTA AS CATEGORIAS RESPEITANDO AS PERMISSÕES
// ======================================================

$nav_categorias = [];

foreach ($nav_categorias_config as $nav_slug => $nav_config) {

    $nav_sistemas_categoria = [];

    foreach ($nav_config['ids'] as $nav_id_sistema) {

        // Só entra se o sistema estiver entre os
        // sistemas raiz permitidos para este usuário.
        if (isset($nav_sistemas_raiz_por_id[$nav_id_sistema])) {

            // Pega o sistema real da tabela
            $nav_item_sistema = $nav_sistemas_raiz_por_id[$nav_id_sistema];

            // Anexa os filhos reais desse sistema, caso existam
            $nav_item_sistema['subitens'] =
                $nav_sistemas_filhos[$nav_id_sistema] ?? [];

            // Adiciona na categoria
            $nav_sistemas_categoria[] = $nav_item_sistema;
        }
    }

    // Categoria sem nenhum acesso não aparece.
    if (!empty($nav_sistemas_categoria)) {

        $nav_categorias[] = [
            'slug'     => $nav_slug,
            'nome'     => $nav_config['nome'],
            'icone'    => $nav_config['icone'],
            'cor'      => $nav_config['cor'],
            'sistemas' => $nav_sistemas_categoria
        ];
    }
}

        ?>

       <div id="nocEstruturaTeste" style="
            width: 100%;

            display: grid;
            grid-template-columns: 360px 420px 360px;
            gap: 135px;

            align-items: center;
            justify-content: center;

            min-height: 610px;

            padding: 20px 20px;

            position: relative;
            isolation: isolate;
        ">


        <!-- ====================================================== -->
        <!-- CAMADA SVG - CONEXÕES DO NOC -->
        <!-- ====================================================== -->

        <svg
            id="nocLinhasSvg"
            aria-hidden="true"
            style="
                position: absolute;
                inset: 0;

                width: 100%;
                height: 100%;

                pointer-events: none;

                z-index: 0;

                overflow: visible;
            "
        ></svg>

        <!-- ====================================================== -->
<!-- PASSO 3 - TESTE VISUAL DAS NOVAS CATEGORIAS -->
<!-- ====================================================== -->

<div id="nocCategoriasTeste" style="max-width: 380px; display: flex; flex-direction: column; gap: 12px;">

    <div style="
        color: #22d3ee;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .18em;
        margin-bottom: 5px;
    ">
        ENTRADA
    </div>

    <?php foreach ($nav_categorias as $nav_categoria): ?>

        <?php
            $nav_categoria_json = htmlspecialchars(
                json_encode(
                    $nav_categoria['sistemas'],
                    JSON_UNESCAPED_UNICODE |
                    JSON_HEX_APOS |
                    JSON_HEX_QUOT
                ),
                ENT_QUOTES,
                'UTF-8'
            );
        ?>

        <button

        data-nome="<?= htmlspecialchars($nav_categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>"
        data-subitens="<?= $nav_categoria_json; ?>"
        onclick="abrirCategoriaNoc(this)"

            type="button"
            style="
                --categoria-cor: <?= htmlspecialchars($nav_categoria['cor'], ENT_QUOTES, 'UTF-8'); ?>;

                width: 100%;
                min-height: 72px;

                display: grid;
                grid-template-columns: 50px 1fr 28px;
                align-items: center;
                gap: 10px;

                padding: 9px 12px;

                color: white;
                text-align: left;

                border: 1px solid <?= htmlspecialchars($nav_categoria['cor'], ENT_QUOTES, 'UTF-8'); ?>;
                border-radius: 11px;

                background: linear-gradient(
                    145deg,
                    rgba(15,23,42,.96),
                    rgba(2,12,28,.96)
                );

                cursor: pointer;
            "
        >

            <span style="
                width: 44px;
                height: 44px;

                display: flex;
                align-items: center;
                justify-content: center;

                border-radius: 9px;
                background: rgba(2,6,23,.70);

                font-size: 24px;
            ">
                <?= $nav_categoria['icone']; ?>
            </span>


            <span style="
                font-size: 10px;
                font-weight: 900;
                line-height: 1.2;
                text-transform: uppercase;
            ">
                <?= htmlspecialchars(
                    $nav_categoria['nome'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </span>


            <span style="
                color: <?= htmlspecialchars($nav_categoria['cor'], ENT_QUOTES, 'UTF-8'); ?>;
                font-size: 14px;
            ">
                ▶
            </span>

        </button>

    <?php endforeach; ?>

</div>

<!-- ====================================================== -->
<!-- PASSO 4 - NOC CENTRAL REAL -->
<!-- ====================================================== -->

<div style="
    display: flex;
    align-items: center;
    justify-content: center;
">

    <?php if ($nav_noc_central): ?>

        <a
            href="<?= htmlspecialchars(
                $nav_noc_central['url'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
            target="_blank"
            rel="noopener noreferrer"
            id="nocCentralTeste"
            style="
                width: 350px;
                height: 290px;

                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;

                text-decoration: none;
                text-align: center;
                color: white;

                clip-path: polygon(
                    25% 0%,
                    75% 0%,
                    100% 50%,
                    75% 100%,
                    25% 100%,
                    0% 50%
                );

                background:
                    radial-gradient(
                        circle,
                        rgba(14, 116, 144, .38),
                        rgba(3, 18, 38, .98) 70%
                    );

                filter:
                    drop-shadow(
                        0 0 14px rgba(34, 211, 238, .55)
                    );

                transition:
                    transform .25s ease,
                    filter .25s ease;
            "
        >

            <div style="
                font-size: 38px;
                margin-bottom: 12px;
            ">
                <?= $nav_noc_central['icone']; ?>
            </div>

            <div style="
                color: #22d3ee;
                font-size: 21px;
                font-weight: 900;
                text-transform: uppercase;
            ">
                <?= htmlspecialchars(
                    $nav_noc_central['nome'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </div>

            <div style="
                margin-top: 5px;
                color: #67e8f9;
                font-size: 8px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .08em;
            ">
                CENTRAL DE ATENDIMENTO
            </div>

            <div style="
                margin-top: 12px;
                color: rgba(255,255,255,.75);
                font-size: 8px;
                font-weight: 700;
            ">
                ACESSO AO NOC DE CHAMADOS
            </div>

        </a>

    <?php else: ?>

        <div style="
            width: 300px;
            height: 250px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: rgba(255,255,255,.4);

            clip-path: polygon(
                25% 0%,
                75% 0%,
                100% 50%,
                75% 100%,
                25% 100%,
                0% 50%
            );

            background: rgba(15,23,42,.85);
        ">
            NOC CHAMADOS NÃO LIBERADO
        </div>

    <?php endif; ?>

</div>

   <div id="nocSaidasTeste" style="
        display: flex;
        flex-direction: column;
        gap: 28px;
    ">

        <!-- AUTOCORREÇÃO -->
        <div
            id="nocSaidaAutocorrecao"
            style="
                min-height: 190px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 18px;
                text-align: center;
                border: 1px solid rgba(168, 85, 247, .75);
                border-radius: 14px;
                background:
                    linear-gradient(
                        145deg,
                        rgba(76, 29, 149, .18),
                        rgba(2, 6, 23, .96)
                    );
                box-shadow:
                    0 0 20px rgba(168, 85, 247, .10);
            "
        >

            <div style="font-size: 32px; margin-bottom: 10px;">
                ⚙️
            </div>

            <div style="
                color: #60a5fa;
                font-size: 14px;
                font-weight: 900;
                text-transform: uppercase;
            ">
                AUTOCORREÇÃO
            </div>

            <div style="
                margin-top: 3px;
                color: #c084fc;
                font-size: 8px;
                font-weight: 700;
                text-transform: uppercase;
            ">
                SELF-HEALING
            </div>

            <div style="
                margin-top: 10px;
                color: rgba(255,255,255,.78);
                font-size: 8px;
                font-weight: 700;
                line-height: 1.45;
            ">
                SCRIPTS DE CURA<br>
                EXECUTADOS
            </div>

        </div>


        <!-- ESCALONAMENTO ITSM -->
        <div
            id="nocSaidaItsm"
            style="
                min-height: 190px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 18px;
                text-align: center;
                border: 1px solid rgba(168, 85, 247, .75);
                border-radius: 14px;
                background:
                    linear-gradient(
                        145deg,
                        rgba(76, 29, 149, .18),
                        rgba(2, 6, 23, .96)
                    );
                box-shadow:
                    0 0 20px rgba(168, 85, 247, .10);
            "
        >

            <div style="font-size: 32px; margin-bottom: 10px;">
                ✉️
            </div>

            <div style="
                color: #60a5fa;
                font-size: 14px;
                font-weight: 900;
                text-transform: uppercase;
            ">
                ESCALONAMENTO ITSM
            </div>

            <div style="
                margin-top: 3px;
                color: #c084fc;
                font-size: 8px;
                font-weight: 700;
                text-transform: uppercase;
            ">
                TICKET
            </div>

            <div style="
                margin-top: 10px;
                color: rgba(255,255,255,.78);
                font-size: 8px;
                font-weight: 700;
                line-height: 1.45;
            ">
                ABERTURA DE CHAMADO<br>
                COM DIAGNÓSTICO
            </div>

        </div>

    </div>


</div>

<!-- fecha nocEstruturaTeste -->



       <div id="gridSistemasPrincipal" style="display: none;" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-8 gap-y-7 max-h-[62vh] overflow-y-auto pr-4 custom-scrollbar-compact animate-in fade-in duration-300">
                <?php if (empty($nav_sistemas_raiz)): ?>
            <div class="col-span-full text-center py-10 text-white/40 text-xs font-bold uppercase tracking-widest">
                ⚠️ NENHUM ACESSO LIBERADO PARA SEU PERFIL.
            </div>
       <?php else: ?>

            <?php
            // Converte as cores cadastradas no banco em cores para o efeito neon
            $nav_cores_neon = [
                'bg-blue-600'    => '#22d3ee',
                'bg-amber-500'   => '#f59e0b',
                'bg-amber-600'   => '#d97706',
                'bg-emerald-500' => '#10b981',
                'bg-emerald-600' => '#059669',
                'bg-purple-500'  => '#a855f7',
                'bg-purple-600'  => '#9333ea',
                'bg-slate-500'   => '#64748b',
                'bg-slate-600'   => '#475569',
                'bg-slate-800'   => '#1e293b',
                'bg-red-500'     => '#ef4444',
                'bg-red-600'     => '#dc2626'
            ];
            ?>

            <?php foreach ($nav_sistemas_raiz as $nav_sys):
                $nav_is_grupo = ($nav_sys['url'] === '#');
                $nav_cor_neon = $nav_cores_neon[$nav_sys['cor']] ?? '#22d3ee';
            ?>

                <?php if ($nav_is_grupo):
                    $nav_sub_json = isset($nav_sistemas_filhos[$nav_sys['id']])
                        ? json_encode(
                            $nav_sistemas_filhos[$nav_sys['id']],
                            JSON_HEX_APOS | JSON_HEX_QUOT
                        )
                        : '[]';
                ?>

                    <div
                        onclick='abrirPastaSistemas(
                            <?php echo json_encode($nav_sys['nome']); ?>,
                            <?php echo $nav_sub_json; ?>
                        )'
                        class="sistema-card sistema-card-neon"
                        style="--neon-cor: <?php echo $nav_cor_neon; ?>;"
                        data-nome="<?php echo strtoupper(htmlspecialchars($nav_sys['nome'], ENT_QUOTES, 'UTF-8')); ?>"
                        data-subitens='<?php echo htmlspecialchars($nav_sub_json, ENT_QUOTES, 'UTF-8'); ?>'
                    >
                        <span class="sistema-card-neon__tipo">Módulo</span>
                        <span class="sistema-card-neon__status"></span>

                        <div class="sistema-card-neon__icone">
                            <?php echo $nav_sys['icone']; ?>
                        </div>

                        <span class="sistema-card-neon__nome">
                            <?php echo htmlspecialchars($nav_sys['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                <?php else: ?>

                    <a
                        href="<?php echo htmlspecialchars($nav_sys['url'], ENT_QUOTES, 'UTF-8'); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="sistema-card sistema-card-neon"
                        style="--neon-cor: <?php echo $nav_cor_neon; ?>;"
                        data-nome="<?php echo strtoupper(htmlspecialchars($nav_sys['nome'], ENT_QUOTES, 'UTF-8')); ?>"
                    >
                        <span class="sistema-card-neon__tipo">Sistema</span>

                        <div class="sistema-card-neon__icone">
                            <?php echo $nav_sys['icone']; ?>
                        </div>

                        <span class="sistema-card-neon__nome">
                            <?php echo htmlspecialchars($nav_sys['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </a>

                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
        </div>

        <div id="gridSistemasSub" class="hidden grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-4 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar-compact animate-in slide-in-from-right-5 duration-300"></div>

        <div id="rodapeSistemasModal" class="mt-8 pt-4 border-t border-white/5 flex justify-between items-center text-[9px] font-bold text-white/20 uppercase tracking-widest">
            <span>Launchpad de Aplicações</span>
            <span>Comercial Souza Atacado</span>
        </div>
    </div>
</div>

<?php
// Evita que variáveis internas do módulo vazem para o restante do index.php.
unset(
    $nav_sistemas_permitidos,
    $nav_sistemas_raiz,
    $nav_sistemas_filhos,
    $nav_noc_central,
    $nav_sistemas_raiz_por_id,
    $nav_categorias_config,
    $nav_categorias,
    $nav_sistemas_categoria,
    $nav_item_sistema,
    $nav_stmt_sys,
    $nav_sys,
    $nav_id_sistema,
    $nav_config,
    $nav_slug,
    $nav_categoria,
    $nav_categoria_json,
    $nav_cores_neon,
    $nav_cor_neon,
    $nav_sub_json,
    $nav_is_grupo
);
?>

<style>
        /* =========================================================
        MODAL DE SISTEMAS — VISUAL TECNOLÓGICO NEON
        ========================================================= */

        #modalSistemas {
            background:
                radial-gradient(circle at 15% 20%, rgba(34, 211, 238, 0.12), transparent 30%),
                radial-gradient(circle at 85% 75%, rgba(168, 85, 247, 0.14), transparent 32%),
                rgba(2, 6, 23, 0.82);
        }

            #modalSistemas .modal-sistemas-painel {
            position: relative;

            width: 96vw;
            max-width: 1800px;

            height: 92vh;
            max-height: 950px;

            background:
                linear-gradient(
                    145deg,
                    rgba(15, 23, 42, 0.98),
                    rgba(2, 6, 23, 0.98)
                );

            border: 1px solid rgba(103, 232, 249, 0.22);

            box-shadow:
                0 0 0 1px rgba(168, 85, 247, 0.08),
                0 0 35px rgba(34, 211, 238, 0.10),
                0 30px 80px rgba(0, 0, 0, 0.55);
        }

        #modalSistemas .modal-sistemas-painel.modo-sub {
            height: auto;
        }

        #modalSistemas .modal-sistemas-painel::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            border-radius: inherit;
            background:
                linear-gradient(90deg, transparent 49%, rgba(34, 211, 238, 0.025) 50%, transparent 51%),
                linear-gradient(0deg, transparent 49%, rgba(168, 85, 247, 0.025) 50%, transparent 51%);
            background-size: 40px 40px;
            mask-image: linear-gradient(to bottom, black, transparent 80%);
        }

        #modalSistemas .modal-sistemas-header {
            position: relative;
            z-index: 2;
        }

        #inputBuscaSistemas {
            background: rgba(15, 23, 42, 0.78);
            border: 1px solid rgba(34, 211, 238, 0.20);
            box-shadow: inset 0 0 16px rgba(34, 211, 238, 0.03);
        }

        #inputBuscaSistemas:focus {
            border-color: rgba(34, 211, 238, 0.75);
            box-shadow:
                0 0 0 3px rgba(34, 211, 238, 0.10),
                0 0 22px rgba(34, 211, 238, 0.12);
        }

        /* Card principal, card filho e resultado da pesquisa */
        .sistema-card-neon {
            --neon-cor: #22d3ee;

            position: relative;
            min-height: 150px;
            padding: 18px 12px;
            overflow: hidden;
            isolation: isolate;
            cursor: pointer;
            text-decoration: none;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            border-radius: 16px;
            border: 1px solid color-mix(in srgb, var(--neon-cor) 32%, transparent);
            background:
                linear-gradient(145deg, rgba(30, 41, 59, 0.88), rgba(15, 23, 42, 0.94));

            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.04),
                0 10px 25px rgba(0, 0, 0, 0.24);

            transition:
                transform 0.25s ease,
                border-color 0.25s ease,
                box-shadow 0.25s ease,
                background 0.25s ease;
        }

        .sistema-card-neon::before {
            content: "";
            position: absolute;
            width: 90px;
            height: 90px;
            top: -50px;
            right: -45px;
            z-index: -1;
            border-radius: 999px;
            background: var(--neon-cor);
            opacity: 0.12;
            filter: blur(22px);
            transition: opacity 0.25s ease;
        }

        .sistema-card-neon::after {
            content: "";
            position: absolute;
            left: 15%;
            right: 15%;
            bottom: 0;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                var(--neon-cor),
                transparent
            );
            opacity: 0.55;
        }

        .sistema-card-neon:hover {
            transform: translateY(-5px);
            border-color: color-mix(in srgb, var(--neon-cor) 75%, white 8%);
            background:
                linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 1));

            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--neon-cor) 18%, transparent),
                0 0 24px color-mix(in srgb, var(--neon-cor) 20%, transparent),
                0 18px 34px rgba(0, 0, 0, 0.36);
        }

        .sistema-card-neon:hover::before {
            opacity: 0.25;
        }

        /* Área do ícone */
        .sistema-card-neon__icone {
            position: relative;
            width: 52px;
            height: 52px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;
            border: 1px solid color-mix(in srgb, var(--neon-cor) 60%, transparent);
            background:
                radial-gradient(circle, color-mix(in srgb, var(--neon-cor) 16%, transparent), transparent 68%),
                rgba(2, 6, 23, 0.72);

            color: white;
            font-size: 24px;

            box-shadow:
                inset 0 0 18px color-mix(in srgb, var(--neon-cor) 10%, transparent),
                0 0 16px color-mix(in srgb, var(--neon-cor) 12%, transparent);

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease;
        }

        .sistema-card-neon:hover .sistema-card-neon__icone {
            transform: scale(1.08);
            box-shadow:
                inset 0 0 22px color-mix(in srgb, var(--neon-cor) 18%, transparent),
                0 0 22px color-mix(in srgb, var(--neon-cor) 28%, transparent);
        }

        /* Nome do sistema */
        .sistema-card-neon__nome {
            position: relative;
            z-index: 2;
            margin-top: 14px;

            color: rgba(226, 232, 240, 0.78);
            font-size: 10.5px;
            font-weight: 800;
            line-height: 1.2;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.025em;

            transition: color 0.25s ease;
        }

        .sistema-card-neon:hover .sistema-card-neon__nome {
            color: #ffffff;
        }

        /* Indicador das pastas */
        .sistema-card-neon__status {
            position: absolute;
            top: 9px;
            right: 9px;
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--neon-cor);
            box-shadow: 0 0 10px var(--neon-cor);
        }

        .sistema-card-neon__tipo {
            position: absolute;
            top: 8px;
            left: 9px;

            color: color-mix(in srgb, var(--neon-cor) 80%, white);
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        /* Ajustes para telas menores */
        @media (max-width: 640px) {
            .sistema-card-neon {
                min-height: 112px;
                padding: 12px 7px;
            }

            .sistema-card-neon__icone {
                width: 46px;
                height: 46px;
                font-size: 21px;
            }

            .sistema-card-neon__nome {
                font-size: 8px;
            }
        }

       /* Rede de conexões entre os cards */
        #gridSistemasPrincipal,
        #gridSistemasSub {
            position: relative;
            isolation: isolate;
        }

        .rede-sistemas-svg {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 0;
            overflow: visible;
            pointer-events: none;
        }
        .rede-sistemas-linha {
            fill: none;
            stroke: #22d3ee;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 7 7;
            opacity: 0.72;
            animation: redeSistemasMovimento 14s linear infinite;
        }

        .rede-sistemas-linha-roxa {
            stroke: #a855f7;
        }

        .rede-sistemas-ponto {
            fill: #67e8f9;
            opacity: 0.95;
        }
      
        #gridSistemasPrincipal > .sistema-card-neon,
        #gridSistemasSub > .sistema-card-neon {
            position: relative;
            z-index: 2;
        }

        @keyframes redeSistemasMovimento {
            from {
                stroke-dashoffset: 0;
            }

            to {
                stroke-dashoffset: -100;
            }
        }

        @media (max-width: 640px) {
            .rede-sistemas-linha {
                opacity: 0.40;
                stroke-width: 1.5;
            }
        }

        /* =========================================================
        PESQUISA TECNOLÓGICA DE SISTEMAS
        ========================================================= */

        .pesquisa-sistemas-wrapper {
            position: relative;
            z-index: 35;
        }

        .pesquisa-sistemas {
            position: relative;
            width: 290px;
            height: 42px;

            display: flex;
            align-items: center;

            border: 1px solid rgba(34, 211, 238, 0.35);
            border-radius: 12px;

            background:
                linear-gradient(145deg, rgba(15, 23, 42, 0.94), rgba(2, 6, 23, 0.94));

            box-shadow:
                inset 0 0 18px rgba(34, 211, 238, 0.035),
                0 0 0 1px rgba(168, 85, 247, 0.03);

            transition:
                border-color 0.25s ease,
                box-shadow 0.25s ease,
                transform 0.25s ease;
        }

        .pesquisa-sistemas:focus-within {
            border-color: rgba(34, 211, 238, 0.9);
            box-shadow:
                0 0 0 3px rgba(34, 211, 238, 0.08),
                0 0 22px rgba(34, 211, 238, 0.15),
                inset 0 0 20px rgba(34, 211, 238, 0.05);
            transform: translateY(-1px);
        }

        .pesquisa-sistemas::after {
            content: "";
            position: absolute;
            left: 18%;
            right: 18%;
            bottom: -1px;
            height: 1px;

            background: linear-gradient(
                90deg,
                transparent,
                #22d3ee,
                #a855f7,
                transparent
            );

            opacity: 0.8;
        }

        .pesquisa-sistemas__icone {
            width: 18px;
            height: 18px;
            margin-left: 13px;
            flex-shrink: 0;
            color: #67e8f9;
        }

        .pesquisa-sistemas__icone svg {
            width: 100%;
            height: 100%;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
        }

        .pesquisa-sistemas input {
            width: 100%;
            height: 100%;
            padding: 0 38px 0 11px;

            color: #f8fafc;
            font-size: 11px;
            font-weight: 600;

            border: none;
            outline: none;
            background: transparent;
        }

        .pesquisa-sistemas input::placeholder {
            color: rgba(148, 163, 184, 0.65);
        }

        .pesquisa-sistemas__limpar {
            position: absolute;
            top: 50%;
            right: 9px;
            transform: translateY(-50%);

            width: 25px;
            height: 25px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: rgba(148, 163, 184, 0.65);
            font-size: 18px;
            line-height: 1;

            border: 1px solid transparent;
            border-radius: 7px;
            background: transparent;

            transition: all 0.2s ease;
        }

        .pesquisa-sistemas__limpar:hover {
            color: white;
            border-color: rgba(168, 85, 247, 0.35);
            background: rgba(168, 85, 247, 0.12);
        }

        @media (max-width: 640px) {
            .pesquisa-sistemas {
                width: 100%;
            }

            .pesquisa-sistemas-wrapper {
                width: 100%;
            }
        }

        #gridSistemasPrincipal,
        #gridSistemasSub {
            overflow-x: hidden !important;
        }

      /* =========================================================
        LINHAS NOC - ENERGIA NEON
        ========================================================= */

        .noc-linha-viva {
            animation: nocLinhaViva 2.2s ease-in-out infinite;
        }

        @keyframes nocLinhaViva {

            0%, 100% {
                opacity: .78;
                filter:
                    drop-shadow(0 0 3px #22d3ee)
                    drop-shadow(0 0 5px rgba(34, 211, 238, .35));
            }

            50% {
                opacity: 1;
                filter:
                    drop-shadow(0 0 6px #22d3ee)
                    drop-shadow(0 0 12px rgba(103, 232, 249, .80));
            }
        }
</style>

<script>
const SISTEMAS_CORES_NEON = Object.freeze({
    'bg-blue-600': '#22d3ee',
    'bg-amber-500': '#f59e0b',
    'bg-amber-600': '#d97706',
    'bg-emerald-500': '#10b981',
    'bg-emerald-600': '#059669',
    'bg-purple-500': '#a855f7',
    'bg-purple-600': '#9333ea',
    'bg-slate-500': '#64748b',
    'bg-slate-600': '#475569',
    'bg-slate-800': '#1e293b',
    'bg-red-500': '#ef4444',
    'bg-red-600': '#dc2626'
});

    function desenharConexoesSistemas(grid) {
        if (!grid) return;

        // Remove a rede anterior antes de redesenhar
        const redeAnterior = grid.querySelector('.rede-sistemas-svg');

        if (redeAnterior) {
            redeAnterior.remove();
        }

        const cards = Array.from(
            grid.querySelectorAll(':scope > .sistema-card-neon')
        ).filter(card => card.style.display !== 'none');

        if (cards.length < 2) return;

        const estiloGrid = window.getComputedStyle(grid);
        const colunas = estiloGrid.gridTemplateColumns
            .split(' ')
            .filter(Boolean)
            .length;

        const largura = grid.scrollWidth;
        const altura = grid.scrollHeight;

        const svgNS = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(svgNS, 'svg');

        svg.classList.add('rede-sistemas-svg');
        svg.setAttribute('width', largura);
        svg.setAttribute('height', altura);
        svg.setAttribute('viewBox', `0 0 ${largura} ${altura}`);

        const gridRect = grid.getBoundingClientRect();

        function posicaoCard(card) {
            const rect = card.getBoundingClientRect();

            return {
                esquerda: rect.left - gridRect.left + grid.scrollLeft,
                direita: rect.right - gridRect.left + grid.scrollLeft,
                topo: rect.top - gridRect.top + grid.scrollTop,
                base: rect.bottom - gridRect.top + grid.scrollTop,
                centroX: rect.left - gridRect.left + grid.scrollLeft + rect.width / 2,
                centroY: rect.top - gridRect.top + grid.scrollTop + rect.height / 2
            };
        }

        function criarCaminho(d, roxo = false) {
            const path = document.createElementNS(svgNS, 'path');

            path.setAttribute('d', d);
            path.classList.add('rede-sistemas-linha');

            if (roxo) {
                path.classList.add('rede-sistemas-linha-roxa');
            }

            svg.appendChild(path);
        }

        function criarPonto(x, y) {
            const ponto = document.createElementNS(svgNS, 'circle');

            ponto.setAttribute('cx', x);
            ponto.setAttribute('cy', y);
            ponto.setAttribute('r', 3);
            ponto.classList.add('rede-sistemas-ponto');

            svg.appendChild(ponto);
        }

        cards.forEach((card, indice) => {
            const atual = posicaoCard(card);

            // Liga o card ao próximo card da mesma linha
            const existeCardDireita =
                (indice + 1) < cards.length &&
                ((indice + 1) % colunas !== 0);

            if (existeCardDireita) {
                const direita = posicaoCard(cards[indice + 1]);
                const meioX = (atual.direita + direita.esquerda) / 2;

                criarCaminho(
                    `M ${atual.direita} ${atual.centroY}
                    H ${meioX}
                    V ${direita.centroY}
                    H ${direita.esquerda}`,
                    indice % 2 !== 0
                );

                criarPonto(meioX, atual.centroY);
            }

            // Liga o card ao card da linha inferior
            const indiceInferior = indice + colunas;

            if (indiceInferior < cards.length) {
                const inferior = posicaoCard(cards[indiceInferior]);
                const meioY = (atual.base + inferior.topo) / 2;

                criarCaminho(
                    `M ${atual.centroX} ${atual.base}
                    V ${meioY}
                    H ${inferior.centroX}
                    V ${inferior.topo}`,
                    indice % 2 === 0
                );

                criarPonto(atual.centroX, meioY);
            }
        });

        grid.prepend(svg);
    }

function abrirCategoriaNoc(elemento) {

    const nomeCategoria = elemento.getAttribute('data-nome') || 'Categoria';
    const subitensJson = elemento.getAttribute('data-subitens') || '[]';

    let subitens = [];

    try {
        subitens = JSON.parse(subitensJson);
    } catch (erro) {
        console.error('Erro ao carregar os sistemas da categoria:', erro);
        return;
    }

    const estruturaNoc = document.getElementById('nocEstruturaTeste');

    if (estruturaNoc) {
        estruturaNoc.style.display = 'none';
    }

    abrirPastaSistemas(nomeCategoria, subitens);
}

function abrirPastaSistemas(nomePasta, subitens) {

    document
    .getElementById('modalSistemasPainel')
    ?.classList.add('modo-sub');
    const gridPrincipal = document.getElementById('gridSistemasPrincipal');
    const gridSub = document.getElementById('gridSistemasSub');
    const btnVoltar = document.getElementById('btnVoltarModal');
    const titulo = document.getElementById('tituloModalSistemas');
    const subtitulo = document.getElementById('subtituloModalSistemas');

    if (!gridSub) {
        console.error('gridSistemasSub não encontrado no modal.');
        return;
    }

    if (gridPrincipal) {
        gridPrincipal.classList.add('hidden');
        gridPrincipal.style.display = 'none';
    }

    gridSub.classList.remove('hidden');

    if (btnVoltar) {
        btnVoltar.classList.remove('hidden');
    }

    if (titulo) {
        titulo.innerText = nomePasta;
    }

    if (subtitulo) {
        subtitulo.innerText = 'Módulo interno • Aplicações liberadas para seu perfil';
    }

    if (!Array.isArray(subitens) || subitens.length === 0) {
        gridSub.innerHTML = `
            <div class="col-span-full text-center py-12">
                <span class="text-3xl block mb-3">📭</span>
                <p class="text-[10px] text-white/30 font-black uppercase tracking-widest">
                    Nenhuma aplicação vinculada a este módulo.
                </p>
            </div>
        `;
        return;
    }

    gridSub.innerHTML = subitens.map((item, index) => {
        const corNeon = SISTEMAS_CORES_NEON[item.cor] || '#22d3ee';
        const filhos = Array.isArray(item.subitens) ? item.subitens : [];
        const temFilhos = filhos.length > 0;
        const urlItem = typeof item.url === 'string' ? item.url.trim() : '';
        const ehModulo = temFilhos || urlItem === '' || urlItem === '#';
        const nomeSeguro = String(item.nome || 'Sistema');
        const iconeSeguro = item.icone || '🖥️';

        if (ehModulo) {
            return `
                <button
                    type="button"
                    class="sistema-card-neon modulo-interno-noc"
                    data-index="${index}"
                    data-nome="${nomeSeguro.toUpperCase()}"
                    style="--neon-cor: ${corNeon}; width: 100%;"
                >
                    <span class="sistema-card-neon__tipo">Módulo</span>
                    <span class="sistema-card-neon__status"></span>

                    <div class="sistema-card-neon__icone">
                        ${iconeSeguro}
                    </div>

                    <span class="sistema-card-neon__nome">
                        ${nomeSeguro}
                    </span>
                </button>
            `;
        }

        return `
            <a
                href="${urlItem}"
                target="_blank"
                rel="noopener noreferrer"
                class="sistema-card-neon"
                data-nome="${nomeSeguro.toUpperCase()}"
                style="--neon-cor: ${corNeon};"
            >
                <span class="sistema-card-neon__tipo">Sistema</span>

                <div class="sistema-card-neon__icone">
                    ${iconeSeguro}
                </div>

                <span class="sistema-card-neon__nome">
                    ${nomeSeguro}
                </span>
            </a>
        `;
    }).join('');

    gridSub.querySelectorAll('.modulo-interno-noc').forEach(botao => {
        botao.addEventListener('click', function () {
            const index = Number(this.dataset.index);
            const modulo = subitens[index];

            if (!modulo) {
                return;
            }

            const filhosModulo = Array.isArray(modulo.subitens)
                ? modulo.subitens
                : [];

            abrirPastaSistemas(modulo.nome || 'Módulo', filhosModulo);
        });
    });

    requestAnimationFrame(function () {
        desenharConexoesSistemas(gridSub);
    });
}

function exibirPrincipalSistemas() {
    
    document
    .getElementById('modalSistemasPainel')
    ?.classList.remove('modo-sub');
    const estruturaNoc = document.getElementById('nocEstruturaTeste');
    const gridPrincipal = document.getElementById('gridSistemasPrincipal');
    const gridSub = document.getElementById('gridSistemasSub');
    const btnVoltar = document.getElementById('btnVoltarModal');
    const titulo = document.getElementById('tituloModalSistemas');
    const subtitulo = document.getElementById('subtituloModalSistemas');
    const inputBusca = document.getElementById('inputBuscaSistemas');

    if (estruturaNoc) {
        estruturaNoc.style.display = 'grid';
    }

    if (gridPrincipal) {
        gridPrincipal.classList.add('hidden');
        gridPrincipal.style.display = 'none';
    }

    if (gridSub) {
        gridSub.classList.add('hidden');
        gridSub.innerHTML = '';
    }

    if (btnVoltar) {
        btnVoltar.classList.add('hidden');
    }

    if (titulo) {
        titulo.innerText = 'Sistemas de Navegação';
    }

    if (subtitulo) {
        subtitulo.innerText = 'Sistemas e ferramentas autorizados para seu perfil';
    }

    if (inputBusca) {
        inputBusca.value = '';
    }
}

function abrirModalSistemas() {

    const modal = document.getElementById('modalSistemas');

    if (!modal) {
        console.error('modalSistemas não encontrado.');
        return;
    }

    exibirPrincipalSistemas();

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    requestAnimationFrame(function () {

    requestAnimationFrame(function () {

        testarLinhaNoc();

    });

});
}

function fecharModalSistemas() {
    const modal = document.getElementById('modalSistemas');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}


function limparBuscaSistemas() {
    const input = document.getElementById('inputBuscaSistemas');

    input.value = '';
    filtrarSistemas();
    input.focus();

    requestAnimationFrame(function () {
        desenharConexoesSistemas(
            document.getElementById('gridSistemasPrincipal')
        );
    });
}

function normalizarTextoBusca(texto) {
    return String(texto)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .trim();
}

 function filtrarSistemas() {

    const input = document.getElementById('inputBuscaSistemas');
    const gridPrincipal = document.getElementById('gridSistemasPrincipal');
    const gridSub = document.getElementById('gridSistemasSub');
    const estruturaNoc = document.getElementById('nocEstruturaTeste');
    const painel = document.getElementById('modalSistemasPainel');

    if (!input || !gridPrincipal) {
        return;
    }

    const filtro = normalizarTextoBusca(input.value);

    // Remove linhas antigas
    const redeAnterior = gridPrincipal.querySelector('.rede-sistemas-svg');

    if (redeAnterior) {
        redeAnterior.remove();
    }

    // Remove resultados gerados pela pesquisa anterior
    gridPrincipal
        .querySelectorAll('.card-dinamico-busca, .mensagem-busca-vazia')
        .forEach(elemento => elemento.remove());

    // Cards originais
    const cardsOriginais = Array.from(
        gridPrincipal.querySelectorAll(':scope > .sistema-card')
    );

    // =====================================================
    // PESQUISA VAZIA = VOLTA PARA O NOC
    // =====================================================

    if (filtro === '') {

        cardsOriginais.forEach(card => {
            card.style.display = '';
        });

        exibirPrincipalSistemas();

        return;
    }

    // =====================================================
    // ENTROU PESQUISA = ESCONDE NOC E MOSTRA RESULTADOS
    // =====================================================

    if (estruturaNoc) {
        estruturaNoc.style.display = 'none';
    }

    if (gridSub) {
        gridSub.classList.add('hidden');
        gridSub.innerHTML = '';
    }

    gridPrincipal.classList.remove('hidden');
    gridPrincipal.style.display = 'grid';

    // Evita o modal enorme durante a pesquisa
    if (painel) {
        painel.classList.add('modo-sub');
    }

    // Esconde todos antes de filtrar
    cardsOriginais.forEach(card => {
        card.style.display = 'none';
    });

    let totalEncontrado = 0;

    // =====================================================
    // PESQUISA NOS MÓDULOS E SISTEMAS
    // =====================================================

    cardsOriginais.forEach(card => {

        const nomeSistema = normalizarTextoBusca(
            card.getAttribute('data-nome') || ''
        );

        const subitensJson =
            card.getAttribute('data-subitens') || '';

        // Encontrou o próprio módulo/sistema
        if (nomeSistema.includes(filtro)) {

            card.style.display = '';
            totalEncontrado++;

        }

        // Procura também dentro dos módulos
        if (subitensJson) {

            try {

                const subitens = JSON.parse(subitensJson);

                subitens.forEach(sub => {

                    const nomeSubitem =
                        normalizarTextoBusca(sub.nome || '');

                    if (!nomeSubitem.includes(filtro)) {
                        return;
                    }

                    const corNeon =
                        SISTEMAS_CORES_NEON[sub.cor] || '#22d3ee';

                    const novoCard =
                        document.createElement('a');

                    novoCard.href = sub.url;
                    novoCard.target = '_blank';
                    novoCard.rel = 'noopener noreferrer';

                    novoCard.className =
                        'sistema-card-neon card-dinamico-busca';

                    novoCard.style.setProperty(
                        '--neon-cor',
                        corNeon
                    );

                    novoCard.innerHTML = `
                        <span class="sistema-card-neon__tipo">
                            Sistema
                        </span>

                        <div class="sistema-card-neon__icone">
                            ${sub.icone || '🖥️'}
                        </div>

                        <span class="sistema-card-neon__nome">
                            ${sub.nome || 'Sistema'}
                        </span>
                    `;

                    gridPrincipal.appendChild(novoCard);

                    totalEncontrado++;

                });

            } catch (erro) {

                console.error(
                    'Erro ao interpretar os sistemas do módulo:',
                    erro
                );

            }
        }
    });

    // =====================================================
    // NENHUM RESULTADO
    // =====================================================

    if (totalEncontrado === 0) {

        const mensagem = document.createElement('div');

        mensagem.className =
            'mensagem-busca-vazia col-span-full text-center py-12';

        mensagem.innerHTML = `
            <span class="text-3xl block mb-3">🔍</span>

            <p class="text-white/60 text-xs font-black uppercase tracking-widest">
                Nenhum sistema encontrado
            </p>

            <p class="text-white/30 text-[10px] mt-2">
                Tente pesquisar usando outro nome.
            </p>
        `;

        gridPrincipal.appendChild(mensagem);

        return;
    }

    // Redesenha as conexões dos resultados
    requestAnimationFrame(function () {
        desenharConexoesSistemas(gridPrincipal);
    });
}
   

function testarLinhaNoc() {

    const estrutura =
        document.getElementById('nocEstruturaTeste');

    const svg =
        document.getElementById('nocLinhasSvg');

    const categorias =
        Array.from(
            document.querySelectorAll(
                '#nocCategoriasTeste > button'
            )
        );

    const noc =
        document.getElementById('nocCentralTeste');


    if (
        !estrutura ||
        !svg ||
        categorias.length === 0 ||
        !noc
    ) {
        return;
    }


    // =====================================================
    // LIMPA DESENHO ANTERIOR
    // =====================================================

    svg.innerHTML = '';


    const largura =
        estrutura.clientWidth;

    const altura =
        estrutura.clientHeight;


    svg.setAttribute(
        'viewBox',
        `0 0 ${largura} ${altura}`
    );


    const base =
        estrutura.getBoundingClientRect();


    // =====================================================
    // FUNÇÃO PARA PEGAR POSIÇÃO DOS ELEMENTOS
    // =====================================================

    function posicao(elemento) {

        const r =
            elemento.getBoundingClientRect();

        return {

            esquerda:
                r.left - base.left,

            direita:
                r.right - base.left,

            topo:
                r.top - base.top,

            baixo:
                r.bottom - base.top,

            centroX:
                r.left -
                base.left +
                r.width / 2,

            centroY:
                r.top -
                base.top +
                r.height / 2

        };

    }


    const posCategorias =
        categorias.map(posicao);

    const posNoc =
        posicao(noc);


    const svgNS =
        'http://www.w3.org/2000/svg';


    // =====================================================
    // DEFINIÇÕES / SETA
    // =====================================================

    const defs =
        document.createElementNS(
            svgNS,
            'defs'
        );


    const marker =
        document.createElementNS(
            svgNS,
            'marker'
        );

    marker.setAttribute(
        'id',
        'setaEntradaNoc'
    );

    marker.setAttribute(
        'markerWidth',
        '8'
    );

    marker.setAttribute(
        'markerHeight',
        '8'
    );

    marker.setAttribute(
        'refX',
        '7'
    );

    marker.setAttribute(
        'refY',
        '4'
    );

    marker.setAttribute(
        'orient',
        'auto'
    );


    const ponta =
        document.createElementNS(
            svgNS,
            'path'
        );

    ponta.setAttribute(
        'd',
        'M 0 0 L 8 4 L 0 8 Z'
    );

    ponta.setAttribute(
        'fill',
        '#22d3ee'
    );


    marker.appendChild(ponta);

    defs.appendChild(marker);

    svg.appendChild(defs);


    // =====================================================
    // FUNÇÃO PARA CRIAR LINHA
    // =====================================================

    function criarLinha(
        d,
        larguraLinha = 2.5,
        seta = false
    ) {

        const path =
            document.createElementNS(
                svgNS,
                'path'
            );


        path.setAttribute(
            'd',
            d
        );

        path.setAttribute(
            'fill',
            'none'
        );

        path.setAttribute(
            'stroke',
            '#22d3ee'
        );

        path.setAttribute(
            'stroke-width',
            larguraLinha
        );

        path.setAttribute(
            'stroke-linecap',
            'round'
        );

        path.setAttribute(
            'stroke-linejoin',
            'round'
        );


        if (seta) {

            path.setAttribute(
                'marker-end',
                'url(#setaEntradaNoc)'
            );

        }
       path.style.filter =
        'drop-shadow(0 0 4px #22d3ee)';

        path.classList.add('noc-linha-viva');

        svg.appendChild(path);


    }


    // =====================================================
    // POSIÇÃO DO TRONCO VERTICAL
    // =====================================================

    const direitaBotoes =
        Math.max(
            ...posCategorias.map(
                p => p.direita
            )
        );


    // Tronco fica no espaço entre botões e NOC
    const xTronco =
        direitaBotoes + 48;


    const primeiroY =
        posCategorias[0].centroY;


    const ultimoY =
        posCategorias[
            posCategorias.length - 1
        ].centroY;


    // =====================================================
    // LINHAS DE CADA CATEGORIA ATÉ O TRONCO
    // =====================================================

    posCategorias.forEach(
        pos => {

            criarLinha(
                `
                M ${pos.direita} ${pos.centroY}
                H ${xTronco}
                `,
                2,
                true
            );

        }
    );


    // =====================================================
    // TRONCO VERTICAL
    // =====================================================

    criarLinha(
        `
        M ${xTronco} ${primeiroY}
        V ${ultimoY}
        `,
        3
    );


    // =====================================================
    // TRONCO → NOC
    // =====================================================

    const indiceCentral =
    Math.floor(posCategorias.length / 2);

    const ySaidaPrincipal =
        posCategorias[indiceCentral].centroY;

    criarLinha(
        `
        M ${xTronco} ${ySaidaPrincipal}
        H ${posNoc.esquerda - 14}
        `,
        3,
        true
    );

    // =====================================================
    // NOC → AUTOCORREÇÃO / ITSM
    // =====================================================

    const auto = document.getElementById('nocSaidaAutocorrecao');
    const itsm = document.getElementById('nocSaidaItsm');

    if (auto && itsm) {

        const pAuto = posicao(auto);
        const pItsm = posicao(itsm);

        const inicioX = posNoc.direita + 8;
        const ramalX = inicioX + 65;

        criarLinha(
            `M ${inicioX} ${posNoc.centroY}
            H ${ramalX}
            V ${pAuto.centroY}
            H ${pAuto.esquerda - 12}`,
            3,
            true
        );

        criarLinha(
            `M ${inicioX} ${posNoc.centroY}
            H ${ramalX}
            V ${pItsm.centroY}
            H ${pItsm.esquerda - 12}`,
            3,
            true
        );
    }

}
</script>