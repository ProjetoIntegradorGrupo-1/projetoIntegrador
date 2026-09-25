<?php
// processar_exclusao_usuario.php
session_start();

// 1. Validação de Autenticação e Autorização
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../../Frontend/oqfazer.html?erro=nao_autorizado");
    exit;
}

require_once '../../conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura e sanitização
    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

    if (!$id_usuario) {
        header("Location: ../../Frontend/oqfazer.html?erro=id_invalido");
        exit;
    }

    try {
        // Exclusão lógica: altera o status para manter o histórico
        $sql = "UPDATE Usuarios SET status = 'inativo' WHERE id_usuario = :id_usuario";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../../Frontend/oqfazer.html?sucesso=usuario_removido");
        exit;

    } catch (PDOException $e) {
        // Log seguro no servidor
        error_log("Erro ao inativar utilizador ID {$id_usuario}: " . $e->getMessage());
        
        header("Location: ../../Frontend/oqfazer.html?erro=falha_banco");
        exit;
    }

} else {
    header("Location: ../../Frontend/oqfazer.html");
    exit;
}
?>