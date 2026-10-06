<?php
// backend/processar_detalhes_checklist.php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vistoria        = $_SESSION['id_vistoria_ativa'] ?? intval($_POST['id_vistoria'] ?? 0);
    $descricao_detalhada= trim($_POST['descricao_detalhada'] ?? '');

    // Diretório de armazenamento para uploads
    $upload_dir = __DIR__ . '/uploads/evidencias/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    try {
        // Atualiza a descrição de não conformidade se existir vistoria ativa
        if ($id_vistoria > 0 && !empty($descricao_detalhada)) {
            $stmt = $pdo->prepare("UPDATE Vistorias SET descricao_nao_conformidade = :descricao WHERE id_vistoria = :id");
            $stmt->bindParam(':descricao', $descricao_detalhada);
            $stmt->bindParam(':id', $id_vistoria, PDO::PARAM_INT);
            $stmt->execute();
        }

        // Função auxiliar para processar e salvar upload de evidência
        $salvarEvidencia = function($fileKey, $tipoPadrao) use ($pdo, $id_vistoria, $upload_dir) {
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $nomeOriginal = basename($_FILES[$fileKey]['name']);
                $extensao     = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
                $extensoesValidas = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

                if (in_array($extensao, $extensoesValidas)) {
                    $novoNome = uniqid('evid_', true) . '.' . $extensao;
                    $destino  = $upload_dir . $novoNome;

                    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $destino)) {
                        $caminhoRelativo = 'uploads/evidencias/' . $novoNome;
                        $tipo = ($extensao === 'pdf') ? 'documento' : 'foto';

                        $stmtEv = $pdo->prepare("INSERT INTO EvidenciasVistoria (id_vistoria, tipo_evidencia, caminho_arquivo, nome_original) 
                                                 VALUES (:id_vistoria, :tipo, :caminho, :nome_orig)");
                        $stmtEv->bindParam(':id_vistoria', $id_vistoria, PDO::PARAM_INT);
                        $stmtEv->bindParam(':tipo', $tipo);
                        $stmtEv->bindParam(':caminho', $caminhoRelativo);
                        $stmtEv->bindParam(':nome_orig', $nomeOriginal);
                        $stmtEv->execute();
                    }
                }
            }
        };

        if ($id_vistoria > 0) {
            $salvarEvidencia('foto_evidencia', 'foto');
            $salvarEvidencia('documento_evidencia', 'documento');
        }

        // Redireciona para o passo final de assinaturas
        header("Location: ../frontend/assinaturaChecklist.html");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao registrar detalhes da vistoria: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/detalhesNaoConformidade.html");
    exit;
}
?>

