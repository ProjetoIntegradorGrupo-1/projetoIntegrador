<?php
// frontend/ocorrencias.php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Apenas Gestores e Supervisores gerenciam a fila de ocorrências e manutenção
autorizarAcesso(['gestor', 'supervisor']);


$nome_usuario = $_SESSION['nome_usuario'] ?? 'Gestor';

// Busca todas as ocorrências com dados da vistoria e do veículo
$sql = "SELECT o.*, v.data_vistoria, v.hora_vistoria, v.id_vistoria
        FROM Ocorrencias o
        LEFT JOIN Vistorias v ON o.id_vistoria = v.id_vistoria
        ORDER BY 
            CASE o.status 
                WHEN 'aberta' THEN 1 
                WHEN 'em_oficina' THEN 2 
                WHEN 'resolvida' THEN 3 
            END ASC,
            CASE o.criticidade
                WHEN 'critica' THEN 1
                WHEN 'alta' THEN 2
                WHEN 'media' THEN 3
                WHEN 'baixa' THEN 4
            END ASC,
            o.id_ocorrencia DESC";

$stmt = $pdo->query($sql);
$ocorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contadores de KPIs de Ocorrências
$totalOcorrencias = count($ocorrencias);
$totalAbertas = 0;
$totalOficina = 0;
$totalResolvidas = 0;
$totalCriticas = 0;

foreach ($ocorrencias as $oc) {
    if ($oc['status'] === 'aberta') $totalAbertas++;
    if ($oc['status'] === 'em_oficina') $totalOficina++;
    if ($oc['status'] === 'resolvida') $totalResolvidas++;
    if ($oc['criticidade'] === 'critica') $totalCriticas++;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triagem e Gestão de Ocorrências - Axion</title>
    <!-- Google Fonts: Roboto Mono e Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .font-mono {
            font-family: 'Roboto Mono', monospace !important;
            letter-spacing: 0.02em;
        }

        .card-master-detail {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        }

        /* Itens da lista Master */
        .item-ocorrencia {
            cursor: pointer;
            border-left: 4px solid transparent;
            transition: all 0.15s ease-in-out;
            border-radius: 8px;
            margin-bottom: 8px;
            padding: 12px;
            background-color: #ffffff;
            border: 1px solid #edf2f7;
        }

        .item-ocorrencia:hover {
            background-color: #f1f5f9;
            transform: translateX(2px);
        }

        .item-ocorrencia.ativo {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            border-left: 4px solid #0284c7;
        }

        .foto-thumb {
            width: 54px;
            height: 54px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .foto-preview-lg {
            width: 100%;
            max-height: 270px;
            object-fit: cover;
            border-radius: 8px;
            background-color: #0f172a;
        }

        /* Badges de Criticidade */
        .badge-crit-critica {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: 600;
        }
        .badge-crit-alta {
            background-color: #ffedd5;
            color: #c2410c;
            border: 1px solid #fed7aa;
            font-weight: 600;
        }
        .badge-crit-media {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fef08a;
            font-weight: 600;
        }
        .badge-crit-baixa {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            font-weight: 600;
        }

        /* Badges de Status da Ocorrência */
        .badge-st-aberta {
            background-color: #fce7f3;
            color: #be185d;
            border: 1px solid #fbcfe8;
            font-weight: 500;
        }
        .badge-st-oficina {
            background-color: #f3e8ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
            font-weight: 500;
        }
        .badge-st-resolvida {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            font-weight: 500;
        }

        .section-header-detail {
            border-left: 4px solid #0d6efd;
            padding-left: 10px;
            font-weight: 700;
            color: #0d6efd;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
    </style>
</head>

<body>

    <!-- Barra de Navegação Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-3 mb-4 shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold fs-4">Axion <span class="badge bg-danger fs-6">Triagem de Ocorrências</span></span>
            <div class="d-flex align-items-center text-white gap-2">
                <span class="me-3 small"><i class="bi bi-person-circle me-1"></i> Olá, <?= htmlspecialchars($nome_usuario) ?></span>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                <a href="oqfazer.php" class="btn btn-outline-light btn-sm me-2">Menu Principal</a>
                <a href="../backend/logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">

        <!-- TÍTULO E RESUMO DE KPIs -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Painel Master-Detail de Triagem Técnica</h1>
                <p class="text-muted small mb-0">Gestão operacional de avarias, despacho para oficina e autorização de reparos (WCAG / Fitts)</p>
            </div>
            <div class="d-flex gap-2">
                <a href="cadcheck.php" class="btn btn-primary btn-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i> Nova Inspeção
                </a>
            </div>
        </div>

        <!-- KPI CARDS RÁPIDOS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card card-master-detail p-3">
                    <span class="text-muted small fw-semibold">Total de Ocorrências</span>
                    <h3 class="fw-bold text-dark mb-0"><?= $totalOcorrencias ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-master-detail p-3 border-danger-subtle">
                    <span class="text-danger small fw-semibold"><i class="bi bi-exclamation-octagon me-1"></i> Gravidade Crítica</span>
                    <h3 class="fw-bold text-danger mb-0"><?= $totalCriticas ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-master-detail p-3 border-warning-subtle">
                    <span class="text-warning-emphasis small fw-semibold"><i class="bi bi-tools me-1"></i> Em Oficina</span>
                    <h3 class="fw-bold text-warning-emphasis mb-0"><?= $totalOficina ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-master-detail p-3 border-success-subtle">
                    <span class="text-success small fw-semibold"><i class="bi bi-check2-circle me-1"></i> Reparos Resolvidos</span>
                    <h3 class="fw-bold text-success mb-0"><?= $totalResolvidas ?></h3>
                </div>
            </div>
        </div>

        <!-- LAYOUT MASTER-DETAIL (FIGURA 2 - PROFA. MARTA) -->
        <div class="row g-4">
            
            <!-- COLUNA ESQUERDA: LISTA MASTER DE OCORRÊNCIAS -->
            <div class="col-lg-5">
                <div class="card card-master-detail h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h6 fw-bold mb-0 text-uppercase" style="letter-spacing: 0.04em;">
                                Ocorrências Registradas (<?= $totalOcorrencias ?>)
                            </h2>
                            <small class="text-muted">Clique na linha para inspecionar</small>
                        </div>
                    </div>

                    <!-- Filtros Rápidos -->
                    <div class="p-2 border-bottom bg-light d-flex gap-1 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary active py-0 px-2 filter-btn" onclick="filtrarLista('todas', this)">Todas</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 filter-btn" onclick="filtrarLista('aberta', this)">Abertas (<?= $totalAbertas ?>)</button>
                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2 filter-btn" onclick="filtrarLista('em_oficina', this)">Em Oficina (<?= $totalOficina ?>)</button>
                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 filter-btn" onclick="filtrarLista('resolvida', this)">Resolvidas (<?= $totalResolvidas ?>)</button>
                    </div>

                    <!-- Container da Lista de Itens -->
                    <div class="p-3" style="max-height: 700px; overflow-y: auto;" id="containerListaOcorrencias">
                        <?php if (empty($ocorrencias)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                Nenhuma ocorrência registrada no momento.
                            </div>
                        <?php else: ?>
                            <?php foreach ($ocorrencias as $idx => $oc): 
                                $caminhoFoto = !empty($oc['foto_evidencia']) ? '../backend/' . $oc['foto_evidencia'] : '';
                                $fotoExiste = (!empty($caminhoFoto) && file_exists($caminhoFoto));

                                $critClass = match($oc['criticidade']) {
                                    'critica' => 'badge-crit-critica',
                                    'alta' => 'badge-crit-alta',
                                    'media' => 'badge-crit-media',
                                    default => 'badge-crit-baixa'
                                };
                                $critLabel = ucfirst($oc['criticidade']);

                                $statusClass = match($oc['status']) {
                                    'aberta' => 'badge-st-aberta',
                                    'em_oficina' => 'badge-st-oficina',
                                    default => 'badge-st-resolvida'
                                };
                                $statusLabel = match($oc['status']) {
                                    'aberta' => 'Aberta',
                                    'em_oficina' => 'Em Oficina',
                                    default => 'Resolvida'
                                };

                                $dataFmt = date('d/m/Y H:i', strtotime($oc['data_abertura']));
                                $jsonOcorrencia = htmlspecialchars(json_encode($oc), ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="item-ocorrencia <?= ($idx === 0) ? 'ativo' : '' ?>" 
                                 data-status="<?= $oc['status'] ?>" 
                                 data-id="<?= $oc['id_ocorrencia'] ?>"
                                 data-info='<?= $jsonOcorrencia ?>'
                                 onclick="selecionarOcorrencia(this)">
                                
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Miniatura da Foto -->
                                    <div>
                                        <?php if ($fotoExiste): ?>
                                            <img src="<?= htmlspecialchars($caminhoFoto) ?>" alt="Evidência" class="foto-thumb">
                                        <?php else: ?>
                                            <div class="foto-thumb d-flex align-items-center justify-content-center bg-light text-secondary">
                                                <i class="bi bi-exclamation-triangle fs-4 text-warning"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Dados Centrais -->
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="d-flex align-items-baseline gap-2">
                                            <strong class="text-dark text-truncate d-block mb-0"><?= htmlspecialchars($oc['modelo_veiculo']) ?></strong>
                                            <span class="font-mono text-muted small"><?= htmlspecialchars($oc['placa_veiculo']) ?></span>
                                        </div>
                                        <p class="text-secondary small mb-1 text-truncate" title="<?= htmlspecialchars($oc['descricao_falha']) ?>">
                                            <?= htmlspecialchars($oc['descricao_falha']) ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                <?= $dataFmt ?> • Fiscal: <?= htmlspecialchars($oc['fiscal_responsavel']) ?>
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Badges Laterais (Criticidade e Status) -->
                                    <div class="text-end d-flex flex-column gap-1">
                                        <span class="badge <?= $critClass ?> px-2 py-1" style="font-size: 0.75rem;"><?= $critLabel ?></span>
                                        <span class="badge <?= $statusClass ?> px-2 py-1" style="font-size: 0.72rem;"><?= $statusLabel ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- COLUNA DIREITA: PAINEL DE INSPEÇÃO RÁPIDA (DETAIL VIEW) -->
            <div class="col-lg-7">
                <div class="card card-master-detail p-4 sticky-top" style="top: 20px;">
                    
                    <!-- Cabeçalho do Detalhe -->
                    <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
                        <div>
                            <div class="section-header-detail mb-1">Painel de Inspeção Técnica</div>
                            <h2 class="h5 fw-bold text-dark mb-0" id="detalheTituloHeader">
                                <?= !empty($ocorrencias[0]) ? htmlspecialchars($ocorrencias[0]['codigo_ocorrencia'] . ' — ' . $ocorrencias[0]['modelo_veiculo']) : 'Nenhuma Seleção' ?>
                            </h2>
                        </div>
                        <div>
                            <span id="detalheBadgeStatus" class="badge badge-st-aberta px-3 py-2 fs-6">
                                <?= !empty($ocorrencias[0]) ? ($ocorrencias[0]['status'] === 'aberta' ? 'Aberta' : ($ocorrencias[0]['status'] === 'em_oficina' ? 'Em Oficina' : 'Resolvida')) : 'Status' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Indicador de Gravidade -->
                    <div class="p-2 px-3 rounded mb-3 bg-light border d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold text-secondary">Avaliação de Risco Operacional:</span>
                        <span id="detalheBadgeGravidade" class="badge badge-crit-critica px-3 py-2 fw-bold">
                            Gravidade: <?= !empty($ocorrencias[0]) ? ucfirst($ocorrencias[0]['criticidade']) : 'N/D' ?>
                        </span>
                    </div>

                    <!-- Foto Ampliada de Evidência -->
                    <div class="mb-3 position-relative rounded overflow-hidden border bg-dark text-center">
                        <img id="detalheFotoLg" src="" alt="Evidência Fotográfica Ampliada" class="foto-preview-lg">
                        <div id="detalheSemFotoMsg" class="py-5 text-white-50 d-none">
                            <i class="bi bi-camera-slash fs-1 d-block mb-1"></i>
                            Evidência sem anexo fotográfico digital.
                        </div>
                        <div class="bg-dark bg-opacity-75 text-white px-3 py-1 text-start d-flex justify-content-between align-items-center">
                            <small class="fw-semibold"><i class="bi bi-camera me-1"></i> Evidência Fotográfica</small>
                            <small class="font-mono text-light opacity-75" id="detalheLocalPatio">GPS Pátio Sul</small>
                        </div>
                    </div>

                    <!-- Diagnóstico do Inspetor -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-1">Diagnóstico do Inspetor:</h6>
                        <p class="text-dark bg-light p-3 rounded border mb-2" id="detalheDescricaoFalha" style="white-space: pre-line;">
                            <?= !empty($ocorrencias[0]) ? htmlspecialchars($ocorrencias[0]['descricao_falha']) : '' ?>
                        </p>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Subsistema: <strong class="text-dark" id="detalheSubsistema"><?= !empty($ocorrencias[0]) ? htmlspecialchars($ocorrencias[0]['subsistema']) : 'Geral' ?></strong></span>
                            <span>Laudo Vinculado: <a href="#" id="detalheLinkLaudo" class="font-mono text-primary fw-semibold" target="_blank">VST-#<?= !empty($ocorrencias[0]) ? $ocorrencias[0]['id_vistoria'] : '1' ?></a></span>
                        </div>
                    </div>

                    <!-- Ação Recomendada (Caixa Amarela de Alerta Operacional) -->
                    <div class="p-3 mb-4 rounded border border-warning-subtle bg-warning-subtle text-dark">
                        <strong class="d-block small text-warning-emphasis mb-1">
                            <i class="bi bi-shield-exclamation me-1"></i> Ação Recomendada:
                        </strong>
                        <p class="mb-2 small" id="detalheAcaoRecomendada">
                            <?= !empty($ocorrencias[0]) ? htmlspecialchars($ocorrencias[0]['acao_recomendada'] ?? 'Veículo impedido de circular até avaliação e despacho.') : '' ?>
                        </p>
                        <div class="pt-2 border-top border-warning-subtle d-flex justify-content-between align-items-center small">
                            <span>Condição de Circulação:</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-mono" id="detalheStatusVeiculo">
                                Impedido de Circular / Retido na Oficina
                            </span>
                        </div>
                    </div>

                    <!-- AÇÕES DE DESPACHO OPERACIONAL (BOTÕES DO GESTOR) -->
                    <div class="pt-3 border-top">
                        <span class="small fw-bold text-muted text-uppercase d-block mb-2">Ações de Despacho Operacional:</span>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary fw-semibold d-flex align-items-center gap-1" id="btnEnviarOficina" onclick="executarDespacho('enviar_oficina')">
                                <i class="bi bi-tools"></i> Enviar p/ Oficina
                            </button>
                            <button type="button" class="btn btn-success fw-semibold d-flex align-items-center gap-1" id="btnAprovarReparo" onclick="executarDespacho('aprovar_reparo')">
                                <i class="bi bi-check2-circle"></i> Aprovar Reparo
                            </button>
                            <a href="#" id="btnLaudoPdf" target="_blank" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                                <i class="bi bi-file-earmark-pdf"></i> Ver Laudo Completo em PDF
                            </a>
                        </div>
                        <div id="msgAlertaDespacho" class="mt-3 d-none alert alert-success py-2 px-3 small mb-0"></div>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <!-- JS do Bootstrap e Lógica Dinâmica Master-Detail -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let ocorrenciaAtual = null;

        // Inicializa com o primeiro item da lista
        document.addEventListener('DOMContentLoaded', () => {
            const primeiroItem = document.querySelector('.item-ocorrencia');
            if (primeiroItem) {
                selecionarOcorrencia(primeiroItem);
            }
        });

        function selecionarOcorrencia(elemento) {
            // Remove destaque ativo dos outros
            document.querySelectorAll('.item-ocorrencia').forEach(el => el.classList.remove('ativo'));
            elemento.classList.add('ativo');

            const info = JSON.parse(elemento.getAttribute('data-info'));
            ocorrenciaAtual = info;

            // Atualiza Painel de Detalhes
            document.getElementById('detalheTituloHeader').textContent = info.codigo_ocorrencia + ' — ' + info.modelo_veiculo;
            
            // Status Badge
            const badgeStatus = document.getElementById('detalheBadgeStatus');
            if (info.status === 'aberta') {
                badgeStatus.className = 'badge badge-st-aberta px-3 py-2 fs-6';
                badgeStatus.textContent = 'Aberta';
            } else if (info.status === 'em_oficina') {
                badgeStatus.className = 'badge badge-st-oficina px-3 py-2 fs-6';
                badgeStatus.textContent = 'Em Oficina';
            } else {
                badgeStatus.className = 'badge badge-st-resolvida px-3 py-2 fs-6';
                badgeStatus.textContent = 'Resolvida';
            }

            // Criticidade Badge
            const badgeGravidade = document.getElementById('detalheBadgeGravidade');
            badgeGravidade.textContent = 'Gravidade: ' + info.criticidade.toUpperCase();
            if (info.criticidade === 'critica') {
                badgeGravidade.className = 'badge badge-crit-critica px-3 py-2 fw-bold';
            } else if (info.criticidade === 'alta') {
                badgeGravidade.className = 'badge badge-crit-alta px-3 py-2 fw-bold';
            } else if (info.criticidade === 'media') {
                badgeGravidade.className = 'badge badge-crit-media px-3 py-2 fw-bold';
            } else {
                badgeGravidade.className = 'badge badge-crit-baixa px-3 py-2 fw-bold';
            }

            // Foto Ampliada
            const imgFoto = document.getElementById('detalheFotoLg');
            const msgSemFoto = document.getElementById('detalheSemFotoMsg');
            if (info.foto_evidencia) {
                imgFoto.src = '../backend/' + info.foto_evidencia;
                imgFoto.classList.remove('d-none');
                msgSemFoto.classList.add('d-none');
            } else {
                imgFoto.classList.add('d-none');
                msgSemFoto.classList.remove('d-none');
            }

            document.getElementById('detalheLocalPatio').textContent = info.local_patio || 'Pátio Operacional';
            document.getElementById('detalheDescricaoFalha').textContent = info.descricao_falha;
            document.getElementById('detalheSubsistema').textContent = info.subsistema || 'Geral';
            document.getElementById('detalheAcaoRecomendada').textContent = info.acao_recomendada || 'Inspeção técnica necessária.';

            // Status do Veículo
            const stVeiculo = document.getElementById('detalheStatusVeiculo');
            if (info.status_veiculo === 'retido_oficina') {
                stVeiculo.className = 'badge bg-danger-subtle text-danger border border-danger-subtle font-mono';
                stVeiculo.textContent = 'Impedido de Circular / Retido na Oficina';
            } else {
                stVeiculo.className = 'badge bg-success-subtle text-success border border-success-subtle font-mono';
                stVeiculo.textContent = 'Liberado para Circulação';
            }

            // Links para o laudo
            const urlLaudo = 'laudoVistoria.php?id_vistoria=' + info.id_vistoria;
            document.getElementById('detalheLinkLaudo').href = urlLaudo;
            document.getElementById('detalheLinkLaudo').textContent = 'VST-' + String(info.id_vistoria).padStart(4, '0');
            document.getElementById('btnLaudoPdf').href = urlLaudo;

            // Esconde alerta anterior
            document.getElementById('msgAlertaDespacho').classList.add('d-none');
        }

        // Filtro rápido de abas
        function filtrarLista(statusFiltro, btn) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const itens = document.querySelectorAll('.item-ocorrencia');
            let primeiroVisivel = null;

            itens.forEach(item => {
                const itemStatus = item.getAttribute('data-status');
                if (statusFiltro === 'todas' || itemStatus === statusFiltro) {
                    item.classList.remove('d-none');
                    if (!primeiroVisivel) primeiroVisivel = item;
                } else {
                    item.classList.add('d-none');
                }
            });

            if (primeiroVisivel) {
                selecionarOcorrencia(primeiroVisivel);
            }
        }

        // Execução de Despacho com chamada AJAX
        async function executarDespacho(acao) {
            if (!ocorrenciaAtual) {
                alert('Selecione uma ocorrência para despachar.');
                return;
            }

            const confirmMsg = (acao === 'enviar_oficina')
                ? 'Confirma o envio do veículo para a oficina credenciada e retenção da circulação?'
                : 'Confirma a aprovação do reparo e liberação do veículo para a frota?';

            if (!confirm(confirmMsg)) return;

            const formData = new FormData();
            formData.append('id_ocorrencia', ocorrenciaAtual.id_ocorrencia);
            formData.append('acao', acao);

            try {
                const resp = await fetch('../backend/processar_despacho_ocorrencia.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await resp.json();

                if (res.sucesso) {
                    const alertBox = document.getElementById('msgAlertaDespacho');
                    alertBox.className = 'mt-3 alert alert-success py-2 px-3 small mb-0 d-block';
                    alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ' + res.mensagem;

                    // Atualiza objeto em memória
                    ocorrenciaAtual.status = res.novo_status;
                    ocorrenciaAtual.status_veiculo = res.status_veiculo;

                    // Atualiza o card na lista lateral
                    const cardAtivo = document.querySelector('.item-ocorrencia.ativo');
                    if (cardAtivo) {
                        cardAtivo.setAttribute('data-status', res.novo_status);
                        cardAtivo.setAttribute('data-info', JSON.stringify(ocorrenciaAtual));
                        const badgeSt = cardAtivo.querySelectorAll('.badge')[1];
                        if (badgeSt) {
                            badgeSt.textContent = res.status_label;
                            badgeSt.className = 'badge ' + (res.novo_status === 'em_oficina' ? 'badge-st-oficina' : 'badge-st-resolvida') + ' px-2 py-1';
                        }
                    }

                    // Atualiza visualização do painel
                    selecionarOcorrencia(cardAtivo);
                } else {
                    alert('Erro ao processar: ' + (res.erro || 'Falha na resposta do servidor.'));
                }
            } catch (err) {
                alert('Erro de conexão com o servidor: ' + err.message);
            }
        }
    </script>
</body>

</html>

