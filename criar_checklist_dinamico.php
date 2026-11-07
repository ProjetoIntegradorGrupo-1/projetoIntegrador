<?php
// criar_checklist_dinamico.php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_criador = (int)$_SESSION['id_usuario'];
    $titulo     = trim($_POST['titulo'] ?? '');
    $categoria  = trim($_POST['categoria'] ?? 'outros');
    $descricao  = trim($_POST['descricao'] ?? "Checklist operacional da categoria $categoria");

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO Checklists (id_criador, titulo, categoria, descricao, ativo)
                VALUES (:id_criador, :titulo, :categoria, :descricao, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_criador' => $id_criador,
            ':titulo'     => $titulo,
            ':categoria'  => $categoria,
            ':descricao'  => $descricao
        ]);
        $idChecklist = (int)$pdo->lastInsertId();

        // Salva perguntas principais e subperguntas condicionais
        if (!empty($_POST['perguntas']) && is_array($_POST['perguntas'])) {
            $stmtPerg = $pdo->prepare("
                INSERT INTO Checklist_Perguntas (
                    id_checklist, id_pergunta_pai, condicao_pai, ordem, texto, tipo, obrigatorio, resposta_esperada, orientacao
                ) VALUES (
                    :id_chk, :id_pai, :cond, :ordem, :texto, :tipo, :obrig, :esp, :ori
                )
            ");
            $ordem = 1;
            foreach ($_POST['perguntas'] as $p) {
                $textoP = trim($p['texto'] ?? '');
                if ($textoP === '') continue;

                $stmtPerg->execute([
                    ':id_chk' => $idChecklist,
                    ':id_pai' => null,
                    ':cond'   => null,
                    ':ordem'  => $ordem++,
                    ':texto'  => $textoP,
                    ':tipo'   => $p['tipo'] ?? 'sim_nao',
                    ':obrig'  => isset($p['obrigatorio']) ? 1 : 0,
                    ':esp'    => $p['resposta_esperada'] ?? 'sim',
                    ':ori'    => trim($p['orientacao'] ?? '')
                ]);
                $idPai = (int)$pdo->lastInsertId();

                if (!empty($p['subperguntas']) && is_array($p['subperguntas'])) {
                    $subOrdem = 1;
                    foreach ($p['subperguntas'] as $sub) {
                        $textoSub = trim($sub['texto'] ?? '');
                        if ($textoSub === '') continue;
                        $stmtPerg->execute([
                            ':id_chk' => $idChecklist,
                            ':id_pai' => $idPai,
                            ':cond'   => $sub['condicao'] ?? 'sim',
                            ':ordem'  => $subOrdem++,
                            ':texto'  => $textoSub,
                            ':tipo'   => $sub['tipo'] ?? 'texto',
                            ':obrig'  => isset($sub['obrigatorio']) ? 1 : 0,
                            ':esp'    => null,
                            ':ori'    => null
                        ]);
                    }
                }
            }
        }

        $pdo->commit();
        header("Location: oqfazer.php?aba=checklists&sucesso=checklist_criado");
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<script>
                alert('Erro ao salvar checklist: " . addslashes($e->getMessage()) . "');
                window.location.href = 'criarChecklist.html';
              </script>";
        exit;
    }
} else {
    header("Location: oqfazer.php");
    exit;
}
?>
