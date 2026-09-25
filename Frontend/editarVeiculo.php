<?php
require_once __DIR__ . '/../backend/auth/verificar_sessao.php';
require_once __DIR__ . '/../backend/config/conexao.php';

// Apenas administradores e gestores podem editar veículos
exigirPerfil(['administrador', 'gestor']);

// Busca todos os veículos ativos para popular o select
try {
    $stmtVeiculos = $pdo->query("SELECT id_veiculo, placa, marca, modelo FROM Veiculos WHERE status = 'ativo' ORDER BY placa");
    $veiculos = $stmtVeiculos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $veiculos = [];
}

// Se um veículo foi selecionado para carregamento dos dados
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
    <title>Editar Veículo - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/axion-pro.css">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm" style="max-width: 600px; width: 100%;">
            <h1 class="h4 text-center mb-4">Editar Veículo</h1>

            <!-- 1. BLOCO DE BUSCA DO VEÍCULO -->
            <form action="" method="GET" class="mb-4 p-3 bg-light border rounded">
                <label for="select-veiculo" class="form-label fw-bold">Selecione o Veículo para Editar</label>
                <div class="input-group">
                    <select id="select-veiculo" name="id_veiculo" class="form-select" required>
                        <option value="" selected disabled>Escolha um veículo cadastrado...</option>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v['id_veiculo'] ?>" <?= ($idSelecionado == $v['id_veiculo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['placa']) ?> | <?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Carregar Dados</button>
                </div>
            </form>

            <hr class="my-4">

            <?php if ($veiculoSelecionado): ?>
                <!-- 2. FORMULÁRIO DE EDIÇÃO DO VEÍCULO -->
                <form action="../backend/veiculos/processar_edicao_veiculo.php" method="POST">
                    <!-- ID Oculto do Veículo para o Backend -->
                    <input type="hidden" name="id_veiculo" value="<?= $veiculoSelecionado['id_veiculo'] ?>">

                    <div class="row">
                        <!-- Marca -->
                        <div class="col-md-6 mb-3">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" id="marca" name="marca" class="form-control" value="<?= htmlspecialchars($veiculoSelecionado['marca']) ?>" required>
                        </div>

                        <!-- Modelo -->
                        <div class="col-md-6 mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" id="modelo" name="modelo" class="form-control" value="<?= htmlspecialchars($veiculoSelecionado['modelo']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Ano -->
                        <div class="col-md-6 mb-3">
                            <label for="ano" class="form-label">Ano</label>
                            <input type="number" id="ano" name="ano" class="form-control" value="<?= htmlspecialchars($veiculoSelecionado['ano'] ?? '') ?>" min="1900" max="2099" required>
                        </div>

                        <!-- Placa -->
                        <div class="col-md-6 mb-3">
                            <label for="placa" class="form-label">Placa</label>
                            <input type="text" id="placa" name="placa" class="form-control text-uppercase" value="<?= htmlspecialchars($veiculoSelecionado['placa']) ?>" maxlength="7" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Cor -->
                        <div class="col-md-6 mb-3">
                            <label for="cor" class="form-label">Cor</label>
                            <input type="text" id="cor" name="cor" class="form-control" value="<?= htmlspecialchars($veiculoSelecionado['cor'] ?? '') ?>" required>
                        </div>

                        <!-- Km Rodado -->
                        <div class="col-md-6 mb-3">
                            <label for="kmrodado" class="form-label">Km Rodado</label>
                            <input type="number" id="kmrodado" name="kmrodado" class="form-control" value="<?= htmlspecialchars($veiculoSelecionado['kmrodado'] ?? 0) ?>" min="0" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Renavam -->
                        <div class="col-md-6 mb-3">
                            <label for="renavam" class="form-label">Renavam</label>
                            <input type="text" id="renavam" name="renavam" class="form-control" value="<?= htmlspecialchars($veiculoSelecionado['renavam'] ?? '') ?>" required>
                        </div>

                        <!-- Chassi -->
                        <div class="col-md-6 mb-3">
                            <label for="chassi" class="form-label">Chassi</label>
                            <input type="text" id="chassi" name="chassi" class="form-control text-uppercase" value="<?= htmlspecialchars($veiculoSelecionado['chassi'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="d-grid gap-2 mt-3">
                        <input type="submit" value="Salvar Alterações" class="btn btn-primary">
                        <a href="../backend/php/oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center text-muted py-3 small">
                    Selecione um veículo acima e clique em "Carregar Dados" para preencher o formulário de edição.
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