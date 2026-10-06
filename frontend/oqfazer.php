<?php
// frontend/oqfazer.php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

require_once __DIR__ . '/../backend/conexao.php';

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
    <title>Menu Principal - Axion</title>
    <!-- Google Fonts: Inter e Roboto Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">
    <!-- CSS do Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .font-mono { font-family: 'Roboto Mono', monospace !important; }
        .menu-card { border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        .btn-opcao-motorista { min-height: 70px; border-radius: 12px; font-size: 1.15rem; font-weight: 700; }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card menu-card p-4 bg-white" style="max-width: 580px; width: 100%;">
            
            <!-- IDENTIFICAÇÃO DO USUÁRIO E PAPEL RBAC -->
            <div class="text-center mb-3">
                <h1 class="h4 fw-bold text-dark mb-1">Olá, <?= htmlspecialchars($nome_usuario); ?>!</h1>
                <p class="text-muted small mb-2">Bem-vindo ao Axion Frotas</p>
                
                <?php if ($perfil_usuario === 'gestor'): ?>
                    <span class="badge bg-primary px-3 py-2 text-uppercase fs-6">
                        <i class="bi bi-shield-lock-fill me-1"></i> Gestor Administrador
                    </span>
                <?php elseif ($perfil_usuario === 'supervisor'): ?>
                    <span class="badge bg-info text-dark px-3 py-2 text-uppercase fs-6">
                        <i class="bi bi-person-badge-fill me-1"></i> Supervisor Operacional
                    </span>
                <?php elseif ($perfil_usuario === 'cliente'): ?>
                    <span class="badge bg-secondary px-3 py-2 text-uppercase fs-6">
                        <i class="bi bi-person-check-fill me-1"></i> Cliente / Locatário
                    </span>
                <?php else: ?>
                    <span class="badge bg-dark px-3 py-2 text-uppercase fs-6">
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
                <div class="alert alert-warning border-warning-subtle small d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning-emphasis"></i>
                        <strong><?= $totalChecklistsPendentes ?></strong> modelo(s) aguardando validação do Gestor.
                    </div>
                    <a href="gestaoChecklists.php" class="btn btn-sm btn-outline-dark fw-semibold">Avaliar</a>
                </div>
            <?php endif; ?>

            <!-- =============================================================== -->
            <!-- VISÃO 1: MOTORISTA / CLIENTE (FOCO TOTAL NA VISTORIA DE CAMPO)   -->
            <!-- =============================================================== -->
            <?php if ($perfil_usuario === 'motorista' || $perfil_usuario === 'cliente'): ?>
                
                <div class="p-3 bg-light rounded border text-center my-3">
                    <i class="bi bi-clipboard2-check fs-1 text-primary d-block mb-2"></i>
                    <h2 class="h5 fw-bold text-dark mb-1">Inspeção Veicular no Pátio</h2>
                    <p class="text-muted small mb-3">Seu perfil está autorizado para realização de vistorias com fotos de avarias e assinatura digital.</p>
                    
                    <a href="cadcheck.php" class="btn btn-primary btn-opcao-motorista w-100 d-flex align-items-center justify-content-center gap-2 shadow-sm">
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
            <!-- VISÃO 2: GESTOR ADMINISTRADOR & SUPERVISOR                      -->
            <!-- =============================================================== -->
            <?php else: ?>

                <!-- BOTÕES DE ACESSO RÁPIDO GERENCIAIS -->
                <div class="mb-3">
                    <a href="dashboard.php" class="btn btn-primary w-100 fw-semibold mb-2 shadow-sm">
                        <i class="bi bi-speedometer2 me-1"></i> Painel de Vistorias e Relatórios (Dashboard)
                    </a>
                    <a href="ocorrencias.php" class="btn btn-outline-danger w-100 fw-semibold mb-2">
                        <i class="bi bi-exclamation-octagon me-1"></i> Triagem de Ocorrências (Master-Detail)
                    </a>
                    <a href="gestaoChecklists.php" class="btn btn-outline-success w-100 fw-semibold mb-2">
                        <i class="bi bi-patch-check me-1"></i> Governança e Validação de Checklists
                    </a>
                    <a href="cadcheck.php" class="btn btn-outline-primary w-100 fw-semibold">
                        <i class="bi bi-clipboard-plus me-1"></i> Iniciar Nova Vistoria Veicular
                    </a>
                </div>

                <form action="../backend/processar_opcao.php" method="post">
                    <fieldset class="mb-4 p-3 bg-light rounded border">
                        <legend class="form-label fw-bold small text-primary mb-2">Ações Administrativas Disponíveis:</legend>

                        <!-- OPÇÃO COMUM A GESTOR E SUPERVISOR -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="opcao" id="optCadUser" value="cadastrar_usuario" checked>
                            <label class="form-check-label fw-semibold" for="optCadUser">
                                Cadastrar Usuário <?= $perfil_usuario === 'supervisor' ? '(Motoristas / Clientes)' : '(Supervisores / Equipe)' ?>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="opcao" id="optCadCar" value="cadastrar_veiculo">
                            <label class="form-check-label fw-semibold" for="optCadCar">Cadastrar Novo Veículo na Frota</label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="opcao" id="optCriarCheck" value="criar_checklist">
                            <label class="form-check-label fw-semibold" for="optCriarCheck">
                                Criar Modelo de Checklist <?= $perfil_usuario === 'supervisor' ? '<small class="text-muted">(enviará p/ aprovação do Gestor)</small>' : '' ?>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="opcao" id="optEditCar" value="editar_veiculo">
                            <label class="form-check-label" for="optEditCar">Editar Veículo</label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="opcao" id="optEditCheck" value="editar_checklist">
                            <label class="form-check-label" for="optEditCheck">Editar Checklist</label>
                        </div>

                        <!-- OPÇÕES EXCLUSIVAS DO GESTOR ADMINISTRADOR -->
                        <?php if ($perfil_usuario === 'gestor'): ?>
                            <div class="border-top pt-2 mt-2">
                                <span class="badge bg-danger-subtle text-danger mb-2">Privilégios Exclusivos do Gestor</span>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="opcao" id="optEditUser" value="editar_usuario">
                                    <label class="form-check-label" for="optEditUser">Editar / Gerenciar Usuários</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="opcao" id="optExcCar" value="excluir_veiculo">
                                    <label class="form-check-label text-danger" for="optExcCar"><i class="bi bi-trash me-1"></i> Excluir Veículo</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="opcao" id="optExcCheck" value="excluir_checklist">
                                    <label class="form-check-label text-danger" for="optExcCheck"><i class="bi bi-trash me-1"></i> Excluir Checklist</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="opcao" id="optExcUser" value="excluir_usuario">
                                    <label class="form-check-label text-danger" for="optExcUser"><i class="bi bi-trash me-1"></i> Excluir Usuário</label>
                                </div>
                            </div>
                        <?php endif; ?>

                    </fieldset>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary fw-semibold py-2">
                            <i class="bi bi-arrow-right-circle me-1"></i> Acessar Módulo Selecionado
                        </button>
                        <a href="../backend/logout.php" class="btn btn-outline-secondary">
                            <i class="bi bi-box-arrow-right me-1"></i> Sair / Voltar ao Login
                        </a>
                    </div>
                </form>

            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>