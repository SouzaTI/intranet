<?php
declare(strict_types=1);

final class EmailAssinaturaService
{
    public function enviarNovoPendente(PDO $pdoIntra, PDO $pdoGlpi, int $envelopeId, int $usuarioId): void
    {
        if (!$this->notificacaoAtiva($pdoIntra, 'notificar_novo_pendente')) return;
        $envelope = $this->buscarEnvelope($pdoIntra, $envelopeId);
        $stmt = $pdoIntra->prepare("SELECT ordem FROM assinaturas_fluxo WHERE fk_assinatura = ? AND glpi_user_id = ? AND status = 'pendente'");
        $stmt->execute([$envelopeId, $usuarioId]);
        $ordem = $stmt->fetchColumn();
        if ($ordem === false) throw new RuntimeException('A etapa ainda não está pendente para este usuário.');

        $destinatario = $this->buscarUsuario($pdoGlpi, $usuarioId);
        $criador = $this->buscarUsuario($pdoGlpi, (int)$envelope['criado_por']);
        if (!$destinatario['email']) throw new RuntimeException('Usuário sem e-mail válido no GLPI: ' . $destinatario['nome']);

        $stmtTotal = $pdoIntra->prepare('SELECT COUNT(*) FROM assinaturas_fluxo WHERE fk_assinatura = ?');
        $stmtTotal->execute([$envelopeId]);
        $totalEtapas = (int)$stmtTotal->fetchColumn();
        $stmtDocs = $pdoIntra->prepare('SELECT COUNT(*) FROM assinatura_documentos WHERE envelope_id = ?');
        $stmtDocs->execute([$envelopeId]);

        $mail = $this->novoMailer();
        $mail->addAddress($destinatario['email'], $destinatario['nome']);
        $bannerCid = $this->adicionarBanner($mail, 'novo_documento');
        $mail->Subject = '[Ação necessária] Novo documento aguardando sua assinatura';
        $mail->Body = $this->renderizarTemplate('novo_documento', [
            'nome_destinatario' => $destinatario['nome'], 'titulo' => $envelope['titulo'],
            'criador' => $criador['nome'], 'tipo_fluxo' => ucfirst($envelope['tipo_fluxo']),
            'etapa' => (int)$ordem, 'total_etapas' => $totalEtapas,
            'quantidade_documentos' => (int)$stmtDocs->fetchColumn(),
            'url_acao' => $this->urlIntranet('minhas_assinaturas.php'), 'banner_cid' => $bannerCid,
        ]);
        $mail->AltBody = "Novo documento aguardando sua assinatura: {$envelope['titulo']}. Acesse a Intranet Souza.";
        $mail->send();
        $this->registrarEmail($pdoIntra, $envelopeId, $usuarioId, 'NOVO_PENDENTE', $destinatario['email']);
    }

    public function enviarDocumentoAssinado(PDO $pdoIntra, PDO $pdoGlpi, int $envelopeId, int $assinanteId): void
    {
        if (!$this->notificacaoAtiva($pdoIntra, 'notificar_documento_assinado')) return;
        $envelope = $this->buscarEnvelope($pdoIntra, $envelopeId);
        $assinante = $this->buscarUsuario($pdoGlpi, $assinanteId);
        $criador = $this->buscarUsuario($pdoGlpi, (int)$envelope['criado_por']);
        $stmt = $pdoIntra->prepare('SELECT assinado_em FROM assinaturas_fluxo WHERE fk_assinatura = ? AND glpi_user_id = ? AND status = ?');
        $stmt->execute([$envelopeId, $assinanteId, 'assinado']);
        $assinadoEm = $stmt->fetchColumn();
        if ($assinadoEm === false) throw new RuntimeException('Assinatura ainda não registrada.');
        $stmtProgresso = $pdoIntra->prepare("SELECT COUNT(*), SUM(status = 'assinado') FROM assinaturas_fluxo WHERE fk_assinatura = ?");
        $stmtProgresso->execute([$envelopeId]);
        [$total, $assinados] = array_map('intval', $stmtProgresso->fetch(PDO::FETCH_NUM));

        $destinatarios = [];
        foreach ([$assinante, $criador] as $pessoa) if ($pessoa['email']) $destinatarios[strtolower($pessoa['email'])] = $pessoa;
        if (!$destinatarios) throw new RuntimeException('Assinante e criador estão sem e-mail válido no GLPI.');
        $mail = $this->novoMailer();
        foreach ($destinatarios as $pessoa) $mail->addAddress($pessoa['email'], $pessoa['nome']);
        $bannerCid = $this->adicionarBanner($mail, 'documento_assinado');
        $mail->Subject = '[Atualização] Documento assinado — ' . $envelope['titulo'];
        $mail->Body = $this->renderizarTemplate('documento_assinado', [
            'titulo' => $envelope['titulo'], 'assinante' => $assinante['nome'],
            'setor' => $assinante['setor'], 'assinado_em' => date('d/m/Y H:i', strtotime((string)$assinadoEm)),
            'progresso' => "{$assinados} de {$total} assinaturas",
            'url_acao' => $this->urlIntranet('detalhe_envelope.php?id=' . $envelopeId), 'banner_cid' => $bannerCid,
        ]);
        $mail->AltBody = "Assinatura registrada por {$assinante['nome']} no envelope {$envelope['titulo']}. Progresso: {$assinados} de {$total}.";
        $mail->send();
        $this->registrarEmail($pdoIntra, $envelopeId, $assinanteId, 'DOCUMENTO_ASSINADO', implode(', ', array_keys($destinatarios)));
    }

    public function enviarLembretePendente(PDO $pdoIntra, PDO $pdoGlpi, int $envelopeId, int $usuarioId): void
    {
        if (!$this->notificacaoAtiva($pdoIntra, 'notificar_lembrete_pendente')) return;
        $envelope = $this->buscarEnvelope($pdoIntra, $envelopeId);
        $stmt = $pdoIntra->prepare("SELECT atualizado_em FROM assinaturas_fluxo WHERE fk_assinatura = ? AND glpi_user_id = ? AND status = 'pendente'");
        $stmt->execute([$envelopeId, $usuarioId]);
        $desde = $stmt->fetchColumn();
        if ($desde === false) throw new RuntimeException('A assinatura não está mais pendente.');
        $destinatario = $this->buscarUsuario($pdoGlpi, $usuarioId);
        if (!$destinatario['email']) throw new RuntimeException('Usuário sem e-mail válido no GLPI: ' . $destinatario['nome']);
        $stmtDocs = $pdoIntra->prepare('SELECT COUNT(*) FROM assinatura_documentos WHERE envelope_id = ?');
        $stmtDocs->execute([$envelopeId]);
        $horas = max(1, (int)floor((time() - strtotime((string)$desde)) / 3600));
        $mail = $this->novoMailer();
        $mail->addAddress($destinatario['email'], $destinatario['nome']);
        $bannerCid = $this->adicionarBanner($mail, 'documento_pendente');
        $mail->Subject = '[Lembrete] Documento ainda pendente de assinatura';
        $mail->Body = $this->renderizarTemplate('documento_pendente', [
            'nome_destinatario' => $destinatario['nome'], 'titulo' => $envelope['titulo'],
            'disponivel_desde' => date('d/m/Y H:i', strtotime((string)$desde)),
            'tempo_pendente' => $horas . ' hora' . ($horas === 1 ? '' : 's'),
            'quantidade_documentos' => (int)$stmtDocs->fetchColumn(),
            'url_acao' => $this->urlIntranet('minhas_assinaturas.php'), 'banner_cid' => $bannerCid,
        ]);
        $mail->AltBody = "Lembrete: o envelope {$envelope['titulo']} continua aguardando sua assinatura.";
        $mail->send();
        $this->registrarEmail($pdoIntra, $envelopeId, $usuarioId, 'LEMBRETE_PENDENTE', $destinatario['email']);
    }

    public function enviarEnvelope(PDO $pdoIntra, PDO $pdoGlpi, int $envelopeId, bool $forcarReenvio = false): void
    {
        if (!$forcarReenvio && !$this->notificacaoAtiva($pdoIntra, 'notificar_fluxo_concluido')) return;
        $this->carregarMailer();
        $stmt = $pdoIntra->prepare('SELECT * FROM sistemas_assinaturas WHERE id = ?');
        $stmt->execute([$envelopeId]);
        $envelope = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$envelope || $envelope['status'] !== 'concluido') throw new RuntimeException('Envelope ainda não concluído.');
        if (!empty($envelope['email_enviado_em']) && !$forcarReenvio) return;

        // E-mails informados manualmente são adicionais. O criador e todas as
        // pessoas do fluxo também recebem o resultado final pelo e-mail do GLPI.
        $destinatariosMap = [];
        foreach ($this->normalizarEmails((string) ($envelope['emails_finalizacao'] ?? '')) as $email) {
            $destinatariosMap[strtolower($email)] = $email;
        }

        $stmtUsuarios = $pdoIntra->prepare('SELECT glpi_user_id FROM assinaturas_fluxo WHERE fk_assinatura = ?');
        $stmtUsuarios->execute([$envelopeId]);
        $usuariosIds = array_map('intval', $stmtUsuarios->fetchAll(PDO::FETCH_COLUMN));
        $usuariosIds[] = (int) $envelope['criado_por'];
        $usuariosIds = array_values(array_unique(array_filter($usuariosIds)));

        if ($usuariosIds) {
            $marcadores = implode(',', array_fill(0, count($usuariosIds), '?'));
            $stmtEmails = $pdoGlpi->prepare("SELECT users_id, email FROM glpi_useremails
                WHERE users_id IN ({$marcadores}) AND email IS NOT NULL AND email <> ''
                ORDER BY is_default DESC, id ASC");
            $stmtEmails->execute($usuariosIds);
            foreach ($stmtEmails->fetchAll(PDO::FETCH_ASSOC) as $registro) {
                $email = trim((string) $registro['email']);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $destinatariosMap[strtolower($email)] = $email;
                }
            }
        }

        $destinatarios = array_values($destinatariosMap);
        if (!$destinatarios) throw new RuntimeException('Nenhum destinatário válido. Verifique os e-mails dos participantes no GLPI.');

        $stmtDocs = $pdoIntra->prepare('SELECT nome_original, arquivo_atual_path FROM assinatura_documentos WHERE envelope_id = ? ORDER BY id');
        $stmtDocs->execute([$envelopeId]);
        $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
        if (!$documentos) throw new RuntimeException('Nenhum documento final localizado.');

        $stmtFluxo = $pdoIntra->prepare('SELECT glpi_user_id, ordem, assinado_em FROM assinaturas_fluxo WHERE fk_assinatura = ? AND status = ? ORDER BY ordem, id');
        $stmtFluxo->execute([$envelopeId, 'assinado']);
        $fluxoAssinado = $stmtFluxo->fetchAll(PDO::FETCH_ASSOC);
        $nomesUsuarios = [];
        if ($fluxoAssinado) {
            $idsFluxo = array_values(array_unique(array_map('intval', array_column($fluxoAssinado, 'glpi_user_id'))));
            $marcadoresFluxo = implode(',', array_fill(0, count($idsFluxo), '?'));
            $stmtNomes = $pdoGlpi->prepare("SELECT id, TRIM(CONCAT(COALESCE(firstname,''), ' ', COALESCE(realname,''))) AS nome FROM glpi_users WHERE id IN ({$marcadoresFluxo})");
            $stmtNomes->execute($idsFluxo);
            foreach ($stmtNomes->fetchAll(PDO::FETCH_ASSOC) as $usuario) {
                $nomesUsuarios[(int)$usuario['id']] = trim((string)$usuario['nome']) ?: ('Usuário #' . $usuario['id']);
            }
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $this->env('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = $this->env('SMTP_USER');
        $mail->Password = $this->env('SMTP_PASS');
        $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 465);
        $mail->Timeout = 30;
        $mail->SMTPSecure = ($_ENV['SMTP_SECURE'] ?? 'ssl') === 'tls'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->setFrom($_ENV['SMTP_FROM'] ?? $mail->Username, $_ENV['SMTP_FROM_NAME'] ?? 'Intranet Souza');
        foreach ($destinatarios as $email) $mail->addAddress($email);

        foreach ($documentos as $i => $doc) {
            $absoluto = dirname(__DIR__) . '/' . ltrim($doc['arquivo_atual_path'], '/\\');
            if (!is_file($absoluto)) throw new RuntimeException('Arquivo final não encontrado: ' . $doc['nome_original']);
            $nome = preg_replace('/[^A-Za-z0-9._-]/u', '_', $doc['nome_original']);
            $mail->addAttachment($absoluto, 'ASSINADO_' . ($i + 1) . '_' . $nome);
        }

        $mail->isHTML(true);
        $mail->Subject = '[Concluído] ' . $envelope['titulo'] . ' — Portal de Assinaturas';
        $criadoEm = date('d/m/Y H:i', strtotime($envelope['criado_em']));
        $concluidoEm = !empty($envelope['concluido_em']) ? date('d/m/Y H:i', strtotime($envelope['concluido_em'])) : date('d/m/Y H:i');
        $tipoFluxo = ucfirst((string)$envelope['tipo_fluxo']);
        $qtdDocumentos = count($documentos);
        $documentosTemplate = array_column($documentos, 'nome_original');
        $assinantesTemplate = [];
        foreach ($fluxoAssinado as $item) {
            $assinantesTemplate[] = [
                'nome' => $nomesUsuarios[(int)$item['glpi_user_id']] ?? ('Usuário #' . $item['glpi_user_id']),
                'momento' => !empty($item['assinado_em']) ? date('d/m/Y H:i', strtotime($item['assinado_em'])) : 'Data não informada',
            ];
        }

        $bannerCid = null;
        $banner = dirname(__DIR__) . '/assets/emails/fluxo_concluido.png';
        if (is_file($banner)) {
            $bannerCid = 'banner_fluxo_concluido';
            $mail->addEmbeddedImage($banner, $bannerCid, 'fluxo_concluido.png', 'base64', 'image/png');
        }

        $mail->Body = $this->renderizarTemplate('fluxo_concluido', [
            'titulo' => $envelope['titulo'],
            'tipo_fluxo' => $tipoFluxo,
            'criado_em' => $criadoEm,
            'concluido_em' => $concluidoEm,
            'documentos' => $documentosTemplate,
            'assinantes' => $assinantesTemplate,
            'banner_cid' => $bannerCid,
        ]);
        $mail->AltBody = "Fluxo concluído: {$envelope['titulo']}. Documentos: {$qtdDocumentos}. Criado em {$criadoEm}; concluído em {$concluidoEm}. Os documentos assinados seguem anexados.";
        $mail->send();

        $pdoIntra->prepare('UPDATE sistemas_assinaturas SET email_enviado_em = NOW(), erro_email = NULL WHERE id = ?')->execute([$envelopeId]);
        $pdoIntra->prepare("INSERT INTO assinatura_eventos
            (envelope_id, glpi_user_id, evento, descricao, ip_origem)
            VALUES (?, ?, 'EMAIL_ENVIADO', ?, ?)")
            ->execute([$envelopeId, (int) $envelope['criado_por'], ($forcarReenvio ? 'Documento final reenviado para ' : 'Documento final enviado para ') . implode(', ', $destinatarios), $_SERVER['REMOTE_ADDR'] ?? null]);
    }

    private function carregarMailer(): void
    {
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) return;
        foreach ([dirname(__DIR__) . '/vendor/autoload.php', dirname(__DIR__) . '/validador_documentos/vendor/autoload.php'] as $autoload) {
            if (is_file($autoload)) { require_once $autoload; break; }
        }
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) throw new RuntimeException('PHPMailer não instalado na intranet.');
    }

    public function obterConfiguracoes(PDO $pdoIntra): array
    {
        $padrao = [
            'notificar_novo_pendente' => 1,
            'notificar_documento_assinado' => 1,
            'notificar_lembrete_pendente' => 1,
            'notificar_fluxo_concluido' => 1,
            'horas_para_lembrete' => 24,
        ];
        try {
            $registro = $pdoIntra->query('SELECT * FROM assinatura_configuracoes WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
            return $registro ? array_merge($padrao, $registro) : $padrao;
        } catch (PDOException $e) {
            error_log('Assinaturas/configuracoes: ' . $e->getMessage());
            return $padrao;
        }
    }

    private function notificacaoAtiva(PDO $pdoIntra, string $campo): bool
    {
        $permitidos = ['notificar_novo_pendente', 'notificar_documento_assinado', 'notificar_lembrete_pendente', 'notificar_fluxo_concluido'];
        if (!in_array($campo, $permitidos, true)) throw new InvalidArgumentException('Tipo de notificação inválido.');
        $configuracoes = $this->obterConfiguracoes($pdoIntra);
        return (int)($configuracoes[$campo] ?? 1) === 1;
    }

    private function novoMailer(): \PHPMailer\PHPMailer\PHPMailer
    {
        $this->carregarMailer();
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8'; $mail->isHTML(true); $mail->isSMTP();
        $mail->Host = $this->env('SMTP_HOST'); $mail->SMTPAuth = true;
        $mail->Username = $this->env('SMTP_USER'); $mail->Password = $this->env('SMTP_PASS');
        $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 465); $mail->Timeout = 30;
        $mail->SMTPSecure = strtolower((string)($_ENV['SMTP_SECURE'] ?? 'ssl')) === 'tls'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->setFrom($_ENV['SMTP_FROM'] ?? $mail->Username, $_ENV['SMTP_FROM_NAME'] ?? 'Intranet Souza');
        return $mail;
    }

    private function buscarEnvelope(PDO $pdoIntra, int $envelopeId): array
    {
        $stmt = $pdoIntra->prepare('SELECT * FROM sistemas_assinaturas WHERE id = ?');
        $stmt->execute([$envelopeId]); $envelope = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$envelope) throw new RuntimeException('Envelope não encontrado.');
        return $envelope;
    }

    private function buscarUsuario(PDO $pdoGlpi, int $usuarioId): array
    {
        $stmt = $pdoGlpi->prepare("SELECT TRIM(CONCAT(COALESCE(u.firstname,''), ' ', COALESCE(u.realname,''))) nome,
            COALESCE(NULLIF(TRIM(l.name), ''), 'Não informado') setor,
            (SELECT ue.email FROM glpi_useremails ue WHERE ue.users_id=u.id AND ue.email IS NOT NULL AND ue.email<>'' ORDER BY ue.is_default DESC, ue.id LIMIT 1) email
            FROM glpi_users u LEFT JOIN glpi_locations l ON l.id=u.locations_id WHERE u.id=?");
        $stmt->execute([$usuarioId]); $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) throw new RuntimeException('Usuário do GLPI não encontrado: ' . $usuarioId);
        $usuario['nome'] = trim((string)$usuario['nome']) ?: ('Usuário #' . $usuarioId);
        $usuario['email'] = filter_var($usuario['email'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$usuario['email'] : null;
        return $usuario;
    }

    private function adicionarBanner(\PHPMailer\PHPMailer\PHPMailer $mail, string $nome): ?string
    {
        $arquivo = dirname(__DIR__) . '/assets/emails/' . $nome . '.png';
        if (!is_file($arquivo)) return null;
        $cid = 'banner_' . $nome;
        $mail->addEmbeddedImage($arquivo, $cid, $nome . '.png', 'base64', 'image/png');
        return $cid;
    }

    private function registrarEmail(PDO $pdoIntra, int $envelopeId, int $usuarioId, string $codigo, string $destino): void
    {
        $pdoIntra->prepare("INSERT INTO assinatura_eventos (envelope_id, glpi_user_id, evento, descricao, ip_origem)
            VALUES (?, ?, 'EMAIL_ENVIADO', ?, ?)")->execute([$envelopeId, $usuarioId, $codigo . ': ' . $destino, $_SERVER['REMOTE_ADDR'] ?? null]);
    }

    private function urlIntranet(string $caminho): string
    {
        $base = defined('BASE_URL') ? (string)BASE_URL : '';
        return rtrim($base, '/') . '/' . ltrim($caminho, '/');
    }

    private function renderizarTemplate(string $nome, array $dados): string
    {
        if (!preg_match('/^[a-z_]+$/', $nome)) {
            throw new RuntimeException('Nome de modelo de e-mail inválido.');
        }
        $arquivo = dirname(__DIR__) . '/templates/emails/' . $nome . '.php';
        if (!is_file($arquivo)) {
            throw new RuntimeException('Modelo de e-mail não encontrado: ' . $nome);
        }
        ob_start();
        try {
            require $arquivo;
            return (string) ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    private function normalizarEmails(string $valor): array
    {
        $saida = [];
        foreach (preg_split('/[,;\r\n]+/', $valor, -1, PREG_SPLIT_NO_EMPTY) as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) $saida[strtolower($email)] = $email;
        }
        return array_values($saida);
    }

    private function env(string $nome): string
    {
        $valor = (string) ($_ENV[$nome] ?? getenv($nome) ?: '');
        if ($valor === '') throw new RuntimeException("Configuração {$nome} ausente.");
        return $valor;
    }
}
