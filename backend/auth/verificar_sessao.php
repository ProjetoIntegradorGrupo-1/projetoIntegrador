<?php

function iniciarSessao(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function exigirLogin(): void
{
    iniciarSessao();

    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['perfil_usuario']) || !isset($_SESSION['nome_usuario'])) {
        header('Location: ../../Frontend/index.html?erro=nao_autenticado');
        exit;
    }
}

function exigirPerfil(array $perfisPermitidos): void
{
    exigirLogin();

    $perfilUsuario = $_SESSION['perfil_usuario'] ?? '';

    if (!in_array($perfilUsuario, $perfisPermitidos, true)) {
        header('Location: ../php/oqfazer.php?erro=sem_permissao');
        exit;
    }
}