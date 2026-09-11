<?php
// oqfazer.php
session_start();

// Se o usuário não estiver logado, manda de volta para o login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

$nome_usuario = $_SESSION['nome_usuario'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O que fazer - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm" style="max-width: 500px; width: 100%;">
            
            <h1 class="h4 text-center mb-1">Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</h1>
            <p class="text-muted text-center small mb-4">Bem-vindo ao Axion</p>

            <form action="processar_opcao.php" method="post">
                <fieldset class="mb-4">
                    <legend class="form-label fw-bold small text-primary mb-3">O que você deseja fazer hoje?</legend>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt1" value="cadastrar_usuario" checked>
                        <label class="form-check-label" for="opt1">Cadastrar Usuário</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt2" value="cadastrar_veiculo">
                        <label class="form-check-label" for="opt2">Cadastrar Veículo</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt3" value="criar_checklist">
                        <label class="form-check-label" for="opt3">Criar Checklist</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt4" value="editar_usuario">
                        <label class="form-check-label" for="opt4">Editar Usuário</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt5" value="editar_veiculo">
                        <label class="form-check-label" for="opt5">Editar Veículo</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt6" value="editar_checklist">
                        <label class="form-check-label" for="opt6">Editar Checklist</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt7" value="excluir_usuario">
                        <label class="form-check-label" for="opt7">Excluir Usuário</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt8" value="excluir_veiculo">
                        <label class="form-check-label" for="opt8">Excluir Veículo</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt9" value="excluir_checklist">
                        <label class="form-check-label" for="opt9">Excluir Checklist</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="opcao" id="opt10" value="preencher_checklist">
                        <label class="form-check-label" for="opt10">Preencher Checklist</label>
                    </div>
                </fieldset>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                    <a href="logout.php" class="btn btn-outline-secondary">Sair / Voltar ao Login</a>
                </div>
            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>