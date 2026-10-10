<?php
// frontend/novaSenha.php
session_start();
require_once __DIR__ . '/../backend/conexao.php';

$token = trim($_GET['token'] ?? '');
$tokenValido = false;
$usuarioNome = '';
$mensagemErro = '';

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT r.id_usuario, r.expira_em, r.usado, u.nome, u.email 
                               FROM RecuperacaoSenha r
                               JOIN Usuarios u ON r.id_usuario = u.id_usuario
                               WHERE r.token = :token LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($registro) {
            if ($registro['usado'] == 1) {
                $mensagemErro = "Este link de recuperação já foi utilizado anteriormente.";
            } elseif (strtotime($registro['expira_em']) < time()) {
                $mensagemErro = "Este link de recuperação expirou (validade máxima de 2 horas).";
            } else {
                $tokenValido = true;
                $usuarioNome = $registro['nome'];
            }
        } else {
            $mensagemErro = "Token de recuperação inválido ou não encontrado.";
        }
    } catch (PDOException $e) {
        $mensagemErro = "Erro ao validar token: " . $e->getMessage();
    }
} else {
    $mensagemErro = "Nenhum token de recuperação fornecido na URL.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Nova Senha - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 shadow-sm" style="max-width: 420px; width: 100%;">
            <h1 class="h4 mb-3 text-center fw-bold">Redefinir Senha</h1>

            <?php if (!$tokenValido): ?>
                <div class="alert alert-danger text-center small mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Atenção:</strong> <?= htmlspecialchars($mensagemErro) ?>
                </div>
                <div class="d-grid gap-2">
                    <a href="esqueciSenha.html" class="btn btn-primary">Solicitar Novo Link</a>
                    <a href="index.html" class="btn btn-outline-secondary">Voltar ao Login</a>
                </div>
            <?php else: ?>
                <p class="text-muted text-center small mb-3">
                    Olá, <strong><?= htmlspecialchars($usuarioNome) ?></strong>. Digite sua nova senha de acesso abaixo.
                </p>

                <form action="../backend/processar_nova_senha.php" method="post">
                    <!-- Campo Oculto com o Token validado -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label for="novaSenha" class="form-label fw-semibold">Nova Senha</label>
                        <input type="password" name="txtNovaSenha" id="novaSenha" class="form-control" placeholder="Mínimo 6 caracteres" required minlength="6" autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="confirmarSenha" class="form-label fw-semibold">Confirmar Nova Senha</label>
                        <input type="password" name="txtConfirmarSenha" id="confirmarSenha" class="form-control" placeholder="Repita a nova senha" required minlength="6">
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <input type="submit" value="Salvar Nova Senha" class="btn btn-primary fw-semibold py-2">
                        <a href="index.html" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

