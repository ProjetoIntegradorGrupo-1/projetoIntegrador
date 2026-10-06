<?php
// backend/processar_cadastro_motorista.php
require_once 'conexao.php';
require_once 'auth_check.php';

// Cadastro de motoristas é restrito a Gestores e Supervisores
autorizarAcesso(['gestor', 'supervisor']);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $nome          = trim($_POST['nome']);
    $sobrenome     = trim($_POST['sobrenome']);
    $nome_completo = $nome . ' ' . $sobrenome;
    $email         = trim($_POST['email']);
    $cpf           = trim($_POST['cpf']);
    
    // Senha padrão inicial para o motorista
    $senha_padrao  = 'mudar123';
    $senha_cripto  = password_hash($senha_padrao, PASSWORD_DEFAULT);
    
    // Perfil específico de motorista
    $perfil        = 'motorista'; 

    try {
        $sql = "INSERT INTO Usuarios (nome, email, senha, cpf_matricula, perfil) 
                VALUES (:nome, :email, :senha, :cpf_matricula, :perfil)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome_completo);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha_cripto);
        $stmt->bindParam(':cpf_matricula', $cpf);
        $stmt->bindParam(':perfil', $perfil);
        
        $stmt->execute();

        header("Location: ../frontend/oqfazer.php?sucesso=motorista_cadastrado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao cadastrar motorista: " . addslashes($e->getMessage()) . "');
                window.location.href = '../frontend/caduser.php';
              </script>";
        exit;

    }

} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>