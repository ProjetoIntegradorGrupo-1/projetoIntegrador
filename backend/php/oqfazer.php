<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
exigirLogin();

$perfil = $_SESSION['perfil_usuario'] ?? '';
$nome = $_SESSION['nome_usuario'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">

        <div class="card p-4 shadow-sm text-center" style="max-width: 500px; width: 100%;">

            <h1 class="h4 mb-3">Bem-vindo(a), <span class="text-primary"><?php echo htmlspecialchars($nome); ?></span>!
            </h1>
            <p class="text-muted mb-4">Perfil ativo: <strong><?php echo ucfirst(htmlspecialchars($perfil)); ?></strong>
            </p>

            <!-- O formulário envia os cliques para o nosso novo roteador -->
            <form action="../checklist/processar_opcao.php" method="POST" class="d-grid gap-3">

                <?php if ($perfil === 'administrador' || $perfil === 'gestor'): ?>
                    <button type="submit" name="opcao" value="ver_dashboard" class="btn btn-outline-dark p-3 fw-bold">
                        Aceder ao Dashboard
                    </button>

                    <!-- Mantenha os restantes botões abaixo -->
                     
                <?php endif; ?>

                <?php if ($perfil === 'administrador' || $perfil === 'gestor'): ?>
                    <button type="submit" name="opcao" value="cadastrar_veiculo" class="btn btn-outline-success p-3">
                        Gerenciar Veículos
                    </button>

                    <button type="submit" name="opcao" value="criar_checklist" class="btn btn-outline-info p-3">
                        Criar Modelos de Checklist
                    </button>
                <?php endif; ?>

                <!-- Botão liberado para todos os perfis operacionais -->
                <button type="submit" name="opcao" value="realizar_inspecao" class="btn btn-primary p-3 shadow-sm">
                    Realizar Inspeção Veicular
                </button>
                <!-- Botão de Histórico (pode ser visto pelo Gestor e Administrador, ou todos) -->
                <button type="submit" name="opcao" value="ver_historico"
                    class="btn btn-outline-secondary p-3 shadow-sm">
                    Consultar Histórico
                </button>

            </form>

            <hr class="my-4">

            <a href="../auth/logout.php" class="btn btn-danger w-100">Sair do Sistema</a>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>