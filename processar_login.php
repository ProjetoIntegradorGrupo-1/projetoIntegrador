<?php
// processar_login.php
session_start(); // Inicia a sessão para guardar os dados do usuário logado
require_once 'conexao.php'; // Puxa a conexão com o banco

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['txtuser']);
    $senha_digitada = trim($_POST['txtsenha']);

    // Busca o usuário no banco (aceita tanto E-mail quanto CPF/Matrícula)
    $sql = "SELECT id_usuario, nome, senha, perfil FROM Usuarios WHERE email = :user OR cpf_matricula = :user LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user', $user);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se o usuário existe e se a senha bate
    // Nota: Usar password_verify() é a prática correta de mercado para senhas criptografadas.
    if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
        
        // Login bem-sucedido: guarda os dados na sessão
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome_usuario'] = $usuario['nome'];
        $_SESSION['perfil_usuario'] = $usuario['perfil'];

        // Redireciona para o menu principal
        header("Location: oqfazer.php");
        exit;
    } else {
        // Falha no login: redireciona de volta com erro
        header("Location: index.html?erro=credenciais_invalidas");
        exit;
    }
} else {
    // Se tentarem acessar a página diretamente sem enviar o formulário
    header("Location: index.html");
    exit;
}
?>