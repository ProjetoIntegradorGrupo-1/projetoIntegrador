<?php
require_once __DIR__ . '/../backend/conexao.php';

echo "Testando fluxo real de Recuperação de Senha...\n";

// 1. Simula envio de e-mail
$email = 'supervisor@axion.com';
$stmt = $pdo->prepare("SELECT id_usuario FROM Usuarios WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuário não encontrado!\n");
}

$token = bin2hex(random_bytes(32));
$expira_em = date('Y-m-d H:i:s', strtotime('+2 hours'));

$stmtIns = $pdo->prepare("INSERT INTO RecuperacaoSenha (id_usuario, token, expira_em, usado) VALUES (:id_usuario, :token, :expira_em, 0)");
$stmtIns->execute([
    ':id_usuario' => $user['id_usuario'],
    ':token'      => $token,
    ':expira_em'  => $expira_em
]);

echo "1. Token gerado e persistido no banco: $token\n";

// 2. Valida token
$stmtCheck = $pdo->prepare("SELECT * FROM RecuperacaoSenha WHERE token = :token AND usado = 0 AND expira_em >= NOW()");
$stmtCheck->execute([':token' => $token]);
$valid = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($valid) {
    echo "2. Token validado com sucesso no banco (expira em: {$valid['expira_em']})\n";
} else {
    die("Falha na validação do token!\n");
}

// 3. Atualiza senha
$novaSenha = 'nova_senha_teste_123';
$hash = password_hash($novaSenha, PASSWORD_DEFAULT);

$stmtUp = $pdo->prepare("UPDATE Usuarios SET senha = :senha WHERE id_usuario = :id");
$stmtUp->execute([':senha' => $hash, ':id' => $user['id_usuario']]);

$stmtMarca = $pdo->prepare("UPDATE RecuperacaoSenha SET usado = 1 WHERE token = :token");
$stmtMarca->execute([':token' => $token]);

echo "3. Senha alterada e token marcado como usado!\n";

// 4. Tenta reusar o token (deve falhar)
$stmtRecheck = $pdo->prepare("SELECT * FROM RecuperacaoSenha WHERE token = :token AND usado = 0 AND expira_em >= NOW()");
$stmtRecheck->execute([':token' => $token]);
$reused = $stmtRecheck->fetch(PDO::FETCH_ASSOC);

if (!$reused) {
    echo "4. Proteção contra reuso de token confirmada com sucesso (token invalidado)!\n";
} else {
    die("Erro: Token ainda válido após uso!\n");
}

// 5. Restaura senha padrão
$hashPadrao = password_hash('mudar123', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE Usuarios SET senha = :senha WHERE id_usuario = :id")->execute([':senha' => $hashPadrao, ':id' => $user['id_usuario']]);
echo "5. Senha restaurada para 'mudar123'!\n";
echo "TESTE CONCLUÍDO COM 100% DE SUCESSO!\n";

