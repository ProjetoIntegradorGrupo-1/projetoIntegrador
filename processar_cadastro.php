<?php
// processar_cadastro.php
session_start();
require_once 'conexao.php';

// Verifica se a requisição foi feita via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Captura e limpa os dados enviados pelo formulário
    $nome          = trim($_POST['nome']);
    $sobrenome     = trim($_POST['sobrenome']);
    $nome_completo = $nome . ' ' . $sobrenome; // Unimos nome e sobrenome para a coluna 'nome' da tabela
    $email         = trim($_POST['email']);
    $cpf           = trim($_POST['cpf']);
    
    // Define uma senha padrão temporária para o novo usuário cadastrado (ex: 'mudar123')
    // e usa password_hash() para criptografá-la de forma segura
    $senha_padrao  = 'mudar123';
    $senha_cripto  = password_hash($senha_padrao, PASSWORD_DEFAULT);
    
    // Define o perfil com base na URL de ação ou padrão 'motorista'
    // Como a tabela unificada aceita os perfis, definimos 'motorista' como padrão geral
    $perfil        = 'motorista'; 

    try {
        // Prepara a instrução SQL para inserir na tabela 'Usuarios' que criamos no DBeaver
        $sql = "INSERT INTO Usuarios (nome, email, senha, cpf_matricula, perfil) 
                VALUES (:nome, :email, :senha, :cpf_matricula, :perfil)";
        
        $stmt = $pdo->prepare($sql);
        
        // Associa os parâmetros com segurança (proteção contra SQL Injection)
        $stmt->bindParam(':nome', $nome_completo);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha_cripto);
        $stmt->bindParam(':cpf_matricula', $cpf);
        $stmt->bindParam(':perfil', $perfil);
        
        // Executa o comando no banco de dados
        $stmt->execute();

        // Sucesso: redireciona de volta ao menu com uma mensagem ou alerta de sucesso
        header("Location: oqfazer.php?sucesso=usuario_cadastrado");
        exit;

    } catch (PDOException $e) {
        // Se houver erro (por exemplo, CPF ou E-mail duplicado no banco)
        // Redireciona com a mensagem de erro
        echo "<script>
                alert('Erro ao cadastrar usuário: " . addslashes($e->getMessage()) . "');
                window.location.href = 'cadastrarUsuario.html';
              </script>";
        exit;
    }

} else {
    // Se acessado diretamente sem enviar o formulário, redireciona para o menu
    header("Location: oqfazer.php");
    exit;
}
?>