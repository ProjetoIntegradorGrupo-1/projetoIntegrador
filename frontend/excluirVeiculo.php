<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Exclusão de veículos é prerrogativa exclusiva do Gestor Administrador
autorizarAcesso(['gestor']);


// Busca lista de todos os veículos ativos
$stmtTodos = $pdo->query("SELECT id_veiculo, placa, marca_modelo, ano, cor, chassi, km_rodado 
                          FROM Veiculos 
                          WHERE status != 'inativo' 
                          ORDER BY marca_modelo ASC");
$listaVeiculos = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

$id_selecionado = intval($_GET['id_veiculo'] ?? 0);
$veiculoAtual = null;

if ($id_selecionado > 0) {
    $stmtVeic = $pdo->prepare("SELECT * FROM Veiculos WHERE id_veiculo = :id AND status != 'inativo' LIMIT 1");
    $stmtVeic->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtVeic->execute();
    $veiculoAtual = $stmtVeic->fetch(PDO::FETCH_ASSOC);
} elseif (count($listaVeiculos) > 0) {
    $id_selecionado = $listaVeiculos[0]['id_veiculo'];
    $stmtVeic = $pdo->prepare("SELECT * FROM Veiculos WHERE id_veiculo = :id AND status != 'inativo' LIMIT 1");
    $stmtVeic->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtVeic->execute();
    $veiculoAtual = $stmtVeic->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Veículo - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm border-danger" style="max-width: 550px; width: 100%;">
            <h1 class="h4 text-center text-danger mb-1">Excluir Veículo</h1>
            <p class="text-muted text-center small mb-4">Inative o cadastro do veículo da frota ativa</p>

            <!-- Seleção do Veículo Dinâmica -->
            <form action="excluirVeiculo.php" method="get" class="mb-4">
                <label for="select-veiculo" class="form-label fw-bold">Selecione o Veículo</label>
                <div class="input-group">
                    <select id="select-veiculo" name="id_veiculo" class="form-select" onchange="this.form.submit()" required>
                        <?php if (empty($listaVeiculos)): ?>
                            <option value="" disabled selected>Nenhum veículo ativo cadastrado</option>
                        <?php else: ?>
                            <?php foreach ($listaVeiculos as $v): ?>
                                <option value="<?= $v['id_veiculo'] ?>" <?= ($v['id_veiculo'] == $id_selecionado) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['placa']) ?> | <?= htmlspecialchars($v['marca_modelo']) ?> (<?= htmlspecialchars($v['cor'] ?? 'Cor N/D') ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-outline-danger" type="submit">Carregar</button>
                </div>
            </form>

            <hr>

            <?php if ($veiculoAtual): ?>
            <!-- Confirmação e Envio para o Backend -->
            <form action="../backend/processar_exclusao_veiculo.php" method="post">
                <input type="hidden" name="id_veiculo" value="<?= $veiculoAtual['id_veiculo'] ?>">

                <div class="card bg-danger-subtle border-danger p-3 mb-4">
                    <h2 class="h6 text-danger fw-bold mb-2">Dados do Veículo Selecionado:</h2>
                    <ul class="mb-0 small text-dark ps-3">
                        <li><strong>Placa:</strong> <?= htmlspecialchars($veiculoAtual['placa']) ?></li>
                        <li><strong>Modelo/Marca:</strong> <?= htmlspecialchars($veiculoAtual['marca_modelo']) ?></li>
                        <li><strong>Ano:</strong> <?= htmlspecialchars($veiculoAtual['ano'] ?? 'N/D') ?></li>
                        <li><strong>Cor:</strong> <?= htmlspecialchars($veiculoAtual['cor'] ?? 'N/D') ?></li>
                        <li><strong>Km Atual:</strong> <?= number_format($veiculoAtual['km_rodado'] ?? 0, 0, ',', '.') ?> km</li>
                        <li><strong>Chassi:</strong> <?= htmlspecialchars($veiculoAtual['chassi'] ?? 'N/D') ?></li>
                    </ul>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="confirmarExclusaoVeiculo" required>
                    <label class="form-check-label small fw-semibold text-danger" for="confirmarExclusaoVeiculo">
                        Confirmar a inativação deste veículo no sistema Axion.
                    </label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger">Inativar Veículo</button>
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">Nenhum veículo disponível para inativação.</div>
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

