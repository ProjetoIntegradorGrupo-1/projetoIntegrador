<?php
// processar_login.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['txtuser'] ?? '');
    $senha_digitada = trim($_POST['txtsenha'] ?? '');
    $cpf_limpo = preg_replace('/\D/', '', $user);
    $cpf_mascarado = (strlen($cpf_limpo) === 11)
        ? substr($cpf_limpo, 0, 3) . '.' . substr($cpf_limpo, 3, 3) . '.' . substr($cpf_limpo, 6, 3) . '-' . substr($cpf_limpo, 9, 2)
        : $user;

    // Aceita atalho de perfil (admin, supervisor, motorista, cliente), e-mail ou CPF (com ou sem máscara)
    $atalhos = [
        'admin'      => 'admin@axion.com',
        'supervisor' => 'supervisor@axion.com',
        'motorista'  => 'motorista@axion.com',
        'cliente'    => 'cliente@axion.com'
    ];
    $buscaEmail = $atalhos[strtolower($user)] ?? $user;

    $sql = "SELECT id_usuario, nome, email, senha, perfil
            FROM Usuarios
            WHERE email = :email
               OR cpf_matricula = :user
               OR cpf_matricula = :cpf_masc
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email'    => $buscaEmail,
        ':user'     => $user,
        ':cpf_masc' => $cpf_mascarado
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
        $_SESSION['id_usuario']     = $usuario['id_usuario'];
        $_SESSION['nome_usuario']   = $usuario['nome'];
        $_SESSION['email_usuario']  = $usuario['email'];
        $_SESSION['perfil_usuario'] = $usuario['perfil'];

        header("Location: oqfazer.php");
        exit;
    } else {
        header("Location: index.html?erro=credenciais_invalidas");
        exit;
    }
} else {
    header("Location: index.html");
    exit;
}
?>
