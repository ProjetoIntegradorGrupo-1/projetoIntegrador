<?php
require_once __DIR__ . '/../backend/auth/verificar_sessao.php';
require_once __DIR__ . '/../backend/config/conexao.php';

// Apenas administradores e gestores podem excluir veículos
exigirPerfil(['administrador', 'gestor']);

// Busca todos os veículos ativos para popular o select
try {
    $stmtVeiculos = $pdo->query("SELECT id_veiculo, placa, modelo, marca FROM Veiculos WHERE status = 'ativo' ORDER BY placa");
    $veiculos = $stmtVeiculos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $veiculos = [];
}

// Se um veículo foi selecionado via GET para exibição dos detalhes
$veiculoSelecionado = null;
$idSelecionado = $_GET['id_veiculo'] ?? null;

if (!empty($idSelecionado)) {
    try {
        $stmtDetalhe = $pdo->prepare("SELECT * FROM Veiculos WHERE id_veiculo = ? LIMIT 1");
        $stmtDetalhe->execute([$idSelecionado]);
        $veiculoSelecionado = $stmtDetalhe->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $veiculoSelecionado = null;
    }
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
    <link rel="stylesheet" href="css/axion-pro.css">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm border-danger" style="max-width: 550px; width: 100%;">
            <h1 class="h4 text-center text-danger mb-1">Excluir Veículo</h1>
            <p class="text-muted text-center small mb-4">Remova o cadastro do veículo da frota</p>

            <!-- Seleção do Veículo -->
            <form action="" method="GET" class="mb-4">
                <label for="select-veiculo" class="form-label fw-bold">Selecione o Veículo</label>
                <div class="input-group">
                    <select id="select-veiculo" name="id_veiculo" class="form-select" required>
                        <option value="" selected disabled>Escolha um veículo...</option>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v['id_veiculo'] ?>" <?= ($idSelecionado == $v['id_veiculo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['placa']) ?> | <?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-danger" type="submit">Carregar</button>
                </div>
            </form>

            <hr>

            <?php if ($veiculoSelecionado): ?>
                <!-- Confirmação e Envio -->
                <form action="../backend/veiculos/processar_exclusao_veiculo.php" method="POST">
                    <input type="hidden" name="id_veiculo" value="<?= $veiculoSelecionado['id_veiculo'] ?>">

                    <div class="card bg-danger-subtle border-danger p-3 mb-4">
                        <h2 class="h6 text-danger fw-bold mb-2">Dados do Veículo Selecionado:</h2>
                        <ul class="mb-0 small text-dark ps-3">
                            <li><strong>Placa:</strong> <?= htmlspecialchars($veiculoSelecionado['placa']) ?></li>
                            <li><strong>Modelo/Marca:</strong> <?= htmlspecialchars($veiculoSelecionado['marca']) ?> <?= htmlspecialchars($veiculoSelecionado['modelo']) ?></li>
                            <li><strong>Ano:</strong> <?= htmlspecialchars($veiculoSelecionado['ano'] ?? 'N/D') ?></li>
                            <li><strong>Chassi:</strong> <?= htmlspecialchars($veiculoSelecionado['chassi'] ?? 'N/D') ?></li>
                        </ul>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirmarExclusaoVeiculo" required>
                        <label class="form-check-label small fw-semibold text-danger" for="confirmarExclusaoVeiculo">
                            Confirmar a exclusão permanente deste veículo do sistema.
                        </label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">Excluir Veículo</button>
                        <a href="../backend/php/oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center text-muted py-3 small">
                    Selecione um veículo acima e clique em "Carregar" para visualizar os detalhes e prosseguir com a exclusão.
                </div>
                <div class="d-grid gap-2">
                    <a href="../backend/php/oqfazer.php" class="btn btn-outline-secondary">Voltar ao Menu</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- BARRA DE ACESSIBILIDADE FLUTUANTE -->
    <div class="position-fixed bottom-0 start-0 p-3" style="z-index: 1050;">
        <div class="bg-white p-2 rounded-pill shadow-sm border d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" onclick="toggleAltoContraste()" title="Ativar Alto Contraste">
                🌓 Contraste
            </button>
            <div class="vr"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle fw-bold" onclick="mudarZoom('menos')" title="Diminuir Letra">
                A-
            </button>
            <button type="button" class="btn btn-sm btn-light rounded-pill px-2 text-muted small" onclick="mudarZoom('reset')" title="Restaurar Padrão">
                Zoom
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle fw-bold" onclick="mudarZoom('mais')" title="Aumentar Letra">
                A+
            </button>
        </div>
    </div>

    <!-- Scripts essenciais no final -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/axion-accessibility.js"></script>
</body>

</html>