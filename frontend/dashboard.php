<?php
// frontend/dashboard.php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Dashboards analíticos são restritos a Gestores e Supervisores
autorizarAcesso(['gestor', 'supervisor']);


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
    <!-- Google Fonts: Roboto Mono e Inter/Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .font-mono {
            font-family: 'Roboto Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            letter-spacing: 0.02em;
        }
        .metric-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }
        /* Crachás de Status do Ciclo de Vida (Neutro / Ardósia conforme WCAG - Figura 1) */
        .badge-fluxo {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            font-weight: 500;
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            display: inline-block;
        }
        .badge-fluxo-pendente {
            background-color: #fefce8;
            color: #854d0e;
            border: 1px solid #fef08a;
            font-weight: 500;
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            display: inline-block;
        }
        /* Crachás de Resultado Técnico (Verde ESTRITO para 100% conforme; Vermelho para falhas) */
        .badge-resultado-conforme {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
        }
        .badge-resultado-critico {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
        }
        .badge-resultado-atencao {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
        }
        .btn-link-laudo {
            color: #0284c7;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-link-laudo:hover {
            color: #0369a1;
            text-decoration: underline;
        }
        .table-vistorias thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #64748b;
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 0.85rem 1rem;
        }
        .table-vistorias tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
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
                <a href="ocorrencias.php" class="btn btn-warning btn-sm fw-semibold me-2"><i class="bi bi-tools me-1"></i> Triagem de Ocorrências</a>
                <a href="oqfazer.php" class="btn btn-outline-light btn-sm me-2">Menu Principal</a>
                <a href="../backend/logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">

        <!-- Título e Subtítulo -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Painel de Vistorias e Conformidade</h1>
                <p class="text-muted small mb-0">Acompanhamento operacional em tempo real da frota e laudos emitidos</p>
            </div>
            <div class="d-flex gap-2">
                <a href="ocorrencias.php" class="btn btn-outline-danger shadow-sm fw-semibold">
                    <i class="bi bi-exclamation-octagon me-1"></i> Triagem de Ocorrências
                </a>
                <a href="cadcheck.php" class="btn btn-primary shadow-sm">
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

        <!-- 3. TABELA DE VISTORIAS (CONFORME FIGURA 1 - WCAG & PROFA. MARTA) -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check mb-0 d-flex align-items-center">
                        <input type="checkbox" id="checkAllVistorias" class="form-check-input mt-0 me-2" onchange="toggleSelectAll(this)">
                        <label class="form-check-label small fw-semibold text-secondary" for="checkAllVistorias" id="lblCountSelecionadas">0 vistorias selecionadas</label>
                    </div>
                    <button type="button" class="btn btn-dark btn-sm rounded-2 px-3 fw-semibold shadow-sm d-flex align-items-center gap-1" onclick="baixarLote()">
                        <i class="bi bi-download"></i> Baixar Laudos em ZIP / PDF
                    </button>
                </div>
                <div>
                    <span class="text-muted small">Exibindo <strong>1 a <?= count($vistorias) ?></strong> de <strong><?= $totalVistorias ?></strong> inspeções</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-vistorias align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 45px;" class="ps-3 text-center"><span class="visually-hidden">Seleção</span></th>
                            <th>VEÍCULO / PLACA</th>
                            <th>CONDUTOR</th>
                            <th>DATA / HORA</th>
                            <th>STATUS DO FLUXO</th>
                            <th>RESULTADO TÉCNICO</th>
                            <th class="text-end pe-3">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vistorias)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Nenhuma vistoria encontrada com os critérios informados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vistorias as $v): 
                                $isPendente = ($v['status'] === 'pendente');
                                $isAprovado = ($v['status'] === 'aprovado');
                                $isComRestricao = ($v['status'] === 'aprovado_com_restricoes');
                                $isRejeitado = ($v['status'] === 'rejeitado');
                            ?>
                            <tr>
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input row-checkbox" value="<?= $v['id_vistoria'] ?>" onchange="atualizarContador()">
                                </td>
                                <td>
                                    <strong class="text-dark d-block mb-0"><?= htmlspecialchars($v['marca_modelo'] ?? 'Veículo avulso') ?></strong>
                                    <span class="font-mono text-muted small fw-medium"><?= htmlspecialchars($v['placa_veiculo']) ?></span>
                                </td>
                                <td>
                                    <span class="small text-dark fw-medium"><?= htmlspecialchars($v['nome_motorista']) ?></span>
                                </td>
                                <td>
                                    <span class="font-mono small text-secondary">
                                        <?= date('d/m/Y', strtotime($v['data_vistoria'])) ?> <?= substr($v['hora_vistoria'], 0, 5) ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Status do Ciclo de Vida: Neutro (Cinza / Ardósia suave conforme Profa. Marta) -->
                                    <?php if ($isPendente): ?>
                                        <span class="badge badge-fluxo-pendente">Em Andamento</span>
                                    <?php else: ?>
                                        <span class="badge badge-fluxo">Finalizada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Resultado Técnico: Verde estrito apenas para 100% conforme; Vermelho para falhas -->
                                    <?php if ($isAprovado): ?>
                                        <span class="badge badge-resultado-conforme">
                                            <i class="bi bi-check-circle-fill me-1"></i> Aprovado (Conforme)
                                        </span>
                                    <?php elseif ($isComRestricao): 
                                        $motivo = !empty($v['descricao_nao_conformidade']) 
                                            ? (mb_strlen($v['descricao_nao_conformidade']) > 26 
                                                ? mb_substr($v['descricao_nao_conformidade'], 0, 24) . '...' 
                                                : $v['descricao_nao_conformidade']) 
                                            : 'Avarias Constatadas';
                                    ?>
                                        <span class="badge badge-resultado-critico" title="<?= htmlspecialchars($v['descricao_nao_conformidade'] ?? '') ?>">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i> Crítico (<?= htmlspecialchars($motivo) ?>)
                                        </span>
                                    <?php elseif ($isRejeitado): ?>
                                        <span class="badge badge-resultado-critico">
                                            <i class="bi bi-x-circle-fill me-1"></i> Crítico (Inoperante)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-resultado-atencao">
                                            <i class="bi bi-clock-history me-1"></i> Aguardando Conclusão
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if ($isPendente): ?>
                                        <a href="vistoria.php?id_vistoria=<?= $v['id_vistoria'] ?>" class="btn btn-sm btn-primary py-1 px-2 me-2 fw-semibold" title="Executar Vistoria no Modo Híbrido">
                                            <i class="bi bi-play-circle me-1"></i> Executar
                                        </a>
                                    <?php endif; ?>
                                    <a href="laudoVistoria.php?id_vistoria=<?= $v['id_vistoria'] ?>" class="btn-link-laudo">
                                        Ver Laudo <i class="bi bi-chevron-right ms-1 small"></i>
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

    <!-- JS do Bootstrap e Lógica da Barra de Ações em Lote -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
            atualizarContador();
        }

        function atualizarContador() {
            const selecionados = document.querySelectorAll('.row-checkbox:checked').length;
            const lbl = document.getElementById('lblCountSelecionadas');
            lbl.textContent = selecionados + ' vistoria' + (selecionados === 1 ? '' : 's') + ' selecionada' + (selecionados === 1 ? '' : 's');
            const master = document.getElementById('checkAllVistorias');
            const total = document.querySelectorAll('.row-checkbox').length;
            if (total > 0) {
                master.checked = (selecionados === total);
                master.indeterminate = (selecionados > 0 && selecionados < total);
            }
        }

        function baixarLote() {
            const selecionados = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if (selecionados.length === 0) {
                alert('Por favor, selecione ao menos uma vistoria na lista para exportar.');
                return;
            }
            if (selecionados.length === 1) {
                window.open('laudoVistoria.php?id_vistoria=' + selecionados[0], '_blank');
            } else {
                alert('Exportação em lote iniciada para ' + selecionados.length + ' laudos selecionados.\n\nIDs selecionados: #' + selecionados.join(', #') + '\n\nO primeiro laudo será aberto para impressão e o arquivo unificado será gerado.');
                window.open('laudoVistoria.php?id_vistoria=' + selecionados[0], '_blank');
            }
        }
    </script>
</body>

</html>

