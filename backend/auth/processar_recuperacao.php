<?php

session_start();

// Garante que o caminho da conexão está correto de forma robusta
require_once __DIR__ . '/../config/conexao.php';

// Validação estrita do método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/index.html');
    exit;
}

$usuarioDigitado = trim($_POST['txtuser'] ?? '');
$senhaDigitada = $_POST['txtsenha'] ?? '';

// Validação de campos vazios
if ($usuarioDigitado === '' || $senhaDigitada === '') {
    header('Location: ../../Frontend/index.html?erro=campos_obrigatorios');
    exit;
}

try {
    $sql = "
        SELECT
            id_usuario,
            nome,
            cpf,
            email,
            senha,
            perfil,
            status
        FROM Usuarios
        WHERE email = :usuario
           OR cpf = :usuario
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario' => $usuarioDigitado
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Valida se o usuário existe, se está ativo e se a senha confere
    if (
        !$usuario ||
        $usuario['status'] !== 'ativo' ||
        !password_verify($senhaDigitada, $usuario['senha'])
    ) {
        // Mensagem genérica por segurança para não expor se o e-mail/CPF existe ou não
        header('Location: ../../Frontend/index.html?erro=credenciais_invalidas');
        exit;
    }

    // Prevenção contra Session Fixation
    session_regenerate_id(true);

    // Atribuição de dados essenciais à sessão
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nome_usuario'] = $usuario['nome'];
    $_SESSION['perfil_usuario'] = $usuario['perfil'];
    $_SESSION['email_usuario'] = $usuario['email']; // Opcional, mas útil

    // Redirecionamento de sucesso
    header('Location: ../php/oqfazer.php');
    exit;

} catch (PDOException $e) {
    // Em produção, registre o erro em um arquivo de log em vez de exibi-lo
    // error_log("Erro no login: " . $e->getMessage());
    header('Location: ../../Frontend/index.html?erro=sistema');
    exit;
}