<?php
session_start();

// Verifica se a variável de sessão criada no login NÃO existe
if (!isset($_SESSION['usuario_logado'])) {
    // Expulsa o invasor de volta para a tela de login
    header("Location: index.php?erro=nao_autorizado");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/axion-pro.css">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 shadow-sm" style="max-width: 400px; width: 100%;">
            <h1 class="h4 mb-3 text-center">Recuperar Senha</h1>
            <p class="text-muted text-center small mb-4">
                Digite o e-mail cadastrado para receber o link de redefinição de senha.
            </p>

            <!-- Mensagem de Erro (Exibida pelo PHP quando o e-mail não for encontrado) -->
            <!-- Para testar o visual do erro, remova a classe 'd-none' -->
            <div class="alert alert-danger alert-dismissible fade show d-none" role="alert" id="alertaErro">
                Usuário não encontrado.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <form action="../backend/auth/processar_recuperacao.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" name="txtEmail" id="email" class="form-control" placeholder="nome@empresa.com" required autofocus>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <input type="submit" value="Enviar Link de Recuperação" class="btn btn-primary">
<a href="../backend/php/oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>                </div>
            </form>
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