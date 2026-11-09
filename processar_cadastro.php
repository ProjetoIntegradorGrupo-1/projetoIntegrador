<?php
// processar_cadastro.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome          = trim($_POST['nome'] ?? '');
    $sobrenome     = trim($_POST['sobrenome'] ?? '');
    $nome_completo = trim($nome . ($sobrenome !== '' ? ' ' . $sobrenome : ''));
    $email         = trim($_POST['email'] ?? '');
    $cpf           = trim($_POST['cpf'] ?? '');
    $dt_nasc       = !empty($_POST['dtNascimento']) ? $_POST['dtNascimento'] : null;
    $genero        = trim($_POST['genero'] ?? '');
    $cnh           = preg_replace('/\D/', '', $_POST['cnh'] ?? '');
    $validade_cnh  = !empty($_POST['validade-cnh']) ? $_POST['validade-cnh'] : null;
    $categoria_cnh = strtoupper(trim($_POST['categoria-cnh'] ?? 'B'));
    $cep           = trim($_POST['cep'] ?? '');
    $logradouro    = trim($_POST['logradouro'] ?? '');
    $numero        = trim($_POST['numero'] ?? '');
    $bairro        = trim($_POST['bairro'] ?? '');
    $cidade        = trim($_POST['cidade'] ?? '');
    $estado        = strtoupper(trim($_POST['estado'] ?? ''));
    $telefone      = trim($_POST['telefone'] ?? '');

    // Validação redundante no Backend (CPF, E-mail com @, Data de Nascimento <= 100 anos, CNH 11 dígitos e Validade <= 20 anos)
    if (!axion_validar_cpf($cpf)) {
        echo "<script>alert('CPF inválido! Verifique os dígitos verificadores informados.'); window.history.back();</script>";
        exit;
    }
    if (strpos($email, '@') === false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('E-mail inválido! É obrigatório conter @ e um domínio válido.'); window.history.back();</script>";
        exit;
    }
    $anoAtual = (int)date('Y');
    if ($dt_nasc && ((int)substr($dt_nasc, 0, 4) < ($anoAtual - 100) || $dt_nasc > date('Y-m-d'))) {
        echo "<script>alert('Data de nascimento inválida! Só é permitido cadastrar pessoas nascidas nos últimos 100 anos.'); window.history.back();</script>";
        exit;
    }
    if (strlen($cnh) !== 11) {
        echo "<script>alert('Número da CNH inválido! A CNH deve conter exatamente 11 dígitos.'); window.history.back();</script>";
        exit;
    }
    if ($validade_cnh && (int)substr($validade_cnh, 0, 4) > ($anoAtual + 20)) {
        echo "<script>alert('Validade da CNH inválida! O ano de validade deve ser no máximo 20 anos à frente.'); window.history.back();</script>";
        exit;
    }

    // Formata CPF no padrão 000.000.000-00
    $cpf_digitos = preg_replace('/\D/', '', $cpf);
    $cpf_formatado = substr($cpf_digitos, 0, 3) . '.' . substr($cpf_digitos, 3, 3) . '.' . substr($cpf_digitos, 6, 3) . '-' . substr($cpf_digitos, 9, 2);

    $senha_padrao = !empty($_POST['senha']) ? trim($_POST['senha']) : 'mudar123';
    $senha_cripto = password_hash($senha_padrao, PASSWORD_DEFAULT);

    $perfil_solicitado = strtolower(trim($_POST['perfil'] ?? ($perfil_forcado ?? 'motorista')));
    if (!in_array($perfil_solicitado, ['admin', 'supervisor', 'motorista', 'cliente'], true)) {
        $perfil_solicitado = 'motorista';
    }

    try {
        $sql = "INSERT INTO Usuarios (
                    nome, sobrenome, email, senha, cpf_matricula, perfil,
                    data_nascimento, genero, cnh, validade_cnh, categoria_cnh,
                    cep, logradouro, numero, bairro, cidade, estado, telefone, status
                ) VALUES (
                    :nome, :sobrenome, :email, :senha, :cpf_matricula, :perfil,
                    :dt_nasc, :genero, :cnh, :val_cnh, :cat_cnh,
                    :cep, :logradouro, :numero, :bairro, :cidade, :estado, :telefone, 'ativo'
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'          => $nome_completo,
            ':sobrenome'     => $sobrenome,
            ':email'         => $email,
            ':senha'         => $senha_cripto,
            ':cpf_matricula' => $cpf_formatado,
            ':perfil'        => $perfil_solicitado,
            ':dt_nasc'       => $dt_nasc,
            ':genero'        => $genero,
            ':cnh'           => $cnh,
            ':val_cnh'       => $validade_cnh,
            ':cat_cnh'       => $categoria_cnh,
            ':cep'           => $cep,
            ':logradouro'    => $logradouro,
            ':numero'        => $numero,
            ':bairro'        => $bairro,
            ':cidade'        => $cidade,
            ':estado'        => $estado,
            ':telefone'      => $telefone
        ]);

        header("Location: oqfazer.php?aba=usuarios&sucesso={$perfil_solicitado}_cadastrado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao cadastrar usuário (CPF ou E-mail já existente?): " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }
} else {
    header("Location: oqfazer.php");
    exit;
}
?>
