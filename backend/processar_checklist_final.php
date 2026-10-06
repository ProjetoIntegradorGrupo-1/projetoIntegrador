<?php
// backend/processar_checklist_final.php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vistoria           = $_SESSION['id_vistoria_ativa'] ?? intval($_POST['id_vistoria'] ?? 0);
    $assinatura_motorista  = $_POST['assinatura_motorista'] ?? '';
    $assinatura_vistoriador= $_POST['assinatura_vistoriador'] ?? '';

    try {
        if ($id_vistoria > 0) {
            // Verifica se houve não conformidade registrada
            $stmtCheck = $pdo->prepare("SELECT descricao_nao_conformidade FROM Vistorias WHERE id_vistoria = :id");
            $stmtCheck->bindParam(':id', $id_vistoria, PDO::PARAM_INT);
            $stmtCheck->execute();
            $vistoria = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $statusFinal = (!empty($vistoria['descricao_nao_conformidade'])) 
                           ? 'aprovado_com_restricoes' 
                           : 'aprovado';

            // Salva as assinaturas e atualiza o status final
            $sql = "UPDATE Vistorias 
                    SET assinatura_motorista = :ass_mot, 
                        assinatura_vistoriador = :ass_vist, 
                        status = :status 
                    WHERE id_vistoria = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':ass_mot', $assinatura_motorista);
            $stmt->bindParam(':ass_vist', $assinatura_vistoriador);
            $stmt->bindParam(':status', $statusFinal);
            $stmt->bindParam(':id', $id_vistoria, PDO::PARAM_INT);
            $stmt->execute();

            // Limpa a vistoria ativa da sessão
            unset($_SESSION['id_vistoria_ativa']);
        }

        // Redireciona com aviso de sucesso para o menu principal
        header("Location: ../frontend/oqfazer.php?sucesso=checklist_concluido");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao salvar assinaturas da vistoria: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/assinaturaChecklist.html");
    exit;
}
?>

