<?php
// frontend/cadcar.php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Apenas Gestores e Supervisores têm permissão para cadastrar novos veículos na frota
autorizarAcesso(['gestor', 'supervisor']);

$perfilLogado = $_SESSION['perfil_usuario'] ?? 'supervisor';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Veículos - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm border-0" style="max-width: 650px; width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="oqfazer.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar ao Menu
                </a>
                <span class="badge bg-primary px-3 py-2 text-uppercase">
                    <i class="bi bi-car-front-fill"></i> Cadastro de Frota
                </span>
            </div>

            <h1 class="h4 text-center mb-1 fw-bold">Cadastro de Veículos</h1>
            <p class="text-muted text-center small mb-4">Adicione novos veículos para disponibilidade na fila de vistorias</p>

            <form action="../backend/processar_cadastro_veiculo.php" method="post">
                <div class="row">
                    <!-- Marca -->
                    <div class="col-md-6 mb-3">
                        <label for="marca" class="form-label fw-semibold">Marca</label>
                        <input type="text" id="marca" name="marca" class="form-control" placeholder="Ex: Volkswagen" required autofocus>
                    </div>

                    <!-- Modelo -->
                    <div class="col-md-6 mb-3">
                        <label for="modelo" class="form-label fw-semibold">Modelo</label>
                        <input type="text" id="modelo" name="modelo" class="form-control" placeholder="Ex: Gol 1.0" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Ano -->
                    <div class="col-md-6 mb-3">
                        <label for="ano" class="form-label fw-semibold">Ano Fabricação/Modelo</label>
                        <input type="number" id="ano" name="ano" class="form-control" placeholder="Ex: 2022" min="1900" max="2099" required>
                    </div>

                    <!-- Placa -->
                    <div class="col-md-6 mb-3">
                        <label for="placa" class="form-label fw-semibold">Placa (Padrão Mercosul)</label>
                        <input type="text" id="placa" name="placa" class="form-control text-uppercase" placeholder="Ex: ABC1D23" maxlength="7" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Cor -->
                    <div class="col-md-6 mb-3">
                        <label for="cor" class="form-label fw-semibold">Cor</label>
                        <input type="text" id="cor" name="cor" class="form-control" placeholder="Ex: Branco" required>
                    </div>

                    <!-- Km Rodado -->
                    <div class="col-md-6 mb-3">
                        <label for="kmrodado" class="form-label fw-semibold">Km Atual (Hodômetro)</label>
                        <input type="number" id="kmrodado" name="kmrodado" class="form-control" placeholder="Ex: 45000" min="0" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Renavam -->
                    <div class="col-md-6 mb-3">
                        <label for="renavam" class="form-label fw-semibold">Renavam</label>
                        <input type="text" id="renavam" name="renavam" class="form-control" placeholder="Digite o Renavam" required>
                    </div>

                    <!-- Chassi -->
                    <div class="col-md-6 mb-3">
                        <label for="chassi" class="form-label fw-semibold">Chassi</label>
                        <input type="text" id="chassi" name="chassi" class="form-control text-uppercase" placeholder="Digite o Chassi" required>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary fw-semibold py-2">
                        <i class="bi bi-check-circle-fill me-1"></i> Cadastrar Veículo
                    </button>
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

