<?php
// frontend/cadcheck.php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}
require_once __DIR__ . '/../backend/conexao.php';

// Busca lista de veículos cadastrados (ativos e em manutenção/reinspeção, excluindo apenas os deletados)
$stmtVeiculos = $pdo->query("SELECT id_veiculo, placa, marca_modelo, km_rodado, status FROM Veiculos WHERE status != 'inativo' ORDER BY marca_modelo ASC");
$veiculos = $stmtVeiculos->fetchAll(PDO::FETCH_ASSOC);

// Busca modelos de checklist ativos
$stmtCheck = $pdo->query("SELECT id_checklist, titulo, categoria FROM Checklists WHERE status = 'ativo' ORDER BY id_checklist ASC");
$checklists = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

$dataAtual = date('Y-m-d');
$horaAtual = date('H:i');
$nomeVistoriador = $_SESSION['nome_usuario'] ?? 'Inspetor';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abertura de Vistoria - Axion</title>
    <!-- Google Fonts: Roboto Mono -->
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
        }
        .font-mono {
            font-family: 'Roboto Mono', monospace !important;
            letter-spacing: 0.02em;
        }
        .form-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }
        .campo-readonly {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1;
            color: #475569;
            cursor: not-allowed;
        }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card form-card p-4 bg-white" style="max-width: 680px; width: 100%;">
            
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-0">Nova Inspeção Veicular</h1>
                    <small class="text-muted">Abertura de checklist operacional de frota</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                    <i class="bi bi-shield-check me-1"></i> Auditoria Digital
                </span>
            </div>

            <form action="../backend/processar_cadastro_checklist.php" method="post" id="formVistoria">
                
                <!-- SELEÇÃO INTELIGENTE DE VEÍCULO (Eliminação da redundância visual - WCAG / Profa. Marta) -->
                <div class="mb-3 p-3 rounded bg-light border">
                    <label for="select_veiculo" class="form-label fw-bold small text-primary mb-1">
                        <i class="bi bi-truck me-1"></i> Veículo Inspecionado
                    </label>
                    <select id="select_veiculo" class="form-select mb-2" onchange="aoMudarVeiculo(this)" required autofocus>
                        <option value="" disabled selected>-- Selecione um veículo da frota --</option>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= htmlspecialchars($v['placa']) ?>" data-km="<?= $v['km_rodado'] ?>">
                                <?= htmlspecialchars($v['marca_modelo']) ?> — Placa: <?= htmlspecialchars($v['placa']) ?><?= ($v['status'] === 'manutencao') ? ' ⚠️ (Em Manutenção)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="__avulso__">➕ Outro veículo avulso (não cadastrado)</option>
                    </select>

                    <div class="row g-2 align-items-center">
                        <div class="col-sm-6">
                            <label for="placadoveiculo" class="form-label small text-muted mb-0">Placa Vinculada (Automática)</label>
                            <input type="text" id="placadoveiculo" name="placadoveiculo" class="form-control form-control-sm font-mono text-uppercase campo-readonly" placeholder="Selecione o veículo" maxlength="10" required readonly>
                        </div>
                        <div class="col-sm-6">
                            <small id="infoPlaca" class="text-muted d-block mt-3 mt-sm-0">
                                <i class="bi bi-lock-fill text-secondary me-1"></i> A placa é preenchida automaticamente para evitar inconsistências.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Modelo de Checklist -->
                    <div class="col-md-6 mb-3">
                        <label for="id_checklist" class="form-label small fw-semibold">Modelo de Checklist</label>
                        <select id="id_checklist" name="id_checklist" class="form-select form-select-sm" required>
                            <?php foreach ($checklists as $c): ?>
                                <option value="<?= $c['id_checklist'] ?>">
                                    <?= htmlspecialchars($c['titulo']) ?> (<?= htmlspecialchars($c['categoria'] ?? 'Geral') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Motorista Condutor -->
                    <div class="col-md-6 mb-3">
                        <label for="nomedomotorista" class="form-label small fw-semibold">Motorista Condutor</label>
                        <input type="text" id="nomedomotorista" name="nomedomotorista" class="form-control form-control-sm" placeholder="Nome completo do condutor" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Quilometragem (Odômetro) -->
                    <div class="col-md-6 mb-3">
                        <label for="kmrodado" class="form-label small fw-semibold">Quilometragem Atual (Km)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="kmrodado" name="kmrodado" class="form-control font-mono" placeholder="Ex: 45000" min="0" required>
                            <span class="input-group-text font-mono">km</span>
                        </div>
                    </div>

                    <!-- Vistoriador Responsável (Preenchido da Sessão) -->
                    <div class="col-md-6 mb-3">
                        <label for="nomevistoriador" class="form-label small fw-semibold">Vistoriador Responsável</label>
                        <input type="text" id="nomevistoriador" name="nomevistoriador" class="form-control form-control-sm campo-readonly" value="<?= htmlspecialchars($nomeVistoriador) ?>" readonly required>
                    </div>
                </div>

                <!-- TIMESTAMP DO SERVIDOR (Proteção antifraude contra digitação retroativa) -->
                <div class="mb-3 p-2 rounded bg-light border">
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="datavistoria" class="form-label small text-muted mb-1">
                                <i class="bi bi-calendar3 me-1"></i> Data do Registro
                            </label>
                            <input type="date" id="datavistoria" name="datavistoria" class="form-control form-control-sm font-mono campo-readonly" value="<?= $dataAtual ?>" readonly required>
                        </div>
                        <div class="col-6">
                            <label for="horavistoria" class="form-label small text-muted mb-1">
                                <i class="bi bi-clock me-1"></i> Horário do Registro
                            </label>
                            <input type="time" id="horavistoria" name="horavistoria" class="form-control form-control-sm font-mono campo-readonly" value="<?= $horaAtual ?>" readonly required>
                        </div>
                    </div>
                    <small class="text-success d-block mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-shield-check"></i> Timestamp fixado com horário oficial do servidor para conformidade jurídica e auditoria.
                    </small>
                </div>

                <!-- Botões de Ação -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary flex-fill fw-semibold">
                        <i class="bi bi-arrow-right-circle me-1"></i> Prosseguir para Itens de Vistoria
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- JS do Bootstrap e Lógica da Seleção Automática de Placa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function aoMudarVeiculo(select) {
            const inputPlaca = document.getElementById('placadoveiculo');
            const infoPlaca = document.getElementById('infoPlaca');
            const inputKm = document.getElementById('kmrodado');

            if (select.value === '__avulso__') {
                inputPlaca.value = '';
                inputPlaca.readOnly = false;
                inputPlaca.classList.remove('campo-readonly');
                inputPlaca.focus();
                infoPlaca.innerHTML = '<span class="text-primary"><i class="bi bi-pencil-square"></i> Digite a placa do veículo avulso.</span>';
            } else if (select.value) {
                inputPlaca.value = select.value;
                inputPlaca.readOnly = true;
                inputPlaca.classList.add('campo-readonly');
                
                // Preenche sugestão de KM se cadastrado
                const opt = select.options[select.selectedIndex];
                const km = opt.getAttribute('data-km');
                if (km && (!inputKm.value || inputKm.value === '0')) {
                    inputKm.value = km;
                }

                infoPlaca.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Placa vinculada com sucesso.</span>';
            }
        }
    </script>
</body>

</html>

