<?php
// frontend/oqfazer.php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

require_once __DIR__ . '/../backend/conexao.php';

// Garante que o nome na sessão reflita o valor atualizado em UTF-8 no banco
$stmtUser = $pdo->prepare("SELECT nome, perfil FROM Usuarios WHERE id_usuario = :id LIMIT 1");
$stmtUser->execute([':id' => $_SESSION['id_usuario']]);
if ($rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION['nome_usuario']   = $rowUser['nome'];
    $_SESSION['perfil_usuario'] = $rowUser['perfil'];
}

$nome_usuario   = $_SESSION['nome_usuario'] ?? 'Usuário';
$perfil_usuario = $_SESSION['perfil_usuario'] ?? 'motorista';

// Se for gestor ou supervisor, verifica se há checklists aguardando aprovação
$totalChecklistsPendentes = 0;
if ($perfil_usuario === 'gestor' || $perfil_usuario === 'supervisor') {
    $stmtPend = $pdo->query("SELECT COUNT(*) FROM Checklists WHERE status = 'pendente_aprovacao'");
    $totalChecklistsPendentes = intval($stmtPend->fetchColumn());
}

$erro = $_GET['erro'] ?? '';
$sucesso = $_GET['sucesso'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal - Axion Frotas</title>
    <!-- Google Fonts: Inter e Roboto Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">
    <!-- CSS do Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; overflow-x: hidden; }
        .font-mono { font-family: 'Roboto Mono', monospace !important; }
        .menu-card { border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .fluxo-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; transition: transform 0.15s ease, box-shadow 0.15s ease; max-width: 100%; overflow: hidden; }
        .fluxo-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
        .fluxo-badge { font-size: 0.72rem; letter-spacing: 0.5px; font-weight: 700; text-transform: uppercase; white-space: normal; text-align: left; line-height: 1.35; max-width: 100%; }
        .badge-perfil-topo { white-space: normal; line-height: 1.35; max-width: 100%; }
        .btn-opcao-motorista { min-height: 70px; border-radius: 12px; font-size: 1.15rem; font-weight: 700; white-space: normal; }
        .btn-action-tile { border-radius: 8px; font-weight: 600; font-size: 0.88rem; padding: 8px 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; white-space: normal; text-align: center; max-width: 100%; }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-3 py-sm-4 px-2 px-sm-3">
        <div class="card menu-card p-3 p-sm-4 bg-white" style="max-width: 780px; width: 100%;">
            
            <!-- IDENTIFICAÇÃO DO USUÁRIO E PAPEL RBAC -->
            <div class="text-center mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mb-1">
                    <i class="bi bi-shield-check text-primary fs-3"></i>
                    <h1 class="h4 fw-bold text-dark mb-0">Olá, <?= htmlspecialchars($nome_usuario); ?>!</h1>
                </div>
                <p class="text-muted small mb-2">Portal de Vistorias e Inteligência Operacional Axion</p>
                
                <?php if ($perfil_usuario === 'gestor'): ?>
                    <span class="badge bg-primary px-3 py-2 text-uppercase fs-6 badge-perfil-topo">
                        <i class="bi bi-shield-lock-fill me-1"></i> Gestor Administrador
                    </span>
                <?php elseif ($perfil_usuario === 'supervisor'): ?>
                    <span class="badge bg-info text-dark px-3 py-2 text-uppercase fs-6 badge-perfil-topo">
                        <i class="bi bi-person-badge-fill me-1"></i> Supervisor Operacional
                    </span>
                <?php elseif ($perfil_usuario === 'cliente'): ?>
                    <span class="badge bg-secondary px-3 py-2 text-uppercase fs-6 badge-perfil-topo">
                        <i class="bi bi-person-check-fill me-1"></i> Cliente / Locatário
                    </span>
                <?php else: ?>
                    <span class="badge bg-dark px-3 py-2 text-uppercase fs-6 badge-perfil-topo">
                        <i class="bi bi-truck me-1"></i> Motorista / Inspetor
                    </span>
                <?php endif; ?>
            </div>

            <!-- ALERTAS DE FEEDBACK E SEGURANÇA -->
            <?php if ($erro === 'acesso_negado'): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <i class="bi bi-slash-circle-fill me-1"></i>
                    <strong>Acesso Negado:</strong> Seu perfil de usuário (<?= ucfirst($perfil_usuario) ?>) não possui permissão para executar esta ação administrativa.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <?php
                    $msgSucesso = match($sucesso) {
                        'supervisor_cadastrado' => 'Supervisor cadastrado com sucesso!',
                        'motorista_cadastrado'  => 'Motorista / Inspetor cadastrado com sucesso!',
                        'cliente_cadastrado'    => 'Cliente cadastrado com sucesso!',
                        'usuario_cadastrado'    => 'Usuário cadastrado com sucesso!',
                        'veiculo_cadastrado'    => 'Veículo adicionado à frota com sucesso!',
                        'veiculo_editado'       => 'Dados do veículo atualizados com sucesso!',
                        'veiculo_excluido'      => 'Veículo inativado com sucesso!',
                        'usuario_excluido'      => 'Usuário desativado com sucesso!',
                        'checklist_excluido'    => 'Modelo de checklist arquivado com sucesso!',
                        'checklist_editado'     => 'Modelo de checklist atualizado com sucesso!',
                        'checklist_concluido'   => 'Vistoria finalizada e laudo emitido com sucesso!',
                        default                 => 'Operação realizada com sucesso!'
                    };
                ?>
                <div class="alert alert-success alert-dismissible fade show small" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= $msgSucesso ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- AVISO DE GOVERNANÇA: CHECKLISTS PENDENTES DE VALIDAÇÃO -->
            <?php if ($totalChecklistsPendentes > 0 && ($perfil_usuario === 'gestor' || $perfil_usuario === 'supervisor')): ?>
                <div class="alert alert-warning border-warning-subtle small d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div>
                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning-emphasis"></i>
                        <strong>Fluxo 2:</strong> <strong><?= $totalChecklistsPendentes ?></strong> modelo(s) de checklist aguardando validação do Gestor.
                    </div>
                    <a href="gestaoChecklists.php" class="btn btn-sm btn-outline-dark fw-semibold">Avaliar Agora</a>
                </div>
            <?php endif; ?>

            <!-- =============================================================== -->
            <!-- VISÃO 1: MOTORISTA / CLIENTE (FOCO TOTAL NA VISTORIA DE CAMPO)   -->
            <!-- =============================================================== -->
            <?php if ($perfil_usuario === 'motorista' || $perfil_usuario === 'cliente'): ?>
                
                <div class="p-3 p-sm-4 bg-light rounded border text-center my-3">
                    <i class="bi bi-clipboard2-check fs-1 text-primary d-block mb-2"></i>
                    <h2 class="h5 fw-bold text-dark mb-1">Fluxo 3: Inspeção Veicular no Pátio</h2>
                    <p class="text-muted small mb-3">Seu perfil está autorizado para realização de vistorias com fotos de avarias e assinatura digital na tela.</p>
                    
                    <a href="cadcheck.php" class="btn btn-primary btn-opcao-motorista w-100 d-flex align-items-center justify-content-center gap-2 shadow-sm mb-2">
                        <i class="bi bi-play-circle-fill fs-3"></i>
                        <span>Iniciar Nova Vistoria</span>
                    </a>
                </div>

                <div class="d-grid mt-3">
                    <a href="../backend/logout.php" class="btn btn-outline-secondary">
                        <i class="bi bi-box-arrow-right me-1"></i> Sair / Desconectar
                    </a>
                </div>

            <!-- =============================================================== -->
            <!-- VISÃO 2: GESTOR ADMINISTRADOR & SUPERVISOR (5 FLUXOS DE VALOR) -->
            <!-- =============================================================== -->
            <?php else: ?>

                <!-- MAPEAMENTO DOS 5 FLUXOS DE VALOR -->
                <div class="d-flex flex-column gap-3 mb-4">

                    <!-- FLUXO 1: GESTÃO DE USUÁRIOS E FROTA (CRUD) -->
                    <div class="fluxo-card p-3 border-start border-4 border-primary">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                            <span class="badge bg-primary-subtle text-primary fluxo-badge">Fluxo 1 — CRUD Usuários & Veículos</span>
                            <span class="small text-muted"><i class="bi bi-people-fill me-1"></i> Acessos & Frota</span>
                        </div>
                        <h2 class="h6 fw-bold text-dark mb-2">Gestão de Usuários e Veículos</h2>
                        
                        <div class="row g-2">
                            <!-- Coluna Usuários -->
                            <div class="col-sm-6">
                                <div class="p-2 bg-light rounded border">
                                    <small class="text-muted d-block fw-bold mb-1"><i class="bi bi-person-gear me-1"></i> Usuários (RBAC)</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="caduser.php" class="btn btn-sm btn-primary btn-action-tile flex-fill">
                                            <i class="bi bi-person-plus-fill"></i> Cadastrar
                                        </a>
                                        <?php if ($perfil_usuario === 'gestor'): ?>
                                            <a href="editarUsuario.php" class="btn btn-sm btn-outline-secondary btn-action-tile">
                                                <i class="bi bi-pencil-square"></i> Editar
                                            </a>
                                            <a href="excluirUsuario.php" class="btn btn-sm btn-outline-danger btn-action-tile" title="Inativação Lógica">
                                                <i class="bi bi-trash"></i> Excluir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Coluna Veículos -->
                            <div class="col-sm-6">
                                <div class="p-2 bg-light rounded border">
                                    <small class="text-muted d-block fw-bold mb-1"><i class="bi bi-truck me-1"></i> Frota Patrimonial</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="cadcar.php" class="btn btn-sm btn-success btn-action-tile flex-fill">
                                            <i class="bi bi-plus-circle-fill"></i> Cadastrar
                                        </a>
                                        <a href="editarVeiculo.php" class="btn btn-sm btn-outline-secondary btn-action-tile">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                        <?php if ($perfil_usuario === 'gestor'): ?>
                                            <a href="excluirVeiculo.php" class="btn btn-sm btn-outline-danger btn-action-tile" title="Inativação Lógica">
                                                <i class="bi bi-trash"></i> Excluir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FLUXO 2: MODELAGEM E GOVERNANÇA DE CHECKLISTS (CRUD) -->
                    <div class="fluxo-card p-3 border-start border-4 border-success">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                            <span class="badge bg-success-subtle text-success fluxo-badge">Fluxo 2 — CRUD & Governança de Checklists</span>
                            <span class="small text-muted"><i class="bi bi-clipboard2-check-fill me-1"></i> Modelos & Validação</span>
                        </div>
                        <h2 class="h6 fw-bold text-dark mb-2">Modelagem & Aprovação de Checklists</h2>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <a href="criarChecklist.php" class="btn btn-sm btn-outline-success btn-action-tile flex-fill">
                                <i class="bi bi-plus-circle"></i> Criar Novo Modelo
                            </a>
                            <a href="editarChecklist.php" class="btn btn-sm btn-outline-secondary btn-action-tile">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if ($perfil_usuario === 'gestor'): ?>
                                <a href="excluirChecklist.php" class="btn btn-sm btn-outline-danger btn-action-tile">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
                            <?php endif; ?>
                            <a href="gestaoChecklists.php" class="btn btn-sm btn-success btn-action-tile flex-fill">
                                <i class="bi bi-patch-check-fill"></i> Governança / Aprovação
                                <?php if ($totalChecklistsPendentes > 0): ?>
                                    <span class="badge bg-white text-success ms-1"><?= $totalChecklistsPendentes ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>

                    <!-- FLUXO 3: INSPEÇÃO VEICULAR EM CAMPO E LAUDOS TÉCNICOS -->
                    <div class="fluxo-card p-3 border-start border-4 border-info">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                            <span class="badge bg-info-subtle text-info-emphasis fluxo-badge">Fluxo 3 — Execução & Laudos</span>
                            <span class="small text-muted"><i class="bi bi-camera-fill me-1"></i> Campo / Pátio</span>
                        </div>
                        <h2 class="h6 fw-bold text-dark mb-2">Inspeção Veicular no Pátio & Emissão de Laudos</h2>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <a href="cadcheck.php" class="btn btn-primary btn-action-tile flex-fill py-2">
                                <i class="bi bi-play-circle-fill fs-5"></i>
                                <span>Iniciar Nova Vistoria (Modo Campo / Lista)</span>
                            </a>
                        </div>
                    </div>

                    <!-- FLUXO 4: TRIAGEM E DESPACHO DE OCORRÊNCIAS / OFICINA -->
                    <div class="fluxo-card p-3 border-start border-4 border-danger">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                            <span class="badge bg-danger-subtle text-danger fluxo-badge">Fluxo 4 — Triagem & Manutenção</span>
                            <span class="small text-muted"><i class="bi bi-wrench-adjustable me-1"></i> Oficina / Reparos</span>
                        </div>
                        <h2 class="h6 fw-bold text-dark mb-2">Triagem de Ocorrências e Retenção de Frota</h2>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <a href="ocorrencias.php" class="btn btn-outline-danger btn-action-tile flex-fill py-2">
                                <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
                                <span>Abrir Painel Master-Detail de Ocorrências</span>
                            </a>
                        </div>
                    </div>

                    <!-- FLUXO 5: AUDITORIA, KPIS & GOVERNANÇA ÁGIL -->
                    <div class="fluxo-card p-3 border-start border-4 border-dark">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                            <span class="badge bg-dark-subtle text-dark fluxo-badge">Fluxo 5 — Inteligência & Auditoria</span>
                            <span class="small text-muted"><i class="bi bi-graph-up me-1"></i> Gestão Estratégica</span>
                        </div>
                        <h2 class="h6 fw-bold text-dark mb-2">Dashboard Analítico, Indicadores & Auditoria RBAC</h2>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <a href="dashboard.php" class="btn btn-dark btn-action-tile flex-fill py-2">
                                <i class="bi bi-speedometer2 fs-5"></i>
                                <span>Painel Executivo de Vistorias, Histórico e PDFs</span>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- FORMULÁRIO RÁPIDO LEGADO (COMPATIBILIDADE RETROATIVA) -->
                <details class="mb-4">
                    <summary class="small text-muted fw-semibold cursor-pointer mb-2">
                        <i class="bi bi-sliders me-1"></i> Acesso alternativo por formulário rápido (Modo Legado)
                    </summary>
                    <form action="../backend/processar_opcao.php" method="post" class="p-3 bg-light rounded border">
                        <div class="row g-2 align-items-center">
                            <div class="col-8">
                                <select name="opcao" class="form-select form-select-sm">
                                    <option value="dashboard">Dashboard Executivo</option>
                                    <option value="preencher_checklist">Iniciar Vistoria</option>
                                    <option value="ocorrencias">Triagem de Ocorrências</option>
                                    <option value="gestao_checklists">Governança de Checklists</option>
                                    <option value="cadastrar_usuario">Cadastrar Usuário</option>
                                    <option value="cadastrar_veiculo">Cadastrar Veículo</option>
                                    <option value="criar_checklist">Criar Modelo Checklist</option>
                                    <option value="editar_veiculo">Editar Veículo</option>
                                    <option value="editar_checklist">Editar Checklist</option>
                                    <?php if ($perfil_usuario === 'gestor'): ?>
                                        <option value="editar_usuario">Editar Usuário</option>
                                        <option value="excluir_veiculo">Excluir Veículo</option>
                                        <option value="excluir_checklist">Excluir Checklist</option>
                                        <option value="excluir_usuario">Excluir Usuário</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-sm btn-secondary w-100 fw-semibold">
                                    Ir <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </details>

                <div class="d-grid gap-2 border-top pt-3">
                    <a href="../backend/logout.php" class="btn btn-outline-secondary">
                        <i class="bi bi-box-arrow-right me-1"></i> Sair / Desconectar Sessão
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>