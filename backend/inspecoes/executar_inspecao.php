<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirLogin();

$id_inspecao = $_GET['id'] ?? null;

if (!$id_inspecao) {
    header('Location: ../php/oqfazer.php?erro=inspecao_nao_encontrada');
    exit;
}

// 1. Carregar os dados principais da Inspeção, Veículo e Checklist
$sqlInspecao = "SELECT i.*, v.placa, v.marca, v.modelo, c.titulo 
                FROM Inspecoes i
                JOIN Veiculos v ON i.id_veiculo = v.id_veiculo
                JOIN Checklists c ON i.id_checklist = c.id_checklist
                WHERE i.id_inspecao = ?";
$stmtInsp = $pdo->prepare($sqlInspecao);
$stmtInsp->execute([$id_inspecao]);
$inspecao = $stmtInsp->fetch();

if (!$inspecao || $inspecao['status'] !== 'rascunho') {
    die("Inspeção inválida ou já finalizada.");
}

// 2. Carregar as perguntas do Checklist associado
$sqlPerguntas = "SELECT * FROM Perguntas WHERE id_checklist = ? ORDER BY ordem";
$stmtPerg = $pdo->prepare($sqlPerguntas);
$stmtPerg->execute([$inspecao['id_checklist']]);
$perguntas = $stmtPerg->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executar Inspeção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow-sm mx-auto" style="max-width: 800px;">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Inspeção: <?= htmlspecialchars($inspecao['titulo']) ?></h4>
                <small>Veículo:
                    <?= htmlspecialchars($inspecao['placa'] . ' - ' . $inspecao['marca'] . ' ' . $inspecao['modelo']) ?></small>
            </div>

            <div class="card-body p-4">
                <form action="salvar_respostas.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_inspecao" value="<?= $inspecao['id_inspecao'] ?>">

                    <?php foreach ($perguntas as $p): ?>
                        <div class="mb-4 p-3 border rounded bg-white shadow-sm">
                            <label class="form-label fw-bold">
                                <?= $p['ordem'] ?>. <?= htmlspecialchars($p['texto_pergunta']) ?>
                                <?php if ($p['obrigatorio']): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>

                            <!-- Desenha o input consoante o tipo de resposta configurado -->
                            <?php if ($p['tipo_resposta'] === 'sim_nao'): ?>
                                <select name="respostas[<?= $p['id_pergunta'] ?>][valor]" class="form-select"
                                    <?= $p['obrigatorio'] ? 'required' : '' ?>>
                                    <option value="" selected disabled>Selecione...</option>
                                    <option value="Conforme">Conforme (Sim)</option>
                                    <option value="Não Conforme">Não Conforme (Não)</option>
                                    <option value="Não Aplicável">Não Aplicável</option>
                                </select>

                            <?php elseif ($p['tipo_resposta'] === 'numero'): ?>
                                <input type="number" name="respostas[<?= $p['id_pergunta'] ?>][valor]" class="form-control"
                                    placeholder="Introduza o valor numérico" <?= $p['obrigatorio'] ? 'required' : '' ?>>

                            <?php else: ?>
                                <input type="text" name="respostas[<?= $p['id_pergunta'] ?>][valor]" class="form-control"
                                    placeholder="Introduza o texto" <?= $p['obrigatorio'] ? 'required' : '' ?>>
                            <?php endif; ?>

                            <!-- Campo opcional para observações em cada pergunta -->
                            <div class="mt-2">
                                <input type="text" name="respostas[<?= $p['id_pergunta'] ?>][observacao]"
                                    class="form-control form-control-sm text-muted"
                                    placeholder="Observação opcional para este item...">
                            </div>
                            <div class="mt-3 p-3 bg-light border rounded border-warning">
                                <label class="form-label text-dark fw-bold small">Registar Ocorrência <small
                                        class="text-muted">(Preencha se houver um problema)</small></label>
                                <input type="text" name="respostas[<?= $p['id_pergunta'] ?>][ocorrencia]"
                                    class="form-control form-control-sm mb-2"
                                    placeholder="Descreva o defeito ou não conformidade...">

                                <label class="form-label text-dark fw-bold small">Anexar Evidências <small
                                        class="text-muted">(Fotografias)</small></label>
                                <input type="file" name="evidencias_<?= $p['id_pergunta'] ?>[]"
                                    class="form-control form-control-sm" multiple accept="image/*">
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <hr class="my-4">

                    <div class="mb-4">
                        <label for="observacao_geral" class="form-label fw-bold">Observações Gerais da Inspeção</label>
                        <textarea name="observacao_geral" id="observacao_geral" class="form-control" rows="3"
                            placeholder="Anotações finais..."></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="acao" value="salvar_rascunho"
                            class="btn btn-outline-primary">Guardar Rascunho</button>
                        <button type="submit" name="acao" value="finalizar" class="btn btn-success">Finalizar
                            Inspeção</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>