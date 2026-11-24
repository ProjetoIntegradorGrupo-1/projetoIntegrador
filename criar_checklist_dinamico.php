<?php
// criar_checklist_dinamico.php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/backend/auth.php';
$eu = axion_exigir_perfil(['admin', 'supervisor']);
// Checklist criado pelo supervisor vai para o admin aprovar antes de poder ser usado
$statusAprovacao = $eu['perfil'] === 'admin' ? 'aprovado' : 'pendente';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    axion_validar_csrf();
    $id_criador = (int)$_SESSION['id_usuario'];
    $titulo     = trim($_POST['titulo'] ?? '');
    $categoria  = trim($_POST['categoria'] ?? 'outros');
    $descricao  = trim($_POST['descricao'] ?? "Checklist operacional da categoria $categoria");

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO Checklists (id_criador, titulo, categoria, descricao, ativo, status_aprovacao)
                VALUES (:id_criador, :titulo, :categoria, :descricao, 1, :status_aprovacao)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_criador' => $id_criador,
            ':titulo'     => $titulo,
            ':categoria'  => $categoria,
            ':descricao'  => $descricao,
            ':status_aprovacao' => $statusAprovacao
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
                    ':esp'    => ($p['tipo'] ?? 'sim_nao') === 'sim_nao' ? ($p['resposta_esperada'] ?? 'sim') : null,
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

        // Um modelo sem nenhuma pergunta não serve para vistoria
        if (empty($ordem) || $ordem === 1 || $titulo === '') {
            $pdo->rollBack();
            echo "<script>alert('Informe o título e pelo menos uma pergunta.'); window.history.back();</script>";
            exit;
        }
        $pdo->commit();
        header("Location: oqfazer.php?aba=checklists&sucesso=" . ($statusAprovacao === 'pendente' ? 'checklist_enviado_aprovacao' : 'checklist_criado'));
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<script>
                alert('Erro ao salvar checklist.');
                window.location.href = 'criarChecklist.html';
              </script>";
        exit;
    }
} else {
    header("Location: oqfazer.php");
    exit;
}
?>
