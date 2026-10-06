<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Edição cadastral de veículos é restrita a Gestores e Supervisores
autorizarAcesso(['gestor', 'supervisor']);


// Busca lista de todos os veículos ativos para o select
$stmtTodos = $pdo->query("SELECT id_veiculo, placa, marca_modelo, marca, modelo, ano, cor, km_rodado, renavam, chassi 
                          FROM Veiculos 
                          WHERE status != 'inativo' 
                          ORDER BY marca_modelo ASC");
$listaVeiculos = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

// Identifica o veículo a ser editado
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
    <title>Editar Veículo - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm" style="max-width: 650px; width: 100%;">
            <h1 class="h4 text-center mb-4">Editar Veículo</h1>

            <!-- 1. BLOCO DE BUSCA DINÂMICA DO VEÍCULO -->
            <form action="editarVeiculo.php" method="get" class="mb-4 p-3 bg-light border rounded">
                <label for="select-veiculo" class="form-label fw-bold">Selecione o Veículo para Editar</label>
                <div class="input-group">
                    <select id="select-veiculo" name="id_veiculo" class="form-select" onchange="this.form.submit()" required>
                        <?php if (empty($listaVeiculos)): ?>
                            <option value="" disabled selected>Nenhum veículo ativo encontrado</option>
                        <?php else: ?>
                            <?php foreach ($listaVeiculos as $v): ?>
                                <option value="<?= $v['id_veiculo'] ?>" <?= ($v['id_veiculo'] == $id_selecionado) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['placa']) ?> | <?= htmlspecialchars($v['marca_modelo']) ?> (<?= htmlspecialchars($v['cor'] ?? 'Cor N/D') ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Carregar Dados</button>
                </div>
            </form>

            <hr class="my-4">

            <?php if ($veiculoAtual): ?>
            <!-- 2. FORMULÁRIO DE EDIÇÃO PREENCHIDO COM DADOS REAIS DO BANCO -->
            <form action="../backend/processar_edicao_veiculo.php" method="post">
                <!-- ID Oculto do Veículo para o Backend -->
                <input type="hidden" name="id_veiculo" value="<?= $veiculoAtual['id_veiculo'] ?>">

                <div class="row">
                    <!-- Marca -->
                    <div class="col-md-6 mb-3">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" id="marca" name="marca" class="form-control" value="<?= htmlspecialchars($veiculoAtual['marca'] ?? '') ?>" required>
                    </div>

                    <!-- Modelo -->
                    <div class="col-md-6 mb-3">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" id="modelo" name="modelo" class="form-control" value="<?= htmlspecialchars($veiculoAtual['modelo'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Ano -->
                    <div class="col-md-6 mb-3">
                        <label for="ano" class="form-label">Ano</label>
                        <input type="number" id="ano" name="ano" class="form-control" value="<?= htmlspecialchars($veiculoAtual['ano'] ?? '') ?>" min="1900" max="2099" required>
                    </div>

                    <!-- Placa -->
                    <div class="col-md-6 mb-3">
                        <label for="placa" class="form-label">Placa</label>
                        <input type="text" id="placa" name="placa" class="form-control text-uppercase" value="<?= htmlspecialchars($veiculoAtual['placa'] ?? '') ?>" maxlength="10" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Cor -->
                    <div class="col-md-6 mb-3">
                        <label for="cor" class="form-label">Cor</label>
                        <input type="text" id="cor" name="cor" class="form-control" value="<?= htmlspecialchars($veiculoAtual['cor'] ?? '') ?>" required>
                    </div>

                    <!-- Km Rodado -->
                    <div class="col-md-6 mb-3">
                        <label for="kmrodado" class="form-label">Km Rodado</label>
                        <input type="number" id="kmrodado" name="kmrodado" class="form-control" value="<?= htmlspecialchars($veiculoAtual['km_rodado'] ?? 0) ?>" min="0" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Renavam -->
                    <div class="col-md-6 mb-3">
                        <label for="renavam" class="form-label">Renavam</label>
                        <input type="text" id="renavam" name="renavam" class="form-control" value="<?= htmlspecialchars($veiculoAtual['renavam'] ?? '') ?>" required>
                    </div>

                    <!-- Chassi -->
                    <div class="col-md-6 mb-3">
                        <label for="chassi" class="form-label">Chassi</label>
                        <input type="text" id="chassi" name="chassi" class="form-control text-uppercase" value="<?= htmlspecialchars($veiculoAtual['chassi'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-grid gap-2 mt-3">
                    <input type="submit" value="Salvar Alterações" class="btn btn-primary">
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">Nenhum veículo ativo disponível para edição.</div>
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

