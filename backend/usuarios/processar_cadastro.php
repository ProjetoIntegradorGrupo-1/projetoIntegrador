<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Proteção: apenas administradores podem cadastrar usuários
exigirPerfil(['administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/caduser.html');
    exit;
}

try {
    // Inicia a transação para garantir que as 3 tabelas sejam gravadas juntas
    $pdo->beginTransaction();

    // 1. Coleta e inserção de Usuários
    $nome = trim($_POST['nome'] ?? '') . ' ' . trim($_POST['sobrenome'] ?? '');
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''); // Limpa a formatação
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $telefone = $_POST['telefone'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $perfil = $_POST['perfil'] ?? '';

    
    // Hash da senha obrigatório
    $senhaHash = password_hash($_POST['senha'] ?? '', PASSWORD_DEFAULT);

    $sqlUsuario = "INSERT INTO Usuarios (nome, cpf, data_nascimento, telefone, email, senha, perfil, status) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo')";
    $stmtUser = $pdo->prepare($sqlUsuario);
    $stmtUser->execute([$nome, $cpf, $data_nascimento, $telefone, $email, $senhaHash, $perfil]);
    
    $idUsuario = $pdo->lastInsertId();

    // 2. Coleta e inserção de Endereços
    $sqlEndereco = "INSERT INTO Enderecos (id_usuario, logradouro, numero, bairro, cidade, estado, cep) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtEnd = $pdo->prepare($sqlEndereco);
    $stmtEnd->execute([
        $idUsuario,
        $_POST['logradouro'] ?? '',
        $_POST['numero'] ?? '',
        $_POST['bairro'] ?? '',
        $_POST['cidade'] ?? '',
        $_POST['estado'] ?? '',
        preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '')
    ]);

    // 3. Coleta e inserção de CNH (apenas se os dados forem preenchidos)
    if (!empty($_POST['numero_cnh'])) {
        $sqlCnh = "INSERT INTO CNHs (id_usuario, numero, categoria, validade) VALUES (?, ?, ?, ?)";
        $stmtCnh = $pdo->prepare($sqlCnh);
        $stmtCnh->execute([
            $idUsuario,
            $_POST['numero_cnh'],
            $_POST['categoria_cnh'] ?? '',
            $_POST['validade_cnh'] ?? null
        ]);
    }

    // Confirma a transação
    $pdo->commit();
    header('Location: ../php/oqfazer.php?sucesso=usuario_cadastrado');
    exit;

} catch (PDOException $e) {
    // Se der qualquer erro de banco (ex: CPF duplicado), desfaz tudo
    $pdo->rollBack();
    error_log("Erro no cadastro de usuário: " . $e->getMessage());
    header('Location: ../../Frontend/caduser.html?erro=falha_cadastro');
    exit;
}