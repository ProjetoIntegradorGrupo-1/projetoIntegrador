<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}
require_once '../backend/conexao.php';

// Busca lista de todos os usuários ativos
$stmtTodos = $pdo->query("SELECT id_usuario, nome, cpf_matricula, perfil, email FROM Usuarios WHERE ativo = 1 ORDER BY nome ASC");
$listaUsuarios = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

$id_selecionado = intval($_GET['id_usuario'] ?? 0);
$usuarioAtual = null;

if ($id_selecionado > 0) {
    $stmtUser = $pdo->prepare("SELECT * FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtUser->execute();
    $usuarioAtual = $stmtUser->fetch(PDO::FETCH_ASSOC);
} elseif (count($listaUsuarios) > 0) {
    $id_selecionado = $listaUsuarios[0]['id_usuario'];
    $stmtUser = $pdo->prepare("SELECT * FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtUser->execute();
    $usuarioAtual = $stmtUser->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Usuário - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm border-danger" style="max-width: 550px; width: 100%;">
            <h1 class="h4 text-center text-danger mb-1">Excluir Usuário</h1>
            <p class="text-muted text-center small mb-4">Esta ação desativará o acesso do usuário ao sistema</p>

            <!-- Seleção do Usuário Dinâmica -->
            <form action="excluirUsuario.php" method="get" class="mb-4">
                <label for="select-usuario" class="form-label fw-bold">Selecione o Usuário</label>
                <div class="input-group">
                    <select id="select-usuario" name="id_usuario" class="form-select" onchange="this.form.submit()" required>
                        <?php if (empty($listaUsuarios)): ?>
                            <option value="" disabled selected>Nenhum usuário cadastrado</option>
                        <?php else: ?>
                            <?php foreach ($listaUsuarios as $u): ?>
                                <option value="<?= $u['id_usuario'] ?>" <?= ($u['id_usuario'] == $id_selecionado) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nome']) ?> (CPF: <?= htmlspecialchars($u['cpf_matricula']) ?>) - <?= ucfirst(htmlspecialchars($u['perfil'])) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-outline-danger" type="submit">Carregar</button>
                </div>
            </form>

            <hr>

            <?php if ($usuarioAtual): ?>
            <!-- Confirmação e Envio -->
            <form action="../backend/processar_exclusao_usuario.php" method="post">
                <input type="hidden" name="id_usuario" value="<?= $usuarioAtual['id_usuario'] ?>">

                <div class="card bg-danger-subtle border-danger p-3 mb-4">
                    <h2 class="h6 text-danger fw-bold mb-2">Dados do Usuário Selecionado:</h2>
                    <ul class="mb-0 small text-dark ps-3">
                        <li><strong>Nome:</strong> <?= htmlspecialchars($usuarioAtual['nome']) ?></li>
                        <li><strong>CPF:</strong> <?= htmlspecialchars($usuarioAtual['cpf_matricula']) ?></li>
                        <li><strong>Perfil:</strong> <?= ucfirst(htmlspecialchars($usuarioAtual['perfil'])) ?></li>
                        <li><strong>E-mail:</strong> <?= htmlspecialchars($usuarioAtual['email']) ?></li>
                    </ul>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="confirmarExclusao" required>
                    <label class="form-check-label small fw-semibold text-danger" for="confirmarExclusao">
                        Estou ciente de que esta ação desativará o acesso deste usuário ao sistema.
                    </label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger">Excluir Usuário</button>
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">Nenhum usuário disponível para exclusão.</div>
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

