<?php
// backend/processar_edicao_checklist.php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_checklist = intval($_POST['id_checklist'] ?? 0);
    $titulo       = trim($_POST['titulo'] ?? '');
    $categoria    = trim($_POST['categoria'] ?? '');
    $perguntas    = $_POST['perguntas'] ?? [];

    if ($id_checklist <= 0 || empty($titulo)) {
        echo "<script>alert('Título e ID do checklist são obrigatórios.'); window.history.back();</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Atualiza dados do Checklist
        $stmt = $pdo->prepare("UPDATE Checklists SET titulo = :titulo, categoria = :categoria WHERE id_checklist = :id");
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':id', $id_checklist, PDO::PARAM_INT);
        $stmt->execute();

        // 2. Se perguntas foram enviadas, sincroniza atualizações e inserções
        if (!empty($perguntas) && is_array($perguntas)) {
            $stmtUpdateP = $pdo->prepare("UPDATE Perguntas 
                                          SET texto_pergunta = :texto, tipo_resposta = :tipo, 
                                              obrigatorio = :obrigatorio, categoria = :categoria, ordem = :ordem 
                                          WHERE id_pergunta = :id_p AND id_checklist = :id_c");

            $stmtInsertP = $pdo->prepare("INSERT INTO Perguntas (id_checklist, texto_pergunta, tipo_resposta, obrigatorio, categoria, ordem) 
                                          VALUES (:id_c, :texto, :tipo, :obrigatorio, :categoria, :ordem)");

            $ordem = 1;
            foreach ($perguntas as $p) {
                $id_p_raw    = $p['id_pergunta'] ?? '';
                $texto       = trim($p['texto'] ?? '');
                $tipo        = trim($p['tipo'] ?? 'sim_nao');
                $catPerg     = trim($p['categoria'] ?? $categoria);
                $obrigatorio = isset($p['obrigatorio']) ? 1 : 0;

                if (empty($texto)) {
                    continue;
                }

                if (is_numeric($id_p_raw) && intval($id_p_raw) > 0) {
                    // Atualiza pergunta existente
                    $id_p = intval($id_p_raw);
                    $stmtUpdateP->bindParam(':texto', $texto);
                    $stmtUpdateP->bindParam(':tipo', $tipo);
                    $stmtUpdateP->bindParam(':obrigatorio', $obrigatorio, PDO::PARAM_INT);
                    $stmtUpdateP->bindParam(':categoria', $catPerg);
                    $stmtUpdateP->bindParam(':ordem', $ordem, PDO::PARAM_INT);
                    $stmtUpdateP->bindParam(':id_p', $id_p, PDO::PARAM_INT);
                    $stmtUpdateP->bindParam(':id_c', $id_checklist, PDO::PARAM_INT);
                    $stmtUpdateP->execute();
                } else {
                    // Insere nova pergunta
                    $stmtInsertP->bindParam(':id_c', $id_checklist, PDO::PARAM_INT);
                    $stmtInsertP->bindParam(':texto', $texto);
                    $stmtInsertP->bindParam(':tipo', $tipo);
                    $stmtInsertP->bindParam(':obrigatorio', $obrigatorio, PDO::PARAM_INT);
                    $stmtInsertP->bindParam(':categoria', $catPerg);
                    $stmtInsertP->bindParam(':ordem', $ordem, PDO::PARAM_INT);
                    $stmtInsertP->execute();
                }
                $ordem++;
            }
        }

        $pdo->commit();

        header("Location: ../frontend/oqfazer.php?sucesso=checklist_editado");
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<script>
                alert('Erro ao editar checklist: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>

