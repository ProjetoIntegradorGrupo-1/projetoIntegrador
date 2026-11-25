<?php
/**
 * backend/auth.php
 * Controle de sessão e de nível de acesso usado por todos os endpoints PHP.
 * O perfil SEMPRE vem do banco (no login) e nunca pode ser trocado pelo usuário.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const AXION_PERFIS = ['admin', 'supervisor', 'motorista', 'cliente'];

function axion_usuario_logado(): ?array {
    if (empty($_SESSION['id_usuario'])) return null;
    return [
        'id'     => (int)$_SESSION['id_usuario'],
        'nome'   => $_SESSION['nome_usuario'] ?? '',
        'email'  => $_SESSION['email_usuario'] ?? '',
        'perfil' => $_SESSION['perfil_usuario'] ?? '',
    ];
}

function axion_quer_json(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'application/json') !== false
        || ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['acao']);
}

/** Interrompe a requisição com erro (JSON para chamadas fetch, redirecionamento para formulários). */
function axion_negar(int $status, string $msg, string $redirect = 'index.html'): void {
    if (axion_quer_json()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false, 'erro' => $msg]);
    } else {
        $base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/backend/') !== false || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/database/') !== false) ? '../' : '';
        header('Location: ' . $base . $redirect);
    }
    exit;
}

/** Exige usuário logado e, opcionalmente, um dos perfis informados. Retorna o usuário. */
function axion_exigir_perfil(array $perfis = AXION_PERFIS): array {
    global $pdo;
    $u = axion_usuario_logado();
    if (!$u) {
        axion_negar(401, 'Sessão expirada. Faça login novamente.', 'index.html');
    }
    // Confere no banco se o usuário ainda existe e está ativo, e recarrega o perfil real.
    if (isset($pdo)) {
        $st = $pdo->prepare("SELECT nome, email, perfil, status FROM Usuarios WHERE id_usuario = :id LIMIT 1");
        $st->execute([':id' => $u['id']]);
        $row = $st->fetch();
        if (!$row || ($row['status'] ?? 'ativo') !== 'ativo') {
            $_SESSION = [];
            session_destroy();
            axion_negar(401, 'Usuário não encontrado ou inativo. Faça login novamente.', 'index.html');
        }
        $_SESSION['nome_usuario']   = $row['nome'];
        $_SESSION['email_usuario']  = $row['email'];
        $_SESSION['perfil_usuario'] = $row['perfil'];
        $u['nome'] = $row['nome'];
        $u['email'] = $row['email'];
        $u['perfil'] = $row['perfil'];
    }
    if (!in_array($u['perfil'], $perfis, true)) {
        axion_negar(403, 'Seu nível de acesso não permite esta operação.', 'oqfazer.php?erro=sem_permissao');
    }
    return $u;
}

function axion_eh_gestor(array $u): bool {
    return in_array($u['perfil'], ['admin', 'supervisor'], true);
}

/** Token simples contra envio de formulários por outros sites (CSRF). */
function axion_csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function axion_validar_csrf(): void {
    $enviado = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($enviado) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
        axion_negar(419, 'Formulário expirado. Recarregue a página e tente novamente.', 'oqfazer.php?erro=formulario_expirado');
    }
}

function axion_json(array $dados, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados);
    exit;
}

function axion_formatar_cpf(string $cpf): string {
    $d = preg_replace('/\D/', '', $cpf);
    if (strlen($d) !== 11) return $cpf;
    return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2);
}
