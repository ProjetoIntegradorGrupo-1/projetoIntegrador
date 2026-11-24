<?php
/**
 * backend/api.php
 * Endpoints JSON e processadores de Busca, Edição, Exclusão e Vistoria para o Sistema Axion.
 * Toda ação exige login; ações de gestão exigem perfil admin/supervisor (ver backend/auth.php).
 */
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/auth.php';

$acao = $_REQUEST['acao'] ?? '';
$ehPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

const AXION_STATUS_VEICULO = ['ativo', 'em_rota', 'manutencao'];
const AXION_CATEGORIAS_CHECKLIST = ['eletrico', 'mecanico', 'estetico', 'seguranca', 'pre_viagem', 'outros'];

/** Termo de busca só com dígitos (para achar CPF/CNH digitados com ou sem máscara). */
function axion_digitos(string $s): string {
    return preg_replace('/\D/', '', $s);
}

function axion_buscar_usuario(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT id_usuario, nome, perfil, id_supervisor, senha, email, cpf_matricula, status, cnh, validade_cnh FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Situação da vistoria no fluxo de solicitação de assinatura.
 * recusada | concluida | aguardando_vistoriador | aguardando_motorista | aguardando_assinaturas
 */
function axion_situacao(array $v): string {
    if (!empty($v['recusado_em'])) return 'recusada';
    $m = !empty($v['assinado_motorista_em']);
    $s = !empty($v['assinado_vistoriador_em']);
    if ($m && $s) return 'concluida';
    if ($m) return 'aguardando_vistoriador';
    if ($s) return 'aguardando_motorista';
    return 'aguardando_assinaturas';
}

/** Papel do usuário logado na vistoria: motorista, vistoriador ou null. */
function axion_papel(array $v, array $eu): ?string {
    if ((int)$v['id_motorista'] === $eu['id']) return 'motorista';
    if ((int)$v['id_vistoriador'] === $eu['id']) return 'vistoriador';
    return null;
}

// 0. CONTEXTO DA SESSÃO (quem está logado + token dos formulários)
if ($acao === 'contexto') {
    $u = axion_exigir_perfil();
    $extra = ['supervisor' => null];
    if ($u['perfil'] === 'motorista') {
        $st = $pdo->prepare("SELECT s.id_usuario, s.nome FROM Usuarios m JOIN Usuarios s ON s.id_usuario = m.id_supervisor WHERE m.id_usuario = :id");
        $st->execute([':id' => $u['id']]);
        $extra['supervisor'] = $st->fetch() ?: null;
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE recusado_em IS NULL AND
        ((id_motorista = :a AND assinado_motorista_em IS NULL) OR (id_vistoriador = :b AND assinado_vistoriador_em IS NULL))");
    $st->execute([':a' => $u['id'], ':b' => $u['id']]);
    $extra['pendencias'] = (int)$st->fetchColumn();
    axion_json(['sucesso' => true, 'usuario' => $u, 'csrf' => axion_csrf_token()] + $extra);
}

// 1. BUSCA GERAL / FILTRADA (JSON)
if ($acao === 'buscar') {
    $tipo = $_GET['tipo'] ?? 'veiculos';
    $u = axion_exigir_perfil($tipo === 'usuarios' ? ['admin', 'supervisor'] : AXION_PERFIS);
    $termo = trim($_GET['q'] ?? '');
    $digitos = axion_digitos($termo);
    $filtroExtra = trim($_GET['filtro'] ?? '');
    $limite = min(250, max(10, (int)($_GET['limite'] ?? 100)));

    if ($tipo === 'veiculos') {
        $sql = "SELECT id_veiculo, placa, marca, modelo, marca_modelo, ano, cor, km_rodado, renavam, chassi, status
                FROM Veiculos WHERE 1=1";
        $params = [];
        if ($termo !== '') {
            $placaLimpa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $termo));
            $sql .= " AND (placa LIKE :q OR placa LIKE :placa OR marca_modelo LIKE :q OR marca LIKE :q OR modelo LIKE :q OR cor LIKE :q OR renavam LIKE :q OR chassi LIKE :q)";
            $params[':q'] = "%$termo%";
            $params[':placa'] = "%$placaLimpa%";
        }
        if ($filtroExtra !== '') {
            $sql .= " AND status = :status";
            $params[':status'] = $filtroExtra;
        }
        $sql .= " ORDER BY id_veiculo DESC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        axion_json(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
    }

    if ($tipo === 'usuarios') {
        $sql = "SELECT u.id_usuario, u.nome, u.sobrenome, u.email, u.cpf_matricula, u.perfil, u.categoria_cnh, u.cnh, u.validade_cnh,
                       u.cep, u.logradouro, u.numero, u.bairro, u.cidade, u.estado, u.telefone, u.data_nascimento, u.genero, u.status,
                       u.id_supervisor, s.nome AS supervisor_nome
                FROM Usuarios u LEFT JOIN Usuarios s ON s.id_usuario = u.id_supervisor
                WHERE 1=1";
        $params = [];
        if ($termo !== '') {
            $cond = "u.nome LIKE :q OR u.email LIKE :q OR u.cpf_matricula LIKE :q OR u.cidade LIKE :q OR u.telefone LIKE :q OR u.cnh LIKE :q";
            $params[':q'] = "%$termo%";
            // CPF salvo com máscara (000.000.000-00): compara também só os dígitos
            if (strlen($digitos) >= 3) {
                $cond .= " OR REPLACE(REPLACE(u.cpf_matricula, '.', ''), '-', '') LIKE :dig OR u.cnh LIKE :dig";
                $params[':dig'] = "%$digitos%";
            }
            $sql .= " AND ($cond)";
        }
        if ($filtroExtra === 'minha_equipe') {
            $sql .= " AND u.id_supervisor = :me";
            $params[':me'] = $u['id'];
        } elseif ($filtroExtra === 'inativo') {
            $sql .= " AND u.status <> 'ativo'";
        } elseif ($filtroExtra !== '') {
            $sql .= " AND u.perfil = :perfil";
            $params[':perfil'] = $filtroExtra;
        }
        $sql .= " ORDER BY FIELD(u.perfil, 'admin', 'supervisor', 'motorista', 'cliente'), u.nome ASC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        axion_json(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
    }

    if ($tipo === 'checklists') {
        $sql = "SELECT c.id_checklist, c.titulo, c.categoria, c.descricao, c.ativo, c.criado_em,
                       COALESCE(u.nome, 'Sistema Axion') AS criador_nome,
                       (SELECT COUNT(*) FROM Checklist_Perguntas p WHERE p.id_checklist = c.id_checklist) AS total_perguntas,
                       (SELECT COUNT(*) FROM Checklist_Execucoes e WHERE e.id_checklist = c.id_checklist) AS total_execucoes
                FROM Checklists c
                LEFT JOIN Usuarios u ON u.id_usuario = c.id_criador
                WHERE 1=1";
        $params = [];
        if ($termo !== '') {
            $sql .= " AND (c.titulo LIKE :q OR c.categoria LIKE :q OR c.descricao LIKE :q)";
            $params[':q'] = "%$termo%";
        }
        if ($filtroExtra !== '') {
            $sql .= " AND c.categoria = :cat";
            $params[':cat'] = $filtroExtra;
        }
        if (!empty($_GET['somente_ativos'])) {
            $sql .= " AND c.ativo = 1 AND EXISTS (SELECT 1 FROM Checklist_Perguntas p2 WHERE p2.id_checklist = c.id_checklist)";
        }
        $sql .= " ORDER BY c.ativo DESC, c.id_checklist DESC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        axion_json(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
    }

    if ($tipo === 'vistorias') {
        $sql = "SELECT e.id_execucao, e.id_checklist, e.placa_veiculo, e.nome_motorista, e.nome_vistoriador, e.data_vistoria,
                       e.hora_vistoria, e.km_rodado, e.status_conformidade, e.assinado_em, e.id_motorista, e.id_vistoriador,
                       e.assinado_motorista_em, e.assinado_vistoriador_em, e.recusado_em,
                       COALESCE(c.titulo, 'Checklist removido') AS checklist_titulo,
                       COALESCE(c.categoria, '-') AS checklist_categoria
                FROM Checklist_Execucoes e
                LEFT JOIN Checklists c ON c.id_checklist = e.id_checklist
                WHERE 1=1";
        $params = [];
        if ($u['perfil'] === 'motorista') {
            // Motorista só enxerga as próprias vistorias
            $sql .= " AND e.id_motorista = :me";
            $params[':me'] = $u['id'];
        }
        if ($termo !== '') {
            $sql .= " AND (e.placa_veiculo LIKE :q OR e.nome_motorista LIKE :q OR e.nome_vistoriador LIKE :q OR c.titulo LIKE :q OR e.observacoes LIKE :q)";
            $params[':q'] = "%$termo%";
        }
        if ($filtroExtra === 'pendente') {
            $sql .= " AND e.assinado_em IS NULL AND e.recusado_em IS NULL";
        } elseif ($filtroExtra === 'recusada') {
            $sql .= " AND e.recusado_em IS NOT NULL";
        } elseif ($filtroExtra === 'minhas_pendencias') {
            $sql .= " AND e.recusado_em IS NULL AND ((e.id_motorista = :p1 AND e.assinado_motorista_em IS NULL) OR (e.id_vistoriador = :p2 AND e.assinado_vistoriador_em IS NULL))";
            $params[':p1'] = $u['id'];
            $params[':p2'] = $u['id'];
        } elseif ($filtroExtra !== '') {
            $sql .= " AND e.status_conformidade = :st";
            $params[':st'] = $filtroExtra;
        }
        $sql .= " ORDER BY e.data_vistoria DESC, e.id_execucao DESC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $dados = array_map(function ($v) use ($u) {
            $v['situacao'] = axion_situacao($v);
            $papel = axion_papel($v, $u);
            $v['minha_vez'] = $v['situacao'] !== 'recusada' && (
                ($papel === 'motorista' && empty($v['assinado_motorista_em'])) ||
                ($papel === 'vistoriador' && empty($v['assinado_vistoriador_em'])));
            return $v;
        }, $stmt->fetchAll());
        axion_json(['sucesso' => true, 'dados' => $dados]);
    }

    axion_json(['sucesso' => false, 'erro' => 'Tipo de busca inválido'], 400);
}

// 1a. DADOS DO PAINEL (indicadores e gráficos), conforme o perfil de quem está logado
if ($acao === 'dashboard') {
    $u = axion_exigir_perfil();
    $p = $u['perfil'];
    // Quais vistorias entram nos números de cada perfil
    $esc = '1=1';
    $par = [];
    if ($p === 'motorista') {
        $esc = 'e.id_motorista = :me';
        $par[':me'] = $u['id'];
    } elseif ($p === 'supervisor') {
        $esc = '(e.id_vistoriador = :me OR e.id_motorista IN (SELECT id_usuario FROM Usuarios WHERE id_supervisor = :me2))';
        $par[':me'] = $u['id'];
        $par[':me2'] = $u['id'];
    }
    $q = function (string $sql, array $extra = []) use ($pdo, $par) {
        $st = $pdo->prepare($sql);
        $st->execute($par + $extra);
        return $st;
    };

    $tot = $q("SELECT COUNT(*) AS total,
                      SUM(e.status_conformidade = 'conforme') AS conformes,
                      SUM(e.status_conformidade = 'nao_conforme') AS nao_conformes,
                      SUM(e.assinado_em IS NULL AND e.recusado_em IS NULL) AS aguardando,
                      SUM(e.recusado_em IS NOT NULL) AS recusadas,
                      SUM(e.data_vistoria >= CURDATE() - INTERVAL 29 DAY) AS ultimos30,
                      MAX(e.data_vistoria) AS ultima
               FROM Checklist_Execucoes e WHERE $esc")->fetch();

    // Vistorias por dia nos últimos 14 dias (conforme x não conforme)
    $porDia = $q("SELECT e.data_vistoria AS dia, SUM(e.status_conformidade = 'conforme') AS c, SUM(e.status_conformidade = 'nao_conforme') AS n
                  FROM Checklist_Execucoes e WHERE $esc AND e.data_vistoria >= CURDATE() - INTERVAL 13 DAY GROUP BY e.data_vistoria")->fetchAll();
    $mapa = [];
    foreach ($porDia as $d) $mapa[$d['dia']] = $d;
    $serie = [];
    for ($i = 13; $i >= 0; $i--) {
        $dia = date('Y-m-d', strtotime("-$i day"));
        $serie[] = ['dia' => $dia, 'conforme' => (int)($mapa[$dia]['c'] ?? 0), 'nao_conforme' => (int)($mapa[$dia]['n'] ?? 0)];
    }

    $frota = $pdo->query("SELECT status, COUNT(*) AS qtd FROM Veiculos GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

    $dados = [
        'perfil' => $p,
        'vistorias' => array_map('intval', array_slice($tot, 0, 6)) + ['ultima' => $tot['ultima']],
        'por_dia' => $serie,
        'frota' => ['ativo' => (int)($frota['ativo'] ?? 0), 'em_rota' => (int)($frota['em_rota'] ?? 0), 'manutencao' => (int)($frota['manutencao'] ?? 0)],
        'checklists_ativos' => (int)$pdo->query("SELECT COUNT(*) FROM Checklists WHERE ativo = 1")->fetchColumn(),
    ];
    $stPend = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE recusado_em IS NULL AND
        ((id_motorista = :a AND assinado_motorista_em IS NULL) OR (id_vistoriador = :b AND assinado_vistoriador_em IS NULL))");
    $stPend->execute([':a' => $u['id'], ':b' => $u['id']]);
    $dados['minhas_pendencias'] = (int)$stPend->fetchColumn();

    if (in_array($p, ['admin', 'supervisor'], true)) {
        // Vistorias por motorista nos últimos 30 dias
        $dados['por_motorista'] = $q("SELECT e.nome_motorista AS nome, SUM(e.status_conformidade = 'conforme') AS conforme, SUM(e.status_conformidade = 'nao_conforme') AS nao_conforme
            FROM Checklist_Execucoes e WHERE $esc AND e.data_vistoria >= CURDATE() - INTERVAL 29 DAY
            GROUP BY e.id_motorista, e.nome_motorista ORDER BY COUNT(*) DESC LIMIT 8")->fetchAll();
        // Veículos com mais avarias nos últimos 90 dias
        $dados['top_avarias'] = $q("SELECT e.placa_veiculo AS placa, COUNT(*) AS qtd FROM Checklist_Execucoes e
            WHERE $esc AND e.status_conformidade = 'nao_conforme' AND e.data_vistoria >= CURDATE() - INTERVAL 89 DAY
            GROUP BY e.placa_veiculo ORDER BY qtd DESC LIMIT 5")->fetchAll();
    }
    if ($p === 'admin') {
        $dados['usuarios'] = $pdo->query("SELECT perfil, COUNT(*) FROM Usuarios WHERE status = 'ativo' GROUP BY perfil")->fetchAll(PDO::FETCH_KEY_PAIR);
        $dados['usuarios_inativos'] = (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE status <> 'ativo'")->fetchColumn();
    }
    if ($p === 'supervisor') {
        $st = $pdo->prepare("SELECT id_usuario, nome, validade_cnh, categoria_cnh,
                (SELECT MAX(data_vistoria) FROM Checklist_Execucoes x WHERE x.id_motorista = u.id_usuario) AS ultima_vistoria
            FROM Usuarios u WHERE u.id_supervisor = :me AND u.perfil = 'motorista' AND u.status = 'ativo' ORDER BY nome");
        $st->execute([':me' => $u['id']]);
        $dados['equipe'] = $st->fetchAll();
    }
    if ($p === 'motorista') {
        $st = $pdo->prepare("SELECT validade_cnh, categoria_cnh FROM Usuarios WHERE id_usuario = :me");
        $st->execute([':me' => $u['id']]);
        $dados['cnh'] = $st->fetch();
    }
    axion_json(['sucesso' => true, 'dados' => $dados]);
}

// 1b. PESSOAS PARA A VISTORIA (motoristas e supervisores, com o vínculo motorista -> supervisor)
if ($acao === 'listar_pessoas') {
    $u = axion_exigir_perfil(['admin', 'supervisor', 'motorista']);
    $sup = $pdo->query("SELECT id_usuario, nome, perfil FROM Usuarios WHERE perfil IN ('supervisor','admin') AND status = 'ativo' ORDER BY nome")->fetchAll();
    $mot = [];
    if (axion_eh_gestor($u)) {
        $mot = $pdo->query("SELECT id_usuario, nome, categoria_cnh, id_supervisor FROM Usuarios WHERE perfil = 'motorista' AND status = 'ativo' ORDER BY nome")->fetchAll();
    }
    axion_json(['sucesso' => true, 'supervisores' => $sup, 'motoristas' => $mot]);
}

// 2. OBTER DETALHE DE REGISTRO PARA EDIÇÃO / EXCLUSÃO (JSON)
if ($acao === 'obter_usuario') {
    axion_exigir_perfil(['admin', 'supervisor']);
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id_usuario, nome, sobrenome, email, cpf_matricula, perfil, data_nascimento, genero,
                                  cnh, validade_cnh, categoria_cnh, cep, logradouro, numero, bairro, cidade, estado, telefone, status, id_supervisor
                           FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    axion_json(['sucesso' => (bool)$user, 'dados' => $user]);
}

if ($acao === 'obter_veiculo') {
    axion_exigir_perfil();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM Veiculos WHERE id_veiculo = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $veic = $stmt->fetch();
    axion_json(['sucesso' => (bool)$veic, 'dados' => $veic]);
}

if ($acao === 'obter_checklist') {
    axion_exigir_perfil();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM Checklists WHERE id_checklist = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $chk = $stmt->fetch();
    if ($chk) {
        $stmtP = $pdo->prepare("SELECT * FROM Checklist_Perguntas WHERE id_checklist = :id ORDER BY ordem ASC, id_pergunta ASC");
        $stmtP->execute([':id' => $id]);
        // Lista na ordem de exibição: cada pergunta principal seguida das suas subperguntas
        $todas = $stmtP->fetchAll();
        $ordenadas = [];
        foreach ($todas as $p) {
            if ($p['id_pergunta_pai'] !== null) continue;
            $ordenadas[] = $p;
            foreach ($todas as $sub) {
                if ((int)$sub['id_pergunta_pai'] === (int)$p['id_pergunta']) $ordenadas[] = $sub;
            }
        }
        $chk['perguntas'] = $ordenadas;
    }
    axion_json(['sucesso' => (bool)$chk, 'dados' => $chk]);
}

// 3. SALVAR EDIÇÃO DE USUÁRIO
if ($acao === 'salvar_edicao_usuario' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id            = (int)($_POST['id_usuario'] ?? 0);
    $alvo          = $id > 0 ? axion_buscar_usuario($pdo, $id) : null;
    if (!$alvo) {
        header("Location: ../editarUsuario.html?erro=usuario_nao_selecionado");
        exit;
    }
    $nome          = trim($_POST['nome'] ?? '');
    $sobrenome     = trim($_POST['sobrenome'] ?? '');
    $nomeCompleto  = trim($nome . ($sobrenome !== '' && stripos($nome, $sobrenome) === false ? ' ' . $sobrenome : ''));
    $cpf           = axion_formatar_cpf(trim($_POST['cpf'] ?? ''));
    $email         = trim($_POST['email'] ?? '');
    $perfil        = strtolower(trim($_POST['perfil'] ?? $alvo['perfil']));
    $dtNasc        = !empty($_POST['dtNascimento']) ? $_POST['dtNascimento'] : null;
    $genero        = trim($_POST['genero'] ?? '');
    $cnh           = preg_replace('/\D/', '', $_POST['cnh'] ?? '');
    $valCnh        = !empty($_POST['validade-cnh']) ? $_POST['validade-cnh'] : null;
    $catCnh        = strtoupper(trim($_POST['categoria-cnh'] ?? ''));
    $cep           = trim($_POST['cep'] ?? '');
    $logradouro    = trim($_POST['logradouro'] ?? '');
    $numero        = trim($_POST['numero'] ?? '');
    $bairro        = trim($_POST['bairro'] ?? '');
    $cidade        = trim($_POST['cidade'] ?? '');
    $estado        = strtoupper(trim($_POST['estado'] ?? ''));
    $telefone      = trim($_POST['telefone'] ?? '');
    $idSupervisor  = (int)($_POST['id_supervisor'] ?? 0);
    $statusConta   = ($_POST['status'] ?? $alvo['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    $erro = null;
    if ($perfil === 'motorista') {
        // Motorista precisa de CNH completa para dirigir a frota
        if (strlen($cnh) !== 11) $erro = 'cnh_obrigatoria';
        elseif ($catCnh === '') $erro = 'categoria_cnh_obrigatoria';
        elseif (!$valCnh) $erro = 'validade_cnh_obrigatoria';
    }
    if (!in_array($perfil, AXION_PERFIS, true)) $erro = 'perfil_invalido';
    // Supervisor não gerencia contas de admin/supervisor nem promove alguém a esses níveis
    if ($eu['perfil'] === 'supervisor' && (in_array($alvo['perfil'], ['admin', 'supervisor'], true) || in_array($perfil, ['admin', 'supervisor'], true)) && $alvo['id_usuario'] != $eu['id']) $erro = 'sem_permissao';
    // Ninguém muda o próprio nível de acesso
    if ($alvo['id_usuario'] == $eu['id'] && $perfil !== $alvo['perfil']) $erro = 'nao_pode_mudar_proprio_perfil';
    if ($alvo['id_usuario'] == $eu['id'] && $statusConta === 'inativo') $erro = 'nao_pode_desativar_a_si_mesmo';
    if (!axion_validar_cpf($cpf)) $erro = 'cpf_invalido';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erro = 'email_invalido';
    if ($nomeCompleto === '') $erro = 'nome_obrigatorio';

    $supValido = null;
    if ($perfil === 'motorista' && $idSupervisor > 0) {
        $s = axion_buscar_usuario($pdo, $idSupervisor);
        if (!$s || !in_array($s['perfil'], ['supervisor', 'admin'], true)) $erro = 'supervisor_invalido';
        else $supValido = $idSupervisor;
    }
    if ($erro) {
        header("Location: ../editarUsuario.html?id=$id&erro=$erro");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE Usuarios SET
                nome = :nome, sobrenome = :sobrenome, email = :email, cpf_matricula = :cpf, perfil = :perfil,
                data_nascimento = :dt_nasc, genero = :genero, cnh = :cnh, validade_cnh = :val_cnh, categoria_cnh = :cat_cnh,
                cep = :cep, logradouro = :logradouro, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado,
                telefone = :telefone, id_supervisor = :sup, status = :status_conta
            WHERE id_usuario = :id
        ");
        $stmt->execute([
            ':nome' => $nomeCompleto, ':sobrenome' => $sobrenome, ':email' => $email, ':cpf' => $cpf, ':perfil' => $perfil,
            ':dt_nasc' => $dtNasc, ':genero' => $genero, ':cnh' => $cnh, ':val_cnh' => $valCnh, ':cat_cnh' => $catCnh ?: null,
            ':cep' => $cep, ':logradouro' => $logradouro, ':numero' => $numero, ':bairro' => $bairro, ':cidade' => $cidade,
            ':estado' => $estado, ':telefone' => $telefone, ':sup' => $supValido, ':status_conta' => $statusConta, ':id' => $id
        ]);
    } catch (PDOException $e) {
        $codigo = ($e->errorInfo[1] ?? 0) == 1062 ? 'cpf_ou_email_duplicado' : 'erro_banco';
        header("Location: ../editarUsuario.html?id=$id&erro=$codigo");
        exit;
    }
    // Trocou o supervisor do motorista: as solicitações dele que ainda esperam o supervisor vão para o novo
    if ($perfil === 'motorista' && $supValido && (int)$supValido !== (int)$alvo['id_supervisor']) {
        $novo = axion_buscar_usuario($pdo, $supValido);
        $pdo->prepare("UPDATE Checklist_Execucoes SET id_vistoriador = :v, nome_vistoriador = :n
                       WHERE id_motorista = :m AND id_registrado_por = :m2 AND assinado_vistoriador_em IS NULL AND recusado_em IS NULL")
            ->execute([':v' => $supValido, ':n' => $novo['nome'], ':m' => $id, ':m2' => $id]);
    }
    header("Location: ../oqfazer.php?aba=usuarios&sucesso=usuario_editado");
    exit;
}

// 4. EXCLUIR USUÁRIO
if ($acao === 'excluir_usuario' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_usuario'] ?? 0);
    $alvo = $id > 0 ? axion_buscar_usuario($pdo, $id) : null;
    if (!$alvo) { header("Location: ../excluirUsuario.html?erro=usuario_nao_selecionado"); exit; }
    if ($alvo['id_usuario'] == $eu['id']) { header("Location: ../excluirUsuario.html?id=$id&erro=nao_pode_excluir_a_si_mesmo"); exit; }
    if ($eu['perfil'] === 'supervisor' && in_array($alvo['perfil'], ['admin', 'supervisor'], true)) {
        header("Location: ../excluirUsuario.html?id=$id&erro=sem_permissao"); exit;
    }
    // Quem já participou de vistoria não é apagado (o histórico e as assinaturas precisam continuar
    // apontando para a pessoa). Nesse caso a conta é desativada: não consegue mais entrar no sistema.
    $hist = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE id_motorista = :a OR id_vistoriador = :b");
    $hist->execute([':a' => $id, ':b' => $id]);
    if ((int)$hist->fetchColumn() > 0) {
        $pdo->prepare("UPDATE Usuarios SET status = 'inativo' WHERE id_usuario = :id")->execute([':id' => $id]);
        header("Location: ../oqfazer.php?aba=usuarios&sucesso=usuario_desativado");
        exit;
    }
    $pdo->prepare("UPDATE Usuarios SET id_supervisor = NULL WHERE id_supervisor = :id")->execute([':id' => $id]);
    $pdo->prepare("DELETE FROM Usuarios WHERE id_usuario = :id")->execute([':id' => $id]);
    header("Location: ../oqfazer.php?aba=usuarios&sucesso=usuario_excluido");
    exit;
}

// 5. SALVAR EDIÇÃO DE VEÍCULO
if ($acao === 'salvar_edicao_veiculo' && $ehPost) {
    axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id          = (int)($_POST['id_veiculo'] ?? 0);
    if ($id <= 0) { header("Location: ../editarVeiculo.html?erro=veiculo_nao_selecionado"); exit; }
    $marca       = trim($_POST['marca'] ?? '');
    $modelo      = trim($_POST['modelo'] ?? '');
    $marcaModelo = trim("$marca $modelo");
    $ano         = (int)($_POST['ano'] ?? date('Y'));
    $placa       = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
    $cor         = trim($_POST['cor'] ?? '');
    $km          = max(0, (int)($_POST['kmrodado'] ?? 0));
    $renavam     = preg_replace('/\D/', '', $_POST['renavam'] ?? '');
    $chassi      = strtoupper(trim($_POST['chassi'] ?? ''));
    $status      = trim($_POST['status'] ?? 'ativo');
    if (!in_array($status, AXION_STATUS_VEICULO, true)) $status = 'ativo';

    $dup = $pdo->prepare("SELECT COUNT(*) FROM Veiculos WHERE id_veiculo <> :id AND (placa = :placa OR (:ren <> '' AND renavam = :ren2) OR (:ch <> '' AND chassi = :ch2))");
    $dup->execute([':id' => $id, ':placa' => $placa, ':ren' => $renavam, ':ren2' => $renavam, ':ch' => $chassi, ':ch2' => $chassi]);
    if ((int)$dup->fetchColumn() > 0) { header("Location: ../editarVeiculo.html?id=$id&erro=placa_renavam_ou_chassi_duplicado"); exit; }

    $stmt = $pdo->prepare("
        UPDATE Veiculos SET
            placa = :placa, marca = :marca, modelo = :modelo, marca_modelo = :marca_modelo,
            ano = :ano, cor = :cor, km_rodado = :km, renavam = :renavam, chassi = :chassi, status = :status
        WHERE id_veiculo = :id
    ");
    $stmt->execute([
        ':placa' => $placa, ':marca' => $marca, ':modelo' => $modelo, ':marca_modelo' => $marcaModelo, ':ano' => $ano,
        ':cor' => $cor, ':km' => $km, ':renavam' => $renavam, ':chassi' => $chassi, ':status' => $status, ':id' => $id
    ]);
    header("Location: ../oqfazer.php?aba=veiculos&sucesso=veiculo_editado");
    exit;
}

// 6. EXCLUIR VEÍCULO
if ($acao === 'excluir_veiculo' && $ehPost) {
    axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_veiculo'] ?? 0);
    if ($id <= 0) { header("Location: ../excluirVeiculo.html?erro=veiculo_nao_selecionado"); exit; }
    $hist = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE id_veiculo = :id");
    $hist->execute([':id' => $id]);
    if ((int)$hist->fetchColumn() > 0) {
        // Carro com vistorias não é apagado, para não perder o histórico
        header("Location: ../excluirVeiculo.html?id=$id&erro=veiculo_com_historico");
        exit;
    }
    $pdo->prepare("DELETE FROM Veiculos WHERE id_veiculo = :id")->execute([':id' => $id]);
    header("Location: ../oqfazer.php?aba=veiculos&sucesso=veiculo_excluido");
    exit;
}

// 7. SALVAR EDIÇÃO DE CHECKLIST
if ($acao === 'salvar_edicao_checklist' && $ehPost) {
    axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id        = (int)($_POST['id_checklist'] ?? 0);
    $titulo    = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'outros');
    if (!in_array($categoria, AXION_CATEGORIAS_CHECKLIST, true)) $categoria = 'outros';
    $descricao = trim($_POST['descricao'] ?? '');

    if ($id <= 0 || $titulo === '') { header("Location: ../editarChecklist.html?erro=checklist_nao_selecionado"); exit; }
    $pdo->beginTransaction();
    // Salvar um modelo arquivado volta a deixá-lo disponível
    $pdo->prepare("UPDATE Checklists SET titulo = :titulo, categoria = :cat, descricao = :desc, ativo = 1 WHERE id_checklist = :id")
        ->execute([':titulo' => $titulo, ':cat' => $categoria, ':desc' => $descricao, ':id' => $id]);

    if (!empty($_POST['perguntas']) && is_array($_POST['perguntas'])) {
        // Atualiza as perguntas principais preservando as subperguntas condicionais já cadastradas
        $idsMantidos = [];
        $stmtUpd = $pdo->prepare("UPDATE Checklist_Perguntas SET ordem = :ordem, texto = :texto, tipo = :tipo, obrigatorio = :obrig, resposta_esperada = :esp, orientacao = :ori WHERE id_pergunta = :idp AND id_checklist = :id_chk");
        $stmtIns = $pdo->prepare("INSERT INTO Checklist_Perguntas (id_checklist, ordem, texto, tipo, obrigatorio, resposta_esperada, orientacao) VALUES (:id_chk, :ordem, :texto, :tipo, :obrig, :esp, :ori)");
        $ordem = 1;
        foreach ($_POST['perguntas'] as $p) {
            $texto = trim($p['texto'] ?? '');
            if ($texto === '') continue;
            $dados = [
                ':id_chk' => $id, ':ordem' => $ordem++, ':texto' => $texto, ':tipo' => $p['tipo'] ?? 'sim_nao',
                ':obrig' => isset($p['obrigatorio']) ? 1 : 0,
                ':esp' => ($p['tipo'] ?? 'sim_nao') === 'sim_nao' ? ($p['resposta_esperada'] ?? 'sim') : null, ':ori' => trim($p['orientacao'] ?? '')
            ];
            $idp = (int)($p['id_pergunta'] ?? 0);
            if ($idp > 0) {
                $stmtUpd->execute($dados + [':idp' => $idp]);
                $idsMantidos[] = $idp;
            } else {
                $stmtIns->execute($dados);
                $idsMantidos[] = (int)$pdo->lastInsertId();
            }
        }
        // Remove perguntas principais que saíram do formulário (e as subperguntas delas)
        $todas = $pdo->prepare("SELECT id_pergunta FROM Checklist_Perguntas WHERE id_checklist = :id AND id_pergunta_pai IS NULL");
        $todas->execute([':id' => $id]);
        foreach ($todas->fetchAll(PDO::FETCH_COLUMN) as $idp) {
            if (!in_array((int)$idp, $idsMantidos, true)) {
                $pdo->prepare("DELETE FROM Checklist_Perguntas WHERE id_pergunta = :p OR id_pergunta_pai = :p2")->execute([':p' => $idp, ':p2' => $idp]);
            }
        }
    }
    $pdo->commit();
    header("Location: ../oqfazer.php?aba=checklists&sucesso=checklist_editado");
    exit;
}

// 8. EXCLUIR CHECKLIST
if ($acao === 'excluir_checklist' && $ehPost) {
    axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_checklist'] ?? 0);
    if ($id <= 0) { header("Location: ../excluirChecklist.html?erro=checklist_nao_selecionado"); exit; }
    $hist = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE id_checklist = :id");
    $hist->execute([':id' => $id]);
    if ((int)$hist->fetchColumn() > 0) {
        // Modelo já usado em vistorias: é arquivado (some do "Preencher Vistoria"), mas o histórico continua
        $pdo->prepare("UPDATE Checklists SET ativo = 0 WHERE id_checklist = :id")->execute([':id' => $id]);
        header("Location: ../oqfazer.php?aba=checklists&sucesso=checklist_arquivado");
        exit;
    }
    $pdo->prepare("DELETE FROM Checklist_Perguntas WHERE id_checklist = :id")->execute([':id' => $id]);
    $pdo->prepare("DELETE FROM Checklists WHERE id_checklist = :id")->execute([':id' => $id]);
    header("Location: ../oqfazer.php?aba=checklists&sucesso=checklist_excluido");
    exit;
}

// 9. REGISTRAR PREENCHIMENTO DE CHECKLIST / VISTORIA
if ($acao === 'registrar_vistoria' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor', 'motorista']);
    axion_validar_csrf();
    $voltar = function (string $erro) {
        header("Location: ../cadcheck.html?erro=$erro");
        exit;
    };

    $idChecklist = (int)($_POST['id_checklist'] ?? 0);
    $chk = $pdo->prepare("SELECT c.ativo, (SELECT COUNT(*) FROM Checklist_Perguntas p WHERE p.id_checklist = c.id_checklist) AS qtd FROM Checklists c WHERE c.id_checklist = :id");
    $chk->execute([':id' => $idChecklist]);
    $modelo = $chk->fetch();
    if (!$modelo || (int)$modelo['ativo'] !== 1) $voltar('checklist_invalido');
    if ((int)$modelo['qtd'] === 0) $voltar('checklist_sem_perguntas');

    $idVeiculo = (int)($_POST['id_veiculo'] ?? 0);
    $stV = $pdo->prepare("SELECT id_veiculo, placa, km_rodado FROM Veiculos WHERE id_veiculo = :id");
    $stV->execute([':id' => $idVeiculo]);
    $veiculo = $stV->fetch();
    if (!$veiculo) $voltar('veiculo_invalido');

    $km = (int)($_POST['kmrodado'] ?? -1);
    if ($km < 0) $voltar('km_invalido');
    // Hodômetro não volta: o km informado não pode ser menor que o último registrado
    if ($km < (int)$veiculo['km_rodado']) $voltar('km_menor_que_atual');

    // Motorista: se quem está logado é motorista, é ele mesmo (não dá para escolher outro)
    if ($eu['perfil'] === 'motorista') {
        $motorista = axion_buscar_usuario($pdo, $eu['id']);
    } else {
        $motorista = axion_buscar_usuario($pdo, (int)($_POST['id_motorista'] ?? 0));
        if (!$motorista || $motorista['perfil'] !== 'motorista') $voltar('motorista_invalido');
    }

    // Vistoriador: gestor logado é o próprio vistoriador; para motorista, é o supervisor vinculado a ele
    if (axion_eh_gestor($eu)) {
        $vistoriador = axion_buscar_usuario($pdo, $eu['id']);
    } elseif (!empty($motorista['id_supervisor'])) {
        $vistoriador = axion_buscar_usuario($pdo, (int)$motorista['id_supervisor']);
    } else {
        $vistoriador = axion_buscar_usuario($pdo, (int)($_POST['id_vistoriador'] ?? 0));
    }
    if (!$vistoriador || !in_array($vistoriador['perfil'], ['supervisor', 'admin'], true) || $vistoriador['status'] !== 'ativo') $voltar('vistoriador_invalido');
    if ($motorista['status'] !== 'ativo') $voltar('motorista_invalido');
    // Motorista com CNH vencida não pode assumir veículo
    if (!empty($motorista['validade_cnh']) && $motorista['validade_cnh'] < date('Y-m-d')) $voltar('cnh_vencida');
    if ($vistoriador['id_usuario'] == $motorista['id_usuario']) $voltar('motorista_e_vistoriador_iguais');

    // Respostas: guarda cada uma e apura a conformidade pelo gabarito do modelo
    $stP = $pdo->prepare("SELECT * FROM Checklist_Perguntas WHERE id_checklist = :id ORDER BY ordem, id_pergunta");
    $stP->execute([':id' => $idChecklist]);
    $perguntas = $stP->fetchAll();
    $respostasPost = is_array($_POST['respostas'] ?? null) ? $_POST['respostas'] : [];
    $respostasPrincipais = [];
    foreach ($perguntas as $p) {
        if ($p['id_pergunta_pai'] === null) {
            $respostasPrincipais[$p['id_pergunta']] = strtolower(trim((string)($respostasPost[$p['id_pergunta']] ?? '')));
        }
    }
    $linhas = [];
    $naoConforme = false;
    foreach ($perguntas as $p) {
        $idp = (int)$p['id_pergunta'];
        $resp = trim((string)($respostasPost[$idp] ?? ''));
        if ($p['id_pergunta_pai'] !== null) {
            // Subpergunta só vale quando a resposta da pergunta-mãe bate com a condição
            $respPai = $respostasPrincipais[$p['id_pergunta_pai']] ?? '';
            if ($respPai !== strtolower((string)$p['condicao_pai'])) continue;
        }
        if ($resp === '' && (int)$p['obrigatorio'] === 1) $voltar('resposta_obrigatoria');
        $conforme = null;
        if ($p['tipo'] === 'sim_nao' && $p['resposta_esperada'] && $resp !== '') {
            $conforme = (strtolower($resp) === strtolower($p['resposta_esperada'])) ? 1 : 0;
            if ($conforme === 0) $naoConforme = true;
        }
        $linhas[] = [$idp, $p['id_pergunta_pai'], $p['texto'], mb_substr($resp, 0, 255), $p['tipo'] === 'sim_nao' ? $p['resposta_esperada'] : null, $conforme];
    }
    // O usuário ainda pode marcar avaria encontrada fora das perguntas
    if (($_POST['status_conformidade'] ?? '') === 'nao_conforme') $naoConforme = true;
    $status = $naoConforme ? 'nao_conforme' : 'conforme';

    $pdo->beginTransaction();
    $pdo->prepare("
        INSERT INTO Checklist_Execucoes (
            id_checklist, id_veiculo, placa_veiculo, id_motorista, nome_motorista, id_vistoriador, nome_vistoriador,
            id_registrado_por, data_vistoria, hora_vistoria, km_rodado, status_conformidade, observacoes
        ) VALUES (:chk, :veic, :placa, :id_mot, :mot, :id_vist, :vist, :reg, CURDATE(), CURTIME(), :km, :st, :obs)
    ")->execute([
        ':chk' => $idChecklist, ':veic' => $veiculo['id_veiculo'], ':placa' => $veiculo['placa'],
        ':id_mot' => $motorista['id_usuario'], ':mot' => $motorista['nome'],
        ':id_vist' => $vistoriador['id_usuario'], ':vist' => $vistoriador['nome'],
        ':reg' => $eu['id'], ':km' => $km, ':st' => $status, ':obs' => mb_substr(trim($_POST['observacoes'] ?? ''), 0, 2000)
    ]);
    $idExec = (int)$pdo->lastInsertId();
    $stR = $pdo->prepare("INSERT INTO Checklist_Respostas (id_execucao, id_pergunta, id_pergunta_pai, pergunta_texto, resposta, resposta_esperada, conforme) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($linhas as $l) {
        $stR->execute(array_merge([$idExec], $l));
    }
    // Mantém o hodômetro do carro atualizado
    if ($km > (int)$veiculo['km_rodado']) {
        $pdo->prepare("UPDATE Veiculos SET km_rodado = :km WHERE id_veiculo = :id")->execute([':km' => $km, ':id' => $veiculo['id_veiculo']]);
    }
    $pdo->commit();

    header("Location: ../" . ($status === 'nao_conforme' ? 'detalhesNaoConformidade.html' : 'assinaturaChecklist.html') . "?id=$idExec");
    exit;
}

/** Carrega uma vistoria verificando se o usuário pode vê-la/alterá-la. */
function axion_carregar_vistoria(PDO $pdo, array $eu, int $id): ?array {
    $st = $pdo->prepare("SELECT e.*, COALESCE(c.titulo, 'Checklist removido') AS checklist_titulo, c.categoria AS checklist_categoria,
                                v.marca_modelo, v.ano, v.cor
                         FROM Checklist_Execucoes e
                         LEFT JOIN Checklists c ON c.id_checklist = e.id_checklist
                         LEFT JOIN Veiculos v ON v.id_veiculo = e.id_veiculo
                         WHERE e.id_execucao = :id");
    $st->execute([':id' => $id]);
    $v = $st->fetch();
    if (!$v) return null;
    if ($eu['perfil'] === 'motorista' && (int)$v['id_motorista'] !== $eu['id']) return null;
    return $v;
}

// 9b. DETALHES DE NÃO CONFORMIDADE (descrição + foto)
// Só pode ser preenchido/alterado antes da primeira assinatura: quem assina depois precisa ver exatamente o que o outro assinou.
if ($acao === 'salvar_nao_conformidade' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor', 'motorista']);
    axion_validar_csrf();
    $id = (int)($_POST['id_execucao'] ?? 0);
    $v = axion_carregar_vistoria($pdo, $eu, $id);
    $bloqueada = !$v || $v['recusado_em'] || $v['assinado_motorista_em'] || $v['assinado_vistoriador_em']
        || (int)$v['id_registrado_por'] !== $eu['id'];
    if ($bloqueada) { header("Location: ../oqfazer.php?aba=vistorias&erro=vistoria_invalida"); exit; }
    $descricao = trim($_POST['descricao_detalhada'] ?? '');
    if ($descricao === '') { header("Location: ../detalhesNaoConformidade.html?id=$id&erro=descricao_obrigatoria"); exit; }

    $foto = $v['foto_evidencia'];
    foreach (['foto_evidencia', 'documento_evidencia'] as $campo) {
        if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) continue;
        $arq = $_FILES[$campo];
        if ($arq['size'] > 8 * 1024 * 1024) { header("Location: ../detalhesNaoConformidade.html?id=$id&erro=arquivo_muito_grande"); exit; }
        $info = @getimagesize($arq['tmp_name']);
        $ext = $info ? (['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime']] ?? null) : null;
        if (!$ext) { header("Location: ../detalhesNaoConformidade.html?id=$id&erro=arquivo_nao_e_imagem"); exit; }
        $dir = __DIR__ . '/../uploads/vistorias';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $nomeArq = $id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        move_uploaded_file($arq['tmp_name'], "$dir/$nomeArq");
        if ($v['foto_evidencia'] && is_file("$dir/" . basename($v['foto_evidencia']))) @unlink("$dir/" . basename($v['foto_evidencia']));
        $foto = $nomeArq;
        break;
    }
    $pdo->prepare("UPDATE Checklist_Execucoes SET detalhes_nao_conformidade = :d, foto_evidencia = :f WHERE id_execucao = :id")
        ->execute([':d' => mb_substr($descricao, 0, 5000), ':f' => $foto, ':id' => $id]);
    header("Location: ../assinaturaChecklist.html?id=$id");
    exit;
}

// 9c. ASSINATURA (cada pessoa assina o próprio campo, no próprio login)
// Quem registra a vistoria assina primeiro; isso vira uma solicitação para a outra pessoa
// (normalmente o supervisor), que revisa e assina depois, ou recusa com um motivo.
if ($acao === 'assinar_vistoria' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor', 'motorista']);
    axion_validar_csrf();
    $id = (int)($_POST['id_execucao'] ?? 0);
    $v = axion_carregar_vistoria($pdo, $eu, $id);
    if (!$v) axion_json(['sucesso' => false, 'erro' => 'Vistoria não encontrada.'], 404);
    $situacao = axion_situacao($v);
    if ($situacao === 'recusada') axion_json(['sucesso' => false, 'erro' => 'Esta vistoria foi recusada e não pode mais ser assinada.'], 409);
    if ($situacao === 'concluida') axion_json(['sucesso' => false, 'erro' => 'Esta vistoria já foi concluída.'], 409);

    $papel = axion_papel($v, $eu);
    if (!$papel) axion_json(['sucesso' => false, 'erro' => 'Só o motorista ou o vistoriador desta vistoria podem assinar.'], 403);
    if ($v["assinado_{$papel}_em"]) axion_json(['sucesso' => false, 'erro' => 'Você já assinou esta vistoria. Agora falta a outra pessoa.'], 409);
    if ($v['status_conformidade'] === 'nao_conforme' && trim((string)$v['detalhes_nao_conformidade']) === '') {
        axion_json(['sucesso' => false, 'erro' => 'Descreva a não conformidade antes de assinar.', 'redirecionar' => "detalhesNaoConformidade.html?id=$id"], 422);
    }

    $assinatura = (string)($_POST['assinatura'] ?? '');
    if (strpos($assinatura, 'data:image/png;base64,') !== 0 || strlen($assinatura) < 1500 || strlen($assinatura) > 2000000) {
        axion_json(['sucesso' => false, 'erro' => 'Desenhe a sua assinatura no quadro.'], 422);
    }
    $outra = $papel === 'motorista' ? $v['assinatura_vistoriador'] : $v['assinatura_motorista'];
    if ($outra && hash_equals($outra, $assinatura)) axion_json(['sucesso' => false, 'erro' => 'Assinatura idêntica à da outra pessoa.'], 422);

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE Checklist_Execucoes SET assinatura_$papel = :a, assinado_{$papel}_em = NOW() WHERE id_execucao = :id AND assinado_{$papel}_em IS NULL")
        ->execute([':a' => $assinatura, ':id' => $id]);
    $v = axion_carregar_vistoria($pdo, $eu, $id);
    $concluiu = axion_situacao($v) === 'concluida';
    if ($concluiu) {
        $pdo->prepare("UPDATE Checklist_Execucoes SET assinado_em = NOW() WHERE id_execucao = :id")->execute([':id' => $id]);
        // Supervisor pode mandar o carro para manutenção ao aprovar uma vistoria com avaria
        if ($papel === 'vistoriador' && $v['status_conformidade'] === 'nao_conforme' && !empty($_POST['enviar_manutencao']) && $v['id_veiculo']) {
            $pdo->prepare("UPDATE Veiculos SET status = 'manutencao' WHERE id_veiculo = :v")->execute([':v' => $v['id_veiculo']]);
            $pdo->prepare("UPDATE Checklist_Execucoes SET enviado_manutencao = 1 WHERE id_execucao = :id")->execute([':id' => $id]);
        }
    }
    $pdo->commit();
    axion_json(['sucesso' => true, 'concluida' => $concluiu, 'redirecionar' => "verVistoria.html?id=$id&sucesso=" . ($concluiu ? 'concluida' : 'enviada')]);
}

// 9d. RECUSAR VISTORIA (a pessoa que ainda não assinou devolve com um motivo)
if ($acao === 'recusar_vistoria' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor', 'motorista']);
    axion_validar_csrf();
    $id = (int)($_POST['id_execucao'] ?? 0);
    $v = axion_carregar_vistoria($pdo, $eu, $id);
    if (!$v) axion_json(['sucesso' => false, 'erro' => 'Vistoria não encontrada.'], 404);
    $papel = axion_papel($v, $eu);
    $situacao = axion_situacao($v);
    $outroPapel = $papel === 'motorista' ? 'vistoriador' : 'motorista';
    // Recusa é a resposta a uma solicitação: só quem recebeu o pedido (a outra pessoa já assinou) pode recusar
    if (!$papel || in_array($situacao, ['recusada', 'concluida'], true) || $v["assinado_{$papel}_em"] || !$v["assinado_{$outroPapel}_em"]) {
        axion_json(['sucesso' => false, 'erro' => 'Você não pode recusar esta vistoria.'], 403);
    }
    $motivo = trim((string)($_POST['motivo'] ?? ''));
    if (mb_strlen($motivo) < 5) axion_json(['sucesso' => false, 'erro' => 'Explique o motivo da recusa.'], 422);
    $pdo->prepare("UPDATE Checklist_Execucoes SET recusado_em = NOW(), recusado_por = :u, motivo_recusa = :m WHERE id_execucao = :id AND recusado_em IS NULL")
        ->execute([':u' => $eu['id'], ':m' => mb_substr($motivo, 0, 2000), ':id' => $id]);
    axion_json(['sucesso' => true, 'redirecionar' => "verVistoria.html?id=$id&sucesso=recusada"]);
}

// 10. VISUALIZAR VISTORIA PREENCHIDA (respostas, avaria, foto e assinaturas)
if ($acao === 'obter_vistoria') {
    $eu = axion_exigir_perfil();
    $id = (int)($_GET['id'] ?? 0);
    $v = axion_carregar_vistoria($pdo, $eu, $id);
    if (!$v) axion_json(['sucesso' => false, 'erro' => 'Vistoria não encontrada ou sem permissão.'], 404);
    $st = $pdo->prepare("SELECT id_pergunta, id_pergunta_pai, pergunta_texto, resposta, resposta_esperada, conforme FROM Checklist_Respostas WHERE id_execucao = :id ORDER BY id_resposta");
    $st->execute([':id' => $id]);
    $v['respostas'] = $st->fetchAll();
    $v['foto_url'] = $v['foto_evidencia'] ? "backend/api.php?acao=foto_vistoria&id=$id" : null;
    unset($v['foto_evidencia']);
    $v['situacao'] = axion_situacao($v);
    $v['meu_papel'] = axion_papel($v, $eu);
    $v['pode_assinar'] = $v['meu_papel'] && !in_array($v['situacao'], ['recusada', 'concluida'], true) && !$v["assinado_{$v['meu_papel']}_em"];
    $v['pode_editar_avaria'] = !$v['assinado_motorista_em'] && !$v['assinado_vistoriador_em'] && !$v['recusado_em'] && (int)$v['id_registrado_por'] === $eu['id'];
    if ($v['recusado_por']) {
        $r = axion_buscar_usuario($pdo, (int)$v['recusado_por']);
        $v['recusado_por_nome'] = $r['nome'] ?? '';
    }
    // Cliente consulta a vistoria, mas não vê as assinaturas
    if ($eu['perfil'] === 'cliente') {
        $v['assinatura_motorista'] = $v['assinatura_motorista'] ? true : null;
        $v['assinatura_vistoriador'] = $v['assinatura_vistoriador'] ? true : null;
    }
    axion_json(['sucesso' => true, 'dados' => $v, 'usuario' => $eu]);
}

if ($acao === 'foto_vistoria') {
    $eu = axion_exigir_perfil();
    $v = axion_carregar_vistoria($pdo, $eu, (int)($_GET['id'] ?? 0));
    $arq = $v && $v['foto_evidencia'] ? __DIR__ . '/../uploads/vistorias/' . basename($v['foto_evidencia']) : null;
    if (!$arq || !is_file($arq)) { http_response_code(404); exit; }
    $tipos = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    header('Content-Type: ' . ($tipos[strtolower(pathinfo($arq, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($arq));
    readfile($arq);
    exit;
}

axion_json(['sucesso' => false, 'erro' => 'Ação inválida.'], 400);
