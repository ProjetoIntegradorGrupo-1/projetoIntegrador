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

            // Se houver não conformidade, cria automaticamente o registro na tabela Ocorrencias
            if ($statusFinal === 'aprovado_com_restricoes') {
                $checkOc = $pdo->prepare("SELECT COUNT(*) FROM Ocorrencias WHERE id_vistoria = :id");
                $checkOc->execute([':id' => $id_vistoria]);
                if ($checkOc->fetchColumn() == 0) {
                    $stmtVFull = $pdo->prepare("SELECT v.*, ve.marca_modelo FROM Vistorias v LEFT JOIN Veiculos ve ON v.id_veiculo = ve.id_veiculo WHERE v.id_vistoria = :id");
                    $stmtVFull->execute([':id' => $id_vistoria]);
                    $vRow = $stmtVFull->fetch(PDO::FETCH_ASSOC);

                    $codOc = 'OC-2026-' . str_pad($id_vistoria, 3, '0', STR_PAD_LEFT);
                    $stmtFoto = $pdo->prepare("SELECT caminho_arquivo FROM EvidenciasVistoria WHERE id_vistoria = :id LIMIT 1");
                    $stmtFoto->execute([':id' => $id_vistoria]);
                    $fotoRow = $stmtFoto->fetch(PDO::FETCH_ASSOC);

                    $insertOc = $pdo->prepare("INSERT INTO Ocorrencias 
                        (codigo_ocorrencia, id_vistoria, id_veiculo, placa_veiculo, modelo_veiculo, subsistema, descricao_falha, criticidade, status, status_veiculo, acao_recomendada, foto_evidencia, local_patio, fiscal_responsavel)
                        VALUES (:cod, :idv, :idvei, :placa, :mod, :sub, :falha, 'alta', 'aberta', 'retido_oficina', :acao, :foto, 'Pátio Operacional', :fiscal)");
                    $insertOc->execute([
                        ':cod' => $codOc,
                        ':idv' => $id_vistoria,
                        ':idvei' => $vRow['id_veiculo'] ?? null,
                        ':placa' => $vRow['placa_veiculo'],
                        ':mod' => $vRow['marca_modelo'] ?? 'Veículo em Operação',
                        ':sub' => 'Avarias Constatadas',
                        ':falha' => $vRow['descricao_nao_conformidade'] ?? 'Avaria registrada durante inspeção.',
                        ':acao' => 'Veículo retido para triagem técnica e despacho operacional pelo gestor.',
                        ':foto' => $fotoRow['caminho_arquivo'] ?? null,
                        ':fiscal' => $vRow['nome_vistoriador'] ?? 'Inspetor'
                    ]);
                }
            }

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

