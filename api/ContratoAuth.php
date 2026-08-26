<?php
declare(strict_types=1);

final class ContratoAuth
{
    private PDO $pdo;
    private int $usuarioId;
    private bool $admin;
    private bool $diretoria = false;

    /**
     * Setores organizacionais herdados dos grupos da intranet.
     *
     * Exemplo:
     * Grupo "RH" -> grupos_intranet.setor_vinculado = "RECURSOS HUMANOS"
     */
    private array $setoresUsuario = [];

    private array $permissoes = [];

    public function __construct(PDO $pdo, int $usuarioId, bool $admin)
    {
        $this->pdo = $pdo;
        $this->usuarioId = $usuarioId;
        $this->admin = $admin;

        // A Gestão de Contratos passa a usar a própria intranet como fonte
        // de setor. Não depende mais da localização do usuário no GLPI.
        $this->carregarContextoDiretoria();
        $this->carregarSetoresUsuario();
        $this->carregarPermissoes();
    }

    private function normalizarSetor(string $setor): string
    {
        $setor = trim($setor);
        $setor = preg_replace('/\s+/u', ' ', $setor) ?? $setor;
        return mb_strtoupper($setor, 'UTF-8');
    }


    /**
     * Diretoria é uma regra especial de governança:
     * - enxerga TODOS os contratos;
     * - pode alterar/gerenciar somente contratos dos quais é o gestor_id;
     * - nos demais contratos possui acesso somente de leitura.
     */
    private function carregarContextoDiretoria(): void
    {
        if ($this->admin || $this->usuarioId <= 0) {
            $this->diretoria = false;
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT 1
              FROM usuarios_grupos ug
              JOIN grupos_intranet g
                ON g.id = ug.grupo_id
             WHERE ug.usuario_id = :usuario_id
               AND UPPER(TRIM(g.nome)) = 'DIRETORIA'
             LIMIT 1
        ");
        $stmt->execute([':usuario_id' => $this->usuarioId]);

        $this->diretoria = (bool) $stmt->fetchColumn();
    }

    /**
     * Descobre os setores do usuário através dos grupos aos quais ele pertence.
     *
     * Somente grupos com setor_vinculado preenchido representam departamento.
     * Grupos de permissão como CONTAS A PAGAR, GESTORES, ADMIN etc. podem
     * permanecer com setor_vinculado = NULL.
     */
    private function carregarSetoresUsuario(): void
    {
        if ($this->admin || $this->usuarioId <= 0) {
            $this->setoresUsuario = [];
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT DISTINCT TRIM(g.setor_vinculado) AS setor
              FROM usuarios_grupos ug
              JOIN grupos_intranet g
                ON g.id = ug.grupo_id
             WHERE ug.usuario_id = :usuario_id
               AND g.setor_vinculado IS NOT NULL
               AND TRIM(g.setor_vinculado) <> ''
             ORDER BY g.setor_vinculado
        ");
        $stmt->execute([':usuario_id' => $this->usuarioId]);

        $setores = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $setor) {
            $normalizado = $this->normalizarSetor((string) $setor);
            if ($normalizado !== '') {
                $setores[] = $normalizado;
            }
        }

        $this->setoresUsuario = array_values(array_unique($setores));
    }

    private function carregarPermissoes(): void
    {
        if ($this->admin) {
            return;
        }

        $sql = "
            SELECT p.codigo,
                   MAX(
                       CASE
                           WHEN up.efeito = 'NEGAR' THEN 0
                           WHEN up.efeito = 'PERMITIR' THEN 1
                           ELSE COALESCE(gp.permitido, 0)
                       END
                   ) AS permitido
              FROM contratos_permissoes p
         LEFT JOIN contratos_usuarios_permissoes up
                ON up.permissao_id = p.id
               AND up.usuario_id = :usuario_id_up
         LEFT JOIN (
                    SELECT cgp.permissao_id, MAX(cgp.permitido) AS permitido
                      FROM contratos_grupos_permissoes cgp
                      JOIN usuarios_grupos ug ON ug.grupo_id = cgp.grupo_id
                     WHERE ug.usuario_id = :usuario_id_gp
                  GROUP BY cgp.permissao_id
                   ) gp ON gp.permissao_id = p.id
          GROUP BY p.id, p.codigo, up.efeito
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id_up' => $this->usuarioId,
            ':usuario_id_gp' => $this->usuarioId,
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $this->permissoes[$linha['codigo']] = (bool) $linha['permitido'];
        }
    }

    public function usuarioId(): int
    {
        return $this->usuarioId;
    }

    public function versao(): string
    {
        return 'GRUPO_SETOR_DIRETORIA_V3';
    }

    public function isDiretoria(): bool
    {
        return $this->diretoria;
    }

    /**
     * Mantido por compatibilidade com qualquer ponto antigo que espere
     * somente um setor. Retorna o primeiro setor vinculado.
     */
    public function setorUsuario(): string
    {
        return $this->setoresUsuario[0] ?? '';
    }

    /**
     * Nova forma preferencial: permite inclusive mais de um setor vinculado
     * caso o usuário participe de dois grupos departamentais.
     */
    public function setoresUsuario(): array
    {
        return $this->setoresUsuario;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function pode(string $codigo): bool
    {
        return $this->admin || ($this->permissoes[$codigo] ?? false);
    }

    public function exigir(string $codigo): void
    {
        if (!$this->pode($codigo)) {
            throw new RuntimeException('Sem permissão para esta ação.', 403);
        }
    }


    /**
     * Confirma se o usuário logado é o dono/gestor direto do contrato.
     * Admin é tratado separadamente nas regras de autorização.
     */
    public function isDonoContrato(int $contratoId): bool
    {
        if ($contratoId <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            SELECT 1
              FROM contratos
             WHERE id = :contrato_id
               AND gestor_id = :usuario_id
             LIMIT 1
        ");
        $stmt->execute([
            ':contrato_id' => $contratoId,
            ':usuario_id' => $this->usuarioId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Permissão contextual por contrato.
     *
     * DIRETORIA:
     * - leitura de qualquer contrato;
     * - ações de gestão somente quando gestor_id = usuário logado.
     *
     * DEMAIS USUÁRIOS:
     * - mantém a regra de permissão RBAC + acesso ao contrato.
     *
     * Permissões de leitura abaixo não alteram dados e são liberadas para a
     * Diretoria em qualquer contrato para que a visualização seja completa.
     */
    public function podeNoContrato(string $codigo, int $contratoId): bool
    {
        if ($this->admin) {
            return true;
        }

        if (!$this->podeAcessarContrato($contratoId)) {
            return false;
        }

        if ($this->diretoria) {
            $permissoesLeitura = [
                'visualizar',
                'ver_financeiro',
                'ver_restritos',
                'baixar_anexo',
            ];

            if (in_array($codigo, $permissoesLeitura, true)) {
                return true;
            }

            // Qualquer ação que altere estado/dados exige ser dono do contrato.
            return $this->isDonoContrato($contratoId);
        }

        return $this->pode($codigo);
    }

    public function exigirNoContrato(string $codigo, int $contratoId): void
    {
        if (!$this->podeNoContrato($codigo, $contratoId)) {
            if ($this->diretoria && !$this->isDonoContrato($contratoId)) {
                throw new RuntimeException(
                    'A Diretoria possui somente visualização neste contrato. A gestão é exclusiva do dono responsável.',
                    403
                );
            }

            throw new RuntimeException('Sem permissão para esta ação neste contrato.', 403);
        }
    }

    /**
     * Monta a cláusula para os setores herdados dos grupos.
     *
     * Retorno:
     * [
     *   'sql' => " OR UPPER(TRIM(...)) IN (...)",
     *   'params' => [':setor_0' => 'RECURSOS HUMANOS', ...]
     * ]
     */
    private function montarClausulaSetores(string $prefixo): array
    {
        if (empty($this->setoresUsuario)) {
            return ['sql' => '', 'params' => []];
        }

        $placeholders = [];
        $params = [];

        foreach ($this->setoresUsuario as $i => $setor) {
            $nome = ':' . $prefixo . $i;
            $placeholders[] = $nome;
            $params[$nome] = $setor;
        }

        return [
            'sql' => "
                OR UPPER(TRIM(COALESCE(c.setor, ''))) IN (" . implode(', ', $placeholders) . ")
            ",
            'params' => $params,
        ];
    }

    /**
     * Regra de VISUALIZAÇÃO de um contrato:
     *
     * 1) Administrador vê tudo;
     * 2) Dono/gestor sempre vê o próprio contrato;
     * 3) Usuário vê contratos dos setores vinculados aos seus grupos;
     * 4) Acesso individual continua válido;
     * 5) Acesso por grupo continua válido (ex.: Contas a Pagar).
     *
     * Ações continuam dependendo das permissões específicas do módulo.
     */
    public function podeAcessarContrato(int $contratoId): bool
    {
        if ($this->admin || $this->diretoria) {
            return true;
        }

        $setores = $this->montarClausulaSetores('usuario_setor_');

        $params = [
            ':contrato_id' => $contratoId,
            ':usuario_gestor' => $this->usuarioId,
            ':usuario_direto' => $this->usuarioId,
            ':usuario_grupo' => $this->usuarioId,
        ] + $setores['params'];

        $stmt = $this->pdo->prepare("
            SELECT 1
              FROM contratos c
             WHERE c.id = :contrato_id
               AND (
                    c.gestor_id = :usuario_gestor
                    {$setores['sql']}
                    OR EXISTS (
                        SELECT 1
                          FROM contratos_acessos_usuarios cau
                         WHERE cau.contrato_id = c.id
                           AND cau.usuario_id = :usuario_direto
                    )
                    OR EXISTS (
                        SELECT 1
                          FROM contratos_acessos_grupos cag
                          JOIN usuarios_grupos ug
                            ON ug.grupo_id = cag.grupo_id
                         WHERE cag.contrato_id = c.id
                           AND ug.usuario_id = :usuario_grupo
                    )
               )
             LIMIT 1
        ");

        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function exigirAcessoContrato(int $contratoId): void
    {
        $this->exigir('visualizar');

        if (!$this->podeAcessarContrato($contratoId)) {
            throw new RuntimeException(
                'Contrato não encontrado ou sem acesso para seu usuário/grupo/setor.',
                403
            );
        }
    }

    /**
     * Filtro usado na listagem principal.
     *
     * VISUALIZAÇÃO =
     * dono
     * OU setor herdado do grupo
     * OU acesso individual
     * OU acesso por grupo.
     *
     * Admin continua recebendo 1=1.
     */
    public function filtroContratosSql(): array
    {
        // Admin e Diretoria enxergam a listagem completa.
        // A diferença é que a Diretoria só pode GERENCIAR os contratos próprios.
        if ($this->admin || $this->diretoria) {
            return ['1=1', []];
        }

        $setores = $this->montarClausulaSetores('filtro_setor_');

        $params = [
            ':filtro_gestor' => $this->usuarioId,
            ':filtro_direto' => $this->usuarioId,
            ':filtro_grupo' => $this->usuarioId,
        ] + $setores['params'];

        return [
            "(c.gestor_id = :filtro_gestor
              {$setores['sql']}
              OR EXISTS (
                   SELECT 1
                     FROM contratos_acessos_usuarios cau
                    WHERE cau.contrato_id = c.id
                      AND cau.usuario_id = :filtro_direto
              )
              OR EXISTS (
                   SELECT 1
                     FROM contratos_acessos_grupos cag
                     JOIN usuarios_grupos ug
                       ON ug.grupo_id = cag.grupo_id
                    WHERE cag.contrato_id = c.id
                      AND ug.usuario_id = :filtro_grupo
              ))",
            $params,
        ];
    }
}

function contratoCsrfToken(): string
{
    if (empty($_SESSION['contratos_csrf'])) {
        $_SESSION['contratos_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['contratos_csrf'];
}

function contratoValidarCsrf(): void
{
    $recebido = (string) ($_POST['csrf_token'] ?? '');

    if ($recebido === '' || !hash_equals(contratoCsrfToken(), $recebido)) {
        throw new RuntimeException(
            'Sessão expirada. Atualize a página e tente novamente.',
            419
        );
    }
}
