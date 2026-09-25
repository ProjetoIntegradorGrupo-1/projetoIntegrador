<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Proteção: apenas administradores podem editar usuários (ajuste se gestores também puderem)
exigirPerfil(['administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/oqfazer.php');
    exit;
}

$id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
if (!$id_usuario) {
    header('Location: ../../Frontend/oqfazer.php?erro=id_invalido');
    exit;
}

try {
    // Inicia a transação para atualizar Usuário, Endereço e CNH de forma segura
    $pdo->beginTransaction();

    // 1. Coleta e atualização dos dados principais do Usuário
    $nome = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $nome_completo = trim($nome . ' ' . $sobrenome);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $data_nascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $telefone = $_POST['telefone'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $perfil = $_POST['perfil'] ?? '';

    // Se o campo de senha foi preenchido, atualiza o hash; caso contrário, mantém a senha atual
    $senha = $_POST['senha'] ?? '';
    if (!empty($senha)) {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $sqlUsuario = "UPDATE Usuarios SET nome = ?, cpf = ?, data_nascimento = ?, telefone = ?, email = ?, perfil = ?, senha = ? WHERE id_usuario = ?";
        $stmtUser = $pdo->prepare($sqlUsuario);
        $stmtUser->execute([$nome_completo, $cpf, $data_nascimento, $telefone, $email, $perfil, $senhaHash, $id_usuario]);
    } else {
        $sqlUsuario = "UPDATE Usuarios SET nome = ?, cpf = ?, data_nascimento = ?, telefone = ?, email = ?, perfil = ? WHERE id_usuario = ?";
        $stmtUser = $pdo->prepare($sqlUsuario);
        $stmtUser->execute([$nome_completo, $cpf, $data_nascimento, $telefone, $email, $perfil, $id_usuario]);
    }

    // 2. Atualização ou Inserção de Endereço
    $logradouro = $_POST['logradouro'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');

    $stmtCheckEnd = $pdo->prepare("SELECT id_endereco FROM Enderecos WHERE id_usuario = ?");
    $stmtCheckEnd->execute([$id_usuario]);
    
    if ($stmtCheckEnd->fetch()) {
        $sqlEnd = "UPDATE Enderecos SET logradouro = ?, numero = ?, bairro = ?, cidade = ?, estado = ?, cep = ? WHERE id_usuario = ?";
        $stmtEnd = $pdo->prepare($sqlEnd);
        $stmtEnd->execute([$logradouro, $numero, $bairro, $cidade, $estado, $cep, $id_usuario]);
    } else {
        $sqlEnd = "INSERT INTO Enderecos (id_usuario, logradouro, numero, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtEnd = $pdo->prepare($sqlEnd);
        $stmtEnd->execute([$id_usuario, $logradouro, $numero, $bairro, $cidade, $estado, $cep]);
    }

    // 3. Atualização ou Inserção de CNH (se preenchida)
    $numero_cnh = $_POST['numero_cnh'] ?? '';
    $categoria_cnh = $_POST['categoria_cnh'] ?? '';
    $validade_cnh = !empty($_POST['validade_cnh']) ? $_POST['validade_cnh'] : null;

    if (!empty($numero_cnh)) {
        $stmtCheckCnh = $pdo->prepare("SELECT id_cnh FROM CNHs WHERE id_usuario = ?");
        $stmtCheckCnh->execute([$id_usuario]);
        
        if ($stmtCheckCnh->fetch()) {
            $sqlCnh = "UPDATE CNHs SET numero = ?, categoria = ?, validade = ? WHERE id_usuario = ?";
            $stmtCnh = $pdo->prepare($sqlCnh);
            $stmtCnh->execute([$numero_cnh, $categoria_cnh, $validade_cnh, $id_usuario]);
        } else {
            $sqlCnh = "INSERT INTO CNHs (id_usuario, numero, categoria, validade) VALUES (?, ?, ?, ?)";
            $stmtCnh = $pdo->prepare($sqlCnh);
            $stmtCnh->execute([$id_usuario, $numero_cnh, $categoria_cnh, $validade_cnh]);
        }
    }

    // Confirma a transação com sucesso
    $pdo->commit();
    header('Location: ../php/oqfazer.php?sucesso=usuario_atualizado');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao atualizar utilizador ID {$id_usuario}: " . $e->getMessage());
    header('Location: ../../Frontend/editarUsuario.php?erro=falha_banco');
    exit;
}