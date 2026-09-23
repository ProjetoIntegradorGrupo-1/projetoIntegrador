<?php

session_start();

require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/index.html');
    exit;
}

$usuarioDigitado = trim($_POST['txtuser'] ?? '');
$senhaDigitada = $_POST['txtsenha'] ?? '';

if ($usuarioDigitado === '' || $senhaDigitada === '') {
    header('Location: ../../Frontend/index.html?erro=campos_obrigatorios');
    exit;
}

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

$usuario = $stmt->fetch();

if (
    !$usuario ||
    $usuario['status'] !== 'ativo' ||
    !password_verify($senhaDigitada, $usuario['senha'])
) {
    header('Location: ../../Frontend/index.html?erro=credenciais_invalidas');
    exit;
}

session_regenerate_id(true);

$_SESSION['id_usuario'] = $usuario['id_usuario'];
$_SESSION['nome_usuario'] = $usuario['nome'];
$_SESSION['perfil_usuario'] = $usuario['perfil'];

header('Location: ../php/oqfazer.php');
exit;