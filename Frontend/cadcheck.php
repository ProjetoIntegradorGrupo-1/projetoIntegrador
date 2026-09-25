
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preencher Checklist</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/axion-pro.css">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm" style="max-width: 600px; width: 100%;">
            <h1 class="h4 text-center mb-4">Iniciar Vistoria</h1>

            <!-- Aponta para o nosso script backend de iniciar inspeção -->
            <form action="../backend/inspecoes/iniciar_inspecao.php" method="POST">
                <div class="row">
                    <!-- ID do Veículo ou Placa (conforme a sua tabela Veiculos) -->
                    <div class="col-md-6 mb-3">
                        <label for="id_veiculo" class="form-label">ID do Veículo</label>
                        <input type="number" id="id_veiculo" name="id_veiculo" class="form-control" placeholder="ID do veículo" required autofocus>
                    </div>

                    <!-- ID do Checklist / Guião -->
                    <div class="col-md-6 mb-3">
                        <label for="id_checklist" class="form-label">ID do Guião (Checklist)</label>
                        <input type="number" id="id_checklist" name="id_checklist" class="form-control" placeholder="ID do checklist" required>
                    </div>
                </div>

                <div class="row">
                    <!-- Quilometragem -->
                    <div class="col-md-12 mb-3">
                        <label for="km_atual" class="form-label">Quilometragem Atual (Km)</label>
                        <input type="number" id="km_atual" name="km_atual" class="form-control" placeholder="Ex: 45000" min="0" required>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Iniciar Inspeção</button>
                    <a href="../backend/php/oqfazer.php" class="btn btn-outline-secondary">Voltar ao Menu</a>
                </div>
            </form>
        </div>
    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>