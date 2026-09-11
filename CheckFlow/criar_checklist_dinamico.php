<?php
// salvar_checklist_dinamico.php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado na sessão
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $id_criador = $_SESSION['id_usuario'];
    $titulo     = trim($_POST['titulo'] ?? '');
    $categoria  = trim($_POST['categoria'] ?? '');

    try {
        // Insere o cabeçalho do checklist na tabela 'Checklists' criada no DBeaver
        $sql = "INSERT INTO Checklists (id_criador, titulo, categoria) 
                VALUES (:id_criador, :titulo, :categoria)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_criador', $id_criador, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':categoria', $categoria);
        
        $stmt->execute();

        // Redireciona de volta ao menu principal com aviso de sucesso
        header("Location: oqfazer.php?sucesso=checklist_criado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao salvar checklist: " . addslashes($e->getMessage()) . "');
                window.location.href = 'criarChecklis.html';
              </script>";
        exit;
    }

} else {
    header("Location: oqfazer.php");
    exit;
}
?>