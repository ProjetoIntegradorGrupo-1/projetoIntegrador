<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}
require_once '../backend/conexao.php';

// Busca lista de todos os checklists ativos
$stmtTodos = $pdo->query("SELECT id_checklist, titulo, categoria FROM Checklists WHERE status = 'ativo' ORDER BY titulo ASC");
$listaChecklists = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

$id_selecionado = intval($_GET['id_checklist'] ?? 0);
$checklistAtual = null;
$totalPerguntas = 0;
$totalVistorias = 0;

if ($id_selecionado > 0) {
    $stmtC = $pdo->prepare("SELECT * FROM Checklists WHERE id_checklist = :id AND status = 'ativo' LIMIT 1");
    $stmtC->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtC->execute();
    $checklistAtual = $stmtC->fetch(PDO::FETCH_ASSOC);
} elseif (count($listaChecklists) > 0) {
    $id_selecionado = $listaChecklists[0]['id_checklist'];
    $stmtC = $pdo->prepare("SELECT * FROM Checklists WHERE id_checklist = :id AND status = 'ativo' LIMIT 1");
    $stmtC->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtC->execute();
    $checklistAtual = $stmtC->fetch(PDO::FETCH_ASSOC);
}

if ($checklistAtual) {
    // Conta perguntas vinculadas
    $stmtCountP = $pdo->prepare("SELECT COUNT(*) FROM Perguntas WHERE id_checklist = :id");
    $stmtCountP->bindParam(':id', $checklistAtual['id_checklist'], PDO::PARAM_INT);
    $stmtCountP->execute();
    $totalPerguntas = $stmtCountP->fetchColumn();

    // Conta vistorias que utilizaram este checklist
    $stmtCountV = $pdo->prepare("SELECT COUNT(*) FROM Vistorias WHERE id_checklist = :id");
    $stmtCountV->bindParam(':id', $checklistAtual['id_checklist'], PDO::PARAM_INT);
    $stmtCountV->execute();
    $totalVistorias = $stmtCountV->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Checklist - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm border-danger" style="max-width: 550px; width: 100%;">
            <h1 class="h4 text-center text-danger mb-1">Excluir Modelo de Checklist</h1>
            <p class="text-muted text-center small mb-4">Inative o formulário para que não seja mais utilizado em vistorias</p>

            <!-- Seleção do Checklist Dinâmica -->
            <form action="excluirChecklist.php" method="get" class="mb-4">
                <label for="select-checklist" class="form-label fw-bold">Selecione o Modelo de Checklist</label>
                <div class="input-group">
                    <select id="select-checklist" name="id_checklist" class="form-select" onchange="this.form.submit()" required>
                        <?php if (empty($listaChecklists)): ?>
                            <option value="" disabled selected>Nenhum checklist ativo cadastrado</option>
                        <?php else: ?>
                            <?php foreach ($listaChecklists as $c): ?>
                                <option value="<?= $c['id_checklist'] ?>" <?= ($c['id_checklist'] == $id_selecionado) ? 'selected' : '' ?>>
                                    #<?= $c['id_checklist'] ?> - <?= htmlspecialchars($c['titulo']) ?> (<?= ucfirst(htmlspecialchars($c['categoria'] ?? 'Geral')) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-outline-danger" type="submit">Carregar</button>
                </div>
            </form>

            <hr>

            <?php if ($checklistAtual): ?>
            <!-- Confirmação e Envio para o Backend -->
            <form action="../backend/processar_exclusao_checklist.php" method="post">
                <input type="hidden" name="id_checklist" value="<?= $checklistAtual['id_checklist'] ?>">

                <div class="card bg-danger-subtle border-danger p-3 mb-4">
                    <h2 class="h6 text-danger fw-bold mb-2">Resumo do Checklist Selecionado:</h2>
                    <ul class="mb-0 small text-dark ps-3">
                        <li><strong>Título:</strong> <?= htmlspecialchars($checklistAtual['titulo']) ?></li>
                        <li><strong>Categoria:</strong> <?= ucfirst(htmlspecialchars($checklistAtual['categoria'] ?? 'Geral')) ?></li>
                        <li><strong>Total de Perguntas:</strong> <?= $totalPerguntas ?> pergunta(s) vinculada(s)</li>
                        <li><strong>Histórico de Execuções:</strong> <?= $totalVistorias ?> vistoria(s) registrada(s)</li>
                    </ul>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="confirmarExclusaoChecklist" required>
                    <label class="form-check-label small fw-semibold text-danger" for="confirmarExclusaoChecklist">
                        Confirmar a inativação deste modelo de checklist. O histórico de vistorias passadas será preservado.
                    </label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger">Inativar Checklist</button>
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">Nenhum checklist ativo disponível para inativação.</div>
                <div class="d-grid">
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Voltar ao Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

