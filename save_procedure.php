<?php
session_start();
require_once 'conexao.php';
require_once 'log_activity.php'; // Inclui o arquivo de log

// 1. Carrega o autoloader do Composer, que gerencia todas as dependências (Dompdf, HTML Purifier, etc.)
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Qualquer usuário logado pode criar procedimentos.
// A verificação de 'user_id' garante que apenas usuários autenticados possam acessar.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?status=error&msg=" . urlencode("Acesso negado."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e sanitiza os dados do formulário
    $titulo_raw = trim($_POST['titulo'] ?? 'Procedimento Sem Título');
    $codigo = htmlspecialchars(trim($_POST['codigo'] ?? 'N/A'));
    $versao = htmlspecialchars(trim($_POST['versao'] ?? '1.0'));
    $data_emissao = htmlspecialchars(trim($_POST['data_emissao'] ?? date('d/m/Y')));
    $setor_id = filter_input(INPUT_POST, 'setor_id', FILTER_VALIDATE_INT);
    $usuario_id = $_SESSION['user_id'];
    $descricao_alteracao = htmlspecialchars(trim($_POST['descricao_alteracao'] ?? 'Emissão inicial'));
    $responsavel = htmlspecialchars($_SESSION['username'] ?? 'Sistema');

    // Busca o nome do setor para a coluna 'department' (para compatibilidade)
    $departamento = null;
    if ($setor_id) {
        $stmt_setor = $conn->prepare("SELECT nome FROM setores WHERE id = ?");
        $stmt_setor->bind_param("i", $setor_id);
        $stmt_setor->execute();
        $departamento = $stmt_setor->get_result()->fetch_assoc()['nome'] ?? null;
        $stmt_setor->close();
    }

    // Conteúdo do procedimento
    // Limpa o HTML vindo do TinyMCE para maior segurança.
    $objetivo = $purifier->purify($_POST['objetivo'] ?? '');
    $aplicacao = $purifier->purify($_POST['aplicacao'] ?? '');
    $referencias = $purifier->purify($_POST['referencias'] ?? 'Não aplicável.');
    $definicoes = $purifier->purify($_POST['definicoes'] ?? '');
    $responsabilidades = $purifier->purify($_POST['responsabilidades'] ?? '');
    $descricao_procedimento = $purifier->purify($_POST['descricao_procedimento'] ?? '');
    $registros = $purifier->purify($_POST['registros'] ?? '');
    $anexos = $purifier->purify($_POST['anexos'] ?? '');

    // Escapa o título para uso seguro no HTML
    $titulo = htmlspecialchars($titulo_raw);

    // --- Lógica para embutir a imagem do logo ---
    // O caminho foi confirmado como 'img/logo.png'
    $logoPath = __DIR__ . '/img/logo.png';
    $logoSrc = '';
    if (file_exists($logoPath)) {
        $logoData = base64_encode(file_get_contents($logoPath));
        $logoSrc = 'data:image/png;base64,' . $logoData;
    }

    // 2. Define o conteúdo HTML do procedimento com os dados do formulário
    // Usei o seu template como base
    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{$titulo}</title>
    <style>
        @page {
            margin: 4cm 2cm 3cm 2cm;
        }
        body {
            font-family: "Helvetica", sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header, .footer {
            position: fixed;
            left: 0;
            right: 0;
            color: #888;
            text-align: center;
            font-size: 10px;
        }
        .header {
            top: -3.5cm;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            text-align: left;
        }
        .footer {
            bottom: -2.5cm;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            /* Usando flex para distribuir o conteúdo */
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
        .footer .page-number:after {
            content: counter(page);
        }
        .logo {
            width: 120px; /* Ajustado para um tamanho bom */
            margin-bottom: 15px;
        }
        h1, h2, h3 {
            color: #254c90;
            font-family: "Helvetica", sans-serif;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        h1 { font-size: 22px; text-align: left; border-bottom: none; }
        h2 { font-size: 18px; }
        h3 { font-size: 14px; border-bottom: none; }
        p, ul, ol {
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 25px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .summary h3 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .summary ul {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .summary ul li {
            margin-bottom: 5px;
        }
        .summary a {
            text-decoration: none;
            color: #254c90;
        }
        .watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            z-index: -1000;
            font-size: 100px;
            color: #e0e0e0;
            opacity: 0.5;
            font-weight: bold;
            pointer-events: none;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{$logoSrc}" alt="Logo" class="logo">
    </div>

    <div class="watermark">CONFIDENCIAL</div>

    <div class="footer">
        <span>{$titulo} | v{$versao}</span>
        <span>Comercial Souza &copy; 2025</span>
        <span class="page-number">Página </span>
    </div>

    <main>
        <h1>{$titulo}</h1>
        <p style="text-align: left; font-size: 11px; margin-top: -10px; margin-bottom: 25px;">
            <strong>Código:</strong> {$codigo} | <strong>Versão:</strong> {$versao} | <strong>Emissão:</strong> {$data_emissao}
        </p>

        <div class="summary">
            <h3>Sumário</h3>
            <ul>
                <li><a href="#objetivo">1. Objetivo</a></li>
                <li><a href="#aplicacao">2. Campo de Aplicação</a></li>
                <li><a href="#referencias">3. Referências</a></li>
                <li><a href="#definicoes">4. Definições</a></li>
                <li><a href="#responsabilidades">5. Responsabilidades</a></li>
                <li><a href="#procedimento">6. Descrição do Procedimento</a></li>
                <li><a href="#registros">7. Registros</a></li>
                <li><a href="#anexos">8. Anexos</a></li>
                <li><a href="#revisoes">9. Controle de Revisões</a></li>
            </ul>
        </div>

        <div style="page-break-after: always;"></div>

        <h2 id="objetivo">1. Objetivo</h2>
        <p>{$objetivo}</p>

        <h2 id="aplicacao">2. Campo de Aplicação</h2>
        <p>{$aplicacao}</p>

        <h2 id="referencias">3. Referências</h2>
        <p>{$referencias}</p>

        <h2 id="definicoes">4. Definições</h2>
        <div>{$definicoes}</div>

        <h2 id="responsabilidades">5. Responsabilidades</h2>
        <div>{$responsabilidades}</div>

        <h2 id="procedimento">6. Descrição do Procedimento</h2>
        <div>{$descricao_procedimento}</div>

        <h2 id="registros">7. Registros</h2>
        <p>{$registros}</p>

        <h2 id="anexos">8. Anexos</h2>
        <div>{$anexos}</div>

        <h2 id="revisoes">9. Controle de Revisões</h2>
        <table>
            <thead>
                <tr>
                    <th>Versão/Revisão</th>
                    <th>Data da Revisão</th>
                    <th>Descrição da Alteração</th>
                    <th>Responsável pela Alteração</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{$versao}</td>
                    <td>{$data_emissao}</td>
                    <td>{$descricao_alteracao}</td>
                    <td>{$responsavel}</td>
                </tr>
            </tbody>
        </table>
    </main>
</body>
</html>
HTML;

    // 3. Instancia e usa o Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    // Habilita o carregamento de imagens remotas/base64
    $options->set('isRemoteEnabled', true);
    // Define o diretório raiz do projeto. Essencial para o Dompdf encontrar as imagens da pasta /uploads.
    $options->set('chroot', __DIR__);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 4. Salva o PDF no servidor
    $nome_arquivo_pdf = 'procedimento_' . uniqid() . '.pdf';
    $caminho_salvar = __DIR__ . '/uploads/' . $nome_arquivo_pdf;
    
    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0777, true);
    }

    file_put_contents($caminho_salvar, $dompdf->output());

    // 5. Insere o registro do novo arquivo no banco de dados
    $stmt = $conn->prepare(
        "INSERT INTO arquivos (titulo, descricao, tipo, nome_arquivo, departamento, usuario_id, setor_id) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $tipo_arquivo = 'pdf';
    // Usamos o objetivo como descrição para o card
    $descricao_card = strip_tags($objetivo); 

    $stmt->bind_param("sssssii", $titulo_raw, $descricao_card, $tipo_arquivo, $nome_arquivo_pdf, $departamento, $usuario_id, $setor_id);
    
    if ($stmt->execute()) {
        $status = "success";
        $msg = "Procedimento criado e salvo com sucesso!";
        $new_procedure_id = $stmt->insert_id;
        logActivity($usuario_id, 'Procedimento Criado', "Título: {$titulo_raw} (ID: {$new_procedure_id})");
    } else {
        $status = "error";
        $msg = "Erro ao salvar o registro do procedimento no banco de dados.";
        logActivity($usuario_id, 'Erro ao criar procedimento', "Tentativa para título: {$titulo_raw}", 'error');
    }
    $stmt->close();

    // Redireciona de volta para a seção de documentos com a mensagem
    header("Location: index.php?section=documents&status=$status&msg=" . urlencode($msg));
    exit();
}
?>