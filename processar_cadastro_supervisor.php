<?php
// processar_cadastro_supervisor.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $nome          = trim($_POST['nome']);
    $sobrenome     = trim($_POST['sobrenome']);
    $nome_completo = $nome . ' ' . $sobrenome;
    $email         = trim($_POST['email']);
    $cpf           = trim($_POST['cpf']);
    
    // Senha padrão inicial para o supervisor
    $senha_padrao  = 'mudar123';
    $senha_cripto  = password_hash($senha_padrao, PASSWORD_DEFAULT);
    
    // Perfil específico de supervisor
    $perfil        = 'supervisor'; 

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

        header("Location: oqfazer.php?sucesso=supervisor_cadastrado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao cadastrar supervisor: " . addslashes($e->getMessage()) . "');
                window.location.href = 'cadastrarUsuario.html';
              </script>";
        exit;
    }

} else {
    header("Location: oqfazer.php");
    exit;
}
?>