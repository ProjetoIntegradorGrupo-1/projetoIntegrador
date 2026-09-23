<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirLogin();
$id_inspecao = $_GET['id'] ?? null;

if (!$id_inspecao) {
    header('Location: historico.php');
    exit;
}

// 1. Carregar dados gerais da Inspeção
$sqlInsp = "SELECT i.*, v.placa, v.marca, v.modelo, c.titulo, u.nome AS responsável 
            FROM Inspecoes i 
            JOIN Veiculos v ON i.id_veiculo = v.id_veiculo 
            JOIN Checklists c ON i.id_checklist = c.id_checklist 
            JOIN Usuarios u ON i.id_usuario = u.id_usuario 
            WHERE i.id_inspecao = ?";
$stmtInsp = $pdo->prepare($sqlInsp);
$stmtInsp->execute([$id_inspecao]);
$inspecao = $stmtInsp->fetch();

if (!$inspecao)
    die("Inspeção não encontrada.");

// 2. Carregar Respostas, Perguntas e Ocorrências
$sqlResp = "SELECT r.id_resposta, r.valor_resposta, r.observacao, p.texto_pergunta, p.ordem, o.descricao AS ocorrencia 
            FROM Respostas r 
            JOIN Perguntas p ON r.id_pergunta = p.id_pergunta 
            LEFT JOIN Ocorrencias o ON r.id_resposta = o.id_resposta 
            WHERE r.id_inspecao = ? ORDER BY p.ordem";
$stmtResp = $pdo->prepare($sqlResp);
$stmtResp->execute([$id_inspecao]);
$respostas = $stmtResp->fetchAll();

// 3. Agrupar Evidências (Fotografias) por Resposta
$sqlEvid = "SELECT id_resposta, caminho_arquivo FROM Evidencias WHERE id_resposta IN (SELECT id_resposta FROM Respostas WHERE id_inspecao = ?)";
$stmtEvid = $pdo->prepare($sqlEvid);
$stmtEvid->execute([$id_inspecao]);
$evidencias_raw = $stmtEvid->fetchAll();

$evidencias = [];
foreach ($evidencias_raw as $e) {
    $evidencias[$e['id_resposta']][] = $e['caminho_arquivo'];
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Inspeção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow-sm mx-auto" style="max-width: 900px;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Inspeção #<?= htmlspecialchars($inspecao['id_inspecao']) ?> -
                    <?= htmlspecialchars($inspecao['titulo']) ?>
                </h5>
                <a href="historico.php" class="btn btn-sm btn-outline-light">Voltar ao Histórico</a>
            </div>

            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Veículo:</strong>
                            <?= htmlspecialchars($inspecao['placa'] . ' - ' . $inspecao['marca'] . ' ' . $inspecao['modelo']) ?>
                        </p>
                        <p><strong>Responsável:</strong> <?= htmlspecialchars($inspecao['responsável']) ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong>Data Início:</strong> <?= date('d/m/Y H:i', strtotime($inspecao['data_inicio'])) ?>
                        </p>
                        <p><strong>Estado:</strong>
                            <?= $inspecao['status'] === 'finalizada' ? '<span class="text-success fw-bold">Finalizada</span>' : '<span class="text-warning fw-bold">Rascunho</span>' ?>
                        </p>
                    </div>
                </div>

                <h5 class="text-secondary border-bottom pb-2">Itens Inspecionados</h5>

                <?php foreach ($respostas as $r): ?>
                    <div
                        class="p-3 mb-3 border rounded <?= !empty($r['ocorrencia']) ? 'border-danger bg-light' : 'border-success' ?>">
                        <p class="fw-bold mb-1"><?= $r['ordem'] ?>. <?= htmlspecialchars($r['texto_pergunta']) ?></p>
                        <p class="mb-1"><strong>Resposta:</strong> <?= htmlspecialchars($r['valor_resposta']) ?></p>

                        <?php if (!empty($r['observacao'])): ?>
                            <p class="mb-1 text-muted small"><strong>Observação:</strong>
                                <?= htmlspecialchars($r['observacao']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($r['ocorrencia'])): ?>
                            <div class="alert alert-danger mt-2 py-2 px-3 mb-2">
                                <strong>⚠️ Ocorrência Registada:</strong> <?= htmlspecialchars($r['ocorrencia']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Galeria de Evidências -->
                        <?php if (isset($evidencias[$r['id_resposta']])): ?>
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <?php foreach ($evidencias[$r['id_resposta']] as $img_path): ?>
                                    <a href="../../<?= htmlspecialchars($img_path) ?>" target="_blank">
                                        <img src="../../<?= htmlspecialchars($img_path) ?>" alt="Evidência" class="img-thumbnail"
                                            style="height: 100px; object-fit: cover;">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($inspecao['observacao_geral'])): ?>
                    <div class="mt-4 p-3 bg-light border rounded">
                        <h6 class="fw-bold">Observações Gerais</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($inspecao['observacao_geral'])) ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>