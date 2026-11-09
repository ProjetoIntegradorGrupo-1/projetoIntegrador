<?php
// oqfazer.php — Painel Web Principal do Sistema Axion (Dashboard, Abas por Nível de Acesso e Central de Buscas)
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

$nome_usuario   = $_SESSION['nome_usuario'] ?? 'Usuário Axion';
$perfil_usuario = strtolower($_SESSION['perfil_usuario'] ?? 'supervisor');
if (!in_array($perfil_usuario, ['admin', 'supervisor', 'motorista', 'cliente'], true)) {
    $perfil_usuario = 'supervisor';
}

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
    'vistorias'    => (int)$pdo->query("SELECT COUNT(*) FROM Checklist_Execucoes")->fetchColumn(),
];

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
    'banco_populado'        => 'Banco de dados populado com 200 carros, 100 motoristas, 250 clientes, 13 supervisores e 20 modelos de checklists!',
    'perfil_alterado'       => 'Nível de acesso alterado para visualização!'
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
                <!-- Seletor rápido de perfil para avaliação das abas por nível de acesso -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle fw-semibold d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-shield-lock-fill text-primary"></i>
                        <span>Nível: <?php echo strtoupper(htmlspecialchars($perfil_usuario)); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><h6 class="dropdown-header">Simular Nível de Acesso</h6></li>
                        <li><a class="dropdown-item <?php echo $perfil_usuario === 'admin' ? 'active' : ''; ?>" href="backend/api.php?acao=trocar_perfil_demo&perfil=admin"><i class="bi bi-stars me-2"></i>Admin (Acesso Total + Seed)</a></li>
                        <li><a class="dropdown-item <?php echo $perfil_usuario === 'supervisor' ? 'active' : ''; ?>" href="backend/api.php?acao=trocar_perfil_demo&perfil=supervisor"><i class="bi bi-person-badge me-2"></i>Supervisor (Gestão Operacional)</a></li>
                        <li><a class="dropdown-item <?php echo $perfil_usuario === 'motorista' ? 'active' : ''; ?>" href="backend/api.php?acao=trocar_perfil_demo&perfil=motorista"><i class="bi bi-person-vcard me-2"></i>Motorista (Vistorias e Frota)</a></li>
                        <li><a class="dropdown-item <?php echo $perfil_usuario === 'cliente' ? 'active' : ''; ?>" href="backend/api.php?acao=trocar_perfil_demo&perfil=cliente"><i class="bi bi-person-check me-2"></i>Cliente (Consulta)</a></li>
                    </ul>
                </div>

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

        <?php if ($sucessoMsg && isset($mapaMensagens[$sucessoMsg])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                <div><strong>Sucesso:</strong> <?php echo htmlspecialchars($mapaMensagens[$sucessoMsg]); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
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
                        <div class="text-muted small">Vistorias Realizadas</div>
                    </div>
                </div>
            </div>
        </div>

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
                    <i class="bi bi-Database-fill-gear bi-sliders"></i> Administração & Seed (Admin)
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
                        <div class="col-md-4">
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
                        <div class="col-md-2">
                            <label for="paginacao-veiculos-tam" class="form-label small fw-bold mb-1">Exibição</label>
                            <select id="paginacao-veiculos-tam" class="form-select" onchange="mudarTamanhoPagina('veiculos', this.value)">
                                <option value="0" selected>Mostrar Todos</option>
                                <option value="50">50 por página</option>
                                <option value="100">100 por página</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="button" class="btn btn-primary flex-fill" onclick="carregarBusca('veiculos')"><i class="bi bi-funnel-fill me-1"></i>Filtrar</button>
                            <?php if ($isGestor): ?>
                            <a href="cadcar.html" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Novo Carro</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 pt-2 border-top small">
                        <span id="info-total-veiculos" class="text-muted fw-semibold">Carregando registros...</span>
                        <div id="paginacao-veiculos-controles" class="btn-group btn-group-sm"></div>
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
                        <div class="col-md-4">
                            <label for="busca-usuarios-q" class="form-label small fw-bold mb-1"><i class="bi bi-search me-1"></i>Pesquisar Usuário (Nome, CPF, E-mail, CNH ou Cidade)</label>
                            <input type="text" id="busca-usuarios-q" class="form-control" placeholder="Digite nome, CPF, e-mail ou cidade..." oninput="carregarBusca('usuarios')">
                        </div>
                        <div class="col-md-3">
                            <label for="busca-usuarios-perfil" class="form-label small fw-bold mb-1">Filtrar por Perfil / Nível de Acesso</label>
                            <select id="busca-usuarios-perfil" class="form-select" onchange="carregarBusca('usuarios')">
                                <option value="">Todos os Perfis (<?php echo $stats['motoristas'] + $stats['clientes'] + $stats['supervisores'] + $stats['admins']; ?>)</option>
                                <option value="motorista">Motoristas (<?php echo $stats['motoristas']; ?>)</option>
                                <option value="cliente">Clientes (<?php echo $stats['clientes']; ?>)</option>
                                <option value="supervisor">Supervisores (<?php echo $stats['supervisores']; ?>)</option>
                                <option value="admin">Administradores (<?php echo $stats['admins']; ?>)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="paginacao-usuarios-tam" class="form-label small fw-bold mb-1">Exibição</label>
                            <select id="paginacao-usuarios-tam" class="form-select" onchange="mudarTamanhoPagina('usuarios', this.value)">
                                <option value="0" selected>Mostrar Todos</option>
                                <option value="50">50 por página</option>
                                <option value="100">100 por página</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="button" class="btn btn-primary flex-fill" onclick="carregarBusca('usuarios')"><i class="bi bi-funnel-fill me-1"></i>Filtrar</button>
                            <a href="caduser.html" class="btn btn-success"><i class="bi bi-person-plus-fill me-1"></i>Novo</a>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 pt-2 border-top small">
                        <span id="info-total-usuarios" class="text-muted fw-semibold">Carregando registros...</span>
                        <div id="paginacao-usuarios-controles" class="btn-group btn-group-sm"></div>
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
                                    <th>Categorias das Perguntas</th>
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
                                <option value="pendente">Pendente</option>
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
                                </tr>
                            </thead>
                            <tbody id="tbody-vistorias">
                                <tr><td colspan="8" class="text-center py-4 text-muted">Carregando vistorias realizadas...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ABA 5: ADMINISTRAÇÃO & SEED DO BANCO (Exclusiva Admin) -->
            <?php if ($isAdmin): ?>
            <div class="tab-pane fade <?php echo $abaAtiva === 'admin' ? 'show active' : ''; ?>" id="pane-admin" role="tabpanel">
                <div class="card border-0 shadow-sm p-4">
                    <h3 class="h5 fw-bold text-primary mb-2"><i class="bi bi-database-fill-check me-2"></i>Gerenciamento de Banco de Dados & População (Exclusivo Admin)</h3>
                    <p class="text-muted small mb-4">
                        Como <strong>Administrador</strong>, você possui privilégios superiores aos de Supervisor, podendo regerar a carga inicial de demonstração (Seed) a qualquer momento ou exportar o esquema SQL.
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h4 class="h6 fw-bold mb-2">Resumo Atual da Base <code>axion_db</code>:</h4>
                                <ul class="small mb-0">
                                    <li><strong>Veículos (Carros/Frota):</strong> <?php echo $stats['veiculos']; ?> registros (Meta: 200)</li>
                                    <li><strong>Motoristas:</strong> <?php echo $stats['motoristas']; ?> registros (Meta: 100)</li>
                                    <li><strong>Clientes:</strong> <?php echo $stats['clientes']; ?> registros (Meta: 250)</li>
                                    <li><strong>Supervisores:</strong> <?php echo $stats['supervisores']; ?> registros (Meta: 13)</li>
                                    <li><strong>Administradores:</strong> <?php echo $stats['admins']; ?> registro(s)</li>
                                    <li><strong>Modelos de Checklists:</strong> <?php echo $stats['checklists']; ?> modelos com perguntas condicionais</li>
                                    <li><strong>Vistorias Preenchidas:</strong> <?php echo $stats['vistorias']; ?> execuções registradas</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <h4 class="h6 fw-bold mb-2">Restaurar / Repopular Base Completa (Seed)</h4>
                                    <p class="small text-muted">
                                        Caso deseje limpar testes manuais e restaurar exatamente os 200 carros, 100 motoristas, 250 clientes, 13 supervisores, 1 admin e 20 modelos de checklists com 150 vistorias:
                                    </p>
                                </div>
                                <a href="database/seed.php?forcar=1" class="btn btn-danger w-100" onclick="return confirm('Deseja recriar e repopular todos os dados de demonstração do banco axion_db?');">
                                    <i class="bi bi-arrow-repeat me-1"></i>Executar Seed Completo Agora
                                </a>
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
        const IS_MOTORISTA = <?php echo $isMotorista ? 'true' : 'false'; ?>;

        // Armazena todos os registros retornados para paginação instantânea ou exibição completa
        const cacheDados = {
            veiculos: [],
            usuarios: [],
            paginaVeiculos: 1,
            tamVeiculos: 0, // 0 = Mostrar Todos
            paginaUsuarios: 1,
            tamUsuarios: 0  // 0 = Mostrar Todos
        };

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

        function badgeConformidade(st) {
            if (st === 'conforme') return '<span class="badge bg-success">Conforme</span>';
            if (st === 'nao_conforme') return '<span class="badge bg-danger">Não Conforme</span>';
            return '<span class="badge bg-warning text-dark">Pendente</span>';
        }

        function formatarBadgesCategoriasPerguntas(catsStr) {
            const lista = (catsStr || 'eletrico,mecanico,estetico,seguranca').split(',').filter(Boolean);
            return lista.map(cat => `<span class="badge bg-primary-subtle text-primary text-uppercase me-1 mb-1">${cat}</span>`).join('');
        }

        function mudarTamanhoPagina(tipo, novoTam) {
            if (tipo === 'veiculos') {
                cacheDados.tamVeiculos = parseInt(novoTam, 10) || 0;
                cacheDados.paginaVeiculos = 1;
                renderizarTabelaVeiculos();
            } else if (tipo === 'usuarios') {
                cacheDados.tamUsuarios = parseInt(novoTam, 10) || 0;
                cacheDados.paginaUsuarios = 1;
                renderizarTabelaUsuarios();
            }
        }

        function irParaPagina(tipo, pag) {
            if (tipo === 'veiculos') {
                cacheDados.paginaVeiculos = pag;
                renderizarTabelaVeiculos();
            } else if (tipo === 'usuarios') {
                cacheDados.paginaUsuarios = pag;
                renderizarTabelaUsuarios();
            }
        }

        function montarBotoesPaginacao(tipo, totalItens, tamPagina, pagAtual) {
            const container = document.getElementById(`paginacao-${tipo}-controles`);
            const infoEl = document.getElementById(`info-total-${tipo}`);
            if (!tamPagina || tamPagina <= 0 || totalItens <= tamPagina) {
                if (infoEl) infoEl.textContent = `Exibindo todos os ${totalItens} registros encontrados`;
                if (container) container.innerHTML = '';
                return { inicio: 0, fim: totalItens };
            }

            const totalPaginas = Math.ceil(totalItens / tamPagina);
            const pagSegura = Math.min(Math.max(1, pagAtual), totalPaginas);
            const inicio = (pagSegura - 1) * tamPagina;
            const fim = Math.min(inicio + tamPagina, totalItens);

            if (infoEl) infoEl.textContent = `Exibindo ${inicio + 1} a ${fim} de ${totalItens} registros (Página ${pagSegura}/${totalPaginas})`;

            if (container) {
                let html = `<button type="button" class="btn btn-outline-secondary" ${pagSegura === 1 ? 'disabled' : ''} onclick="irParaPagina('${tipo}', ${pagSegura - 1})">Anterior</button>`;
                for (let p = 1; p <= totalPaginas; p++) {
                    html += `<button type="button" class="btn ${p === pagSegura ? 'btn-primary' : 'btn-outline-secondary'}" onclick="irParaPagina('${tipo}', ${p})">${p}</button>`;
                }
                html += `<button type="button" class="btn btn-outline-secondary" ${pagSegura === totalPaginas ? 'disabled' : ''} onclick="irParaPagina('${tipo}', ${pagSegura + 1})">Próxima</button>`;
                container.innerHTML = html;
            }
            return { inicio, fim };
        }

        function renderizarTabelaVeiculos() {
            const tbody = document.getElementById('tbody-veiculos');
            if (!tbody) return;
            const todos = cacheDados.veiculos || [];
            if (todos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">Nenhum veículo encontrado para o filtro informado.</td></tr>';
                montarBotoesPaginacao('veiculos', 0, 0, 1);
                return;
            }
            const { inicio, fim } = montarBotoesPaginacao('veiculos', todos.length, cacheDados.tamVeiculos, cacheDados.paginaVeiculos);
            const fatia = todos.slice(inicio, fim);

            tbody.innerHTML = fatia.map(v => `
                <tr>
                    <td class="text-muted small">#${v.id_veiculo}</td>
                    <td><span class="badge bg-dark font-monospace fs-6">${v.placa}</span></td>
                    <td class="fw-semibold">${v.marca_modelo}</td>
                    <td>${v.ano}</td>
                    <td>${v.cor || '-'}</td>
                    <td>${Number(v.km_rodado || 0).toLocaleString('pt-BR')} km</td>
                    <td class="small text-muted"><div>Ren: ${v.renavam || '-'}</div><div>Chassi: ${v.chassi || '-'}</div></td>
                    <td>${badgeStatusVeiculo(v.status)}</td>
                    <td class="text-end text-nowrap">
                        ${(IS_GESTOR || IS_MOTORISTA) ? `<a href="cadcheck.html?placa=${encodeURIComponent(v.placa)}&km=${v.km_rodado || 0}" class="btn btn-sm btn-warning" title="Preencher Checklist"><i class="bi bi-clipboard2-check"></i></a>` : ''}
                        ${IS_GESTOR ? `
                            <a href="editarVeiculo.html?id=${v.id_veiculo}" class="btn btn-sm btn-outline-primary" title="Editar Veículo"><i class="bi bi-pencil"></i></a>
                            <a href="excluirVeiculo.html?id=${v.id_veiculo}" class="btn btn-sm btn-outline-danger" title="Excluir Veículo"><i class="bi bi-trash"></i></a>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        }

        function renderizarTabelaUsuarios() {
            const tbody = document.getElementById('tbody-usuarios');
            if (!tbody) return;
            const todos = cacheDados.usuarios || [];
            if (todos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Nenhum usuário encontrado.</td></tr>';
                montarBotoesPaginacao('usuarios', 0, 0, 1);
                return;
            }
            const { inicio, fim } = montarBotoesPaginacao('usuarios', todos.length, cacheDados.tamUsuarios, cacheDados.paginaUsuarios);
            const fatia = todos.slice(inicio, fim);

            tbody.innerHTML = fatia.map(u => `
                <tr>
                    <td class="text-muted small">#${u.id_usuario}</td>
                    <td class="fw-semibold">${u.nome}</td>
                    <td class="font-monospace small">${u.cpf_matricula}</td>
                    <td>${badgePerfilUsuario(u.perfil)}</td>
                    <td class="small">${u.cnh ? `${u.cnh} <span class="badge bg-secondary">${u.categoria_cnh || 'B'}</span>` : '-'}</td>
                    <td class="small">${u.cidade ? `${u.cidade}/${u.estado} <div class="text-muted">${u.cep || ''}</div>` : '-'}</td>
                    <td class="small"><div>${u.email}</div><div class="text-muted">${u.telefone || ''}</div></td>
                    <td class="text-end text-nowrap">
                        <a href="editarUsuario.html?id=${u.id_usuario}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <a href="excluirUsuario.html?id=${u.id_usuario}" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            `).join('');
        }

        async function carregarBusca(tipo) {
            try {
                if (tipo === 'veiculos') {
                    const q = document.getElementById('busca-veiculos-q')?.value || '';
                    const filtro = document.getElementById('busca-veiculos-status')?.value || '';
                    // limite=0 traz TODOS os veículos cadastrados (ex: 201+)
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=veiculos&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}&limite=0`);
                    const json = await resp.json();
                    cacheDados.veiculos = json.dados || [];
                    cacheDados.paginaVeiculos = 1;
                    renderizarTabelaVeiculos();
                }

                if (tipo === 'usuarios') {
                    const tbody = document.getElementById('tbody-usuarios');
                    if (!tbody) return;
                    const q = document.getElementById('busca-usuarios-q')?.value || '';
                    const filtro = document.getElementById('busca-usuarios-perfil')?.value || '';
                    // limite=0 traz TODOS os usuários cadastrados (ex: 364+)
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=usuarios&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}&limite=0`);
                    const json = await resp.json();
                    cacheDados.usuarios = json.dados || [];
                    cacheDados.paginaUsuarios = 1;
                    renderizarTabelaUsuarios();
                }

                if (tipo === 'checklists') {
                    const tbody = document.getElementById('tbody-checklists');
                    if (!tbody) return;
                    const q = document.getElementById('busca-checklists-q')?.value || '';
                    const filtro = document.getElementById('busca-checklists-cat')?.value || '';
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=checklists&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}&limite=0`);
                    const json = await resp.json();
                    if (!json.dados || json.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Nenhum modelo de checklist encontrado.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = json.dados.map(c => `
                        <tr>
                            <td class="text-muted small">#${c.id_checklist}</td>
                            <td>
                                <div class="fw-semibold">${c.titulo}</div>
                                <div class="small text-muted">${c.descricao || ''}</div>
                            </td>
                            <td>${formatarBadgesCategoriasPerguntas(c.categorias_perguntas)}</td>
                            <td><span class="badge bg-secondary">${c.total_perguntas} perguntas</span></td>
                            <td><span class="badge bg-success-subtle text-success">${c.total_execucoes} vistorias</span></td>
                            <td class="small">${c.criador_nome}</td>
                            <td class="text-end text-nowrap">
                                ${(IS_GESTOR || IS_MOTORISTA) ? `<a href="cadcheck.html?id_checklist=${c.id_checklist}" class="btn btn-sm btn-warning" title="Preencher este Checklist"><i class="bi bi-clipboard2-check"></i> Usar</a>` : ''}
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
                    const resp = await fetch(`backend/api.php?acao=buscar&tipo=vistorias&q=${encodeURIComponent(q)}&filtro=${encodeURIComponent(filtro)}&limite=0`);
                    const json = await resp.json();
                    if (!json.dados || json.dados.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Nenhuma vistoria encontrada.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = json.dados.map(v => `
                        <tr>
                            <td class="text-muted small">#${v.id_execucao}</td>
                            <td class="small">${v.data_vistoria} <span class="text-muted">${(v.hora_vistoria || '').slice(0,5)}</span></td>
                            <td><span class="badge bg-dark font-monospace">${v.placa_veiculo}</span></td>
                            <td class="fw-semibold small">${v.checklist_titulo}</td>
                            <td class="small">${v.nome_motorista}</td>
                            <td class="small text-muted">${v.nome_vistoriador}</td>
                            <td class="small">${Number(v.km_rodado || 0).toLocaleString('pt-BR')} km</td>
                            <td>${badgeConformidade(v.status_conformidade)}</td>
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
        });
    </script>
</body>

</html>
