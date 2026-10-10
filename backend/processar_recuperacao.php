<?php
// backend/processar_recuperacao.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['txtEmail'] ?? $_POST['email'] ?? '');

    if (empty($email)) {
        header("Location: ../frontend/esqueciSenha.html");
        exit;
    }

    try {
        // 1. Verifica se o e-mail está cadastrado na tabela de Usuários
        $sql = "SELECT id_usuario, nome, email FROM Usuarios WHERE email = :email AND ativo = 1 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 2. Invalida tokens anteriores não utilizados deste usuário
            $stmtCancel = $pdo->prepare("UPDATE RecuperacaoSenha SET usado = 1 WHERE id_usuario = :id AND usado = 0");
            $stmtCancel->execute([':id' => $usuario['id_usuario']]);

            // 3. Gera token criptograficamente seguro e define validade de 2 horas
            $token = bin2hex(random_bytes(32));
            $expira_em = date('Y-m-d H:i:s', strtotime('+2 hours'));

            // 4. Salva o token real na tabela RecuperacaoSenha
            $stmtIns = $pdo->prepare("INSERT INTO RecuperacaoSenha (id_usuario, token, expira_em, usado) VALUES (:id_usuario, :token, :expira_em, 0)");
            $stmtIns->execute([
                ':id_usuario' => $usuario['id_usuario'],
                ':token'      => $token,
                ':expira_em'  => $expira_em
            ]);

            $linkRedefinicao = "../frontend/novaSenha.php?token=" . urlencode($token);
            ?>
            <!DOCTYPE html>
            <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Link de Recuperação - Axion</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            </head>
            <body class="bg-light">
                <div class="container d-flex justify-content-center align-items-center vh-100">
                    <div class="card p-4 shadow-sm text-center" style="max-width: 500px; width: 100%;">
                        <div class="mb-3 text-success">
                            <i class="bi bi-shield-check" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h4 mb-2 text-dark">Link de Recuperação Gerado!</h1>
                        <p class="text-muted small mb-3">
                            Um token de autenticação seguro foi registrado no banco de dados para o usuário <strong><?= htmlspecialchars($usuario['nome']) ?></strong> (<?= htmlspecialchars($usuario['email']) ?>).
                        </p>
                        
                        <div class="alert alert-info text-start small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Ambiente de Homologação / Acadêmico:</strong> Como o servidor local não possui envio de e-mails via SMTP externo habilitado, utilize o botão abaixo para testar o fluxo de redefinição real.
                        </div>

                        <div class="p-3 bg-white border rounded mb-3 text-break small">
                            <code><?= htmlspecialchars($linkRedefinicao) ?></code>
                            <div class="text-muted mt-1" style="font-size: 0.75rem;">Válido até: <?= date('d/m/Y H:i', strtotime($expira_em)) ?> (2 horas)</div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?= $linkRedefinicao ?>" class="btn btn-primary fw-semibold py-2">
                                <i class="bi bi-key me-1"></i> Redefinir Senha Agora
                            </a>
                            <a href="../frontend/index.html" class="btn btn-outline-secondary">Voltar ao Login</a>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            // E-mail não encontrado ou inativo
            ?>
            <!DOCTYPE html>
            <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <title>E-mail não encontrado - Axion</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container d-flex justify-content-center align-items-center vh-100">
                    <div class="card p-4 shadow-sm text-center" style="max-width: 420px; width: 100%;">
                        <div class="alert alert-danger mb-3">
                            E-mail <strong><?= htmlspecialchars($email) ?></strong> não localizado ou usuário inativo.
                        </div>
                        <a href="../frontend/esqueciSenha.html" class="btn btn-primary">Tentar Novamente</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao processar recuperação: " . addslashes($e->getMessage()) . "');
                window.location.href = '../frontend/esqueciSenha.html';
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/esqueciSenha.html");
    exit;
}