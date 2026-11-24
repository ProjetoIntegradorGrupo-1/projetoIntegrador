<?php
// oqfazer.php — Painel Web Principal do Sistema Axion (Dashboard, Abas por Nível de Acesso e Central de Buscas)
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/backend/auth.php';

// O nível de acesso vem do banco a cada página (não existe mais "simular/trocar perfil")
$eu             = axion_exigir_perfil();
$nome_usuario   = $eu['nome'];
$perfil_usuario = $eu['perfil'];
$csrf           = axion_csrf_token();

$isAdmin      = ($perfil_usuario === 'admin');
$isGestor     = in_array($perfil_usuario, ['admin', 'supervisor'], true);
$isMotorista  = ($perfil_usuario === 'motorista');
$isCliente    = ($perfil_usuario === 'cliente');

// Estatísticas em tempo real do banco axion_db
$stats = [
    'veiculos'     => (int)$pdo->query("SELECT COUNT(*) FROM Veiculos")->fetchColumn(),
    'motoristas'   => (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE perfil = 'motorista'")->fetchColumn(),
    'clientes'     => (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE perfil = 'cliente'")->fetchColumn(),
    'supervisores' => (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE perfil = 'supervisor'")->fetchColumn(),
    'admins'       => (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE perfil = 'admin'")->fetchColumn(),
    'checklists'   => (int)$pdo->query("SELECT COUNT(*) FROM Checklists")->fetchColumn(),
];
// Vistorias: motorista vê só as dele; os demais veem todas
$filtroVist = $perfil_usuario === 'motorista' ? ' WHERE id_motorista = ' . (int)$eu['id'] : '';
$filtroVistAnd = $filtroVist ? $filtroVist . ' AND ' : ' WHERE ';
$stats['vistorias']     = (int)$pdo->query("SELECT COUNT(*) FROM Checklist_Execucoes$filtroVist")->fetchColumn();
$stats['nao_conformes'] = (int)$pdo->query("SELECT COUNT(*) FROM Checklist_Execucoes{$filtroVistAnd}status_conformidade = 'nao_conforme'")->fetchColumn();
$stats['pendentes']     = (int)$pdo->query("SELECT COUNT(*) FROM Checklist_Execucoes{$filtroVistAnd}assinado_em IS NULL AND recusado_em IS NULL")->fetchColumn();
// Solicitações que dependem da assinatura do usuário logado
$stPend = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE recusado_em IS NULL AND
    ((id_motorista = :a AND assinado_motorista_em IS NULL) OR (id_vistoriador = :b AND assinado_vistoriador_em IS NULL))");
$stPend->execute([':a' => $eu['id'], ':b' => $eu['id']]);
$minhasPendencias = (int)$stPend->fetchColumn();

$abaAtiva = $_GET['aba'] ?? 'inicio';
$sucessoMsg = $_GET['sucesso'] ?? '';
$mapaMensagens = [
    'admin_cadastrado'      => 'Administrador cadastrado com sucesso!',
    'supervisor_cadastrado' => 'Supervisor cadastrado com sucesso!',
    'motorista_cadastrado'  => 'Motorista cadastrado com sucesso!',
    'cliente_cadastrado'    => 'Cliente cadastrado com sucesso!',
    'usuario_cadastrado'    => 'Usuário cadastrado com sucesso!',
    'usuario_editado'       => 'Dados do usuário atualizados com sucesso!',
    'usuario_excluido'      => 'Usuário removido do sistema com sucesso!',
    'veiculo_cadastrado'    => 'Veículo cadastrado na frota com sucesso!',
    'veiculo_editado'       => 'Dados do veículo atualizados com sucesso!',
    'veiculo_excluido'      => 'Veículo excluído da frota com sucesso!',
    'checklist_criado'      => 'Novo modelo de checklist criado com sucesso!',
    'checklist_editado'     => 'Modelo de checklist atualizado com sucesso!',
    'checklist_excluido'    => 'Modelo de checklist excluído com sucesso!',
    'checklist_arquivado'   => 'Este modelo já foi usado em vistorias, então foi arquivado (não aparece mais para preencher, mas o histórico continua). Para reativar, edite e salve o modelo.',
    'usuario_desativado'    => 'Este usuário já participou de vistorias, então a conta foi desativada em vez de apagada (não consegue mais entrar). Para reativar, edite o usuário.',
];
$erroMsg = $_GET['erro'] ?? '';
$mapaErros = [
    'sem_permissao'        => 'Seu nível de acesso não permite esta operação.',
    'formulario_expirado'  => 'O formulário expirou. Recarregue a página e tente novamente.',
    'vistoria_invalida'    => 'Vistoria não encontrada ou já assinada.',
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Web - Axion Gestão de Frota & Checklists</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="frontend/css/axion-web.css">
</head>

<body>

    <!-- NAVBAR SUPERIOR WEB -->
    <nav class="axion-navbar text-white">
        <div class="container-fluid px-3 px-lg-5 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="oqfazer.php" class="axion-brand">
                    <i class="bi bi-truck-front-fill fs-4"></i>
                    <span>AXION</span>
                    <span class="axion-brand-badge">Frota & Checklists Web</span>
                </a>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                <?php if ($minhasPendencias > 0): ?>
                <a href="#solicitacoes" class="btn btn-sm btn-warning fw-semibold">
                    <i class="bi bi-bell-fill me-1"></i><?php echo $minhasPendencias; ?> para assinar
                </a>
                <?php endif; ?>
                <span class="badge bg-light text-dark d-flex align-items-center gap-1 px-3 py-2">
                    <i class="bi bi-shield-lock-fill text-primary"></i>
                    Nível: <?php echo strtoupper(htmlspecialchars($perfil_usuario)); ?>
                </span>

                <div class="d-none d-md-flex align-items-center gap-2 text-white-50 small border-start ps-3 border-light border-opacity-25">
                    <i class="bi bi-person-circle fs-5 text-white"></i>
                    <div class="lh-sm">
                        <div class="text-white fw-semibold"><?php echo htmlspecialchars($nome_usuario); ?></div>
                        <div class="small">Sessão ativa</div>
                    </div>
                </div>

                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i>Sair
                </a>
            </div>
        </div>
    </nav>

    <!-- CONTEÚDO PRINCIPAL WEB -->
    <main class="axion-web-container">

        <?php if ($erroMsg && isset($mapaErros[$erroMsg])): ?>
            <div class="alert alert-danger shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2"></i><?php echo htmlspecialchars($mapaErros[$erroMsg]); ?>
            </div>
        <?php endif; ?>

        <?php if ($sucessoMsg && isset($mapaMensagens[$sucessoMsg])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                <div><strong>Sucesso:</strong> <?php echo htmlspecialchars($mapaMensagens[$sucessoMsg]); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <!-- SOLICITAÇÕES DE ASSINATURA DO USUÁRIO LOGADO -->
        <?php if ($minhasPendencias > 0): ?>
        <div class="card border-warning shadow-sm mb-4" id="solicitacoes">
            <div class="card-header bg-warning-subtle fw-semibold">
                <i class="bi bi-bell-fill text-warning-emphasis me-1"></i>
                Solicitações de assinatura para você (<?php echo $minhasPendencias; ?>)
            </div>
            <div class="list-group list-group-flush" id="lista-solicitacoes">
                <div class="list-group-item text-muted small">Carregando...</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- INDICADORES DO SISTEMA (KPIs) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="axion-stat-card d-flex align-items-center gap-3">
                    <div class="axion-stat-icon bg-primary-subtle text-primary"><i class="bi bi-car-front-fill"></i></div>
                    <div>
                        <div class="h4 fw-bold mb-0"><?php echo $stats['veiculos']; ?></div>
                        <div class="text-muted small">Carros na Frota</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="axion-stat-card d-flex align-items-center gap-3">
                    <div class="axion-stat-icon bg-success-subtle text-success"><i class="bi bi-person-vcard-fill"></i></div>
                    <div>
                        <div class="h4 fw-bold mb-0"><?php echo $stats['motoristas']; ?></div>
                        <div class="text-muted small">Motoristas</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="axion-stat-card d-flex align-items-center gap-3">
                    <div class="axion-stat-icon bg-info-subtle text-info-emphasis"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="h4 fw-bold mb-0"><?php echo $stats['clientes']; ?></div>
                        <div class="text-muted small">Clientes</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="axion-stat-card d-flex align-items-center gap-3">
                    <div class="axion-stat-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-person-badge-fill"></i></div>
                    <div>
                        <div class="h4 fw-bold mb-0"><?php echo $stats['supervisores']; ?> <span class="fs-6 text-muted fw-normal">(+<?php echo $stats['admins']; ?> Adm)</span></div>
                        <div class="text-muted small">Supervisores</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="axion-stat-card d-flex align-items-center gap-3">
                    <div class="axion-stat-icon bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-ui-checks-grid"></i></div>
                    <div>
                        <div class="h4 fw-bold mb-0"><?php echo $stats['checklists']; ?></div>
                        <div class="text-muted small">Modelos Checklist</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="axion-stat-card d-flex align-items-center gap-3">
                    <div class="axion-stat-icon bg-danger-subtle text-danger"><i class="bi bi-clipboard2-check-fill"></i></div>
                    <div>
                        <div class="h4 fw-bold mb-0"><?php echo $stats['vistorias']; ?></div>
                        <div class="text-muted small"><?php echo $perfil_usuario === 'motorista' ? 'Minhas Vistorias' : 'Vistorias Realizadas'; ?></div>
                        <div class="small"><span class="text-danger"><?php echo $stats['nao_conformes']; ?> não conformes</span> · <span class="text-warning-emphasis"><?php echo $stats['pendentes']; ?> aguardando assinatura</span></div>
                    </div>
                </div>
            </div>
        </div>

        <p class="small text-muted mt-n2 mb-4"><i class="bi bi-info-circle me-1"></i>Indicadores calculados na hora, contando os registros do banco: carros cadastrados, usuários por perfil, modelos de checklist e vistorias (com quantas deram não conforme e quantas ainda aguardam assinatura).</p>

        <!-- NAVEGAÇÃO EM ABAS (Adaptada ao Nível de Acesso: Admin, Supervisor, Motorista, Cliente) -->
        <ul class="nav nav-tabs axion-nav-tabs mb-4" id="axionMainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $abaAtiva === 'inicio' ? 'active' : ''; ?>" id="tab-inicio" data-bs-toggle="tab" data-bs-target="#pane-inicio" type="button" role="tab">
                    <i class="bi bi-grid-1x2-fill"></i> Ações Diretas (Início)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $abaAtiva === 'veiculos' ? 'active' : ''; ?>" id="tab-veiculos" data-bs-toggle="tab" data-bs-target="#pane-veiculos" type="button" role="tab" onclick="carregarBusca('veiculos')">
                    <i class="bi bi-car-front"></i> Busca de Carros (<?php echo $stats['veiculos']; ?>)
                </button>
            </li>
            <?php if ($isGestor): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $abaAtiva === 'usuarios' ? 'active' : ''; ?>" id="tab-usuarios" data-bs-toggle="tab" data-bs-target="#pane-usuarios" type="button" role="tab" onclick="carregarBusca('usuarios')">
                    <i class="bi bi-people"></i> Busca de Usuários (<?php echo $stats['motoristas'] + $stats['clientes'] + $stats['supervisores'] + $stats['admins']; ?>)
                </button>
            </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $abaAtiva === 'checklists' ? 'active' : ''; ?>" id="tab-checklists" data-bs-toggle="tab" data-bs-target="#pane-checklists" type="button" role="tab" onclick="carregarBusca('checklists'); carregarBusca('vistorias');">
                    <i class="bi bi-card-checklist"></i> Busca de Checklists & Vistorias
                </button>
            </li>
            <?php if ($isAdmin): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $abaAtiva === 'admin' ? 'active' : ''; ?>" id="tab-admin" data-bs-toggle="tab" data-bs-target="#pane-admin" type="button" role="tab">
                    <i class="bi bi-Database-fill-gear bi-sliders"></i> Administração (Admin)
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- CONTEÚDO DAS ABAS -->
        <div class="tab-content" id="axionMainTabsContent">

            <!-- ABA 1: AÇÕES DIRETAS (Substitui os antigos radio buttons por botões de abertura direta em 1 clique) -->
            <div class="tab-pane fade <?php echo $abaAtiva === 'inicio' ? 'show active' : ''; ?>" id="pane-inicio" role="tabpanel">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Acesso Rápido por Botões Diretos</h2>
                        <p class="text-muted small mb-0">Clique diretamente na operação desejada (opções exibidas conforme seu nível de acesso: <strong><?php echo strtoupper($perfil_usuario); ?></strong>).</p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <?php if ($isGestor): ?>
                    <!-- GESTÃO DE USUÁRIOS -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="caduser.html" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-primary-subtle text-primary"><i class="bi bi-person-plus-fill"></i></div>
                                <div class="action-title">Cadastrar Usuário</div>
                                <div class="action-desc">Cadastre Motoristas, Clientes, Supervisores ou Admins com validação de CPF e busca de CEP.</div>
                            </div>
                            <span class="btn btn-sm btn-primary w-100"><i class="bi bi-arrow-right-circle me-1"></i>Abrir Cadastro</span>
                        </a>
                    </div>

                    <!-- GESTÃO DE VEÍCULOS -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="cadcar.html" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-success-subtle text-success"><i class="bi bi-car-front-fill"></i></div>
                                <div class="action-title">Cadastrar Veículo</div>
                                <div class="action-desc">Inclua novos carros, utilitários ou caminhões com máscara de placa Mercosul, Renavam e Chassi.</div>
                            </div>
                            <span class="btn btn-sm btn-success w-100"><i class="bi bi-arrow-right-circle me-1"></i>Abrir Cadastro</span>
                        </a>
                    </div>

                    <!-- CRIAR MODELO DE CHECKLIST -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="criarChecklist.html" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-info-subtle text-info-emphasis"><i class="bi bi-file-earmark-plus-fill"></i></div>
                                <div class="action-title">Criar Modelo Checklist</div>
                                <div class="action-desc">Monte formulários inteligentes com perguntas principais e subperguntas condicionais.</div>
                            </div>
                            <span class="btn btn-sm btn-info text-white w-100"><i class="bi bi-arrow-right-circle me-1"></i>Criar Checklist</span>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($isGestor || $isMotorista): ?>
                    <!-- PREENCHER CHECKLIST / VISTORIA -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="cadcheck.html" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-clipboard2-check-fill"></i></div>
                                <div class="action-title">Preencher Checklist</div>
                                <div class="action-desc">Realize a vistoria de um veículo da frota, informe quilometragem e colha as assinaturas.</div>
                            </div>
                            <span class="btn btn-sm btn-warning text-dark fw-semibold w-100"><i class="bi bi-arrow-right-circle me-1"></i>Iniciar Vistoria</span>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($isGestor): ?>
                    <!-- EDITAR USUÁRIO -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="editarUsuario.html" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-person-gear"></i></div>
                                <div class="action-title">Editar Usuário</div>
                                <div class="action-desc">Pesquise e atualize dados cadastrais, categoria de CNH, endereço por CEP ou nível de acesso.</div>
                            </div>
                            <span class="btn btn-sm btn-outline-primary w-100">Editar Usuário</span>
                        </a>
                    </div>

                    <!-- EDITAR VEÍCULO -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="editarVeiculo.html" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-wrench-adjustable-circle"></i></div>
                                <div class="action-title">Editar Veículo</div>
                                <div class="action-desc">Altere quilometragem, cor, status operacional (Ativo, Em Rota, Manutenção) ou dados do carro.</div>
                            </div>
                            <span class="btn btn-sm btn-outline-primary w-100">Editar Veículo</span>
                        </a>
                    </div>

                    <!-- EDITAR CHECKLIST -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="editarChecklist.html" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-pencil-square"></i></div>
                                <div class="action-title">Editar Checklist</div>
                                <div class="action-desc">Modifique perguntas, respostas esperadas e orientações de não conformidade dos checklists.</div>
                            </div>
                            <span class="btn btn-sm btn-outline-primary w-100">Editar Checklist</span>
                        </a>
                    </div>

                    <!-- EXCLUIR REGISTROS -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="axion-action-card">
                            <div>
                                <div class="action-icon bg-danger-subtle text-danger"><i class="bi bi-trash3-fill"></i></div>
                                <div class="action-title">Exclusão Controlada</div>
                                <div class="action-desc">Acesse diretamente a remoção com confirmação de segurança para Usuários, Veículos ou Checklists.</div>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="excluirUsuario.html" class="btn btn-sm btn-outline-danger flex-fill" title="Excluir Usuário"><i class="bi bi-person-x"></i> Usuário</a>
                                <a href="excluirVeiculo.html" class="btn btn-sm btn-outline-danger flex-fill" title="Excluir Veículo"><i class="bi bi-car-front"></i> Carro</a>
                                <a href="excluirChecklist.html" class="btn btn-sm btn-outline-danger flex-fill" title="Excluir Checklist"><i class="bi bi-card-checklist"></i> Check</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ATALHOS DE BUSCA PARA TODOS OS PERFIS -->
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="#pane-veiculos" onclick="document.getElementById('tab-veiculos').click(); return false;" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-primary-subtle text-primary"><i class="bi bi-search"></i></div>
                                <div class="action-title">Buscar Carros (<?php echo $stats['veiculos']; ?>)</div>
                                <div class="action-desc">Pesquise veículos por placa, modelo, marca, cor, Renavam ou status operacional.</div>
                            </div>
                            <span class="btn btn-sm btn-outline-dark w-100"><i class="bi bi-search me-1"></i>Pesquisar Frota</span>
                        </a>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="#pane-checklists" onclick="document.getElementById('tab-checklists').click(); return false;" class="axion-action-card">
                            <div>
                                <div class="action-icon bg-info-subtle text-info-emphasis"><i class="bi bi-journal-check"></i></div>
                                <div class="action-title">Buscar Checklists</div>
                                <div class="action-desc">Consulte os <?php echo $stats['checklists']; ?> modelos cadastrados e as <?php echo $stats['vistorias']; ?> vistorias preenchidas.</div>
                            </div>
                            <span class="btn btn-sm btn-outline-dark w-100"><i class="bi bi-search me-1"></i>Pesquisar Checklists</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ABA 2: BUSCA DE CARROS / VEÍCULOS -->
            <div class="tab-pane fade <?php echo $abaAtiva === 'veiculos' ? 'show active' : ''; ?>" id="pane-veiculos" role="tabpanel">
                <div class="card border-0 shadow-sm p-3 mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label for="busca-veiculos-q" class="form-label small fw-bold mb-1"><i class="bi bi-search me-1"></i>Pesquisar Veículo (Placa, Marca, Modelo, Cor, Renavam ou Chassi)</label>
                            <input type="text" id="busca-veiculos-q" class="form-control" placeholder="Ex: ABC1D23, Hilux, Gol, Sprinter, Branco..." oninput="carregarBusca('veiculos')">
                        </div>
                        <div class="col-md-3">
                            <label for="busca-veiculos-status" class="form-label small fw-bold mb-1">Filtrar por Status</label>
                            <select id="busca-veiculos-status" class="form-select" onchange="carregarBusca('veiculos')">
                                <option value="">Todos os Status (<?php echo $stats['veiculos']; ?>)</option>
                                <option value="ativo">Ativo</option>
                                <option value="em_rota">Em Rota</option>
                                <option value="manutencao">Em Manutenção</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="button" class="btn btn-primary flex-fill" onclick="carregarBusca('veiculos')"><i class="bi bi-funnel-fill me-1"></i>Filtrar</button>
                            <?php if ($isGestor): ?>
                            <a href="cadcar.html" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Novo Carro</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Placa</th>
                                    <th>Marca / Modelo</th>
                                    <th>Ano</th>
                                    <th>Cor</th>
                                    <th>Km Rodado</th>
                                    <th>Renavam / Chassi</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações Diretas</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-veiculos">
                                <tr><td colspan="9" class="text-center py-4 text-muted">Carregando frota de veículos...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ABA 3: BUSCA DE USUÁRIOS (Admin e Supervisor) -->
            <?php if ($isGestor): ?>
            <div class="tab-pane fade <?php echo $abaAtiva === 'usuarios' ? 'show active' : ''; ?>" id="pane-usuarios" role="tabpanel">
                <div class="card border-0 shadow-sm p-3 mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label for="busca-usuarios-q" class="form-label small fw-bold mb-1"><i class="bi bi-search me-1"></i>Pesquisar Usuário (Nome, CPF, E-mail, CNH ou Cidade)</label>
                            <input type="text" id="busca-usuarios-q" class="form-control" placeholder="Digite nome, CPF, e-mail ou cidade..." oninput="carregarBusca('usuarios')">
                        </div>
                        <div class="col-md-4">
                            <label for="busca-usuarios-perfil" class="form-label small fw-bold mb-1">Filtrar por Perfil / Nível de Acesso</label>
                            <select id="busca-usuarios-perfil" class="form-select" onchange="carregarBusca('usuarios')">
                                <option value="">Todos os Perfis (<?php echo $stats['motoristas'] + $stats['clientes'] + $stats['supervisores'] + $stats['admins']; ?>)</option>
                                <option value="motorista">Motoristas (<?php echo $stats['motoristas']; ?>)</option>
                                <option value="cliente">Clientes (<?php echo $stats['clientes']; ?>)</option>
                                <option value="supervisor">Supervisores (<?php echo $stats['supervisores']; ?>)</option>
                                <option value="admin">Administradores (<?php echo $stats['admins']; ?>)</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="button" class="btn btn-primary flex-fill" onclick="carregarBusca('usuarios')"><i class="bi bi-funnel-fill me-1"></i>Filtrar</button>
                            <a href="caduser.html" class="btn btn-success"><i class="bi bi-person-plus-fill me-1"></i>Novo</a>
                        </div>
                    </div>
                </div>

                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome Completo</th>
                                    <th>CPF</th>
                                    <th>Perfil</th>
                                    <th>CNH / Cat.</th>
                                    <th>Cidade/UF (CEP)</th>
                                    <th>Contato</th>
                                    <th class="text-end">Ações Diretas</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-usuarios">
                                <tr><td colspan="8" class="text-center py-4 text-muted">Carregando usuários cadastrados...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ABA 4: BUSCA DE CHECKLISTS E VISTORIAS -->
            <div class="tab-pane fade <?php echo $abaAtiva === 'checklists' ? 'show active' : ''; ?>" id="pane-checklists" role="tabpanel">
                <!-- Sub-bloco 1: Modelos de Checklists -->
                <div class="card border-0 shadow-sm p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <h3 class="h6 fw-bold mb-0 text-primary"><i class="bi bi-ui-checks-grid me-1"></i>1. Modelos de Checklists Cadastrados (<?php echo $stats['checklists']; ?>)</h3>
                        <div class="d-flex gap-2">
                            <?php if ($isGestor || $isMotorista): ?>
                            <a href="cadcheck.html" class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-clipboard2-check me-1"></i>Preencher Vistoria</a>
                            <?php endif; ?>
                            <?php if ($isGestor): ?>
                            <a href="criarChecklist.html" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Criar Novo Modelo</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <input type="text" id="busca-checklists-q" class="form-control form-control-sm" placeholder="Buscar modelo de checklist por título ou descrição..." oninput="carregarBusca('checklists')">
                        </div>
                        <div class="col-md-4">
                            <select id="busca-checklists-cat" class="form-select form-select-sm" onchange="carregarBusca('checklists')">
                                <option value="">Todas as Categorias</option>
                                <option value="eletrico">Elétrico</option>
                                <option value="mecanico">Mecânico</option>
                                <option value="estetico">Estético</option>
                                <option value="seguranca">Segurança</option>
                                <option value="pre_viagem">Pré-Viagem / Rota</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="axion-table-wrapper mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título do Modelo de Checklist</th>
                                    <th>Categoria</th>
                                    <th>Perguntas</th>
                                    <th>Vistorias Realizadas</th>
                                    <th>Criado por</th>
                                    <th class="text-end">Ações Diretas</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-checklists">
                                <tr><td colspan="7" class="text-center py-4 text-muted">Carregando modelos de checklists...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sub-bloco 2: Histórico de Checklists Preenchidos / Vistorias -->
                <div class="card border-0 shadow-sm p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <h3 class="h6 fw-bold mb-0 text-success"><i class="bi bi-clipboard2-pulse-fill me-1"></i>2. Busca de Vistorias / Checklists Preenchidos (<?php echo $stats['vistorias']; ?>)</h3>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <input type="text" id="busca-vistorias-q" class="form-control form-control-sm" placeholder="Buscar por placa do carro, nome do motorista, vistoriador ou checklist..." oninput="carregarBusca('vistorias')">
                        </div>
                        <div class="col-md-4">
                            <select id="busca-vistorias-status" class="form-select form-select-sm" onchange="carregarBusca('vistorias')">
                                <option value="">Todos os Resultados</option>
                                <option value="conforme">Conforme (Liberado)</option>
                                <option value="nao_conforme">Não Conforme (Com Avaria)</option>
                                <option value="pendente">Aguardando assinatura</option>
                                <option value="minhas_pendencias">Aguardando a MINHA assinatura</option>
                                <option value="recusada">Recusadas</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Data / Hora</th>
                                    <th>Placa</th>
                                    <th>Checklist Utilizado</th>
                                    <th>Motorista</th>
                                    <th>Vistoriador</th>
                                    <th>Km</th>
                                    <th>Resultado</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-vistorias">
                                <tr><td colspan="9" class="text-center py-4 text-muted">Carregando vistorias realizadas...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ABA 5: ADMINISTRAÇÃO DO BANCO (Exclusiva Admin) -->
            <?php if ($isAdmin): ?>
            <div class="tab-pane fade <?php echo $abaAtiva === 'admin' ? 'show active' : ''; ?>" id="pane-admin" role="tabpanel">
                <div class="card border-0 shadow-sm p-4">
                    <h3 class="h5 fw-bold text-primary mb-2"><i class="bi bi-database-fill-check me-2"></i>Gerenciamento do Banco de Dados (Exclusivo Admin)</h3>
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h4 class="h6 fw-bold mb-2">Resumo Atual da Base <code>axion_db</code>:</h4>
                                <ul class="small mb-0">
                                    <li><strong>Veículos:</strong> <?php echo $stats['veiculos']; ?></li>
                                    <li><strong>Motoristas:</strong> <?php echo $stats['motoristas']; ?></li>
                                    <li><strong>Clientes:</strong> <?php echo $stats['clientes']; ?></li>
                                    <li><strong>Supervisores:</strong> <?php echo $stats['supervisores']; ?></li>
                                    <li><strong>Administradores:</strong> <?php echo $stats['admins']; ?></li>
                                    <li><strong>Modelos de Checklists:</strong> <?php echo $stats['checklists']; ?></li>
                                    <li><strong>Vistorias:</strong> <?php echo $stats['vistorias']; ?></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <h4 class="h6 fw-bold mb-2 text-danger">Zerar banco de dados</h4>
                                    <p class="small text-muted">
                                        Apaga todos os carros, usuários, checklists e vistorias. Ficam só as 4 contas de acesso
                                        (admin, supervisor, motorista e cliente @axion.com, senha <code>mudar123</code>). Não dá para desfazer.
                                    </p>
                                </div>
                                <form action="database/seed.php" method="post" onsubmit="return confirm('Apagar TODOS os dados do sistema? Esta ação não pode ser desfeita.');">
                                    <input type="hidden" name="acao" value="zerar">
                                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <button type="submit" class="btn btn-danger w-100"><i class="bi bi-trash3 me-1"></i>Zerar banco agora</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- SCRIPT DE BUSCA DINÂMICA -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const IS_GESTOR = <?php echo $isGestor ? 'true' : 'false'; ?>;
        const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const MEU_ID = <?php echo (int)$eu['id']; ?>;
        // Escapa textos do banco antes de colocar no HTML
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const IS_MOTORISTA = <?php echo $isMotorista ? 'true' : 'false'; ?>;

        function badgeStatusVeiculo(st) {
            if (st === 'ativo') return '<span class="badge bg-success-subtle text-success border border-success-subtle">Ativo</span>';
            if (st === 'em_rota') return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Em Rota</span>';
            if (st === 'manutencao') return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Manutenção</span>';
            return `<span class="badge bg-secondary">${st}</span>`;
        }

        function badgePerfilUsuario(p) {
            if (p === 'admin') return '<span class="badge bg-dark">ADMIN</span>';
            if (p === 'supervisor') return '<span class="badge bg-warning text-dark">Supervisor</span>';
            if (p === 'motorista') return '<span class="badge bg-primary">Motorista</span>';
            return '<span class="badge bg-info text-dark">Cliente</span>';
        }

        function badgeSituacao(sit) {
            const m = {
                concluida: '<span class="badge bg-success-subtle text-success mt-1">Concluída</span>',
                aguardando_vistoriador: '<span class="badge bg-warning text-dark mt-1">Aguardando supervisor</span>',
                aguardando_motorista: '<span class="badge bg-warning text-dark mt-1">Aguardando motorista</span>',
                aguardando_assinaturas: '<span class="badge bg-secondary mt-1">Não assinada</span>',
                recusada: '<span class="badge bg-dark mt-1">Recusada</span>'
            };
            return m[sit] || '';
        }

        async function carregarSolicitacoes() {
            const box = document.getElementById('lista-solicitacoes');
            if (!box) return;
            const json = await (await fetch('backend/api.php?acao=buscar&tipo=vistorias&filtro=minhas_pendencias&limite=50')).json();
            const dados = json.dados || [];
            box.innerHTML = dados.length ? dados.map(v => {
                const souVist = Number(v.id_vistoriador) === MEU_ID;
                const outro = souVist ? v.nome_motorista : v.nome_vistoriador;
                const outroAssinou = souVist ? v.assinado_motorista_em : v.assinado_vistoriador_em;
                return `<a href="verVistoria.html?id=${v.id_execucao}" class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold">#${v.id_execucao} · ${esc(v.placa_veiculo)} · ${esc(v.checklist_titulo)} ${badgeConformidade(v.status_conformidade)}</div>
                        <div class="small text-muted">${esc(v.data_vistoria)} · ${outroAssinou ? `${esc(outro)} já assinou e pediu a sua assinatura` : 'Falta a sua assinatura para enviar a solicitação'}</div>
                    </div>
                    <span class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-pen me-1"></i>${souVist && outroAssinou ? 'Revisar e aprovar' : 'Assinar'}</span>
                </a>`;
            }).join('') : '<div class="list-group-item text-muted small">Nenhuma solicitação pendente.</div>';
        }

        function badgeConformidade(st) {
            if (st === 'conforme') return '<span class="badge bg-success">Conforme</span>';
            if (st === 'nao_conforme') return '<span class="badge bg-danger">Não Conforme</span>';
            return '<span class="badge bg-warning text-dark">Pendente</span>';
        }

        async function carregarBusca(tipo) {
            try {
                if (tipo === 'veiculos') {
                    const q = document.getElementById('busca-veiculos-q')?.value || '';
                    const filtro = document.getElementById('busca-veiculos-status')?.value || '';
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=veiculos&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}&limite=200`);
                    const json = await resp.json();
                    const tbody = document.getElementById('tbody-veiculos');
                    if (!tbody) return;
                    if (!json.dados || json.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">Nenhum veículo encontrado para o filtro informado.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = json.dados.map(v => `
                        <tr>
                            <td class="text-muted small">#${v.id_veiculo}</td>
                            <td><span class="badge bg-dark font-monospace fs-6">${esc(v.placa)}</span></td>
                            <td class="fw-semibold">${esc(v.marca_modelo)}</td>
                            <td>${esc(v.ano)}</td>
                            <td>${esc(v.cor || '-')}</td>
                            <td>${Number(v.km_rodado || 0).toLocaleString('pt-BR')} km</td>
                            <td class="small text-muted"><div>Ren: ${esc(v.renavam || '-')}</div><div>Chassi: ${esc(v.chassi || '-')}</div></td>
                            <td>${badgeStatusVeiculo(v.status)}</td>
                            <td class="text-end text-nowrap">
                                ${(IS_GESTOR || IS_MOTORISTA) ? `<a href="cadcheck.html?placa=${encodeURIComponent(v.placa)}" class="btn btn-sm btn-warning" title="Preencher Checklist"><i class="bi bi-clipboard2-check"></i></a>` : ''}
                                ${IS_GESTOR ? `
                                    <a href="editarVeiculo.html?id=${v.id_veiculo}" class="btn btn-sm btn-outline-primary" title="Editar Veículo"><i class="bi bi-pencil"></i></a>
                                    <a href="excluirVeiculo.html?id=${v.id_veiculo}" class="btn btn-sm btn-outline-danger" title="Excluir Veículo"><i class="bi bi-trash"></i></a>
                                ` : ''}
                            </td>
                        </tr>
                    `).join('');
                }

                if (tipo === 'usuarios') {
                    const tbody = document.getElementById('tbody-usuarios');
                    if (!tbody) return;
                    const q = document.getElementById('busca-usuarios-q')?.value || '';
                    const filtro = document.getElementById('busca-usuarios-perfil')?.value || '';
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=usuarios&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}&limite=250`);
                    const json = await resp.json();
                    if (!json.dados || json.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Nenhum usuário encontrado.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = json.dados.map(u => `
                        <tr>
                            <td class="text-muted small">#${u.id_usuario}</td>
                            <td class="fw-semibold">${esc(u.nome)}${u.status === 'inativo' ? ' <span class="badge bg-secondary">Desativado</span>' : ''}${u.perfil === 'motorista' ? `<div class="small text-muted fw-normal">Supervisor: ${esc(u.supervisor_nome || 'não definido')}</div>` : ''}</td>
                            <td class="font-monospace small">${esc(u.cpf_matricula)}</td>
                            <td>${badgePerfilUsuario(u.perfil)}</td>
                            <td class="small">${u.cnh ? `${esc(u.cnh)} <span class="badge bg-secondary">${esc(u.categoria_cnh || '-')}</span>` : '-'}</td>
                            <td class="small">${u.cidade ? `${esc(u.cidade)}/${esc(u.estado)} <div class="text-muted">${esc(u.cep || '')}</div>` : '-'}</td>
                            <td class="small"><div>${esc(u.email)}</div><div class="text-muted">${esc(u.telefone || '')}</div></td>
                            <td class="text-end text-nowrap">
                                ${(IS_ADMIN || !['admin', 'supervisor'].includes(u.perfil)) ? `
                                <a href="editarUsuario.html?id=${u.id_usuario}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                ${Number(u.id_usuario) !== MEU_ID ? `<a href="excluirUsuario.html?id=${u.id_usuario}" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></a>` : ''}` : ''}
                            </td>
                        </tr>
                    `).join('');
                }

                if (tipo === 'checklists') {
                    const tbody = document.getElementById('tbody-checklists');
                    if (!tbody) return;
                    const q = document.getElementById('busca-checklists-q')?.value || '';
                    const filtro = document.getElementById('busca-checklists-cat')?.value || '';
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=checklists&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}`);
                    const json = await resp.json();
                    if (!json.dados || json.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Nenhum modelo de checklist encontrado.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = json.dados.map(c => `
                        <tr>
                            <td class="text-muted small">#${c.id_checklist}</td>
                            <td>
                                <div class="fw-semibold">${esc(c.titulo)} ${Number(c.ativo) === 1 ? '' : '<span class="badge bg-secondary">Arquivado</span>'}</div>
                                <div class="small text-muted">${esc(c.descricao || '')}</div>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary text-uppercase">${esc(c.categoria)}</span></td>
                            <td><span class="badge bg-secondary">${c.total_perguntas} perguntas</span></td>
                            <td><span class="badge bg-success-subtle text-success">${c.total_execucoes} vistorias</span></td>
                            <td class="small">${esc(c.criador_nome)}</td>
                            <td class="text-end text-nowrap">
                                ${(IS_GESTOR || IS_MOTORISTA) && Number(c.ativo) === 1 ? `<a href="cadcheck.html?id_checklist=${c.id_checklist}" class="btn btn-sm btn-warning" title="Preencher este Checklist"><i class="bi bi-clipboard2-check"></i> Usar</a>` : ''}
                                ${IS_GESTOR ? `
                                    <a href="editarChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="excluirChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></a>
                                ` : ''}
                            </td>
                        </tr>
                    `).join('');
                }

                if (tipo === 'vistorias') {
                    const tbody = document.getElementById('tbody-vistorias');
                    if (!tbody) return;
                    const q = document.getElementById('busca-vistorias-q')?.value || '';
                    const filtro = document.getElementById('busca-vistorias-status')?.value || '';
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=vistorias&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}`);
                    const json = await resp.json();
                    if (!json.dados || json.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">Nenhuma vistoria encontrada.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = json.dados.map(v => `
                        <tr>
                            <td class="text-muted small">#${v.id_execucao}</td>
                            <td class="small">${esc(v.data_vistoria)} <span class="text-muted">${(v.hora_vistoria || '').slice(0,5)}</span></td>
                            <td><span class="badge bg-dark font-monospace">${esc(v.placa_veiculo)}</span></td>
                            <td class="fw-semibold small">${esc(v.checklist_titulo)}</td>
                            <td class="small">${esc(v.nome_motorista)}</td>
                            <td class="small text-muted">${esc(v.nome_vistoriador)}</td>
                            <td class="small">${Number(v.km_rodado || 0).toLocaleString('pt-BR')} km</td>
                            <td>${badgeConformidade(v.status_conformidade)}<div>${badgeSituacao(v.situacao)}</div></td>
                            <td class="text-end text-nowrap">
                                ${v.minha_vez ? `<a href="verVistoria.html?id=${v.id_execucao}" class="btn btn-sm btn-warning fw-semibold" title="Revisar e assinar"><i class="bi bi-pen"></i> Assinar</a>` : ''}
                                <a href="verVistoria.html?id=${v.id_execucao}" class="btn btn-sm btn-outline-success" title="Ver checklist preenchido"><i class="bi bi-eye"></i> Ver</a>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (e) {
                console.error('Erro ao carregar busca:', e);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            carregarBusca('veiculos');
            if (IS_GESTOR) carregarBusca('usuarios');
            carregarBusca('checklists');
            carregarBusca('vistorias');
            carregarSolicitacoes();
        });
    </script>
</body>

</html>
