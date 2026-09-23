<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirLogin();

// Procurar veículos ativos
$stmtVeiculos = $pdo->query("SELECT id_veiculo, placa, marca, modelo FROM Veiculos WHERE status = 'ativo' ORDER BY placa");
$veiculos = $stmtVeiculos->fetchAll();

// Procurar checklists ativos
$stmtChecklists = $pdo->query("SELECT id_checklist, titulo, categoria FROM Checklists WHERE status = 'ativo' ORDER BY titulo");
$checklists = $stmtChecklists->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Inspeção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm" style="max-width: 600px; width: 100%;">
            <h1 class="h4 text-center mb-4 text-primary">Iniciar Nova Inspeção</h1>

            <form action="iniciar_inspecao.php" method="POST">

                <div class="mb-4">
                    <label for="id_veiculo" class="form-label">1. Selecione o Veículo</label>
                    <select name="id_veiculo" id="id_veiculo" class="form-select" required>
                        <option value="" selected disabled>Escolha um veículo da frota...</option>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v['id_veiculo'] ?>">
                                <?= htmlspecialchars($v['placa'] . ' - ' . $v['marca'] . ' ' . $v['modelo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="id_checklist" class="form-label">2. Selecione o Checklist</label>
                    <select name="id_checklist" id="id_checklist" class="form-select" required>
                        <option value="" selected disabled>Escolha o guião de inspeção...</option>
                        <?php foreach ($checklists as $c): ?>
                            <option value="<?= $c['id_checklist'] ?>">
                                <?= htmlspecialchars($c['titulo'] . ' (' . $c['categoria'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="quilometragem" class="form-label">Quilometragem Atual do Veículo <small
                            class="text-muted">(Opcional neste passo)</small></label>
                    <input type="number" id="quilometragem" name="quilometragem" class="form-control"
                        placeholder="Ex: 50000" min="0">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Prosseguir para Inspeção</button>
                    <a href="../php/oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>

            </form>
        </div>
    </div>
</body>

</html>