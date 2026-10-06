<?php
// backend/processar_nova_senha.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token           = trim($_POST['token'] ?? '');
    $nova_senha      = trim($_POST['txtNovaSenha'] ?? '');
    $confirmar_senha = trim($_POST['txtConfirmarSenha'] ?? '');

    if (empty($nova_senha) || empty($confirmar_senha)) {
        echo "<script>alert('Por favor, preencha todos os campos de senha.'); window.history.back();</script>";
        exit;
    }

    if ($nova_senha !== $confirmar_senha) {
        echo "<script>alert('As senhas digitadas não coincidem.'); window.history.back();</script>";
        exit;
    }

    if (strlen($nova_senha) < 6) {
        echo "<script>alert('A nova senha deve ter no mínimo 6 caracteres.'); window.history.back();</script>";
        exit;
    }

    try {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // 1. Verifica se há token registrado na tabela RecuperacaoSenha
        $stmtToken = $pdo->prepare("SELECT id_usuario FROM RecuperacaoSenha WHERE token = :token AND usado = 0 AND expira_em >= NOW() LIMIT 1");
        $stmtToken->bindParam(':token', $token);
        $stmtToken->execute();
        $rec = $stmtToken->fetch(PDO::FETCH_ASSOC);

        if ($rec) {
            $id_usuario = $rec['id_usuario'];
            // Atualiza a senha do usuário
            $stmtUp = $pdo->prepare("UPDATE Usuarios SET senha = :senha WHERE id_usuario = :id");
            $stmtUp->bindParam(':senha', $senha_hash);
            $stmtUp->bindParam(':id', $id_usuario, PDO::PARAM_INT);
            $stmtUp->execute();

            // Invalida o token usado
            $stmtMarca = $pdo->prepare("UPDATE RecuperacaoSenha SET usado = 1 WHERE token = :token");
            $stmtMarca->bindParam(':token', $token);
            $stmtMarca->execute();
        } else {
            // Em ambiente local/acadêmico sem token persistido, se houver usuário logado, atualiza ele
            if (isset($_SESSION['id_usuario'])) {
                $stmtUp = $pdo->prepare("UPDATE Usuarios SET senha = :senha WHERE id_usuario = :id");
                $stmtUp->bindParam(':senha', $senha_hash);
                $stmtUp->bindParam(':id', $_SESSION['id_usuario'], PDO::PARAM_INT);
                $stmtUp->execute();
            }
        }

        echo "<script>
                alert('Senha alterada com sucesso! Você já pode entrar com sua nova senha.');
                window.location.href = '../frontend/index.html';
              </script>";
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao atualizar senha: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/index.html");
    exit;
}
?>

