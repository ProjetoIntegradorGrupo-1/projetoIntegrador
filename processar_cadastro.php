<?php
// processar_cadastro.php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/fotos.php';
$eu = axion_exigir_perfil(['admin', 'supervisor']);

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
    $categoria_cnh = strtoupper(trim($_POST['categoria-cnh'] ?? ''));
    if ($cnh === '') { $categoria_cnh = ''; $validade_cnh = null; } // sem CNH (ex.: cliente)
    $cep           = trim($_POST['cep'] ?? '');
    $logradouro    = trim($_POST['logradouro'] ?? '');
    $numero        = trim($_POST['numero'] ?? '');
    $bairro        = trim($_POST['bairro'] ?? '');
    $cidade        = trim($_POST['cidade'] ?? '');
    $estado        = strtoupper(trim($_POST['estado'] ?? ''));
    $telefone      = trim($_POST['telefone'] ?? '');

    // Validação redundante de CPF no Backend
    if (!axion_validar_cpf($cpf)) {
        echo "<script>
                alert('CPF inválido! Verifique os dígitos verificadores informados.');
                window.history.back();
              </script>";
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
    axion_validar_csrf();
    // Supervisor só cadastra motoristas e clientes; admin/supervisor só podem ser criados por um admin
    if ($eu['perfil'] !== 'admin' && in_array($perfil_solicitado, ['admin', 'supervisor'], true)) {
        echo "<script>alert('Somente o Administrador pode cadastrar Supervisores ou Admins.'); window.history.back();</script>";
        exit;
    }
    // Motorista fica vinculado a um supervisor responsável (escolhido no cadastro)
    $id_supervisor = null;
    if ($perfil_solicitado === 'motorista' && !empty($_POST['id_supervisor'])) {
        $stSup = $pdo->prepare("SELECT id_usuario FROM Usuarios WHERE id_usuario = :id AND perfil = 'supervisor'");
        $stSup->execute([':id' => (int)$_POST['id_supervisor']]);
        $id_supervisor = $stSup->fetchColumn() ?: null;
    }
    if ($perfil_solicitado === 'motorista' && !$id_supervisor && $eu['perfil'] === 'supervisor') {
        $id_supervisor = $eu['id']; // supervisor que cadastrou vira o responsável
    }
    if ($perfil_solicitado === 'motorista' && (strlen($cnh) !== 11 || $categoria_cnh === '' || !$validade_cnh)) {
        echo "<script>alert('Motorista precisa de CNH (11 dígitos), categoria e validade.'); window.history.back();</script>";
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('E-mail inválido.'); window.history.back();</script>";
        exit;
    }

    try {
        $sql = "INSERT INTO Usuarios (
                    nome, sobrenome, email, senha, cpf_matricula, perfil,
                    data_nascimento, genero, cnh, validade_cnh, categoria_cnh,
                    cep, logradouro, numero, bairro, cidade, estado, telefone, status, id_supervisor
                ) VALUES (
                    :nome, :sobrenome, :email, :senha, :cpf_matricula, :perfil,
                    :dt_nasc, :genero, :cnh, :val_cnh, :cat_cnh,
                    :cep, :logradouro, :numero, :bairro, :cidade, :estado, :telefone, 'ativo', :id_supervisor
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
            ':cat_cnh'       => $categoria_cnh ?: null,
            ':cep'           => $cep,
            ':logradouro'    => $logradouro,
            ':numero'        => $numero,
            ':bairro'        => $bairro,
            ':cidade'        => $cidade,
            ':estado'        => $estado,
            ':telefone'      => $telefone,
            ':id_supervisor' => $id_supervisor
        ]);

        // Foto enviada junto com o cadastro (opcional)
        $novoId = (int)$pdo->lastInsertId();
        try {
            $foto = axion_receber_foto('foto', 'usuario', $novoId, 3);
            if ($foto) $pdo->prepare("UPDATE Usuarios SET foto = :f WHERE id_usuario = :id")->execute([':f' => $foto, ':id' => $novoId]);
        } catch (RuntimeException $e) {
            // O cadastro foi feito; só a foto não foi aceita
        }
        header("Location: oqfazer.php?aba=usuarios&sucesso={$perfil_solicitado}_cadastrado");
        exit;

    } catch (PDOException $e) {
        $msg = (($e->errorInfo[1] ?? 0) == 1062) ? 'Já existe um usuário com este CPF ou e-mail.' : 'Erro ao cadastrar usuário.';
        echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
        exit;
    }
} else {
    header("Location: oqfazer.php");
    exit;
}
?>
