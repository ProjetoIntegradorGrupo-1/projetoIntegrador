<?php
/**
 * ====================================================================
 * PAINEL PRINCIPAL (DASHBOARD) - AXION FROTAS
 * Ficheiro: oqfazer.php
 * Descrição: Central de monitoramento operacional com indicadores (KPIs)
 *            e adaptabilidade dinâmica baseada no perfil de acesso.
 * ====================================================================
 */

require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Permite o acesso de administradores, gestores e funcionários
exigirPerfil(['administrador', 'gestor', 'funcionario']);

// Identifica o perfil e o ID do utilizador autenticado na sessão atual
$idUsuarioLogado = $_SESSION['id_usuario'];
$perfilUsuario = $_SESSION['perfil_usuario'] ?? 'funcionario';
$isAdminOuGestor = ($perfilUsuario === 'administrador' || $perfilUsuario === 'gestor');

try {
    if ($isAdminOuGestor) {
        // Indicadores globais para administradores e gestores
        $total_inspecoes = $pdo->query("SELECT COUNT(*) FROM Inspecoes")->fetchColumn();
        $total_veiculos = $pdo->query("SELECT COUNT(*) FROM Veiculos WHERE status = 'ativo'")->fetchColumn();
        $ocorrencias_abertas = $pdo->query("SELECT COUNT(*) FROM Ocorrencias WHERE status = 'aberta'")->fetchColumn();
        $inspecoes_pendentes = $pdo->query("SELECT COUNT(*) FROM Inspecoes WHERE status = 'rascunho'")->fetchColumn();

        // Lista as inspeções mais recentes de toda a frota
        $sqlRecentes = "SELECT i.id_inspecao, v.placa, u.nome AS usuario, i.data_inicio, i.status 
                        FROM Inspecoes i 
                        JOIN Veiculos v ON i.id_veiculo = v.id_veiculo 
                        JOIN Usuarios u ON i.id_usuario = u.id_usuario 
                        ORDER BY i.data_inicio DESC LIMIT 5";
        $recentes = $pdo->query($sqlRecentes)->fetchAll();
    } else {
        // Indicadores filtrados especificamente para o funcionário logado
        $stmtInspecoes = $pdo->prepare("SELECT COUNT(*) FROM Inspecoes WHERE id_usuario = ?");
        $stmtInspecoes->execute([$idUsuarioLogado]);
        $total_inspecoes = $stmtInspecoes->fetchColumn();

        $total_veiculos = $pdo->query("SELECT COUNT(*) FROM Veiculos WHERE status = 'ativo'")->fetchColumn();

        $stmtOcorrencias = $pdo->prepare("SELECT COUNT(*) FROM Ocorrencias o JOIN Inspecoes i ON o.id_inspecao = i.id_inspecao WHERE i.id_usuario = ? AND o.status = 'aberta'");
        $stmtOcorrencias->execute([$idUsuarioLogado]);
        $ocorrencias_abertas = $stmtOcorrencias->fetchColumn();

        $stmtPendentes = $pdo->prepare("SELECT COUNT(*) FROM Inspecoes WHERE id_usuario = ? AND status = 'rascunho'");
        $stmtPendentes->execute([$idUsuarioLogado]);
        $inspecoes_pendentes = $stmtPendentes->fetchColumn();

        // Lista apenas as inspeções recentes criadas pelo próprio funcionário
        $sqlRecentes = "SELECT i.id_inspecao, v.placa, u.nome AS usuario, i.data_inicio, i.status 
                        FROM Inspecoes i 
                        JOIN Veiculos v ON i.id_veiculo = v.id_veiculo 
                        JOIN Usuarios u ON i.id_usuario = u.id_usuario 
                        WHERE i.id_usuario = ? 
                        ORDER BY i.data_inicio DESC LIMIT 5";
        $stmtRecentes = $pdo->prepare($sqlRecentes);
        $stmtRecentes->execute([$idUsuarioLogado]);
        $recentes = $stmtRecentes->fetchAll();
    }

} catch (PDOException $e) {
    error_log("Erro no Dashboard: " . $e->getMessage());
    die("Erro ao processar os indicadores do sistema.");
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Axion Frotas - Dashboard</title>
    <!-- Bootstrap 5 e FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #0f3822;
            --primary-green: #198754;
            --light-bg: #f8fafc;
            --card-radius: 16px;
        }

        body {
            background-color: var(--light-bg);
            font-family: system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link {
            color: #64748b;
            border-radius: 10px;
            margin-bottom: 6px;
            font-weight: 500;
            padding: 10px 15px;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #ffffff;
            background-color: var(--primary-dark);
        }
        .sidebar-brand {
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: -0.5px;
        }

        .card-custom {
            border: none;
            border-radius: var(--card-radius);
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .card-highlight {
            background: linear-gradient(135deg, #0f3822 0%, #198754 100%);
            color: #ffffff;
            border: none;
            border-radius: var(--card-radius);
        }
        .card-highlight .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .btn-green {
            background-color: var(--primary-dark);
            color: #fff;
            border-radius: 10px;
            font-weight: 500;
            border: none;
        }
        .btn-green:hover {
            background-color: #164e31;
            color: #fff;
        }

        .action-link-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.2s;
        }
        .action-link-card:hover {
            border-color: var(--primary-green);
            background: #f1fdf4;
            transform: translateY(-2px);
        }
        .icon-box {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #e6f4ed;
            color: var(--primary-green);
            font-size: 1.2rem;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            
            <!-- MENU LATERAL DINÂMICO -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
                <div class="position-sticky pt-2">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                        <h4 class="sidebar-brand fs-5 mb-0">
                            <i class="fa-solid fa-truck-fast text-success me-2"></i>Axion Frotas
                        </h4>
                        <button class="btn btn-sm text-secondary d-md-none border-0" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i class="fa-solid fa-xmark fs-5"></i>
                        </button>
                    </div>
                    
                    <small class="text-uppercase text-muted fw-bold fs-7 px-2 mb-2 d-block">Menu Principal</small>
                    <ul class="nav flex-column gap-1 mb-4">
                        <li class="nav-item">
                            <a href="oqfazer.php" class="nav-link active">
                                <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
                            </a>
                        </li>

                        <!-- Exibido exclusivamente para administradores e gestores -->
                        <?php if ($isAdminOuGestor): ?>
                            <li class="nav-item">
                                <a href="listausuario.php" class="nav-link">
                                    <i class="fa-solid fa-users me-2"></i> Usuários
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="listaveiculo.php" class="nav-link">
                                    <i class="fa-solid fa-truck me-2"></i> Veículos
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a href="listachecklist.php" class="nav-link">
                                <i class="fa-solid fa-clipboard-list me-2"></i> Checklists
                            </a>
                        </li>
                    </ul>

                    <!-- Secção Operacional dedicada para Funcionários -->
                    <?php if (!$isAdminOuGestor): ?>
                        <small class="text-uppercase text-muted fw-bold fs-7 px-2 mb-2 d-block">Operacional</small>
                        <ul class="nav flex-column gap-1 mb-4">
                            <li class="nav-item">
                                <a href="../../Frontend/cadcheck.php" class="nav-link text-success bg-success bg-opacity-10">
                                    <i class="fa-solid fa-plus-circle me-2"></i> Nova Vistoria
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>

                    <small class="text-uppercase text-muted fw-bold fs-7 px-2 mb-2 d-block">Sistema</small>
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item">
                            <a href="../auth/logout.php" class="nav-link text-danger">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- CONTEÚDO PRINCIPAL -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <!-- BARRA SUPERIOR -->
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light border-0 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i class="fa-solid fa-bars text-secondary"></i>
                        </button>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">Dashboard <?= $isAdminOuGestor ? '' : '(Operacional)' ?></h4>
                            <p class="text-muted small mb-0">Visão geral e monitoramento operacional da frota.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <!-- Botão de ação rápida superior adaptado ao perfil -->
                        <?php if ($isAdminOuGestor): ?>
                            <a href="../../Frontend/cadcar.php" class="btn btn-green btn-sm px-3 py-2 shadow-sm">
                                <i class="fa-solid fa-plus me-1"></i> Novo Veículo
                            </a>
                        <?php else: ?>
                            <a href="../../Frontend/cadcheck.php" class="btn btn-green btn-sm px-3 py-2 shadow-sm">
                                <i class="fa-solid fa-plus me-1"></i> Nova Vistoria
                            </a>
                        <?php endif; ?>
                        
                        <div class="bg-light p-2 rounded-circle text-secondary fw-bold px-3">
                            <?= strtoupper(substr($_SESSION['nome_usuario'] ?? 'U', 0, 2)) ?>
                        </div>
                    </div>
                </div>

                <!-- CARTÕES DE INDICADORES (KPIs) -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-highlight p-4 h-100 position-relative">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="small fw-semibold text-uppercase opacity-75"><?= $isAdminOuGestor ? 'Vistorias Realizadas' : 'Minhas Vistorias' ?></span>
                                    <h2 class="fw-bold mt-2 mb-1 display-6"><?= $total_inspecoes ?></h2>
                                </div>
                                <div class="bg-white bg-opacity-25 p-2 rounded-circle">
                                    <i class="fa-solid fa-arrow-trend-up fs-5"></i>
                                </div>
                            </div>
                            <small class="mt-3 d-block opacity-75">Atualizado em tempo real</small>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-custom p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small fw-semibold text-uppercase">Vistorias Pendentes</span>
                                    <h2 class="fw-bold mt-2 mb-1 text-dark display-6"><?= $inspecoes_pendentes ?></h2>
                                </div>
                                <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-circle">
                                    <i class="fa-solid fa-clock fs-5"></i>
                                </div>
                            </div>
                            <small class="text-muted mt-3 d-block">Rascunhos salvos</small>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-custom p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small fw-semibold text-uppercase">Ocorrências Abertas</span>
                                    <h2 class="fw-bold mt-2 mb-1 text-danger display-6"><?= $ocorrencias_abertas ?></h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-circle">
                                    <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                                </div>
                            </div>
                            <small class="text-muted mt-3 d-block">Requer atenção imediata</small>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-custom p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small fw-semibold text-uppercase">Frota Ativa</span>
                                    <h2 class="fw-bold mt-2 mb-1 text-success display-6"><?= $total_veiculos ?></h2>
                                </div>
                                <div class="bg-success bg-opacity-10 text-success p-2 rounded-circle">
                                    <i class="fa-solid fa-truck fs-5"></i>
                                </div>
                            </div>
                            <small class="text-muted mt-3 d-block">Veículos operacionais</small>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO DE ATALHOS RÁPIDOS (Exibido de forma inteligente conforme o perfil) -->
                <?php if ($isAdminOuGestor): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <a href="../../Frontend/caduser.php" class="action-link-card shadow-sm">
                                <div class="icon-box"><i class="fa-solid fa-user-plus"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Cadastrar Usuário</h6>
                                    <small class="text-muted">Adicionar novo operador</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="../../Frontend/cadcar.php" class="action-link-card shadow-sm">
                                <div class="icon-box"><i class="fa-solid fa-truck-medical"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Cadastrar Veículo</h6>
                                    <small class="text-muted">Inserir novo automóvel</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="../../Frontend/cadcheck.php" class="action-link-card shadow-sm">
                                <div class="icon-box" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-list-check"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Criar Checklist</h6>
                                    <small class="text-muted">Configurar nova vistoria</small>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <a href="../../Frontend/cadcheck.php" class="action-link-card shadow-sm">
                                <div class="icon-box" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-clipboard-check"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Realizar Nova Vistoria</h6>
                                    <small class="text-muted">Iniciar checklist de inspeção de veículo</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="listachecklist.php" class="action-link-card shadow-sm">
                                <div class="icon-box"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Meu Histórico</h6>
                                    <small class="text-muted">Consultar vistorias que você realizou</small>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- TABELA DE INSPEÇÕES RECENTES -->
                <div class="card card-custom p-0 overflow-hidden shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom px-4">
                        <h5 class="fw-bold text-dark mb-0"><?= $isAdminOuGestor ? 'Inspeções Recentes (Frota)' : 'As Minhas Inspeções Recentes' ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="py-3 ps-4">ID</th>
                                        <th class="py-3">Veículo (Matrícula)</th>
                                        <th class="py-3">Responsável</th>
                                        <th class="py-3">Data de Início</th>
                                        <th class="py-3 pe-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentes)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Nenhuma inspeção recente registada.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentes as $r): ?>
                                            <tr>
                                                <td class="ps-4 fw-semibold text-success">#<?= $r['id_inspecao'] ?></td>
                                                <td class="fw-medium"><?= htmlspecialchars($r['placa']) ?></td>
                                                <td><?= htmlspecialchars($r['usuario']) ?></td>
                                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($r['data_inicio'])) ?></td>
                                                <td class="pe-4">
                                                    <?php if ($r['status'] === 'finalizada'): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Finalizada</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">Rascunho</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        inicializarDashboard(<?= $total_inspecoes ?>, <?= $inspecoes_pendentes ?>, <?= $ocorrencias_abertas ?>, <?= $total_veiculos ?>);
    </script>
</body>

</html>