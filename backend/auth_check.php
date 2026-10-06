<?php
// backend/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Middleware de controle de acesso baseado em papéis (RBAC).
 *
 * @param array $perfisPermitidos Lista de papéis autorizados (ex: ['gestor'], ['gestor', 'supervisor'])
 * @param string|null $caminhoRedirecionamento Rota de retorno customizada se fornecida
 */
function autorizarAcesso(array $perfisPermitidos = [], ?string $caminhoRedirecionamento = null) {
    $script = $_SERVER['PHP_SELF'] ?? '';
    $isBackend = (strpos($script, '/backend/') !== false);

    // 1. Verifica autenticação de sessão
    if (!isset($_SESSION['id_usuario'])) {
        $loginUrl = $isBackend ? '../frontend/index.html' : 'index.html';
        header("Location: " . $loginUrl);
        exit;
    }

    // 2. Define caminho padrão caso não fornecido
    if ($caminhoRedirecionamento === null) {
        $caminhoRedirecionamento = $isBackend ? '../frontend/oqfazer.php' : 'oqfazer.php';
    }

    // 3. Verifica se há restrição de papéis
    if (!empty($perfisPermitidos)) {
        $perfilAtual = $_SESSION['perfil_usuario'] ?? 'motorista';
        
        if (!in_array($perfilAtual, $perfisPermitidos)) {
            $separador = (strpos($caminhoRedirecionamento, '?') !== false) ? '&' : '?';
            header("Location: " . $caminhoRedirecionamento . $separador . "erro=acesso_negado");
            exit;
        }
    }
}

