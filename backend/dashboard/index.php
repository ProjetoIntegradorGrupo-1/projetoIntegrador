<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirPerfil(['administrador', 'gestor']);

try {
    // 1. Indicadores Principais
    $total_inspecoes = $pdo->query("SELECT COUNT(*) FROM Inspecoes")->fetchColumn();
    $total_veiculos = $pdo->query("SELECT COUNT(*) FROM Veiculos WHERE status = 'ativo'")->fetchColumn();
    $ocorrencias_abertas = $pdo->query("SELECT COUNT(*) FROM Ocorrencias WHERE status = 'aberta'")->fetchColumn();
    $inspecoes_pendentes = $pdo->query("SELECT COUNT(*) FROM Inspecoes WHERE status = 'rascunho'")->fetchColumn();

    // 2. Últimas Inspeções para o resumo
    $sqlRecentes = "SELECT i.id_inspecao, v.placa, u.nome AS usuario, i.data_inicio, i.status 
                    FROM Inspecoes i 
                    JOIN Veiculos v ON i.id_veiculo = v.id_veiculo 
                    JOIN Usuarios u ON i.id_usuario = u.id_usuario 
                    ORDER BY i.data_inicio DESC LIMIT 5";
    $recentes = $pdo->query($sqlRecentes)->fetchAll();

} catch (PDOException $e) {
    error_log("Erro no Dashboard: " . $e->getMessage());
    die("Erro ao processar os indicadores.");
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Operacional - Gestão de Frota</title>
    <!-- Bootstrap 5 e FontAwesome para ícones profissionais -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .card-stat {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s ease;
        }

        .card-stat:hover {
            transform: translateY(-3px);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
    </style>
</head>

<body>

    <!-- Barra Superior / Header Estilo SaaS -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm mb-4">
        <div class="container py-2">
            <div>
                <h4 class="fw-bold text-dark mb-0">Dashboard</h4>
                <p class="text-muted small mb-0">Visão geral das vistorias e ocorrências da frota.</p>
            </div>
            <a href="../php/oqfazer.php" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Voltar ao Menu
            </a>
        </div>
    </nav>

    <div class="container pb-5">

        <!-- Cartões de Indicadores (KPIs) com Estilo Moderno -->
        <div class="row g-3 mb-4">

            <!-- Vistorias Realizadas -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Vistorias Realizadas</span>
                            <h2 class="fw-bold mt-1 mb-0 text-dark"><?= $total_inspecoes ?></h2>
                        </div>
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-clipboard-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vistorias Pendentes (Rascunhos) -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Vistorias Pendentes</span>
                            <h2 class="fw-bold mt-1 mb-0 text-dark"><?= $inspecoes_pendentes ?></h2>
                        </div>
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="fa-solid fa-clock fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ocorrências Abertas -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Ocorrências Abertas</span>
                            <h2 class="fw-bold mt-1 mb-0 text-danger"><?= $ocorrencias_abertas ?></h2>
                        </div>
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Frota Ativa -->
            <div class="col-xl-3 col-md-6">
                <div class="card card-stat shadow-sm p-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Frota Ativa</span>
                            <h2 class="fw-bold mt-1 mb-0 text-success"><?= $total_veiculos ?></h2>
                        </div>
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-truck fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Secção de Gráficos -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3">Vistorias por Período</h5>
                    <div style="height: 280px;">
                        <canvas id="graficoBarras"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3">Estado das Ocorrências</h5>
                    <div style="height: 280px;" class="d-flex justify-content-center align-items-center">
                        <canvas id="graficoRosca"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Resumo Estilizada -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold text-dark mb-0">Inspeções Recentes</h5>
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
                                    <td colspan="5" class="text-center py-4 text-muted">Nenhuma inspeção recente registada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentes as $r): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-primary">#<?= $r['id_inspecao'] ?></td>
                                        <td class="fw-medium"><?= htmlspecialchars($r['placa']) ?></td>
                                        <td><?= htmlspecialchars($r['usuario']) ?></td>
                                        <td class="text-muted"><?= date('d/m/Y H:i', strtotime($r['data_inicio'])) ?></td>
                                        <td class="pe-4">
                                            <?php if ($r['status'] === 'finalizada'): ?>
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Finalizada</span>
                                            <?php else: ?>
                                                <span
                                                    class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">Rascunho</span>
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

    </div>

    <!-- Importação do Chart.js e Script Externo Organizado -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../Frontend/js/dashboard.js"></script>
    <script>
        // Passa os dados dinâmicos do PHP para a função externa
        inicializarDashboard(<?= $total_inspecoes ?>, <?= $inspecoes_pendentes ?>, <?= $ocorrencias_abertas ?>, <?= $total_veiculos ?>);
    </script>
</body>

</html>