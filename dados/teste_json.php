<?php

$caminhoJson = __DIR__ . '/sistemas.json';

if (!file_exists($caminhoJson)) {
    die('ERRO: O arquivo sistemas.json não foi encontrado.');
}

$json = file_get_contents($caminhoJson);

$sistemas = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('ERRO NO JSON: ' . json_last_error_msg());
}


function buscarFilhos($sistemas, $paiId = null)
{
    return array_values(
        array_filter(
            $sistemas,
            function ($item) use ($paiId) {
                return $item['pai_id'] === $paiId;
            }
        )
    );
}


$pais = buscarFilhos($sistemas, null);


// ===============================
// CATEGORIA SELECIONADA
// ===============================

$categoriaSelecionada = null;
$filhosCategoria = [];

// Exemplo:
// teste_json.php?categoria=53

if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {

    $categoriaId = (int) $_GET['categoria'];

    foreach ($pais as $pai) {

        if ((int)$pai['id'] === $categoriaId) {

            $categoriaSelecionada = $pai;
            break;

        }

    }

    if ($categoriaSelecionada !== null) {

        $filhosCategoria = buscarFilhos(
            $sistemas,
            $categoriaSelecionada['id']
        );

    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste JSON - Sistemas</title>

    <style>
       * {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background:
        radial-gradient(circle at center, #0b1830 0%, #06101f 45%, #030815 100%);
    color: #ffffff;
    font-family: Arial, sans-serif;
    padding: 40px;
}

.titulo {
    margin-bottom: 8px;
    font-size: 22px;
    font-weight: 700;
}

.subtitulo {
    margin-bottom: 40px;
    color: #38d9ff;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

/* ÁREA DO FLUXO */

.fluxo-teste {
    min-height: 520px;

    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 150px;

    align-items: center;

    padding: 40px;

    border: 1px solid rgba(56, 189, 248, 0.15);
    border-radius: 18px;

    background: rgba(6, 16, 31, 0.65);
}

/* CARD PAI */

.card-pai {
    position: relative;

    width: 240px;
    min-height: 115px;

    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;

    padding: 20px;

    border: 1px solid #00d9ff;
    border-radius: 14px;

    background:
        linear-gradient(
            145deg,
            rgba(0, 217, 255, 0.10),
            rgba(5, 15, 30, 0.90)
        );

    box-shadow:
        0 0 20px rgba(0, 217, 255, 0.08),
        inset 0 0 20px rgba(0, 217, 255, 0.03);
}

.card-pai .tipo {
    position: absolute;
    top: 10px;
    left: 12px;

    color: #00e5ff;
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 1px;
}

.card-pai .icone {
    font-size: 28px;
    margin-bottom: 12px;
}

.card-pai .nome {
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
}

/* COLUNA DOS FILHOS */

.lista-filhos {
    display: flex;
    flex-direction: column;
    gap: 18px;

    width: 310px;
}

/* CARD FILHO */

.card-filho {
    position: relative;

    min-height: 72px;

    display: flex;
    align-items: center;
    gap: 15px;

    padding: 13px 18px;

    border: 1px solid rgba(0, 217, 255, 0.45);
    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            rgba(10, 31, 50, 0.95),
            rgba(5, 14, 27, 0.98)
        );

    box-shadow:
        0 0 14px rgba(0, 217, 255, 0.05);

    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.card-filho:hover {
    transform: translateX(5px);

    border-color: #00e5ff;

    box-shadow:
        0 0 18px rgba(0, 229, 255, 0.15);
}

.card-filho .icone {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    border: 1px solid #00d9ff;
    border-radius: 9px;

    background: #061525;

    font-size: 19px;

    box-shadow:
        0 0 10px rgba(0, 217, 255, 0.12);
}

.card-filho .conteudo {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.card-filho .tipo {
    color: #00e5ff;
    font-size: 7px;
    font-weight: bold;
    letter-spacing: 1px;
}

.card-filho .nome {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.fluxo-teste {
    position: relative;
    overflow: hidden;
}

.camada-linhas {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
}

.camada-linhas svg {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

.card-pai,
.lista-filhos {
    position: relative;
    z-index: 2;
}

.linha-principal,
.linha-ramo {
    fill: none;
    stroke: #19e6ff;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 0 5px rgba(25, 230, 255, 0.9));
    opacity: 0.95;
}

.seta-linha {
    fill: #19e6ff;
    filter: drop-shadow(0 0 6px rgba(25, 230, 255, 1));
}

/* ==========================================
   TELA PRINCIPAL / NOC
========================================== */

.noc-layout {
    min-height: 610px;

    display: grid;
    grid-template-columns: 300px 420px 300px;
    gap: 90px;

    align-items: center;
    justify-content: center;

    padding: 40px;

    border: 1px solid rgba(56, 189, 248, 0.15);
    border-radius: 18px;

    background: rgba(6, 16, 31, 0.65);

     position: relative;
     overflow: hidden;
}

/* ==========================================
   LINHAS DA TELA PRINCIPAL / NOC
========================================== */

.noc-camada-linhas {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
}

.noc-camada-linhas svg {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

/* Cards ficam na frente das linhas */
.categorias-coluna,
.noc-central,
.saidas-coluna {
    position: relative;
    z-index: 2;
}


/* LINHAS FINAS DAS CATEGORIAS */

.noc-linha-categoria,
.noc-linha-tronco {
    fill: none;
    stroke: #19e6ff;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;

    filter:
        drop-shadow(0 0 4px rgba(25, 230, 255, 0.85));

    opacity: 0.95;
}


/* LINHAS PRINCIPAIS QUE ENTRAM NO NOC */

.noc-linha-nucleo {
    fill: none;
    stroke: #19e6ff;
    stroke-width: 4;
    stroke-linecap: round;
    stroke-linejoin: round;

    filter:
        drop-shadow(0 0 7px rgba(25, 230, 255, 0.95));
}


/* SAÍDAS DO NOC */

.noc-linha-saida {
    fill: none;

    stroke: url(#nocGradienteSaida);
    stroke-width: 4;

    stroke-linecap: round;
    stroke-linejoin: round;

    filter:
        drop-shadow(0 0 8px rgba(192, 38, 255, 0.9));
}


/* SETAS */

.noc-seta-cyan {
    fill: #19e6ff;

    filter:
        drop-shadow(0 0 6px rgba(25, 230, 255, 1));
}

.noc-seta-purple {
    fill: #c026ff;

    filter:
        drop-shadow(0 0 7px rgba(192, 38, 255, 1));
}


/* CATEGORIAS */

.categorias-coluna {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.titulo-coluna {
    color: #00e5ff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    margin-bottom: 5px;
}

.categoria-link {
    position: relative;

    min-height: 66px;

    display: grid;
    grid-template-columns: 45px 1fr 25px;
    align-items: center;
    gap: 10px;

    padding: 10px 12px;

    color: #ffffff;
    text-decoration: none;

    border: 1px solid rgba(0, 217, 255, 0.35);
    border-radius: 10px;

    background: rgba(7, 21, 38, 0.95);

    transition: 0.2s ease;
}

.categoria-link:hover {
    transform: translateX(5px);

    border-color: #00e5ff;

    box-shadow:
        0 0 16px rgba(0, 229, 255, 0.18);
}

.categoria-icone {
    font-size: 23px;
    text-align: center;
}

.categoria-nome {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.categoria-seta {
    color: #00e5ff;
    font-size: 13px;
}


/* NOC CENTRAL */

.noc-central {
    display: flex;
    justify-content: center;
    align-items: center;
}

.noc-hexagono {
    width: 280px;
    height: 240px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    text-align: center;

    border: 2px solid #00e5ff;

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
            rgba(23, 113, 170, 0.24),
            rgba(4, 13, 28, 0.96)
        );

    filter:
        drop-shadow(
            0 0 13px rgba(0, 229, 255, 0.55)
        );
}

.noc-icone {
    font-size: 42px;
    margin-bottom: 15px;
}

.noc-titulo {
    color: #38d9ff;
    font-size: 19px;
    font-weight: 700;
}

.noc-subtitulo {
    color: #8aeaff;
    font-size: 10px;
    margin-top: 5px;
}

.noc-descricao {
    font-size: 11px;
    line-height: 1.5;
    margin-top: 12px;
}


/* SAÍDAS */

.saidas-coluna {
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.saida-card {
    min-height: 170px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding: 20px;

    text-align: center;

    border: 1px solid #7c3aed;
    border-radius: 14px;

    background:
        linear-gradient(
            145deg,
            rgba(76, 29, 149, 0.15),
            rgba(4, 13, 28, 0.95)
        );

    box-shadow:
        0 0 18px rgba(124, 58, 237, 0.12);
}

.saida-card strong {
    margin-top: 10px;
    color: #62b9ff;
    font-size: 16px;
}

.saida-card span {
    margin-top: 5px;
    font-size: 10px;
    color: #b79bff;
}

.saida-card small {
    margin-top: 10px;
    font-size: 10px;
    line-height: 1.5;
}

.saida-icone {
    font-size: 35px;
}


/* VOLTAR */

.btn-voltar {
    display: inline-block;

    margin-bottom: 20px;

    color: #38d9ff;
    text-decoration: none;

    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
}

.btn-voltar:hover {
    color: #ffffff;
}
    </style>
</head>

<body>

<?php if ($categoriaSelecionada === null): ?>

    <!-- ================================================= -->
    <!-- TELA PRINCIPAL / NOC -->
    <!-- ================================================= -->

    <div class="titulo">
        Central de Sistemas
    </div>

    <div class="subtitulo">
        Monitoramento universal e sistemas autorizados
    </div>


    <div class="noc-layout">

      <div class="noc-camada-linhas">
        <svg id="nocLinhasSvg"></svg>
    </div>

        <!-- CATEGORIAS -->
        <div class="categorias-coluna">

            <div class="titulo-coluna">
                ENTRADA
            </div>

            <?php foreach ($pais as $pai): ?>

                <a
                    href="?categoria=<?= (int)$pai['id'] ?>"
                    class="categoria-link"
                    data-noc-categoria="<?= (int)$pai['id'] ?>"
                >

                    <div class="categoria-icone">
                        <?= htmlspecialchars($pai['icone'] ?? '') ?>
                    </div>

                    <div class="categoria-nome">
                        <?= htmlspecialchars($pai['nome']) ?>
                    </div>

                    <div class="categoria-seta">
                        ▶
                    </div>

                </a>

            <?php endforeach; ?>

        </div>


        <!-- NÚCLEO CENTRAL -->
        <div class="noc-central">

           <div class="noc-hexagono" id="nocCentral">

                <div class="noc-icone">
                    ⚙️
                </div>

                <div class="noc-titulo">
                    NOC AUTOMATIZADO
                </div>

                <div class="noc-subtitulo">
                    CÉREBRO AUTÔNOMO
                </div>

                <div class="noc-descricao">
                    DETECÇÃO E ANÁLISE<br>
                    EM TEMPO REAL
                </div>

            </div>

        </div>


        <!-- SAÍDAS -->
        <div class="saidas-coluna">

            <div class="saida-card" id="saidaAutocorrecao">

                <div class="saida-icone">
                    ⚙️
                </div>

                <strong>AUTOCORREÇÃO</strong>

                <span>
                    SELF-HEALING
                </span>

                <small>
                    SCRIPTS DE CURA EXECUTADOS
                </small>

            </div>


            <div class="saida-card" id="saidaItsm">

                <div class="saida-icone">
                    ✉️
                </div>

                <strong>ESCALONAMENTO ITSM</strong>

                <span>
                    TICKET
                </span>

                <small>
                    ABERTURA DE CHAMADO COM DIAGNÓSTICO
                </small>

            </div>

        </div>

    </div>


<?php else: ?>


    <!-- ================================================= -->
    <!-- CATEGORIA EXPANDIDA -->
    <!-- ================================================= -->

    <a href="teste_json.php" class="btn-voltar">
        ← VOLTAR
    </a>


    <div class="titulo">
        <?= htmlspecialchars($categoriaSelecionada['nome']) ?>
    </div>

    <div class="subtitulo">
        Sistemas e aplicações desta categoria
    </div>


    <div class="fluxo-teste">

        <!-- SVG DAS LINHAS -->
        <div class="camada-linhas">
            <svg id="linhasSvg"></svg>
        </div>


        <!-- CATEGORIA PAI -->
        <div
            class="card-pai"
            id="categoria-<?= (int)$categoriaSelecionada['id'] ?>"
        >

            <span class="tipo">
                CATEGORIA
            </span>

            <div class="icone">
                <?= htmlspecialchars($categoriaSelecionada['icone'] ?? '') ?>
            </div>

            <div class="nome">
                <?= htmlspecialchars($categoriaSelecionada['nome']) ?>
            </div>

        </div>


        <!-- FILHOS -->
        <div class="lista-filhos">

            <?php if (!empty($filhosCategoria)): ?>

                <?php foreach ($filhosCategoria as $filho): ?>

                    <div
                        class="card-filho"
                        id="sistema-<?= (int)$filho['id'] ?>"
                        data-id="<?= (int)$filho['id'] ?>"
                        data-pai="<?= (int)$filho['pai_id'] ?>"
                        data-url="<?= htmlspecialchars($filho['url'] ?? '#') ?>"
                    >

                        <div class="icone">
                            <?= htmlspecialchars($filho['icone'] ?? '') ?>
                        </div>

                        <div class="conteudo">

                            <div class="tipo">
                                SISTEMA
                            </div>

                            <div class="nome">
                                <?= htmlspecialchars($filho['nome']) ?>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="sem-filhos">
                    Nenhum sistema cadastrado nesta categoria.
                </div>

            <?php endif; ?>

        </div>

    </div>


<?php endif; ?>


<script>

document.addEventListener('DOMContentLoaded', function () {

    redesenharTudo();

    window.addEventListener('resize', redesenharTudo);

});


function redesenharTudo() {

    // Linhas da categoria expandida
    desenharLinhas();

    // Linhas da tela principal NOC
    desenharLinhasNoc();

}



function desenharLinhas() {

    const fluxo = document.querySelector('.fluxo-teste');
    const pai = document.querySelector('.card-pai');
    const filhos = document.querySelectorAll('.card-filho');
    const svg = document.getElementById('linhasSvg');

    // Na tela NOC esses elementos não existem.
    // Então simplesmente não desenhamos nada.
    if (!fluxo || !pai || !filhos.length || !svg) {
        return;
    }

    svg.innerHTML = '';


    // ===============================
    // SETA SVG
    // ===============================

    const defs =
        document.createElementNS(
            'http://www.w3.org/2000/svg',
            'defs'
        );

    const marker =
        document.createElementNS(
            'http://www.w3.org/2000/svg',
            'marker'
        );

    marker.setAttribute('id', 'arrowHead');
    marker.setAttribute('markerWidth', '10');
    marker.setAttribute('markerHeight', '10');
    marker.setAttribute('refX', '8');
    marker.setAttribute('refY', '5');
    marker.setAttribute('orient', 'auto');
    marker.setAttribute('markerUnits', 'strokeWidth');


    const arrowPath =
        document.createElementNS(
            'http://www.w3.org/2000/svg',
            'path'
        );

    arrowPath.setAttribute(
        'd',
        'M 0 0 L 10 5 L 0 10 z'
    );

    arrowPath.setAttribute(
        'class',
        'seta-linha'
    );


    marker.appendChild(arrowPath);
    defs.appendChild(marker);
    svg.appendChild(defs);


    // ===============================
    // POSIÇÕES
    // ===============================

    const fluxoRect =
        fluxo.getBoundingClientRect();

    const paiRect =
        pai.getBoundingClientRect();


    const startX =
        paiRect.right - fluxoRect.left;

    const startY =
        (paiRect.top - fluxoRect.top)
        +
        (paiRect.height / 2);


    const filhosInfo =
        Array.from(filhos).map(filho => {

            const rect =
                filho.getBoundingClientRect();

            return {

                x:
                    rect.left
                    -
                    fluxoRect.left,

                y:
                    (rect.top - fluxoRect.top)
                    +
                    (rect.height / 2)

            };

        });


    const primeiroY =
        filhosInfo[0].y;

    const ultimoY =
        filhosInfo[
            filhosInfo.length - 1
        ].y;


    const trunkX =
        startX + 90;


    // ===============================
    // PAI → TRONCO
    // ===============================

    svg.appendChild(
        criarPath(

            `M ${startX} ${startY}
             L ${trunkX} ${startY}`,

            'linha-principal'

        )
    );


    // ===============================
    // TRONCO VERTICAL
    // ===============================

    svg.appendChild(
        criarPath(

            `M ${trunkX} ${primeiroY}
             L ${trunkX} ${ultimoY}`,

            'linha-principal'

        )
    );


    // ===============================
    // RAMOS
    // ===============================

    filhosInfo.forEach(filho => {

        const ramo =
            criarPath(

                `M ${trunkX} ${filho.y}
                 L ${filho.x - 14} ${filho.y}`,

                'linha-ramo'

            );

        ramo.setAttribute(
            'marker-end',
            'url(#arrowHead)'
        );

        svg.appendChild(ramo);

    });

}

// ======================================================
// LINHAS DA TELA PRINCIPAL / NOC
// ======================================================

function desenharLinhasNoc() {

    const layout =
        document.querySelector('.noc-layout');

    const svg =
        document.getElementById('nocLinhasSvg');

    const categorias =
        Array.from(
            document.querySelectorAll('.categoria-link')
        );

    const noc =
        document.getElementById('nocCentral');

    const saidaAutocorrecao =
        document.getElementById('saidaAutocorrecao');

    const saidaItsm =
        document.getElementById('saidaItsm');


    // Estamos dentro de uma categoria expandida?
    // Então não existe NOC e simplesmente saímos.
    if (
        !layout ||
        !svg ||
        !categorias.length ||
        !noc ||
        !saidaAutocorrecao ||
        !saidaItsm
    ) {
        return;
    }


    // Limpa desenho anterior
    svg.innerHTML = '';


    // ==================================================
    // DEFINIÇÕES SVG
    // ==================================================

    criarDefsNoc(svg);


    // ==================================================
    // POSIÇÃO GERAL DA ÁREA
    // ==================================================

    const layoutRect =
        layout.getBoundingClientRect();


    // ==================================================
    // POSIÇÕES DAS CATEGORIAS
    // ==================================================

    const categoriasInfo =
        categorias.map(categoria => {

            const rect =
                categoria.getBoundingClientRect();

            return {

                elemento: categoria,

                x:
                    rect.right -
                    layoutRect.left,

                y:
                    rect.top -
                    layoutRect.top +
                    (rect.height / 2)

            };

        });


    const primeiraCategoria =
        categoriasInfo[0];

    const ultimaCategoria =
        categoriasInfo[
            categoriasInfo.length - 1
        ];


    // ==================================================
    // POSIÇÃO DO NOC
    // ==================================================

    const nocRect =
        noc.getBoundingClientRect();

    const nocLeft =
        nocRect.left -
        layoutRect.left;

    const nocRight =
        nocRect.right -
        layoutRect.left;

    const nocTop =
        nocRect.top -
        layoutRect.top;

    const nocWidth =
        nocRect.width;

    const nocHeight =
        nocRect.height;

    const nocCenterY =
        nocTop +
        (nocHeight / 2);


    // ==================================================
    // TRONCO VERTICAL
    // ==================================================

    const distanciaCategoriasNoc =
        nocLeft -
        primeiraCategoria.x;

    const trunkX =
        primeiraCategoria.x +
        Math.max(
            40,
            Math.min(
                75,
                distanciaCategoriasNoc * 0.28
            )
        );


    // Linha vertical principal
    svg.appendChild(

        criarPath(

            `
            M ${trunkX} ${primeiraCategoria.y}
            L ${trunkX} ${ultimaCategoria.y}
            `,

            'noc-linha-tronco'

        )

    );


    // ==================================================
    // CATEGORIAS → TRONCO
    // ==================================================

    categoriasInfo.forEach(categoria => {

        const linha = criarPath(

            `
            M ${categoria.x} ${categoria.y}
            L ${trunkX - 10} ${categoria.y}
            `,

            'noc-linha-categoria'

        );


        linha.setAttribute(
            'marker-end',
            'url(#nocArrowCyan)'
        );


        svg.appendChild(linha);

    });


    // ==================================================
    // TRONCO → NOC
    // DUAS ENTRADAS COMO NA REFERÊNCIA
    // ==================================================

    const entradaSuperiorY =
        nocCenterY - 38;

    const entradaInferiorY =
        nocCenterY + 38;


    // Por causa do formato hexagonal,
    // entramos um pouco dentro da caixa do NOC.
    const entradaNocX =
        nocLeft +
        (nocWidth * 0.035);


    // Entrada superior
    const entradaSuperior =
        criarPath(

            `
            M ${trunkX} ${entradaSuperiorY}
            L ${entradaNocX - 10} ${entradaSuperiorY}
            `,

            'noc-linha-nucleo'

        );


    entradaSuperior.setAttribute(
        'marker-end',
        'url(#nocArrowCyan)'
    );


    svg.appendChild(
        entradaSuperior
    );


    // Entrada inferior
    const entradaInferior =
        criarPath(

            `
            M ${trunkX} ${entradaInferiorY}
            L ${entradaNocX - 10} ${entradaInferiorY}
            `,

            'noc-linha-nucleo'

        );


    entradaInferior.setAttribute(
        'marker-end',
        'url(#nocArrowCyan)'
    );


    svg.appendChild(
        entradaInferior
    );


    // ==================================================
    // POSIÇÕES DAS SAÍDAS
    // ==================================================

    const autoRect =
        saidaAutocorrecao.getBoundingClientRect();

    const itsmRect =
        saidaItsm.getBoundingClientRect();


    const autoX =
        autoRect.left -
        layoutRect.left;

    const autoY =
        autoRect.top -
        layoutRect.top +
        (autoRect.height / 2);


    const itsmX =
        itsmRect.left -
        layoutRect.left;

    const itsmY =
        itsmRect.top -
        layoutRect.top +
        (itsmRect.height / 2);


    // ==================================================
    // PONTOS DE SAÍDA DO NOC
    // ==================================================

    const saidaNocX =
        nocRight -
        (nocWidth * 0.035);

    const saidaSuperiorY =
        nocCenterY - 38;

    const saidaInferiorY =
        nocCenterY + 38;


    // ==================================================
    // NOC → AUTOCORREÇÃO
    // ==================================================

    const meioSuperiorX =
        saidaNocX +
        (
            (autoX - saidaNocX) *
            0.52
        );


    const caminhoAuto =
        criarCaminhoOrtogonal(

            saidaNocX,
            saidaSuperiorY,

            autoX - 16,
            autoY,

            meioSuperiorX

        );


    const linhaAuto =
        criarPath(

            caminhoAuto,

            'noc-linha-saida'

        );


    linhaAuto.setAttribute(
        'marker-end',
        'url(#nocArrowPurple)'
    );


    svg.appendChild(
        linhaAuto
    );


    // ==================================================
    // NOC → ESCALONAMENTO ITSM
    // ==================================================

    const meioInferiorX =
        saidaNocX +
        (
            (itsmX - saidaNocX) *
            0.52
        );


    const caminhoItsm =
        criarCaminhoOrtogonal(

            saidaNocX,
            saidaInferiorY,

            itsmX - 16,
            itsmY,

            meioInferiorX

        );


    const linhaItsm =
        criarPath(

            caminhoItsm,

            'noc-linha-saida'

        );


    linhaItsm.setAttribute(
        'marker-end',
        'url(#nocArrowPurple)'
    );


    svg.appendChild(
        linhaItsm
    );

}

// ======================================================
// DEFINIÇÕES VISUAIS DO SVG NOC
// ======================================================

function criarDefsNoc(svg) {

    const NS =
        'http://www.w3.org/2000/svg';


    const defs =
        document.createElementNS(
            NS,
            'defs'
        );


    // ==========================================
    // GRADIENTE CIANO → ROXO
    // ==========================================

    const gradient =
        document.createElementNS(
            NS,
            'linearGradient'
        );


    gradient.setAttribute(
        'id',
        'nocGradienteSaida'
    );

    gradient.setAttribute(
        'x1',
        '0%'
    );

    gradient.setAttribute(
        'y1',
        '0%'
    );

    gradient.setAttribute(
        'x2',
        '100%'
    );

    gradient.setAttribute(
        'y2',
        '0%'
    );


    const stop1 =
        document.createElementNS(
            NS,
            'stop'
        );

    stop1.setAttribute(
        'offset',
        '0%'
    );

    stop1.setAttribute(
        'stop-color',
        '#19e6ff'
    );


    const stop2 =
        document.createElementNS(
            NS,
            'stop'
        );

    stop2.setAttribute(
        'offset',
        '100%'
    );

    stop2.setAttribute(
        'stop-color',
        '#c026ff'
    );


    gradient.appendChild(
        stop1
    );

    gradient.appendChild(
        stop2
    );


    defs.appendChild(
        gradient
    );


    // ==========================================
    // SETA CIANO
    // ==========================================

    const markerCyan =
        document.createElementNS(
            NS,
            'marker'
        );


    markerCyan.setAttribute(
        'id',
        'nocArrowCyan'
    );

    markerCyan.setAttribute(
        'markerWidth',
        '10'
    );

    markerCyan.setAttribute(
        'markerHeight',
        '10'
    );

    markerCyan.setAttribute(
        'refX',
        '8'
    );

    markerCyan.setAttribute(
        'refY',
        '5'
    );

    markerCyan.setAttribute(
        'orient',
        'auto'
    );

    markerCyan.setAttribute(
        'markerUnits',
        'strokeWidth'
    );


    const setaCyan =
        document.createElementNS(
            NS,
            'path'
        );


    setaCyan.setAttribute(
        'd',
        'M 0 0 L 10 5 L 0 10 z'
    );

    setaCyan.setAttribute(
        'class',
        'noc-seta-cyan'
    );


    markerCyan.appendChild(
        setaCyan
    );


    defs.appendChild(
        markerCyan
    );


    // ==========================================
    // SETA ROXA
    // ==========================================

    const markerPurple =
        document.createElementNS(
            NS,
            'marker'
        );


    markerPurple.setAttribute(
        'id',
        'nocArrowPurple'
    );

    markerPurple.setAttribute(
        'markerWidth',
        '10'
    );

    markerPurple.setAttribute(
        'markerHeight',
        '10'
    );

    markerPurple.setAttribute(
        'refX',
        '8'
    );

    markerPurple.setAttribute(
        'refY',
        '5'
    );

    markerPurple.setAttribute(
        'orient',
        'auto'
    );

    markerPurple.setAttribute(
        'markerUnits',
        'strokeWidth'
    );


    const setaPurple =
        document.createElementNS(
            NS,
            'path'
        );


    setaPurple.setAttribute(
        'd',
        'M 0 0 L 10 5 L 0 10 z'
    );

    setaPurple.setAttribute(
        'class',
        'noc-seta-purple'
    );


    markerPurple.appendChild(
        setaPurple
    );


    defs.appendChild(
        markerPurple
    );


    svg.appendChild(
        defs
    );

}

function criarCaminhoOrtogonal(
    startX,
    startY,
    endX,
    endY,
    midX,
    raio = 12
) {

    // Se os dois pontos estiverem praticamente
    // na mesma altura, basta linha reta.
    if (
        Math.abs(endY - startY) < 2
    ) {

        return `
            M ${startX} ${startY}
            L ${endX} ${endY}
        `;

    }


    const sentido =
        endY > startY
            ? 1
            : -1;


    const distanciaY =
        Math.abs(
            endY - startY
        );


    const r =
        Math.min(
            raio,
            distanciaY / 2
        );


    return `
        M ${startX} ${startY}

        L ${midX - r} ${startY}

        Q
        ${midX} ${startY}
        ${midX} ${startY + (r * sentido)}

        L
        ${midX}
        ${endY - (r * sentido)}

        Q
        ${midX} ${endY}
        ${midX + r} ${endY}

        L ${endX} ${endY}
    `;

}


function criarPath(d, classe) {

    const path =
        document.createElementNS(
            'http://www.w3.org/2000/svg',
            'path'
        );

    path.setAttribute('d', d);
    path.setAttribute('class', classe);

    return path;

}

</script>

</body>
</html>