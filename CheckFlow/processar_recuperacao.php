<?php
// processar_recuperacao.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    try {
        // Verifica se o e-mail está cadastrado na tabela de Usuários
        $sql = "SELECT id_usuario, nome FROM Usuarios WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Como estamos em ambiente de desenvolvimento local (XAMPP) sem servidor de e-mail configurado,
            // geramos um token de recuperação seguro e simulamos o envio exibindo o link na tela.
            $token = bin2hex(random_bytes(32));
            
            // Aqui você poderia salvar o token no banco caso fizesse a validação por link,
            // mas para fins práticos e acadêmicos, vamos simular a recuperação informando que o e-mail foi encontrado.
            
            echo "<script>
                    alert('E-mail encontrado! Um link de recuperação foi simulado para: " . htmlspecialchars($email) . "');
                    window.location.href = 'index.html';
                  </script>";
            exit;
        } else {
            // E-mail não encontrado no banco
            echo "<script>
                    alert('E-mail não cadastrado no sistema.');
                    window.location.href = 'esqueciSenha.html';
                  </script>";
            exit;
        }

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao processar recuperação: " . addslashes($e->getMessage()) . "');
                window.location.href = 'esqueciSenha.html';
              </script>";
        exit;
    }

} else {
    header("Location: esqueciSenha.html");
    exit;
}
?>