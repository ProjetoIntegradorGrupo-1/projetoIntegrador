<?php

session_start();

require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/index.php'); // Atualizado para .php
    exit;
}

$usuario_digitado = trim($_POST['txtuser'] ?? '');
$senha_digitada = $_POST['txtsenha'] ?? '';

if ($usuario_digitado === '' || $senha_digitada === '') {
    header('Location: ../../Frontend/index.php?erro=campos_obrigatorios'); // Atualizado para .php
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
    ':usuario' => $usuario_digitado
]);

$usuario = $stmt->fetch();

if (
    !$usuario ||
    $usuario['status'] !== 'ativo' ||
    !password_verify($senha_digitada, $usuario['senha'])
) {
    header('Location: ../../Frontend/index.php?erro=credenciais_invalidas'); // Atualizado para .php
    exit;
}

session_regenerate_id(true);

// CRIAÇÃO DAS VARIÁVEIS DE SESSÃO
$_SESSION['usuario_logado'] = true; // A chave mestra adicionada!
$_SESSION['id_usuario'] = $usuario['id_usuario'];
$_SESSION['nome_usuario'] = $usuario['nome'];
$_SESSION['perfil_usuario'] = $usuario['perfil'];

header('Location: ../php/oqfazer.php');
exit;