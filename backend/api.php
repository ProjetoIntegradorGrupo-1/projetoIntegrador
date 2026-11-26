<?php
/**
 * backend/api.php
 * Endpoints JSON e processadores de Busca, Edição, Exclusão e Vistoria para o Sistema Axion.
 * Toda ação exige login; ações de gestão exigem perfil admin/supervisor (ver backend/auth.php).
 */
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/fotos.php';

$acao = $_REQUEST['acao'] ?? '';
$ehPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

const AXION_STATUS_VEICULO = ['ativo', 'em_rota', 'manutencao', 'locado'];
// Perfis que trabalham na operação (o cliente só aluga veículos e não vê dados administrativos)
const AXION_PERFIS_INTERNOS = ['admin', 'supervisor', 'motorista'];
const AXION_CATEGORIAS_CHECKLIST = ['eletrico', 'mecanico', 'estetico', 'seguranca', 'pre_viagem', 'locacao', 'outros'];

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
 * Quem o usuário logado pode gerenciar (editar, excluir, redefinir senha).
 * Admin: todos. Supervisor: a si mesmo, clientes e os motoristas da própria equipe (ou sem supervisor).
 */
function axion_pode_gerenciar(array $eu, array $alvo): bool {
    if ($eu['perfil'] === 'admin') return true;
    if ($eu['perfil'] !== 'supervisor') return false;
    if ((int)$alvo['id_usuario'] === (int)$eu['id']) return true;
    if ($alvo['perfil'] === 'cliente') return true;
    if ($alvo['perfil'] === 'motorista') return empty($alvo['id_supervisor']) || (int)$alvo['id_supervisor'] === (int)$eu['id'];
    return false;
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

/** Endereço da foto de perfil (o nome do arquivo muda a cada envio, então serve de versão para o cache). */
function axion_url_foto(int $id, ?string $arquivo): ?string {
    return $arquivo ? "backend/api.php?acao=foto_usuario&id=$id&v=" . rawurlencode(pathinfo($arquivo, PATHINFO_FILENAME)) : null;
}

function axion_url_foto_veiculo(int $id, ?string $arquivo): ?string {
    return $arquivo ? "backend/api.php?acao=foto_veiculo&id=$id&v=" . rawurlencode(pathinfo($arquivo, PATHINFO_FILENAME)) : null;
}

// 0. CONTEXTO DA SESSÃO (quem está logado + token dos formulários)
if ($acao === 'contexto') {
    $u = axion_exigir_perfil();
    $stF = $pdo->prepare("SELECT foto FROM Usuarios WHERE id_usuario = :id");
    $stF->execute([':id' => $u['id']]);
    $u['foto_url'] = axion_url_foto($u['id'], $stF->fetchColumn() ?: null);
    $extra = ['supervisor' => null];
    if (in_array($u['perfil'], ['motorista', 'cliente'], true)) {
        $st = $pdo->prepare("SELECT s.id_usuario, s.nome FROM Usuarios m JOIN Usuarios s ON s.id_usuario = m.id_supervisor WHERE m.id_usuario = :id");
        $st->execute([':id' => $u['id']]);
        $extra['supervisor'] = $st->fetch() ?: null;
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE recusado_em IS NULL AND
        ((id_motorista = :a AND assinado_motorista_em IS NULL) OR (id_vistoriador = :b AND assinado_vistoriador_em IS NULL))");
    $st->execute([':a' => $u['id'], ':b' => $u['id']]);
    $extra['pendencias'] = (int)$st->fetchColumn();
    // Para o admin, a pendência é aprovar os modelos de checklist enviados pelos supervisores
    if ($u['perfil'] === 'admin') {
        $extra['pendencias'] = (int)$pdo->query("SELECT COUNT(*) FROM Checklists WHERE status_aprovacao = 'pendente'")->fetchColumn();
    }
    $ehGestorCtx = in_array($u['perfil'], ['admin', 'supervisor'], true);
    $extra['locacoes_pendentes'] = $ehGestorCtx ? (int)$pdo->query("SELECT COUNT(*) FROM Locacoes WHERE status = 'pendente'")->fetchColumn() : 0;
    $extra['clientes_pendentes'] = $ehGestorCtx ? (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE perfil = 'cliente' AND status = 'pendente'")->fetchColumn() : 0;
    axion_json(['sucesso' => true, 'usuario' => $u, 'csrf' => axion_csrf_token()] + $extra);
}

// 1. BUSCA GERAL / FILTRADA (JSON)
if ($acao === 'buscar') {
    $tipo = $_GET['tipo'] ?? 'veiculos';
    $u = axion_exigir_perfil($tipo === 'usuarios' ? ['admin', 'supervisor'] : AXION_PERFIS_INTERNOS);
    $termo = trim($_GET['q'] ?? '');
    $digitos = axion_digitos($termo);
    $filtroExtra = trim($_GET['filtro'] ?? '');
    $limite = min(250, max(10, (int)($_GET['limite'] ?? 100)));

    if ($tipo === 'veiculos') {
        $sql = "SELECT id_veiculo, placa, marca, modelo, marca_modelo, ano, cor, km_rodado, renavam, chassi, status, finalidade, foto
                FROM Veiculos WHERE 1=1";
        $params = [];
        // Motorista trabalha só com a frota da empresa; os carros de locação são do cliente
        $finalidade = $u['perfil'] === 'motorista' ? 'frota' : ($_GET['finalidade'] ?? '');
        if (in_array($finalidade, ['frota', 'locacao'], true)) {
            $sql .= " AND finalidade = :fin";
            $params[':fin'] = $finalidade;
        }
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
        $dados = array_map(function ($r) {
            $r['foto_url'] = axion_url_foto_veiculo((int)$r['id_veiculo'], $r['foto']);
            unset($r['foto']);
            return $r;
        }, $stmt->fetchAll());
        axion_json(['sucesso' => true, 'dados' => $dados]);
    }

    if ($tipo === 'usuarios') {
        $sql = "SELECT u.id_usuario, u.nome, u.sobrenome, u.email, u.cpf_matricula, u.perfil, u.categoria_cnh, u.cnh, u.validade_cnh,
                       u.cep, u.logradouro, u.numero, u.bairro, u.cidade, u.estado, u.telefone, u.data_nascimento, u.genero, u.status,
                       u.id_supervisor, s.nome AS supervisor_nome, u.foto
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
        // Supervisor administra motoristas e clientes; contas de supervisor/admin ficam com o admin
        if ($u['perfil'] === 'supervisor') {
            $sql .= " AND u.perfil IN ('motorista', 'cliente')";
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
        $dados = array_map(function ($r) {
            $r['foto_url'] = axion_url_foto((int)$r['id_usuario'], $r['foto']);
            unset($r['foto']);
            return $r;
        }, $stmt->fetchAll());
        axion_json(['sucesso' => true, 'dados' => $dados]);
    }

    if ($tipo === 'checklists') {
        $sql = "SELECT c.id_checklist, c.titulo, c.categoria, c.descricao, c.ativo, c.criado_em, c.id_criador,
                       c.status_aprovacao, c.motivo_rejeicao, c.avaliado_em,
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
        if (in_array($filtroExtra, ['pendente', 'rejeitado', 'aprovado'], true)) {
            $sql .= " AND c.status_aprovacao = :st";
            $params[':st'] = $filtroExtra;
        } elseif ($filtroExtra === 'meus') {
            $sql .= " AND c.id_criador = :meu";
            $params[':meu'] = $u['id'];
        } elseif ($filtroExtra !== '') {
            $sql .= " AND c.categoria = :cat";
            $params[':cat'] = $filtroExtra;
        }
        // Quem não é admin só enxerga modelos aprovados (o supervisor vê também os que ele mesmo criou)
        if ($u['perfil'] === 'supervisor') {
            $sql .= " AND (c.status_aprovacao = 'aprovado' OR c.id_criador = :eu_criador)";
            $params[':eu_criador'] = $u['id'];
        } elseif ($u['perfil'] !== 'admin') {
            $sql .= " AND c.status_aprovacao = 'aprovado'";
        }
        if (!empty($_GET['somente_ativos'])) {
            $sql .= " AND c.ativo = 1 AND c.status_aprovacao = 'aprovado' AND EXISTS (SELECT 1 FROM Checklist_Perguntas p2 WHERE p2.id_checklist = c.id_checklist)";
        }
        $sql .= " ORDER BY FIELD(c.status_aprovacao, 'pendente', 'rejeitado', 'aprovado'), c.ativo DESC, c.id_checklist DESC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        axion_json(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
    }

    if ($tipo === 'vistorias') {
        $sql = "SELECT e.id_execucao, e.id_checklist, e.placa_veiculo, e.nome_motorista, e.nome_vistoriador, e.data_vistoria,
                       e.hora_vistoria, e.km_rodado, e.status_conformidade, e.assinado_em, e.id_motorista, e.id_vistoriador,
                       e.assinado_motorista_em, e.assinado_vistoriador_em, e.recusado_em, e.id_locacao, e.tipo_locacao,
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
    $u = axion_exigir_perfil(AXION_PERFIS_INTERNOS);
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

    $frota = $pdo->query("SELECT status, COUNT(*) AS qtd FROM Veiculos" . ($p === 'motorista' ? " WHERE finalidade = 'frota'" : '') . " GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

    $dados = [
        'perfil' => $p,
        'vistorias' => array_map('intval', array_slice($tot, 0, 6)) + ['ultima' => $tot['ultima']],
        'por_dia' => $serie,
        'frota' => ['ativo' => (int)($frota['ativo'] ?? 0), 'em_rota' => (int)($frota['em_rota'] ?? 0), 'manutencao' => (int)($frota['manutencao'] ?? 0), 'locado' => (int)($frota['locado'] ?? 0)],
        'checklists_ativos' => (int)$pdo->query("SELECT COUNT(*) FROM Checklists WHERE ativo = 1")->fetchColumn(),
    ];
    $stPend = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE recusado_em IS NULL AND
        ((id_motorista = :a AND assinado_motorista_em IS NULL) OR (id_vistoriador = :b AND assinado_vistoriador_em IS NULL))");
    $stPend->execute([':a' => $u['id'], ':b' => $u['id']]);
    $dados['minhas_pendencias'] = (int)$stPend->fetchColumn();
    if ($p === 'admin') {
        $dados['minhas_pendencias'] = (int)$pdo->query("SELECT COUNT(*) FROM Checklists WHERE status_aprovacao = 'pendente'")->fetchColumn();
    }

    if ($p === 'admin' || $p === 'supervisor') {
        $dados['locacoes'] = $pdo->query("SELECT SUM(status = 'pendente') AS pendentes, SUM(status = 'ativa') AS ativas,
                SUM(status = 'ativa' AND data_devolucao < CURDATE()) AS atrasadas FROM Locacoes")->fetch();
        $dados['locacoes'] = array_map('intval', $dados['locacoes']);
    }
    if ($p === 'supervisor') {
        // Vistorias por motorista da equipe nos últimos 30 dias
        $dados['por_motorista'] = $q("SELECT e.nome_motorista AS nome, SUM(e.status_conformidade = 'conforme') AS conforme, SUM(e.status_conformidade = 'nao_conforme') AS nao_conforme
            FROM Checklist_Execucoes e WHERE $esc AND e.data_vistoria >= CURDATE() - INTERVAL 29 DAY
            GROUP BY e.id_motorista, e.nome_motorista ORDER BY COUNT(*) DESC LIMIT 8")->fetchAll();
        // Veículos com mais avarias nos últimos 90 dias
        $dados['top_avarias'] = $q("SELECT e.placa_veiculo AS placa, COUNT(*) AS qtd FROM Checklist_Execucoes e
            WHERE $esc AND e.status_conformidade = 'nao_conforme' AND e.data_vistoria >= CURDATE() - INTERVAL 89 DAY
            GROUP BY e.placa_veiculo ORDER BY qtd DESC LIMIT 5")->fetchAll();
    }
    if ($p === 'admin') {
        // Comparação entre equipes: vistorias de cada supervisor nos últimos 30 dias
        $dados['por_supervisor'] = $pdo->query("SELECT e.nome_vistoriador AS nome, SUM(e.status_conformidade = 'conforme') AS conforme, SUM(e.status_conformidade = 'nao_conforme') AS nao_conforme
            FROM Checklist_Execucoes e WHERE e.data_vistoria >= CURDATE() - INTERVAL 29 DAY
            GROUP BY e.id_vistoriador, e.nome_vistoriador ORDER BY COUNT(*) DESC LIMIT 8")->fetchAll();
        $dados['top_avarias'] = $pdo->query("SELECT e.placa_veiculo AS placa, COUNT(*) AS qtd FROM Checklist_Execucoes e
            WHERE e.status_conformidade = 'nao_conforme' AND e.data_vistoria >= CURDATE() - INTERVAL 89 DAY
            GROUP BY e.placa_veiculo ORDER BY qtd DESC LIMIT 5")->fetchAll();
        $dados['checklists_status'] = $pdo->query("SELECT status_aprovacao, COUNT(*) FROM Checklists GROUP BY status_aprovacao")->fetchAll(PDO::FETCH_KEY_PAIR);
        $dados['supervisores'] = $pdo->query("SELECT s.nome,
                (SELECT COUNT(*) FROM Usuarios m WHERE m.id_supervisor = s.id_usuario AND m.perfil = 'motorista' AND m.status = 'ativo') AS motoristas,
                (SELECT COUNT(*) FROM Checklist_Execucoes e WHERE e.id_vistoriador = s.id_usuario AND e.assinado_vistoriador_em IS NULL AND e.recusado_em IS NULL) AS pendentes,
                (SELECT COUNT(*) FROM Checklists c WHERE c.id_criador = s.id_usuario) AS checklists
            FROM Usuarios s WHERE s.perfil = 'supervisor' AND s.status = 'ativo' ORDER BY s.nome")->fetchAll();
        $dados['usuarios'] = $pdo->query("SELECT perfil, COUNT(*) FROM Usuarios WHERE status = 'ativo' GROUP BY perfil")->fetchAll(PDO::FETCH_KEY_PAIR);
        $dados['usuarios_inativos'] = (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE status <> 'ativo'")->fetchColumn();
    }
    if ($p === 'supervisor') {
        $st = $pdo->prepare("SELECT status_aprovacao, COUNT(*) FROM Checklists WHERE id_criador = :me GROUP BY status_aprovacao");
        $st->execute([':me' => $u['id']]);
        $dados['meus_checklists'] = $st->fetchAll(PDO::FETCH_KEY_PAIR);
        $st = $pdo->prepare("SELECT id_usuario, nome, validade_cnh, categoria_cnh, foto,
                (SELECT MAX(data_vistoria) FROM Checklist_Execucoes x WHERE x.id_motorista = u.id_usuario) AS ultima_vistoria
            FROM Usuarios u WHERE u.id_supervisor = :me AND u.perfil = 'motorista' AND u.status = 'ativo' ORDER BY nome");
        $st->execute([':me' => $u['id']]);
        $dados['equipe'] = array_map(function ($r) {
            $r['foto_url'] = axion_url_foto((int)$r['id_usuario'], $r['foto']);
            unset($r['foto']);
            return $r;
        }, $st->fetchAll());
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
    $sup = $pdo->query("SELECT id_usuario, nome, perfil FROM Usuarios WHERE perfil = 'supervisor' AND status = 'ativo' ORDER BY nome")->fetchAll();
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
                                  cnh, validade_cnh, categoria_cnh, cep, logradouro, numero, bairro, cidade, estado, telefone, status, id_supervisor, foto
                           FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if ($user) {
        $user['foto_url'] = axion_url_foto((int)$user['id_usuario'], $user['foto']);
        unset($user['foto']);
    }
    axion_json(['sucesso' => (bool)$user, 'dados' => $user]);
}

if ($acao === 'obter_veiculo') {
    axion_exigir_perfil(AXION_PERFIS_INTERNOS);
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM Veiculos WHERE id_veiculo = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $veic = $stmt->fetch();
    if ($veic) {
        $veic['foto_url'] = axion_url_foto_veiculo((int)$veic['id_veiculo'], $veic['foto']);
        unset($veic['foto']);
    }
    axion_json(['sucesso' => (bool)$veic, 'dados' => $veic]);
}

if ($acao === 'obter_checklist') {
    $euChk = axion_exigir_perfil();
    if ($euChk['perfil'] === 'cliente') {
        $stOk = $pdo->prepare("SELECT COUNT(*) FROM Checklists WHERE id_checklist = :id AND ativo = 1 AND status_aprovacao = 'aprovado'");
        $stOk->execute([':id' => (int)($_GET['id'] ?? 0)]);
        if (!(int)$stOk->fetchColumn()) axion_json(['sucesso' => false, 'erro' => 'Modelo indisponível.'], 403);
    }
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT c.*, COALESCE(u.nome, 'Sistema Axion') AS criador_nome, a.nome AS avaliador_nome
                           FROM Checklists c LEFT JOIN Usuarios u ON u.id_usuario = c.id_criador LEFT JOIN Usuarios a ON a.id_usuario = c.avaliado_por
                           WHERE c.id_checklist = :id LIMIT 1");
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
    if (in_array($perfil, ['motorista', 'cliente'], true)) {
        // Motorista e cliente precisam de CNH completa (os dois podem conduzir os veículos)
        if (strlen($cnh) !== 11) $erro = 'cnh_obrigatoria';
        elseif ($catCnh === '') $erro = 'categoria_cnh_obrigatoria';
        elseif (!$valCnh) $erro = 'validade_cnh_obrigatoria';
    }
    if (!in_array($perfil, AXION_PERFIS, true)) $erro = 'perfil_invalido';
    // Supervisor só gerencia a própria equipe e clientes, e não promove ninguém a admin/supervisor
    if (!axion_pode_gerenciar($eu, $alvo)) $erro = 'sem_permissao';
    if ($eu['perfil'] === 'supervisor' && $alvo['id_usuario'] != $eu['id'] && in_array($perfil, ['admin', 'supervisor'], true)) $erro = 'sem_permissao';
    // Supervisor que deixa de ser supervisor não pode largar motoristas sem responsável
    if ($alvo['perfil'] === 'supervisor' && $perfil !== 'supervisor') {
        $eq = $pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE id_supervisor = :id AND status = 'ativo'");
        $eq->execute([':id' => $id]);
        if ((int)$eq->fetchColumn() > 0) $erro = 'supervisor_com_equipe';
    }
    // Ninguém muda o próprio nível de acesso
    if ($alvo['id_usuario'] == $eu['id'] && $perfil !== $alvo['perfil']) $erro = 'nao_pode_mudar_proprio_perfil';
    if ($alvo['id_usuario'] == $eu['id'] && $statusConta === 'inativo') $erro = 'nao_pode_desativar_a_si_mesmo';
    if (!axion_validar_cpf($cpf)) $erro = 'cpf_invalido';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erro = 'email_invalido';
    if ($nomeCompleto === '') $erro = 'nome_obrigatorio';

    $supValido = null;
    $temSupervisor = in_array($perfil, ['motorista', 'cliente'], true);
    if ($temSupervisor && $idSupervisor > 0) {
        $s = axion_buscar_usuario($pdo, $idSupervisor);
        if (!$s || $s['perfil'] !== 'supervisor') $erro = 'supervisor_invalido';
        else $supValido = $idSupervisor;
    }
    // Motorista e cliente sempre têm um supervisor responsável (o supervisor logado assume, se ninguém foi escolhido)
    if ($temSupervisor && !$supValido) {
        if ($eu['perfil'] === 'supervisor') $supValido = (int)$eu['id'];
        else $erro = $erro ?: 'supervisor_obrigatorio';
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
    // Foto de perfil enviada pelo gestor na edição (ou removida)
    try {
        $stFotoAnt = $pdo->prepare("SELECT foto FROM Usuarios WHERE id_usuario = :id");
        $stFotoAnt->execute([':id' => $id]);
        $fotoAntiga = $stFotoAnt->fetchColumn() ?: null;
        $novaFoto = axion_receber_foto('foto', 'usuario', $id);
        if ($novaFoto || !empty($_POST['remover_foto'])) {
            $pdo->prepare("UPDATE Usuarios SET foto = :f WHERE id_usuario = :id")->execute([':f' => $novaFoto, ':id' => $id]);
            axion_apagar_foto('usuario', $fotoAntiga);
        }
    } catch (RuntimeException $e) {
        header("Location: ../editarUsuario.html?id=$id&erro=foto_invalida");
        exit;
    }

    // Trocou o supervisor do motorista: as solicitações dele que ainda esperam o supervisor vão para o novo
    if ($temSupervisor && $supValido && (int)$supValido !== (int)$alvo['id_supervisor']) {
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
    if (!axion_pode_gerenciar($eu, $alvo)) {
        header("Location: ../excluirUsuario.html?id=$id&erro=sem_permissao"); exit;
    }
    if ($alvo['perfil'] === 'supervisor') {
        $eq = $pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE id_supervisor = :id AND status = 'ativo'");
        $eq->execute([':id' => $id]);
        if ((int)$eq->fetchColumn() > 0) { header("Location: ../excluirUsuario.html?id=$id&erro=supervisor_com_equipe"); exit; }
    }
    // Quem já participou de vistoria não é apagado (o histórico e as assinaturas precisam continuar
    // apontando para a pessoa). Nesse caso a conta é desativada: não consegue mais entrar no sistema.
    $hist = $pdo->prepare("SELECT (SELECT COUNT(*) FROM Checklist_Execucoes WHERE id_motorista = :a OR id_vistoriador = :b)
                                + (SELECT COUNT(*) FROM Locacoes WHERE id_cliente = :c)");
    $hist->execute([':a' => $id, ':b' => $id, ':c' => $id]);
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
    $finalidade  = ($_POST['finalidade'] ?? '') === 'locacao' ? 'locacao' : 'frota';
    // Carro com pedido ou locação em aberto continua sendo de locação
    $stAberta = $pdo->prepare("SELECT COUNT(*) FROM Locacoes WHERE id_veiculo = :id AND status IN ('pendente', 'ativa')");
    $stAberta->execute([':id' => $id]);
    if ($finalidade === 'frota' && (int)$stAberta->fetchColumn() > 0) { header("Location: ../editarVeiculo.html?id=$id&erro=veiculo_com_locacao_aberta"); exit; }
    // "Locado" acompanha a locação: enquanto houver uma em andamento o carro continua locado
    $stLoc = $pdo->prepare("SELECT COUNT(*) FROM Locacoes WHERE id_veiculo = :id AND status = 'ativa'");
    $stLoc->execute([':id' => $id]);
    if ((int)$stLoc->fetchColumn() > 0) $status = 'locado';
    elseif ($status === 'locado') $status = 'ativo';

    $dup = $pdo->prepare("SELECT COUNT(*) FROM Veiculos WHERE id_veiculo <> :id AND (placa = :placa OR (:ren <> '' AND renavam = :ren2) OR (:ch <> '' AND chassi = :ch2))");
    $dup->execute([':id' => $id, ':placa' => $placa, ':ren' => $renavam, ':ren2' => $renavam, ':ch' => $chassi, ':ch2' => $chassi]);
    if ((int)$dup->fetchColumn() > 0) { header("Location: ../editarVeiculo.html?id=$id&erro=placa_renavam_ou_chassi_duplicado"); exit; }

    $stmt = $pdo->prepare("
        UPDATE Veiculos SET
            placa = :placa, marca = :marca, modelo = :modelo, marca_modelo = :marca_modelo,
            ano = :ano, cor = :cor, km_rodado = :km, renavam = :renavam, chassi = :chassi, status = :status, finalidade = :fin
        WHERE id_veiculo = :id
    ");
    $stmt->execute([
        ':placa' => $placa, ':marca' => $marca, ':modelo' => $modelo, ':marca_modelo' => $marcaModelo, ':ano' => $ano,
        ':cor' => $cor, ':km' => $km, ':renavam' => $renavam, ':chassi' => $chassi, ':status' => $status, ':fin' => $finalidade, ':id' => $id
    ]);
    try {
        $stFotoAnt = $pdo->prepare("SELECT foto FROM Veiculos WHERE id_veiculo = :id");
        $stFotoAnt->execute([':id' => $id]);
        $fotoAntiga = $stFotoAnt->fetchColumn() ?: null;
        $novaFoto = axion_receber_foto('foto', 'veiculo', $id, 5);
        if ($novaFoto || !empty($_POST['remover_foto'])) {
            $pdo->prepare("UPDATE Veiculos SET foto = :f WHERE id_veiculo = :id")->execute([':f' => $novaFoto, ':id' => $id]);
            axion_apagar_foto('veiculo', $fotoAntiga);
        }
    } catch (RuntimeException $e) {
        header("Location: ../editarVeiculo.html?id=$id&erro=foto_invalida");
        exit;
    }
    header("Location: ../oqfazer.php?aba=veiculos&sucesso=veiculo_editado");
    exit;
}

// 6. EXCLUIR VEÍCULO
if ($acao === 'excluir_veiculo' && $ehPost) {
    axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_veiculo'] ?? 0);
    if ($id <= 0) { header("Location: ../excluirVeiculo.html?erro=veiculo_nao_selecionado"); exit; }
    $hist = $pdo->prepare("SELECT (SELECT COUNT(*) FROM Checklist_Execucoes WHERE id_veiculo = :id)
                                + (SELECT COUNT(*) FROM Locacoes WHERE id_veiculo = :id2)");
    $hist->execute([':id' => $id, ':id2' => $id]);
    if ((int)$hist->fetchColumn() > 0) {
        // Carro com vistorias não é apagado, para não perder o histórico
        header("Location: ../excluirVeiculo.html?id=$id&erro=veiculo_com_historico");
        exit;
    }
    $stFotoAnt = $pdo->prepare("SELECT foto FROM Veiculos WHERE id_veiculo = :id");
    $stFotoAnt->execute([':id' => $id]);
    axion_apagar_foto('veiculo', $stFotoAnt->fetchColumn() ?: null);
    $pdo->prepare("DELETE FROM Veiculos WHERE id_veiculo = :id")->execute([':id' => $id]);
    header("Location: ../oqfazer.php?aba=veiculos&sucesso=veiculo_excluido");
    exit;
}

/** Supervisor só mexe nos modelos que ele mesmo criou; o admin mexe em todos. */
function axion_pode_gerir_checklist(PDO $pdo, array $eu, int $id): bool {
    if ($eu['perfil'] === 'admin') return true;
    $st = $pdo->prepare("SELECT id_criador FROM Checklists WHERE id_checklist = :id");
    $st->execute([':id' => $id]);
    return (int)$st->fetchColumn() === $eu['id'];
}

// 7. SALVAR EDIÇÃO DE CHECKLIST
if ($acao === 'salvar_edicao_checklist' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id        = (int)($_POST['id_checklist'] ?? 0);
    if ($id > 0 && !axion_pode_gerir_checklist($pdo, $eu, $id)) { header("Location: ../editarChecklist.html?id=$id&erro=checklist_de_outro"); exit; }
    $titulo    = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'outros');
    if (!in_array($categoria, AXION_CATEGORIAS_CHECKLIST, true)) $categoria = 'outros';
    $descricao = trim($_POST['descricao'] ?? '');

    if ($id <= 0 || $titulo === '') { header("Location: ../editarChecklist.html?erro=checklist_nao_selecionado"); exit; }
    $pdo->beginTransaction();
    // Salvar um modelo arquivado volta a deixá-lo disponível.
    // Alteração feita pelo supervisor volta para a aprovação do admin; a do admin já sai aprovada.
    $novoStatus = $eu['perfil'] === 'admin' ? 'aprovado' : 'pendente';
    $pdo->prepare("UPDATE Checklists SET titulo = :titulo, categoria = :cat, descricao = :desc, ativo = 1,
                   status_aprovacao = :st, motivo_rejeicao = NULL,
                   avaliado_por = IF(:st2 = 'aprovado', :eu, NULL), avaliado_em = IF(:st3 = 'aprovado', NOW(), NULL)
                   WHERE id_checklist = :id")
        ->execute([':titulo' => $titulo, ':cat' => $categoria, ':desc' => $descricao, ':st' => $novoStatus, ':st2' => $novoStatus, ':st3' => $novoStatus, ':eu' => $eu['id'], ':id' => $id]);

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
    header("Location: ../oqfazer.php?aba=checklists&sucesso=" . ($novoStatus === 'pendente' ? 'checklist_enviado_aprovacao' : 'checklist_editado'));
    exit;
}

// 8. EXCLUIR CHECKLIST
if ($acao === 'excluir_checklist' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_checklist'] ?? 0);
    if ($id <= 0) { header("Location: ../excluirChecklist.html?erro=checklist_nao_selecionado"); exit; }
    if (!axion_pode_gerir_checklist($pdo, $eu, $id)) { header("Location: ../excluirChecklist.html?id=$id&erro=checklist_de_outro"); exit; }
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

// 8b. APROVAR / REJEITAR MODELO DE CHECKLIST (só admin)
if (in_array($acao, ['aprovar_checklist', 'rejeitar_checklist'], true) && $ehPost) {
    $eu = axion_exigir_perfil(['admin']);
    axion_validar_csrf();
    $id = (int)($_POST['id_checklist'] ?? 0);
    $st = $pdo->prepare("SELECT status_aprovacao FROM Checklists WHERE id_checklist = :id");
    $st->execute([':id' => $id]);
    $atual = $st->fetchColumn();
    if ($atual === false) axion_json(['sucesso' => false, 'erro' => 'Modelo não encontrado.'], 404);
    if ($atual !== 'pendente') axion_json(['sucesso' => false, 'erro' => 'Este modelo não está aguardando aprovação.'], 409);
    if ($acao === 'aprovar_checklist') {
        $pdo->prepare("UPDATE Checklists SET status_aprovacao = 'aprovado', motivo_rejeicao = NULL, avaliado_por = :eu, avaliado_em = NOW() WHERE id_checklist = :id")
            ->execute([':eu' => $eu['id'], ':id' => $id]);
        axion_json(['sucesso' => true, 'redirecionar' => "verChecklist.html?id=$id&sucesso=aprovado"]);
    }
    $motivo = trim((string)($_POST['motivo'] ?? ''));
    if (mb_strlen($motivo) < 5) axion_json(['sucesso' => false, 'erro' => 'Explique o motivo da rejeição para o supervisor poder corrigir.'], 422);
    $pdo->prepare("UPDATE Checklists SET status_aprovacao = 'rejeitado', motivo_rejeicao = :m, avaliado_por = :eu, avaliado_em = NOW() WHERE id_checklist = :id")
        ->execute([':m' => mb_substr($motivo, 0, 2000), ':eu' => $eu['id'], ':id' => $id]);
    axion_json(['sucesso' => true, 'redirecionar' => "verChecklist.html?id=$id&sucesso=rejeitado"]);
}

// 9. REGISTRAR PREENCHIMENTO DE CHECKLIST / VISTORIA
if ($acao === 'registrar_vistoria' && $ehPost) {
    // Vistoria é trabalho operacional: supervisor e motorista (o admin cuida da gestão)
    $eu = axion_exigir_perfil(['supervisor', 'motorista', 'cliente']);
    axion_validar_csrf();
    $idLocacaoPost = (int)($_POST['id_locacao'] ?? 0);
    // Correção: quem registrou pode alterar as respostas enquanto ninguém assinou
    $idEditar = (int)($_POST['id_execucao_editar'] ?? 0);
    $voltar = function (string $erro) use ($idLocacaoPost, $idEditar) {
        $extra = $idEditar ? "&editar=$idEditar" : ($idLocacaoPost ? "&locacao=$idLocacaoPost" : '');
        header("Location: ../cadcheck.html?erro=$erro$extra");
        exit;
    };
    $edicao = null;
    if ($idEditar) {
        $edicao = axion_carregar_vistoria($pdo, $eu, $idEditar);
        if (!$edicao || !axion_vistoria_corrigivel($edicao, $eu)) {
            header("Location: ../verVistoria.html?id=$idEditar&erro=vistoria_nao_editavel");
            exit;
        }
        $_POST['id_veiculo'] = $edicao['id_veiculo'];
    }
    // Locação: o cliente faz a vistoria da própria locação; o supervisor pode fazer junto com o cliente
    // (é o supervisor quem refaz quando uma vistoria do cliente foi recusada)
    $locacaoVist = null;
    $tipoLocacao = null;
    if ($edicao) {
        if ($edicao['id_locacao']) {
            $locacaoVist = axion_locacao_ativa($pdo, (int)$edicao['id_locacao']);
            $tipoLocacao = $edicao['tipo_locacao'];
        }
    } elseif ($eu['perfil'] === 'cliente' || ($eu['perfil'] === 'supervisor' && $idLocacaoPost)) {
        $locacaoVist = $eu['perfil'] === 'cliente'
            ? axion_locacao_do_cliente($pdo, (int)$eu['id'], $idLocacaoPost)
            : axion_locacao_ativa($pdo, $idLocacaoPost);
        if (!$locacaoVist) $voltar('locacao_invalida');
        $tipoLocacao = axion_proxima_vistoria_locacao($pdo, $locacaoVist);
        if (!$tipoLocacao) $voltar('vistoria_locacao_em_andamento');
        if ($eu['perfil'] === 'cliente' && axion_vistoria_locacao_recusada($pdo, $locacaoVist, $tipoLocacao)) $voltar('vistoria_com_supervisor');
        if ($tipoLocacao === 'retirada' && $locacaoVist['data_retirada'] > date('Y-m-d')) $voltar('retirada_antes_da_data');
        $_POST['id_veiculo'] = $locacaoVist['id_veiculo'];
    }

    $idChecklist = (int)($_POST['id_checklist'] ?? 0);
    $chk = $pdo->prepare("SELECT c.ativo, c.status_aprovacao, (SELECT COUNT(*) FROM Checklist_Perguntas p WHERE p.id_checklist = c.id_checklist) AS qtd FROM Checklists c WHERE c.id_checklist = :id");
    $chk->execute([':id' => $idChecklist]);
    $modelo = $chk->fetch();
    if (!$modelo || (int)$modelo['ativo'] !== 1) $voltar('checklist_invalido');
    if ($modelo['status_aprovacao'] !== 'aprovado') $voltar('checklist_nao_aprovado');
    if ((int)$modelo['qtd'] === 0) $voltar('checklist_sem_perguntas');

    $idVeiculo = (int)($_POST['id_veiculo'] ?? 0);
    $stV = $pdo->prepare("SELECT id_veiculo, placa, km_rodado, finalidade FROM Veiculos WHERE id_veiculo = :id");
    $stV->execute([':id' => $idVeiculo]);
    $veiculo = $stV->fetch();
    if (!$veiculo) $voltar('veiculo_invalido');
    // Motorista só vistoria carros da frota da empresa (os de locação são vistoriados pelo cliente)
    if ($eu['perfil'] === 'motorista' && $veiculo['finalidade'] === 'locacao') $voltar('veiculo_de_locacao');

    $km = (int)($_POST['kmrodado'] ?? -1);
    if ($km < 0) $voltar('km_invalido');
    // Hodômetro não volta: o km informado não pode ser menor que o último registrado.
    // Na correção, se o km atual do carro veio desta própria vistoria, vale o maior km das outras vistorias.
    $kmBase = (int)$veiculo['km_rodado'];
    $kmVeioDaVistoria = $edicao && (int)$veiculo['km_rodado'] === (int)$edicao['km_rodado'];
    if ($kmVeioDaVistoria) {
        $stK = $pdo->prepare("SELECT COALESCE(MAX(km_rodado), 0) FROM Checklist_Execucoes WHERE id_veiculo = :v AND id_execucao <> :id");
        $stK->execute([':v' => $idVeiculo, ':id' => $edicao['id_execucao']]);
        $kmBase = (int)$stK->fetchColumn();
    }
    if ($km < $kmBase) $voltar('km_menor_que_atual');

    // Motorista: se quem está logado é motorista (ou cliente na locação), é ele mesmo (não dá para escolher outro).
    // Na locação feita pelo supervisor, é o cliente da locação. Na correção, continuam as mesmas pessoas.
    if ($edicao) {
        $motorista = axion_buscar_usuario($pdo, (int)$edicao['id_motorista']);
    } elseif (in_array($eu['perfil'], ['motorista', 'cliente'], true)) {
        $motorista = axion_buscar_usuario($pdo, $eu['id']);
    } elseif ($locacaoVist) {
        $motorista = axion_buscar_usuario($pdo, (int)$locacaoVist['id_cliente']);
    } else {
        $motorista = axion_buscar_usuario($pdo, (int)($_POST['id_motorista'] ?? 0));
        if (!$motorista || $motorista['perfil'] !== 'motorista') $voltar('motorista_invalido');
    }
    if (!$motorista) $voltar('motorista_invalido');

    // Vistoriador: gestor logado é o próprio vistoriador; para motorista, é o supervisor vinculado a ele
    if ($edicao) {
        $vistoriador = axion_buscar_usuario($pdo, (int)$edicao['id_vistoriador']);
    } elseif (axion_eh_gestor($eu)) {
        $vistoriador = axion_buscar_usuario($pdo, $eu['id']);
    } elseif (!empty($motorista['id_supervisor'])) {
        $vistoriador = axion_buscar_usuario($pdo, (int)$motorista['id_supervisor']);
    } else {
        $vistoriador = axion_buscar_usuario($pdo, (int)($_POST['id_vistoriador'] ?? 0));
    }
    if (!$vistoriador || $vistoriador['perfil'] !== 'supervisor' || $vistoriador['status'] !== 'ativo') $voltar('vistoriador_invalido');
    if ($motorista['status'] !== 'ativo') $voltar('motorista_invalido');
    // Motorista com CNH vencida não pode assumir veículo
    if (!empty($motorista['validade_cnh']) && $motorista['validade_cnh'] < date('Y-m-d')) $voltar('cnh_vencida');
    if ($vistoriador['id_usuario'] == $motorista['id_usuario']) $voltar('motorista_e_vistoriador_iguais');
    if ($eu['perfil'] === 'cliente' && $motorista['perfil'] !== 'cliente') $voltar('locacao_invalida');

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
    if ($edicao) {
        // Mesma vistoria, respostas novas (sem assinaturas, então ninguém concordou com a versão anterior)
        $limparAvaria = $status === 'conforme';
        $pdo->prepare("UPDATE Checklist_Execucoes SET id_checklist = :chk, km_rodado = :km, status_conformidade = :st, observacoes = :obs"
                      . ($limparAvaria ? ", detalhes_nao_conformidade = NULL, foto_evidencia = NULL" : '') . " WHERE id_execucao = :id")
            ->execute([':chk' => $idChecklist, ':km' => $km, ':st' => $status, ':obs' => mb_substr(trim($_POST['observacoes'] ?? ''), 0, 2000), ':id' => $idEditar]);
        if ($limparAvaria && $edicao['foto_evidencia']) @unlink(__DIR__ . '/../uploads/vistorias/' . basename($edicao['foto_evidencia']));
        $pdo->prepare("DELETE FROM Checklist_Respostas WHERE id_execucao = :id")->execute([':id' => $idEditar]);
        $stR = $pdo->prepare("INSERT INTO Checklist_Respostas (id_execucao, id_pergunta, id_pergunta_pai, pergunta_texto, resposta, resposta_esperada, conforme) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($linhas as $l) $stR->execute(array_merge([$idEditar], $l));
        $novoKm = $kmVeioDaVistoria ? max($kmBase, $km) : max((int)$veiculo['km_rodado'], $km);
        $pdo->prepare("UPDATE Veiculos SET km_rodado = :km WHERE id_veiculo = :id")->execute([':km' => $novoKm, ':id' => $veiculo['id_veiculo']]);
        $pdo->commit();
        header("Location: ../" . ($status === 'nao_conforme' ? 'detalhesNaoConformidade.html' : 'assinaturaChecklist.html') . "?id=$idEditar&corrigida=1");
        exit;
    }
    $pdo->prepare("
        INSERT INTO Checklist_Execucoes (
            id_checklist, id_veiculo, placa_veiculo, id_motorista, nome_motorista, id_vistoriador, nome_vistoriador,
            id_registrado_por, data_vistoria, hora_vistoria, km_rodado, status_conformidade, observacoes, id_locacao, tipo_locacao
        ) VALUES (:chk, :veic, :placa, :id_mot, :mot, :id_vist, :vist, :reg, CURDATE(), CURTIME(), :km, :st, :obs, :loc, :tloc)
    ")->execute([
        ':chk' => $idChecklist, ':veic' => $veiculo['id_veiculo'], ':placa' => $veiculo['placa'],
        ':id_mot' => $motorista['id_usuario'], ':mot' => $motorista['nome'],
        ':id_vist' => $vistoriador['id_usuario'], ':vist' => $vistoriador['nome'],
        ':reg' => $eu['id'], ':km' => $km, ':st' => $status, ':obs' => mb_substr(trim($_POST['observacoes'] ?? ''), 0, 2000),
        ':loc' => $locacaoVist ? $locacaoVist['id_locacao'] : null, ':tloc' => $tipoLocacao
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

/** Quem registrou pode corrigir as respostas enquanto ninguém assinou e ela não foi recusada. */
function axion_vistoria_corrigivel(array $v, array $eu): bool {
    return !$v['recusado_em'] && !$v['assinado_motorista_em'] && !$v['assinado_vistoriador_em']
        && (int)$v['id_registrado_por'] === (int)$eu['id'];
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
    if (in_array($eu['perfil'], ['motorista', 'cliente'], true) && (int)$v['id_motorista'] !== $eu['id']) return null;
    return $v;
}

// 9b. DETALHES DE NÃO CONFORMIDADE (descrição + foto)
// Só pode ser preenchido/alterado antes da primeira assinatura: quem assina depois precisa ver exatamente o que o outro assinou.
if ($acao === 'salvar_nao_conformidade' && $ehPost) {
    $eu = axion_exigir_perfil();
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
    $eu = axion_exigir_perfil();
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
            // Carro de locação continua com o cliente até a devolução; ele vai para a manutenção quando a devolução for registrada
            if (!$v['id_locacao']) $pdo->prepare("UPDATE Veiculos SET status = 'manutencao' WHERE id_veiculo = :v")->execute([':v' => $v['id_veiculo']]);
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
    if ($v['id_veiculo']) {
        $stVF = $pdo->prepare("SELECT foto FROM Veiculos WHERE id_veiculo = :id");
        $stVF->execute([':id' => $v['id_veiculo']]);
        $v['veiculo_foto_url'] = axion_url_foto_veiculo((int)$v['id_veiculo'], $stVF->fetchColumn() ?: null);
    }
    $stF = $pdo->prepare("SELECT id_usuario, foto FROM Usuarios WHERE id_usuario IN (:m, :v)");
    $stF->execute([':m' => (int)$v['id_motorista'], ':v' => (int)$v['id_vistoriador']]);
    foreach ($stF->fetchAll() as $f) {
        if ((int)$f['id_usuario'] === (int)$v['id_motorista']) $v['motorista_foto_url'] = axion_url_foto((int)$f['id_usuario'], $f['foto']);
        if ((int)$f['id_usuario'] === (int)$v['id_vistoriador']) $v['vistoriador_foto_url'] = axion_url_foto((int)$f['id_usuario'], $f['foto']);
    }
    $v['pode_assinar'] = $v['meu_papel'] && !in_array($v['situacao'], ['recusada', 'concluida'], true) && !$v["assinado_{$v['meu_papel']}_em"];
    $v['pode_editar_avaria'] = axion_vistoria_corrigivel($v, $eu);
    $v['pode_corrigir'] = $v['pode_editar_avaria'];
    if ($v['recusado_por']) {
        $r = axion_buscar_usuario($pdo, (int)$v['recusado_por']);
        $v['recusado_por_nome'] = $r['nome'] ?? '';
    }
    // Vistoria de locação: dados da locação e a outra vistoria (retirada x devolução) para comparar
    $v['locacao'] = null;
    if ($v['id_locacao']) {
        $stL = $pdo->prepare("SELECT id_locacao, data_retirada, data_devolucao, status FROM Locacoes WHERE id_locacao = :l");
        $stL->execute([':l' => $v['id_locacao']]);
        $v['locacao'] = $stL->fetch() ?: null;
        $stPar = $pdo->prepare("SELECT id_execucao, tipo_locacao, status_conformidade, data_vistoria, km_rodado, recusado_em, assinado_motorista_em, assinado_vistoriador_em
                                FROM Checklist_Execucoes WHERE id_locacao = :l AND id_execucao <> :id AND recusado_em IS NULL ORDER BY id_execucao DESC");
        $stPar->execute([':l' => $v['id_locacao'], ':id' => $id]);
        $v['outras_vistorias_locacao'] = array_map(function ($x) { $x['situacao'] = axion_situacao($x); return $x; }, $stPar->fetchAll());
    }
    if ($eu['perfil'] === 'cliente') unset($v['motivo_recusa']);
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

// 10b. MEUS DADOS (tela Meu perfil)
if ($acao === 'meus_dados') {
    $eu = axion_exigir_perfil();
    $st = $pdo->prepare("SELECT u.nome, u.email, u.cpf_matricula, u.perfil, u.telefone, u.cnh, u.categoria_cnh, u.validade_cnh,
                                u.cidade, u.estado, u.foto, u.criado_em, s.nome AS supervisor_nome
                         FROM Usuarios u LEFT JOIN Usuarios s ON s.id_usuario = u.id_supervisor WHERE u.id_usuario = :id");
    $st->execute([':id' => $eu['id']]);
    $d = $st->fetch();
    $d['foto_url'] = axion_url_foto($eu['id'], $d['foto']);
    unset($d['foto']);
    axion_json(['sucesso' => true, 'dados' => $d]);
}

// 11. FOTOS: cada usuário troca a própria foto; gestores trocam a foto dos veículos
if ($acao === 'salvar_foto_perfil' && $ehPost) {
    $eu = axion_exigir_perfil();
    axion_validar_csrf();
    try {
        $nome = axion_receber_foto('foto', 'usuario', $eu['id']);
    } catch (RuntimeException $e) {
        axion_json(['sucesso' => false, 'erro' => $e->getMessage()], 422);
    }
    if (!$nome) axion_json(['sucesso' => false, 'erro' => 'Escolha uma imagem.'], 422);
    $st = $pdo->prepare("SELECT foto FROM Usuarios WHERE id_usuario = :id");
    $st->execute([':id' => $eu['id']]);
    axion_apagar_foto('usuario', $st->fetchColumn() ?: null);
    $pdo->prepare("UPDATE Usuarios SET foto = :f WHERE id_usuario = :id")->execute([':f' => $nome, ':id' => $eu['id']]);
    axion_json(['sucesso' => true, 'foto_url' => axion_url_foto($eu['id'], $nome)]);
}

if ($acao === 'remover_foto_perfil' && $ehPost) {
    $eu = axion_exigir_perfil();
    axion_validar_csrf();
    $st = $pdo->prepare("SELECT foto FROM Usuarios WHERE id_usuario = :id");
    $st->execute([':id' => $eu['id']]);
    axion_apagar_foto('usuario', $st->fetchColumn() ?: null);
    $pdo->prepare("UPDATE Usuarios SET foto = NULL WHERE id_usuario = :id")->execute([':id' => $eu['id']]);
    axion_json(['sucesso' => true]);
}

if ($acao === 'salvar_foto_veiculo' && $ehPost) {
    axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_veiculo'] ?? 0);
    $st = $pdo->prepare("SELECT foto FROM Veiculos WHERE id_veiculo = :id");
    $st->execute([':id' => $id]);
    $antiga = $st->fetchColumn();
    if ($antiga === false) axion_json(['sucesso' => false, 'erro' => 'Veículo não encontrado.'], 404);
    try {
        $nome = axion_receber_foto('foto', 'veiculo', $id, 5);
    } catch (RuntimeException $e) {
        axion_json(['sucesso' => false, 'erro' => $e->getMessage()], 422);
    }
    if (!$nome) axion_json(['sucesso' => false, 'erro' => 'Escolha uma imagem.'], 422);
    axion_apagar_foto('veiculo', $antiga ?: null);
    $pdo->prepare("UPDATE Veiculos SET foto = :f WHERE id_veiculo = :id")->execute([':f' => $nome, ':id' => $id]);
    axion_json(['sucesso' => true, 'foto_url' => axion_url_foto_veiculo($id, $nome)]);
}

if ($acao === 'foto_usuario') {
    $eu = axion_exigir_perfil();
    $idFoto = (int)($_GET['id'] ?? 0);
    // Cliente só vê a própria foto
    if ($eu['perfil'] === 'cliente' && $idFoto !== (int)$eu['id']) { http_response_code(403); exit; }
    $st = $pdo->prepare("SELECT foto FROM Usuarios WHERE id_usuario = :id");
    $st->execute([':id' => $idFoto]);
    axion_servir_foto('usuario', $st->fetchColumn() ?: null);
}

if ($acao === 'foto_veiculo') {
    axion_exigir_perfil();
    $st = $pdo->prepare("SELECT foto FROM Veiculos WHERE id_veiculo = :id");
    $st->execute([':id' => (int)($_GET['id'] ?? 0)]);
    axion_servir_foto('veiculo', $st->fetchColumn() ?: null);
}

// 12. TROCAR A PRÓPRIA SENHA (todos começam com mudar123)
if ($acao === 'trocar_senha' && $ehPost) {
    $eu = axion_exigir_perfil();
    axion_validar_csrf();
    $atual = (string)($_POST['senha_atual'] ?? '');
    $nova = (string)($_POST['senha_nova'] ?? '');
    $u = axion_buscar_usuario($pdo, $eu['id']);
    if (!password_verify($atual, $u['senha'])) axion_json(['sucesso' => false, 'erro' => 'A senha atual está incorreta.'], 422);
    if (mb_strlen($nova) < 6) axion_json(['sucesso' => false, 'erro' => 'A nova senha precisa ter pelo menos 6 caracteres.'], 422);
    if ($nova === $atual) axion_json(['sucesso' => false, 'erro' => 'A nova senha precisa ser diferente da atual.'], 422);
    $pdo->prepare("UPDATE Usuarios SET senha = :s WHERE id_usuario = :id")->execute([':s' => password_hash($nova, PASSWORD_DEFAULT), ':id' => $eu['id']]);
    axion_json(['sucesso' => true]);
}

// 13. REDEFINIR A SENHA DE OUTRA PESSOA (quem esqueceu a senha pede ao gestor)
if ($acao === 'redefinir_senha' && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $alvo = axion_buscar_usuario($pdo, (int)($_POST['id_usuario'] ?? 0));
    if (!$alvo) axion_json(['sucesso' => false, 'erro' => 'Usuário não encontrado.'], 404);
    if ((int)$alvo['id_usuario'] === (int)$eu['id']) axion_json(['sucesso' => false, 'erro' => 'Para a sua própria senha use "Meu perfil".'], 422);
    if (!axion_pode_gerenciar($eu, $alvo)) axion_json(['sucesso' => false, 'erro' => 'Você não pode redefinir a senha deste usuário.'], 403);
    $pdo->prepare("UPDATE Usuarios SET senha = :s WHERE id_usuario = :id")
        ->execute([':s' => password_hash('mudar123', PASSWORD_DEFAULT), ':id' => $alvo['id_usuario']]);
    axion_json(['sucesso' => true, 'mensagem' => 'Senha redefinida para mudar123. Peça para a pessoa trocar em "Meu perfil".']);
}

// 14. LOCAÇÃO DE VEÍCULOS
// O cliente vê só os veículos disponíveis e as próprias locações; admin/supervisor aprovam e recebem a devolução.

/** Locação aprovada (ativa), de qualquer cliente. */
function axion_locacao_ativa(PDO $pdo, int $idLocacao): ?array {
    $st = $pdo->prepare("SELECT * FROM Locacoes WHERE id_locacao = :id AND status = 'ativa'");
    $st->execute([':id' => $idLocacao]);
    return $st->fetch() ?: null;
}

/** A última vistoria deste tipo foi recusada? Então o supervisor refaz junto com o cliente. */
function axion_vistoria_locacao_recusada(PDO $pdo, array $loc, string $tipo): bool {
    $v = axion_vistorias_locacao($pdo, (int)$loc['id_locacao']);
    return $v[$tipo] && $v[$tipo]['situacao'] === 'recusada';
}

/** Locação aprovada (ativa) do próprio cliente. */
function axion_locacao_do_cliente(PDO $pdo, int $idCliente, int $idLocacao): ?array {
    $st = $pdo->prepare("SELECT * FROM Locacoes WHERE id_locacao = :id AND id_cliente = :c AND status = 'ativa'");
    $st->execute([':id' => $idLocacao, ':c' => $idCliente]);
    return $st->fetch() ?: null;
}

/** Vistoria mais recente de cada tipo (retirada e devolução) de uma locação, com a situação das assinaturas. */
function axion_vistorias_locacao(PDO $pdo, int $idLocacao): array {
    $st = $pdo->prepare("SELECT id_execucao, tipo_locacao, status_conformidade, km_rodado, enviado_manutencao, data_vistoria,
                                assinado_motorista_em, assinado_vistoriador_em, recusado_em, motivo_recusa, nome_vistoriador
                         FROM Checklist_Execucoes WHERE id_locacao = :l ORDER BY id_execucao DESC");
    $st->execute([':l' => $idLocacao]);
    $out = ['retirada' => null, 'devolucao' => null];
    foreach ($st->fetchAll() as $x) {
        $x['situacao'] = axion_situacao($x);
        $tipo = $x['tipo_locacao'];
        if (array_key_exists($tipo, $out) && $out[$tipo] === null) $out[$tipo] = $x;
    }
    return $out;
}

/** Qual vistoria o cliente pode fazer agora: retirada, devolução ou nenhuma (aguardando assinaturas). */
function axion_proxima_vistoria_locacao(PDO $pdo, array $loc): ?string {
    $v = axion_vistorias_locacao($pdo, (int)$loc['id_locacao']);
    $ret = $v['retirada'];
    if (!$ret || $ret['situacao'] === 'recusada') return 'retirada';
    if ($ret['situacao'] !== 'concluida') return null;
    $dev = $v['devolucao'];
    if (!$dev || $dev['situacao'] === 'recusada') return 'devolucao';
    return null;
}

/**
 * Fase da locação para a tela. Locação aprovada só fica "em andamento" depois da vistoria de retirada
 * assinada pelo cliente e pelo supervisor (testemunha).
 */
function axion_fase_locacao(array $l, ?array $vist = null): string {
    if ($l['status'] !== 'ativa') return $l['status'];
    $hoje = date('Y-m-d');
    $retiradaOk = $vist && $vist['retirada'] && $vist['retirada']['situacao'] === 'concluida';
    if (!$retiradaOk) return $l['data_retirada'] > $hoje ? 'aprovada' : 'aguardando_retirada';
    if ($l['data_devolucao'] < $hoje) return 'atrasada';
    return 'em_andamento';
}

/** Dias entre hoje e a data de devolução (negativo = atrasada). */
function axion_dias_para(string $data): int {
    return (int)floor((strtotime($data) - strtotime(date('Y-m-d'))) / 86400);
}

const AXION_SQL_VEICULOS_DISPONIVEIS = "SELECT v.id_veiculo, v.marca, v.modelo, v.marca_modelo, v.ano, v.cor, v.foto
    FROM Veiculos v
    WHERE v.status = 'ativo' AND v.finalidade = 'locacao'
      AND NOT EXISTS (SELECT 1 FROM Locacoes l WHERE l.id_veiculo = v.id_veiculo AND l.status IN ('pendente', 'ativa'))";

if ($acao === 'locacao_veiculos') {
    axion_exigir_perfil();
    $dados = array_map(function ($v) {
        $v['foto_url'] = axion_url_foto_veiculo((int)$v['id_veiculo'], $v['foto']);
        unset($v['foto']);
        return $v;
    }, $pdo->query(AXION_SQL_VEICULOS_DISPONIVEIS . " ORDER BY v.marca_modelo, v.ano DESC")->fetchAll());
    axion_json(['sucesso' => true, 'dados' => $dados]);
}

if ($acao === 'minhas_locacoes') {
    $eu = axion_exigir_perfil(['cliente']);
    $st = $pdo->prepare("SELECT l.id_locacao, l.data_retirada, l.data_devolucao, l.observacao, l.status, l.motivo_recusa,
                                l.devolvido_em, l.criado_em, v.id_veiculo, v.placa, v.marca_modelo, v.ano, v.cor, v.foto
                         FROM Locacoes l JOIN Veiculos v ON v.id_veiculo = l.id_veiculo
                         WHERE l.id_cliente = :me
                         ORDER BY FIELD(l.status, 'ativa', 'pendente', 'devolvida', 'recusada', 'cancelada'), l.data_retirada DESC, l.id_locacao DESC");
    $st->execute([':me' => $eu['id']]);
    $dados = array_map(function ($l) use ($pdo) {
        $l['vistorias'] = axion_vistorias_locacao($pdo, (int)$l['id_locacao']);
        $l['fase'] = axion_fase_locacao($l, $l['vistorias']);
        $l['proxima_vistoria'] = $l['status'] === 'ativa' ? axion_proxima_vistoria_locacao($pdo, $l) : null;
        // Vistoria recusada: o cliente não refaz sozinho, o supervisor resolve junto com ele
        $l['com_supervisor'] = $l['proxima_vistoria'] && $l['vistorias'][$l['proxima_vistoria']]
            && $l['vistorias'][$l['proxima_vistoria']]['situacao'] === 'recusada';
        foreach (['retirada', 'devolucao'] as $t) {
            if ($l['vistorias'][$t] && $l['vistorias'][$t]['situacao'] === 'recusada') $l['vistorias'][$t]['motivo_recusa'] = null;
        }
        $l['dias_para_devolver'] = $l['status'] === 'ativa' ? axion_dias_para($l['data_devolucao']) : null;
        $l['dias'] = axion_dias_para($l['data_devolucao']) - axion_dias_para($l['data_retirada']) + 1;
        // A placa só aparece quando o carro é dele (locação aprovada)
        if (!in_array($l['status'], ['ativa', 'devolvida'], true)) $l['placa'] = null;
        $l['foto_url'] = axion_url_foto_veiculo((int)$l['id_veiculo'], $l['foto']);
        unset($l['foto']);
        return $l;
    }, $st->fetchAll());
    $stC = $pdo->prepare("SELECT cnh, categoria_cnh, validade_cnh FROM Usuarios WHERE id_usuario = :me");
    $stC->execute([':me' => $eu['id']]);
    $disponiveis = (int)$pdo->query("SELECT COUNT(*) FROM (" . AXION_SQL_VEICULOS_DISPONIVEIS . ") x")->fetchColumn();
    axion_json(['sucesso' => true, 'dados' => $dados, 'cnh' => $stC->fetch(), 'disponiveis' => $disponiveis]);
}

if ($acao === 'solicitar_locacao' && $ehPost) {
    $eu = axion_exigir_perfil(['cliente']);
    axion_validar_csrf();
    $idVeiculo = (int)($_POST['id_veiculo'] ?? 0);
    $retirada  = (string)($_POST['data_retirada'] ?? '');
    $devolucao = (string)($_POST['data_devolucao'] ?? '');
    $obs       = mb_substr(trim((string)($_POST['observacao'] ?? '')), 0, 255);
    $dataOk = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false && date('Y-m-d', strtotime($d)) === $d;
    $hoje = date('Y-m-d');
    $erro = null;
    if (!$dataOk($retirada) || !$dataOk($devolucao)) $erro = 'Informe as datas de retirada e de devolução.';
    elseif ($retirada < $hoje) $erro = 'A retirada não pode ser em uma data que já passou.';
    elseif ($retirada > date('Y-m-d', strtotime('+180 days'))) $erro = 'A retirada pode ser marcada para no máximo 180 dias à frente.';
    elseif ($devolucao < $retirada) $erro = 'A devolução precisa ser no mesmo dia ou depois da retirada.';
    elseif (axion_dias_para($devolucao) - axion_dias_para($retirada) + 1 > 90) $erro = 'Cada locação pode ter no máximo 90 dias.';
    if (!$erro) {
        $stC = $pdo->prepare("SELECT cnh, validade_cnh FROM Usuarios WHERE id_usuario = :me");
        $stC->execute([':me' => $eu['id']]);
        $cnh = $stC->fetch();
        if (empty($cnh['cnh']) || empty($cnh['validade_cnh'])) $erro = 'Sua CNH não está cadastrada. Peça para a empresa atualizar o seu cadastro.';
        elseif ($cnh['validade_cnh'] < $devolucao) $erro = 'Sua CNH vence antes da data de devolução (' . date('d/m/Y', strtotime($cnh['validade_cnh'])) . ').';
    }
    if ($erro) axion_json(['sucesso' => false, 'erro' => $erro], 422);
    $pdo->beginTransaction();
    $st = $pdo->prepare(AXION_SQL_VEICULOS_DISPONIVEIS . " AND v.id_veiculo = :id FOR UPDATE");
    $st->execute([':id' => $idVeiculo]);
    if (!$st->fetch()) {
        $pdo->rollBack();
        axion_json(['sucesso' => false, 'erro' => 'Este veículo não está mais disponível. Escolha outro.'], 409);
    }
    $pdo->prepare("INSERT INTO Locacoes (id_veiculo, id_cliente, data_retirada, data_devolucao, observacao) VALUES (:v, :c, :r, :d, :o)")
        ->execute([':v' => $idVeiculo, ':c' => $eu['id'], ':r' => $retirada, ':d' => $devolucao, ':o' => $obs ?: null]);
    $pdo->commit();
    axion_json(['sucesso' => true, 'mensagem' => 'Pedido enviado! A empresa vai analisar e você acompanha a resposta em "Minhas locações".']);
}

// Dados para o cliente preencher a vistoria de retirada/devolução da própria locação
if ($acao === 'dados_vistoria_locacao') {
    $eu = axion_exigir_perfil(['cliente', 'supervisor']);
    $ehCliente = $eu['perfil'] === 'cliente';
    $loc = $ehCliente ? axion_locacao_do_cliente($pdo, (int)$eu['id'], (int)($_GET['id'] ?? 0)) : axion_locacao_ativa($pdo, (int)($_GET['id'] ?? 0));
    if (!$loc) axion_json(['sucesso' => false, 'erro' => 'Locação não encontrada ou ainda não aprovada.'], 404);
    $tipo = axion_proxima_vistoria_locacao($pdo, $loc);
    $comSupervisor = $tipo && axion_vistoria_locacao_recusada($pdo, $loc, $tipo);
    $stV = $pdo->prepare("SELECT id_veiculo, placa, marca_modelo, ano, cor, km_rodado, foto FROM Veiculos WHERE id_veiculo = :v");
    $stV->execute([':v' => $loc['id_veiculo']]);
    $veic = $stV->fetch();
    $veic['foto_url'] = axion_url_foto_veiculo((int)$veic['id_veiculo'], $veic['foto']);
    unset($veic['foto']);
    $stS = $pdo->prepare("SELECT c.nome AS cliente, s.nome AS supervisor FROM Usuarios c LEFT JOIN Usuarios s ON s.id_usuario = c.id_supervisor WHERE c.id_usuario = :c");
    $stS->execute([':c' => $loc['id_cliente']]);
    $pessoas = $stS->fetch();
    $modelos = $pdo->query("SELECT c.id_checklist, c.titulo, c.categoria FROM Checklists c
                            WHERE c.ativo = 1 AND c.status_aprovacao = 'aprovado'
                              AND EXISTS (SELECT 1 FROM Checklist_Perguntas p WHERE p.id_checklist = c.id_checklist)
                            ORDER BY c.categoria = 'locacao' DESC, c.titulo")->fetchAll();
    $padrao = null;
    foreach ($modelos as $m) if ($m['categoria'] === 'locacao') { $padrao = (int)$m['id_checklist']; break; }
    // O cliente usa sempre o modelo padrão da locação; o supervisor pode escolher outro
    if ($ehCliente && $padrao) $modelos = array_values(array_filter($modelos, fn($m) => (int)$m['id_checklist'] === $padrao));
    axion_json(['sucesso' => true, 'locacao' => $loc, 'tipo' => $tipo, 'veiculo' => $veic,
                'cliente' => $pessoas['cliente'] ?? null,
                'testemunha' => $ehCliente ? ($pessoas['supervisor'] ?? null) : $eu['nome'],
                'checklists' => $modelos, 'padrao' => $padrao, 'eu_perfil' => $eu['perfil'],
                'com_supervisor' => $ehCliente && $comSupervisor,
                'pode_hoje' => $tipo !== 'retirada' || $loc['data_retirada'] <= date('Y-m-d')]);
}

if ($acao === 'cancelar_locacao' && $ehPost) {
    $eu = axion_exigir_perfil(['cliente']);
    axion_validar_csrf();
    $st = $pdo->prepare("UPDATE Locacoes SET status = 'cancelada' WHERE id_locacao = :id AND id_cliente = :me AND status = 'pendente'");
    $st->execute([':id' => (int)($_POST['id_locacao'] ?? 0), ':me' => $eu['id']]);
    if (!$st->rowCount()) axion_json(['sucesso' => false, 'erro' => 'Só dá para cancelar um pedido que ainda aguarda aprovação.'], 409);
    axion_json(['sucesso' => true, 'mensagem' => 'Pedido cancelado.']);
}

if ($acao === 'listar_locacoes') {
    axion_exigir_perfil(['admin', 'supervisor']);
    $filtro = $_GET['filtro'] ?? '';
    $sql = "SELECT l.*, c.nome AS cliente_nome, c.email AS cliente_email, c.telefone AS cliente_telefone,
                   c.categoria_cnh, c.validade_cnh, c.foto AS cliente_foto, sp.nome AS testemunha_nome,
                   v.placa, v.marca_modelo, v.ano, v.cor, v.km_rodado, v.status AS veiculo_status, v.foto AS veiculo_foto,
                   a.nome AS avaliado_por_nome, rb.nome AS recebido_por_nome
            FROM Locacoes l
            JOIN Usuarios c ON c.id_usuario = l.id_cliente
            LEFT JOIN Usuarios sp ON sp.id_usuario = c.id_supervisor
            JOIN Veiculos v ON v.id_veiculo = l.id_veiculo
            LEFT JOIN Usuarios a ON a.id_usuario = l.avaliado_por
            LEFT JOIN Usuarios rb ON rb.id_usuario = l.recebido_por
            WHERE 1=1";
    if ($filtro === 'pendente' || $filtro === 'ativa') $sql .= " AND l.status = '$filtro'";
    elseif ($filtro === 'atrasada') $sql .= " AND l.status = 'ativa' AND l.data_devolucao < CURDATE()";
    elseif ($filtro === 'encerradas') $sql .= " AND l.status IN ('devolvida', 'recusada', 'cancelada')";
    $sql .= " ORDER BY FIELD(l.status, 'pendente', 'ativa', 'devolvida', 'recusada', 'cancelada'), l.data_devolucao ASC, l.id_locacao DESC LIMIT 250";
    $dados = array_map(function ($l) use ($pdo) {
        $l['vistorias'] = axion_vistorias_locacao($pdo, (int)$l['id_locacao']);
        $l['fase'] = axion_fase_locacao($l, $l['vistorias']);
        $l['proxima_vistoria'] = $l['status'] === 'ativa' ? axion_proxima_vistoria_locacao($pdo, $l) : null;
        $l['dias_para_devolver'] = $l['status'] === 'ativa' ? axion_dias_para($l['data_devolucao']) : null;
        $l['dias'] = axion_dias_para($l['data_devolucao']) - axion_dias_para($l['data_retirada']) + 1;
        $l['cnh_vence_antes'] = !$l['validade_cnh'] || $l['validade_cnh'] < $l['data_devolucao'];
        $l['cliente_foto_url'] = axion_url_foto((int)$l['id_cliente'], $l['cliente_foto']);
        $l['veiculo_foto_url'] = axion_url_foto_veiculo((int)$l['id_veiculo'], $l['veiculo_foto']);
        unset($l['cliente_foto'], $l['veiculo_foto']);
        return $l;
    }, $pdo->query($sql)->fetchAll());
    axion_json(['sucesso' => true, 'dados' => $dados]);
}

if (in_array($acao, ['aprovar_locacao', 'recusar_locacao', 'registrar_devolucao', 'cancelar_locacao_gestor'], true) && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_locacao'] ?? 0);
    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT l.*, v.status AS veiculo_status, v.km_rodado FROM Locacoes l JOIN Veiculos v ON v.id_veiculo = l.id_veiculo
                         WHERE l.id_locacao = :id FOR UPDATE");
    $st->execute([':id' => $id]);
    $l = $st->fetch();
    $falhar = function (string $msg, int $cod = 409) use ($pdo) { $pdo->rollBack(); axion_json(['sucesso' => false, 'erro' => $msg], $cod); };
    if (!$l) $falhar('Locação não encontrada.', 404);

    if ($acao === 'aprovar_locacao') {
        if ($l['status'] !== 'pendente') $falhar('Este pedido já foi respondido.');
        if ($l['data_devolucao'] < date('Y-m-d')) $falhar('O período pedido já passou. Recuse o pedido para o cliente pedir de novo.');
        if ($l['veiculo_status'] !== 'ativo') $falhar('O veículo não está disponível agora (em rota, manutenção ou já locado).');
        $stSup = $pdo->prepare("SELECT s.id_usuario FROM Usuarios c JOIN Usuarios s ON s.id_usuario = c.id_supervisor AND s.perfil = 'supervisor' AND s.status = 'ativo' WHERE c.id_usuario = :c");
        $stSup->execute([':c' => $l['id_cliente']]);
        if (!$stSup->fetchColumn()) $falhar('Este cliente não tem supervisor responsável. Edite o cadastro do cliente e escolha um: ele assina as vistorias de retirada e devolução.');
        $pdo->prepare("UPDATE Locacoes SET status = 'ativa', avaliado_por = :eu, avaliado_em = NOW() WHERE id_locacao = :id")
            ->execute([':eu' => $eu['id'], ':id' => $id]);
        $pdo->prepare("UPDATE Veiculos SET status = 'locado' WHERE id_veiculo = :v")->execute([':v' => $l['id_veiculo']]);
        $msg = 'Locação aprovada. O veículo agora aparece como locado na frota.';
    } elseif ($acao === 'recusar_locacao') {
        if ($l['status'] !== 'pendente') $falhar('Este pedido já foi respondido.');
        $motivo = mb_substr(trim((string)($_POST['motivo'] ?? '')), 0, 255);
        if ($motivo === '') $falhar('Escreva o motivo da recusa: o cliente vai ver essa mensagem.', 422);
        $pdo->prepare("UPDATE Locacoes SET status = 'recusada', motivo_recusa = :m, avaliado_por = :eu, avaliado_em = NOW() WHERE id_locacao = :id")
            ->execute([':m' => $motivo, ':eu' => $eu['id'], ':id' => $id]);
        $msg = 'Pedido recusado. O cliente vê o motivo em "Minhas locações".';
    } elseif ($acao === 'cancelar_locacao_gestor') {
        // Cliente não apareceu para retirar: libera o carro (só antes da vistoria de retirada concluída)
        $vist = axion_vistorias_locacao($pdo, $id);
        if ($l['status'] !== 'ativa' || ($vist['retirada'] && $vist['retirada']['situacao'] === 'concluida')) {
            $falhar('Só dá para cancelar uma locação aprovada antes da retirada do veículo.');
        }
        $motivo = mb_substr(trim((string)($_POST['motivo'] ?? '')), 0, 255);
        if ($motivo === '') $falhar('Escreva o motivo do cancelamento: o cliente vai ver essa mensagem.', 422);
        $pdo->prepare("UPDATE Locacoes SET status = 'cancelada', motivo_recusa = :m WHERE id_locacao = :id")->execute([':m' => $motivo, ':id' => $id]);
        $pdo->prepare("UPDATE Veiculos SET status = 'ativo' WHERE id_veiculo = :v AND status = 'locado'")->execute([':v' => $l['id_veiculo']]);
        $msg = 'Locação cancelada. O veículo voltou a ficar disponível.';
    } else {
        if ($l['status'] !== 'ativa') $falhar('Só dá para registrar a devolução de uma locação em andamento.');
        // A devolução só é registrada com a vistoria de devolução assinada pelo cliente e pelo supervisor
        $vist = axion_vistorias_locacao($pdo, $id);
        $dev = $vist['devolucao'];
        if (!$dev || $dev['situacao'] !== 'concluida') {
            $falhar('Falta a vistoria de devolução assinada pelo cliente e pelo supervisor. O cliente faz a vistoria em "Minhas locações".');
        }
        $km = (int)$dev['km_rodado'];
        $pdo->prepare("UPDATE Locacoes SET status = 'devolvida', devolvido_em = NOW(), km_devolucao = :km, recebido_por = :eu WHERE id_locacao = :id")
            ->execute([':km' => $km, ':eu' => $eu['id'], ':id' => $id]);
        // Se o supervisor mandou para a manutenção ao aprovar a vistoria com avaria, o carro vai direto para lá
        $novoStatus = (int)$dev['enviado_manutencao'] === 1 ? 'manutencao' : 'ativo';
        $pdo->prepare("UPDATE Veiculos SET status = :st, km_rodado = GREATEST(km_rodado, :km) WHERE id_veiculo = :v")
            ->execute([':st' => $novoStatus, ':km' => $km, ':v' => $l['id_veiculo']]);
        $msg = $novoStatus === 'manutencao'
            ? 'Devolução registrada. Como a vistoria apontou avaria, o veículo foi para a manutenção.'
            : 'Devolução registrada. O veículo voltou a ficar disponível.';
    }
    $pdo->commit();
    axion_json(['sucesso' => true, 'mensagem' => $msg]);
}

// 15. CLIENTES: cadastro feito pelo próprio cliente, aprovação do gestor e ficha completa

/** Ficha do cliente para o gestor: cadastro completo e histórico de locações e avarias. */
function axion_ficha_cliente(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT u.id_usuario, u.nome, u.sobrenome, u.email, u.cpf_matricula, u.data_nascimento, u.genero, u.telefone,
                                u.cep, u.logradouro, u.numero, u.bairro, u.cidade, u.estado, u.cnh, u.categoria_cnh, u.validade_cnh,
                                u.status, u.foto, u.criado_em, s.nome AS supervisor_nome
                         FROM Usuarios u LEFT JOIN Usuarios s ON s.id_usuario = u.id_supervisor
                         WHERE u.id_usuario = :id AND u.perfil = 'cliente'");
    $st->execute([':id' => $id]);
    $c = $st->fetch();
    if (!$c) return null;
    $c['cpf_formatado'] = axion_formatar_cpf($c['cpf_matricula']);
    $c['foto_url'] = axion_url_foto((int)$c['id_usuario'], $c['foto']);
    unset($c['foto']);
    $stL = $pdo->prepare("SELECT l.id_locacao, l.data_retirada, l.data_devolucao, l.status, l.devolvido_em, v.placa, v.marca_modelo
                          FROM Locacoes l JOIN Veiculos v ON v.id_veiculo = l.id_veiculo
                          WHERE l.id_cliente = :id ORDER BY l.data_retirada DESC, l.id_locacao DESC");
    $stL->execute([':id' => $id]);
    $hist = [];
    $resumo = ['total' => 0, 'devolvidas' => 0, 'com_avaria' => 0, 'atrasos' => 0];
    foreach ($stL->fetchAll() as $l) {
        $vt = axion_vistorias_locacao($pdo, (int)$l['id_locacao']);
        $l['avaria_retirada'] = $vt['retirada'] && $vt['retirada']['status_conformidade'] === 'nao_conforme';
        $l['avaria_devolucao'] = $vt['devolucao'] && $vt['devolucao']['status_conformidade'] === 'nao_conforme';
        $l['id_vistoria_retirada'] = $vt['retirada']['id_execucao'] ?? null;
        $l['id_vistoria_devolucao'] = $vt['devolucao']['id_execucao'] ?? null;
        // Avaria que não existia na retirada é responsabilidade da locação
        $l['avaria_nova'] = $l['avaria_devolucao'] && !$l['avaria_retirada'];
        $l['devolveu_atrasado'] = $l['status'] === 'devolvida' && $l['devolvido_em'] && substr($l['devolvido_em'], 0, 10) > $l['data_devolucao'];
        if (in_array($l['status'], ['ativa', 'devolvida'], true)) $resumo['total']++;
        if ($l['status'] === 'devolvida') $resumo['devolvidas']++;
        if ($l['avaria_nova']) $resumo['com_avaria']++;
        if ($l['devolveu_atrasado']) $resumo['atrasos']++;
        $hist[] = $l;
    }
    $c['locacoes'] = $hist;
    $c['resumo'] = $resumo;
    return $c;
}

if ($acao === 'ficha_cliente') {
    axion_exigir_perfil(['admin', 'supervisor']);
    $c = axion_ficha_cliente($pdo, (int)($_GET['id'] ?? 0));
    if (!$c) axion_json(['sucesso' => false, 'erro' => 'Cliente não encontrado.'], 404);
    axion_json(['sucesso' => true, 'dados' => $c]);
}

if ($acao === 'clientes_pendentes') {
    axion_exigir_perfil(['admin', 'supervisor']);
    $dados = array_map(function ($c) {
        $c['cpf_formatado'] = axion_formatar_cpf($c['cpf_matricula']);
        return $c;
    }, $pdo->query("SELECT id_usuario, nome, email, cpf_matricula, telefone, cidade, estado, categoria_cnh, validade_cnh, criado_em
                    FROM Usuarios WHERE perfil = 'cliente' AND status = 'pendente' ORDER BY id_usuario")->fetchAll());
    axion_json(['sucesso' => true, 'dados' => $dados]);
}

if (in_array($acao, ['aprovar_cliente', 'recusar_cliente'], true) && $ehPost) {
    $eu = axion_exigir_perfil(['admin', 'supervisor']);
    axion_validar_csrf();
    $id = (int)($_POST['id_usuario'] ?? 0);
    $st = $pdo->prepare("SELECT id_usuario, nome FROM Usuarios WHERE id_usuario = :id AND perfil = 'cliente' AND status = 'pendente'");
    $st->execute([':id' => $id]);
    $cli = $st->fetch();
    if (!$cli) axion_json(['sucesso' => false, 'erro' => 'Este cadastro já foi analisado.'], 409);
    if ($acao === 'recusar_cliente') {
        // Cadastro recusado é apagado: libera o e-mail e o CPF e não guarda dados de quem não virou cliente
        $pdo->prepare("DELETE FROM Usuarios WHERE id_usuario = :id AND status = 'pendente'")->execute([':id' => $id]);
        axion_json(['sucesso' => true, 'mensagem' => "Cadastro de {$cli['nome']} recusado e removido."]);
    }
    $idSup = (int)($_POST['id_supervisor'] ?? 0);
    if (!$idSup && $eu['perfil'] === 'supervisor') $idSup = $eu['id'];
    $stS = $pdo->prepare("SELECT id_usuario FROM Usuarios WHERE id_usuario = :id AND perfil = 'supervisor' AND status = 'ativo'");
    $stS->execute([':id' => $idSup]);
    if (!$stS->fetchColumn()) axion_json(['sucesso' => false, 'erro' => 'Escolha o supervisor responsável: ele assina como testemunha as vistorias do cliente.'], 422);
    $pdo->prepare("UPDATE Usuarios SET status = 'ativo', id_supervisor = :s WHERE id_usuario = :id")->execute([':s' => $idSup, ':id' => $id]);
    axion_json(['sucesso' => true, 'mensagem' => "Cadastro de {$cli['nome']} aprovado. O cliente já pode entrar e alugar."]);
}

axion_json(['sucesso' => false, 'erro' => 'Ação inválida.'], 400);
