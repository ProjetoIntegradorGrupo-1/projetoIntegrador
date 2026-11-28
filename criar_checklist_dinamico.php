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
    $id_veiculo = !empty($_POST['id_veiculo']) ? (int)$_POST['id_veiculo'] : null;
    $titulo     = trim($_POST['titulo'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $catsValidas = ['eletrico', 'mecanico', 'estetico', 'seguranca', 'pre_viagem', 'locacao', 'outros'];

    // Coleta as categorias das perguntas principais para definir a categoria de referência do modelo
    $categoriasPerguntas = [];
    if (!empty($_POST['perguntas']) && is_array($_POST['perguntas'])) {
        foreach ($_POST['perguntas'] as $p) {
            $cp = trim($p['categoria'] ?? '');
            if (in_array($cp, $catsValidas, true)) $categoriasPerguntas[] = $cp;
        }
    }
    $categoriasUnicas = array_values(array_unique($categoriasPerguntas));
    $categoriaModelo = count($categoriasUnicas) === 1 ? $categoriasUnicas[0] : ($categoriasUnicas[0] ?? 'outros');
    if ($descricao === '') {
        $descricao = 'Checklist multicategoria de vistoria veicular';
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO Checklists (id_criador, id_veiculo, titulo, categoria, descricao, ativo, status_aprovacao)
                VALUES (:id_criador, :id_veiculo, :titulo, :categoria, :descricao, 1, :status_aprovacao)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_criador' => $id_criador,
            ':id_veiculo' => $id_veiculo,
            ':titulo'     => $titulo,
            ':categoria'  => $categoriaModelo,
            ':descricao'  => $descricao,
            ':status_aprovacao' => $statusAprovacao
        ]);
        $idChecklist = (int)$pdo->lastInsertId();

        // Salva perguntas principais (com sua própria categoria) e subperguntas condicionais (sem categoria própria: NULL)
        if (!empty($_POST['perguntas']) && is_array($_POST['perguntas'])) {
            $stmtPerg = $pdo->prepare("
                INSERT INTO Checklist_Perguntas (
                    id_checklist, categoria, id_pergunta_pai, condicao_pai, ordem, texto, tipo, obrigatorio, resposta_esperada, orientacao
                ) VALUES (
                    :id_chk, :cat, :id_pai, :cond, :ordem, :texto, :tipo, :obrig, :esp, :ori
                )
            ");
            $ordem = 1;
            foreach ($_POST['perguntas'] as $p) {
                $textoP = trim($p['texto'] ?? '');
                if ($textoP === '') continue;
                $catP = trim($p['categoria'] ?? 'mecanico');
                if (!in_array($catP, $catsValidas, true)) $catP = 'outros';

                $stmtPerg->execute([
                    ':id_chk' => $idChecklist,
                    ':cat'    => $catP,
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
                            ':cat'    => null,
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
