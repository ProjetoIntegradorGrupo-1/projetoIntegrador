<?php

/**
 * Inicia a sessão de forma segura, se já não estiver ativa.
 */
function iniciarSessao(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Configurações de segurança recomendadas para cookies de sessão
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        session_start();
    }
}

/**
 * Garante que o usuário está autenticado no sistema.
 */
function exigirLogin(): void
{
    iniciarSessao();

    if (empty($_SESSION['id_usuario']) || empty($_SESSION['perfil_usuario']) || empty($_SESSION['nome_usuario'])) {
        // Limpa qualquer dado residual da sessão
        session_unset();
        session_destroy();
        
        header('Location: ../../Frontend/index.html?erro=nao_autenticado');
        exit;
    }
}

/**
 * Garante que o usuário autenticado possui um dos perfis permitidos.
 * 
 * @param array $perfisPermitidos Lista de perfis com acesso liberado (ex: ['administrador', 'gestor'])
 */
function exigirPerfil(array $perfisPermitidos): void
{
    exigirLogin();

    $perfilUsuario = $_SESSION['perfil_usuario'] ?? '';

    // Verifica de forma estricta se o perfil do usuário está na lista permitida
    if (!in_array($perfilUsuario, $perfisPermitidos, true)) {
        header('Location: ../php/oqfazer.php?erro=sem_permissao');
        exit;
    }
}