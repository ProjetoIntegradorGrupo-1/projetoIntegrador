<?php
// frontend/laudoVistoria.php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}
require_once '../backend/conexao.php';

$id_vistoria = intval($_GET['id_vistoria'] ?? 0);

if ($id_vistoria <= 0) {
    header("Location: dashboard.php");
    exit;
}

// 1. Busca os dados da Vistoria com joins
$sql = "SELECT v.*, 
               c.titulo AS titulo_checklist, c.categoria AS categoria_checklist,
               ve.marca, ve.modelo, ve.ano AS ano_veiculo, ve.cor AS cor_veiculo, 
               ve.renavam, ve.chassi, ve.marca_modelo,
               um.cpf_matricula AS cpf_motorista, um.cnh AS cnh_motorista,
               uv.cpf_matricula AS cpf_vistoriador
        FROM Vistorias v
        LEFT JOIN Checklists c ON v.id_checklist = c.id_checklist
        LEFT JOIN Veiculos ve ON v.id_veiculo = ve.id_veiculo
        LEFT JOIN Usuarios um ON v.id_motorista = um.id_usuario
        LEFT JOIN Usuarios uv ON v.id_vistoriador = uv.id_usuario
        WHERE v.id_vistoria = :id LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id_vistoria, PDO::PARAM_INT);
$stmt->execute();
$vistoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vistoria) {
    echo "<script>alert('Vistoria não encontrada.'); window.location.href = 'dashboard.php';</script>";
    exit;
}

// 2. Busca Evidências Fotográficas
$stmtEv = $pdo->prepare("SELECT * FROM EvidenciasVistoria WHERE id_vistoria = :id ORDER BY id_evidencia ASC");
$stmtEv->bindParam(':id', $id_vistoria, PDO::PARAM_INT);
$stmtEv->execute();
$evidencias = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

$statusFormatado = match($vistoria['status']) {
    'aprovado' => 'APROVADO (SEM RESTRIÇÕES)',
    'aprovado_com_restricoes' => 'APROVADO COM RESTRIÇÕES (AVARIAS CONSTATADAS)',
    'rejeitado' => 'REJEITADO (VEÍCULO INOPERANTE)',
    default => 'PENDENTE DE CONCLUSÃO'
};

$badgeCor = match($vistoria['status']) {
    'aprovado' => 'border-success text-success',
    'aprovado_com_restricoes' => 'border-warning text-warning-emphasis',
    'rejeitado' => 'border-danger text-danger',
    default => 'border-secondary text-secondary'
};
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laudo de Vistoria #<?= $vistoria['id_vistoria'] ?> - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .laudo-container {
            max-width: 900px;
            background: #ffffff;
            margin: 30px auto;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        .header-laudo {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0d6efd;
            border-left: 4px solid #0d6efd;
            padding-left: 10px;
            margin-top: 25px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .tabela-dados th {
            width: 25%;
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 0.85rem;
            color: #495057;
        }

        .tabela-dados td {
            font-size: 0.9rem;
        }

        .box-assinatura {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 8px;
            text-align: center;
        }

        .img-assinatura {
            max-height: 80px;
            display: block;
            margin: 0 auto -10px auto;
        }

        .foto-evidencia {
            max-height: 200px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        /* Otimização Estrita para Impressão e PDF */
        @media print {
            body {
                background: none;
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .laudo-container {
                box-shadow: none;
                margin: 0;
                padding: 10mm;
                max-width: 100%;
                border-radius: 0;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>

<body>

    <!-- BARRA DE AÇÕES (NÃO VISÍVEL NA IMPRESSÃO/PDF) -->
    <div class="container-fluid bg-dark text-white py-2 px-4 no-print shadow-sm sticky-top">
        <div class="d-flex justify-content-between align-items-center max-w-900 mx-auto" style="max-width: 900px;">
            <div>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Voltar ao Dashboard
                </a>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-printer me-1"></i> Imprimir / Salvar em PDF
                </button>
            </div>
        </div>
    </div>

    <!-- DOCUMENTO OFICIAL DO LAUDO -->
    <div class="laudo-container">

        <!-- CABEÇALHO OFICIAL -->
        <div class="header-laudo d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">AXION FROTAS • LAUDO OFICIAL DE VISTORIA</h1>
                <p class="text-muted small mb-0">Sistema Corporativo de Vistoria e Inspeção Veicular</p>
                <p class="text-muted small mb-0">CNPJ: 00.000.000/0001-00 • Operação Brasil</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary fs-6 px-3 py-2 mb-1">LAUDO TÉCNICO</span>
                <p class="fw-bold mb-0 text-dark">Nº #<?= str_pad($vistoria['id_vistoria'], 6, '0', STR_PAD_LEFT) ?></p>
                <small class="text-muted">Emissão: <?= date('d/m/Y H:i:s') ?></small>
            </div>
        </div>

        <!-- PARECER DE STATUS GERAL -->
        <div class="p-3 mb-4 rounded border text-center <?= $badgeCor ?> bg-light">
            <span class="small text-muted text-uppercase fw-bold d-block">Resultado da Avaliação Operacional</span>
            <span class="fs-5 fw-bold"><?= $statusFormatado ?></span>
        </div>

        <!-- 1. DADOS DO VEÍCULO -->
        <div class="section-title">1. Dados do Veículo Inspecionado</div>
        <table class="table table-bordered tabela-dados mb-4">
            <tbody>
                <tr>
                    <th>Placa do Veículo</th>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($vistoria['placa_veiculo']) ?></td>
                    <th>Marca / Modelo</th>
                    <td><?= htmlspecialchars($vistoria['marca_modelo'] ?? ($vistoria['marca'] . ' ' . $vistoria['modelo'])) ?></td>
                </tr>
                <tr>
                    <th>Ano de Fabricação</th>
                    <td><?= htmlspecialchars($vistoria['ano_veiculo'] ?? 'N/D') ?></td>
                    <th>Cor Predominante</th>
                    <td><?= htmlspecialchars($vistoria['cor_veiculo'] ?? 'N/D') ?></td>
                </tr>
                <tr>
                    <th>Quilometragem (Odômetro)</th>
                    <td class="fw-bold"><?= number_format($vistoria['km_rodado'], 0, ',', '.') ?> km</td>
                    <th>Renavam</th>
                    <td><?= htmlspecialchars($vistoria['renavam'] ?? 'N/D') ?></td>
                </tr>
                <tr>
                    <th>Número do Chassi</th>
                    <td colspan="3" class="text-uppercase"><?= htmlspecialchars($vistoria['chassi'] ?? 'N/D') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- 2. DADOS DA OPERAÇÃO E INSPETORES -->
        <div class="section-title">2. Dados da Inspeção e Operadores</div>
        <table class="table table-bordered tabela-dados mb-4">
            <tbody>
                <tr>
                    <th>Data da Vistoria</th>
                    <td><?= date('d/m/Y', strtotime($vistoria['data_vistoria'])) ?></td>
                    <th>Horário da Vistoria</th>
                    <td><?= substr($vistoria['hora_vistoria'], 0, 5) ?> hrs</td>
                </tr>
                <tr>
                    <th>Modelo de Checklist</th>
                    <td><?= htmlspecialchars($vistoria['titulo_checklist'] ?? 'Vistoria Geral') ?></td>
                    <th>Categoria</th>
                    <td><?= ucfirst(htmlspecialchars($vistoria['categoria_checklist'] ?? 'Geral')) ?></td>
                </tr>
                <tr>
                    <th>Motorista Condutor</th>
                    <td>
                        <?= htmlspecialchars($vistoria['nome_motorista']) ?><br>
                        <small class="text-muted">CPF/Doc: <?= htmlspecialchars($vistoria['cpf_motorista'] ?? 'Registrado') ?></small>
                    </td>
                    <th>Vistoriador Responsável</th>
                    <td>
                        <?= htmlspecialchars($vistoria['nome_vistoriador']) ?><br>
                        <small class="text-muted">Matrícula: #<?= htmlspecialchars($vistoria['id_vistoriador']) ?></small>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- 3. PARECER TÉCNICO E NÃO CONFORMIDADES -->
        <div class="section-title">3. Parecer Técnico de Não Conformidade</div>
        <div class="p-3 mb-4 rounded border bg-light">
            <?php if (!empty($vistoria['descricao_nao_conformidade'])): ?>
                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Avarias ou Irregularidades Registradas:</h6>
                <p class="mb-0 text-dark" style="white-space: pre-line;"><?= htmlspecialchars($vistoria['descricao_nao_conformidade']) ?></p>
            <?php else: ?>
                <div class="text-success fw-semibold">
                    <i class="bi bi-check-circle-fill me-1"></i> Todos os itens inspecionados encontram-se em perfeita conformidade com as normas de segurança e padrões operacionais da frota.
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. REGISTRO FOTOGRÁFICO DE EVIDÊNCIAS -->
        <?php if (!empty($evidencias)): ?>
            <div class="section-title">4. Registro Fotográfico de Evidências</div>
            <div class="row g-3 mb-4">
                <?php foreach ($evidencias as $idx => $ev): 
                    $caminhoArquivo = '../backend/' . $ev['caminho_arquivo'];
                ?>
                <div class="col-md-4 col-sm-6 text-center">
                    <div class="card p-2 border shadow-none bg-light">
                        <?php if ($ev['tipo_evidencia'] === 'foto' && file_exists($caminhoArquivo)): ?>
                            <img src="<?= htmlspecialchars($caminhoArquivo) ?>" alt="Evidência" class="foto-evidencia w-100 mb-2">
                        <?php else: ?>
                            <div class="p-4 bg-white border rounded mb-2 text-muted">
                                <i class="bi bi-file-earmark-text fs-1 d-block mb-1"></i>
                                Documento Anexo
                            </div>
                        <?php endif; ?>
                        <span class="small text-muted fw-semibold d-block text-truncate"><?= htmlspecialchars($ev['nome_original']) ?></span>
                        <small class="text-secondary">Foto #<?= $idx + 1 ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- 5. TERMO DE RESPONSABILIDADE E ASSINATURAS DIGITAIS -->
        <div class="section-title">5. Declaração e Assinaturas Digitais Coletadas</div>
        <p class="small text-muted text-justify mb-4">
            Declaramos para os devidos fins de direito e controle de conformidade que o veículo acima qualificado foi vistoriado nos termos deste laudo, estando as partes cientes das condições mecânicas, elétricas e estéticas apontadas neste documento. As assinaturas abaixo foram colhidas digitalmente através de dispositivo eletrônico em tela sensível.
        </p>

        <div class="row mt-4">
            <!-- Assinatura do Motorista -->
            <div class="col-6">
                <div class="text-center">
                    <?php if (!empty($vistoria['assinatura_motorista'])): ?>
                        <img src="<?= $vistoria['assinatura_motorista'] ?>" alt="Assinatura Motorista" class="img-assinatura">
                    <?php else: ?>
                        <div style="height: 60px;" class="d-flex align-items-center justify-content-center text-muted small">
                            [Assinatura não coletada]
                        </div>
                    <?php endif; ?>
                    <div class="box-assinatura">
                        <strong class="d-block small"><?= htmlspecialchars($vistoria['nome_motorista']) ?></strong>
                        <span class="text-muted small">Motorista Condutor</span>
                    </div>
                </div>
            </div>

            <!-- Assinatura do Vistoriador -->
            <div class="col-6">
                <div class="text-center">
                    <?php if (!empty($vistoria['assinatura_vistoriador'])): ?>
                        <img src="<?= $vistoria['assinatura_vistoriador'] ?>" alt="Assinatura Vistoriador" class="img-assinatura">
                    <?php else: ?>
                        <div style="height: 60px;" class="d-flex align-items-center justify-content-center text-muted small">
                            [Assinatura não coletada]
                        </div>
                    <?php endif; ?>
                    <div class="box-assinatura">
                        <strong class="d-block small"><?= htmlspecialchars($vistoria['nome_vistoriador']) ?></strong>
                        <span class="text-muted small">Vistoriador Responsável</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RODAPÉ DE CERTIFICAÇÃO DIGITAL -->
        <div class="mt-5 pt-3 border-top text-center text-muted small">
            <span>Documento emitido eletronicamente pela Plataforma Axion • Hash de Validação: <?= md5($vistoria['id_vistoria'] . $vistoria['data_vistoria'] . $vistoria['placa_veiculo']) ?></span>
        </div>

    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
