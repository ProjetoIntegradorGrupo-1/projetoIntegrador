<?php
// backend/processar_edicao_usuario.php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario    = intval($_POST['id_usuario'] ?? 0);
    $nome          = trim($_POST['nome'] ?? '');
    $sobrenome     = trim($_POST['sobrenome'] ?? '');
    $nome_completo = trim($nome . ' ' . $sobrenome);
    $email         = trim($_POST['email'] ?? '');
    $cpf           = trim($_POST['cpf'] ?? '');
    $perfil        = trim($_POST['perfil'] ?? 'motorista');
    $dt_nasc       = !empty($_POST['dtNascimento']) ? $_POST['dtNascimento'] : null;
    $genero        = trim($_POST['genero'] ?? '');
    $cnh           = trim($_POST['cnh'] ?? '');
    $val_cnh       = !empty($_POST['validade-cnh']) ? $_POST['validade-cnh'] : null;
    $cat_cnh       = trim($_POST['categoria-cnh'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');

    if ($id_usuario <= 0 || empty($nome) || empty($email) || empty($cpf)) {
        echo "<script>alert('Campos obrigatórios inválidos.'); window.history.back();</script>";
        exit;
    }

    try {
        $sql = "UPDATE Usuarios 
                SET nome = :nome, email = :email, cpf_matricula = :cpf, perfil = :perfil,
                    data_nascimento = :dt_nasc, genero = :genero, cnh = :cnh, 
                    validade_cnh = :val_cnh, categoria_cnh = :cat_cnh, telefone = :telefone
                WHERE id_usuario = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome_completo);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':perfil', $perfil);
        $stmt->bindValue(':dt_nasc', $dt_nasc, $dt_nasc ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindParam(':genero', $genero);
        $stmt->bindParam(':cnh', $cnh);
        $stmt->bindValue(':val_cnh', $val_cnh, $val_cnh ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindParam(':cat_cnh', $cat_cnh);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../frontend/oqfazer.php?sucesso=usuario_editado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao editar usuário: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>

