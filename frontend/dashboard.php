<?php
// frontend/dashboard.php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}
require_once '../backend/conexao.php';

$nome_usuario = $_SESSION['nome_usuario'] ?? 'Usuário';

// 1. Estatísticas Gerais (KPIs)
$totalVistorias = $pdo->query("SELECT COUNT(*) FROM Vistorias")->fetchColumn();
$totalAprovadas = $pdo->query("SELECT COUNT(*) FROM Vistorias WHERE status = 'aprovado'")->fetchColumn();
$totalRestricoes = $pdo->query("SELECT COUNT(*) FROM Vistorias WHERE status = 'aprovado_com_restricoes'")->fetchColumn();
$totalPendentes = $pdo->query("SELECT COUNT(*) FROM Vistorias WHERE status = 'pendente'")->fetchColumn();
$totalVeiculosAtivos = $pdo->query("SELECT COUNT(*) FROM Veiculos WHERE status = 'ativo'")->fetchColumn();

// Taxa de conformidade
$taxaConformidade = ($totalVistorias > 0) ? round(($totalAprovadas / $totalVistorias) * 100, 1) : 100;

// 2. Filtros de Pesquisa
$filtroPlaca = trim($_GET['placa'] ?? '');
$filtroStatus = trim($_GET['status'] ?? '');
$filtroDataIni = trim($_GET['data_ini'] ?? '');
$filtroDataFim = trim($_GET['data_fim'] ?? '');

$sql = "SELECT v.*, c.titulo AS titulo_checklist, ve.marca_modelo,
               (SELECT COUNT(*) FROM EvidenciasVistoria e WHERE e.id_vistoria = v.id_vistoria) AS total_evidencias
        FROM Vistorias v
        LEFT JOIN Checklists c ON v.id_checklist = c.id_checklist
        LEFT JOIN Veiculos ve ON v.id_veiculo = ve.id_veiculo
        WHERE 1=1";

$params = [];

if (!empty($filtroPlaca)) {
    $sql .= " AND (v.placa_veiculo LIKE :placa OR ve.marca_modelo LIKE :placa)";
    $params[':placa'] = "%{$filtroPlaca}%";
}

if (!empty($filtroStatus)) {
    $sql .= " AND v.status = :status";
    $params[':status'] = $filtroStatus;
}

if (!empty($filtroDataIni)) {
    $sql .= " AND v.data_vistoria >= :data_ini";
    $params[':data_ini'] = $filtroDataIni;
}

if (!empty($filtroDataFim)) {
    $sql .= " AND v.data_vistoria <= :data_fim";
    $params[':data_fim'] = $filtroDataFim;
}

$sql .= " ORDER BY v.data_vistoria DESC, v.hora_vistoria DESC, v.id_vistoria DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vistorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard & Histórico de Vistorias - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .metric-card {
            border-radius: 12px;
            transition: transform 0.15s ease-in-out;
        }
        .metric-card:hover {
            transform: translateY(-3px);
        }
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4em 0.7em;
        }
    </style>
</head>

<body class="bg-light">

    <!-- Barra Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-3 mb-4 shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold fs-4">Axion <span class="badge bg-primary fs-6">Gestão de Frotas</span></span>
            <div class="d-flex align-items-center text-white">
                <span class="me-3 small"><i class="bi bi-person-circle me-1"></i> Olá, <?= htmlspecialchars($nome_usuario) ?></span>
                <a href="oqfazer.php" class="btn btn-outline-light btn-sm me-2">Menu Principal</a>
                <a href="../backend/logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">

        <!-- Título e Subtítulo -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Painel de Vistorias e Conformidade</h1>
                <p class="text-muted small mb-0">Acompanhamento operacional em tempo real da frota e laudos emitidos</p>
            </div>
            <div>
                <a href="cadcheck.html" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Nova Vistoria
                </a>
            </div>
        </div>

        <!-- 1. CARDS DE MÉTRICAS (KPIs) -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card metric-card border-0 shadow-sm bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">Total de Vistorias</span>
                            <h2 class="h3 fw-bold text-dark mt-1 mb-0"><?= $totalVistorias ?></h2>
                        </div>
                        <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                            <i class="bi bi-clipboard-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card metric-card border-0 shadow-sm bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">100% Conformes</span>
                            <h2 class="h3 fw-bold text-success mt-1 mb-0"><?= $totalAprovadas ?></h2>
                        </div>
                        <div class="bg-success-subtle text-success p-3 rounded-circle">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card metric-card border-0 shadow-sm bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">Com Restrições</span>
                            <h2 class="h3 fw-bold text-warning mt-1 mb-0"><?= $totalRestricoes ?></h2>
                        </div>
                        <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card metric-card border-0 shadow-sm bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">Vistorias Pendentes</span>
                            <h2 class="h3 fw-bold text-secondary mt-1 mb-0"><?= $totalPendentes ?></h2>
                        </div>
                        <div class="bg-secondary-subtle text-secondary p-3 rounded-circle">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card metric-card border-0 shadow-sm bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">Frota Ativa</span>
                            <h2 class="h3 fw-bold text-info mt-1 mb-0"><?= $totalVeiculosAtivos ?></h2>
                        </div>
                        <div class="bg-info-subtle text-info p-3 rounded-circle">
                            <i class="bi bi-truck fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card metric-card border-0 shadow-sm bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">Taxa de Conformidade</span>
                            <h2 class="h3 fw-bold text-primary mt-1 mb-0"><?= $taxaConformidade ?>%</h2>
                        </div>
                        <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. BLOCO DE FILTROS -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form action="dashboard.php" method="get" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="placa" class="form-label small fw-semibold">Buscar por Placa ou Veículo</label>
                        <input type="text" id="placa" name="placa" class="form-control form-control-sm" placeholder="Ex: ABC-1D23, Gol..." value="<?= htmlspecialchars($filtroPlaca) ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label small fw-semibold">Status de Aprovação</label>
                        <select id="status" name="status" class="form-select form-select-sm">
                            <option value="">Todos os status</option>
                            <option value="aprovado" <?= ($filtroStatus === 'aprovado') ? 'selected' : '' ?>>Aprovado (Sem avarias)</option>
                            <option value="aprovado_com_restricoes" <?= ($filtroStatus === 'aprovado_com_restricoes') ? 'selected' : '' ?>>Aprovado com Restrições (Avarias)</option>
                            <option value="pendente" <?= ($filtroStatus === 'pendente') ? 'selected' : '' ?>>Pendente / Em andamento</option>
                            <option value="rejeitado" <?= ($filtroStatus === 'rejeitado') ? 'selected' : '' ?>>Rejeitado</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="data_ini" class="form-label small fw-semibold">Data Inicial</label>
                        <input type="date" id="data_ini" name="data_ini" class="form-control form-control-sm" value="<?= htmlspecialchars($filtroDataIni) ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="data_fim" class="form-label small fw-semibold">Data Final</label>
                        <input type="date" id="data_fim" name="data_fim" class="form-control form-control-sm" value="<?= htmlspecialchars($filtroDataFim) ?>">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm" title="Limpar Filtros">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. TABELA DE VISTORIAS -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold mb-0">Histórico de Vistorias Registradas</h2>
                <span class="text-muted small">Exibindo <strong><?= count($vistorias) ?></strong> registro(s)</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-3">ID / Data</th>
                            <th>Veículo</th>
                            <th>Checklist Aplicado</th>
                            <th>Motorista</th>
                            <th>Vistoriador</th>
                            <th>Km Registrado</th>
                            <th>Status</th>
                            <th>Evidências</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vistorias)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Nenhuma vistoria encontrada com os critérios informados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vistorias as $v): 
                                $badgeClass = match($v['status']) {
                                    'aprovado' => 'bg-success',
                                    'aprovado_com_restricoes' => 'bg-warning text-dark',
                                    'rejeitado' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                $statusLabel = match($v['status']) {
                                    'aprovado' => 'Aprovado',
                                    'aprovado_com_restricoes' => 'Com Restrições',
                                    'rejeitado' => 'Rejeitado',
                                    default => 'Pendente'
                                };
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <strong class="text-primary">#<?= $v['id_vistoria'] ?></strong><br>
                                    <span class="small text-muted"><?= date('d/m/Y', strtotime($v['data_vistoria'])) ?> <?= substr($v['hora_vistoria'], 0, 5) ?></span>
                                </td>
                                <td>
                                    <strong class="text-dark"><?= htmlspecialchars($v['placa_veiculo']) ?></strong><br>
                                    <span class="small text-muted"><?= htmlspecialchars($v['marca_modelo'] ?? 'Veículo avulso') ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold small"><?= htmlspecialchars($v['titulo_checklist'] ?? 'Checklist Geral') ?></span>
                                </td>
                                <td>
                                    <span class="small text-dark"><?= htmlspecialchars($v['nome_motorista']) ?></span>
                                </td>
                                <td>
                                    <span class="small text-muted"><?= htmlspecialchars($v['nome_vistoriador']) ?></span>
                                </td>
                                <td>
                                    <span class="small"><?= number_format($v['km_rodado'], 0, ',', '.') ?> km</span>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> badge-status">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($v['total_evidencias'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-camera"></i> <?= $v['total_evidencias'] ?> foto(s)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Sem avarias</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="laudoVistoria.php?id_vistoria=<?= $v['id_vistoria'] ?>" class="btn btn-sm btn-outline-primary" title="Visualizar e Imprimir Laudo Oficial">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Laudo
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
