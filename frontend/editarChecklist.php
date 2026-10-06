<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Edição de modelos de checklist é restrita a Gestores e Supervisores
autorizarAcesso(['gestor', 'supervisor']);


// Busca lista de todos os checklists que não foram excluídos (ativos, pendentes ou com ajuste solicitado)
$stmtTodos = $pdo->query("SELECT id_checklist, titulo, categoria, status, motivo_ajuste 
                          FROM Checklists 
                          WHERE status != 'inativo' 
                          ORDER BY 
                            CASE WHEN status = 'ajuste_solicitado' THEN 1 
                                 WHEN status = 'pendente_aprovacao' THEN 2 
                                 ELSE 3 END, 
                            titulo ASC");
$listaChecklists = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

$id_selecionado = intval($_GET['id_checklist'] ?? 0);
$checklistAtual = null;
$perguntasAtuais = [];

if ($id_selecionado > 0) {
    $stmtC = $pdo->prepare("SELECT * FROM Checklists WHERE id_checklist = :id AND status != 'inativo' LIMIT 1");
    $stmtC->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtC->execute();
    $checklistAtual = $stmtC->fetch(PDO::FETCH_ASSOC);
} elseif (count($listaChecklists) > 0) {
    $id_selecionado = $listaChecklists[0]['id_checklist'];
    $stmtC = $pdo->prepare("SELECT * FROM Checklists WHERE id_checklist = :id AND status != 'inativo' LIMIT 1");
    $stmtC->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtC->execute();
    $checklistAtual = $stmtC->fetch(PDO::FETCH_ASSOC);
}


if ($checklistAtual) {
    $stmtP = $pdo->prepare("SELECT * FROM Perguntas WHERE id_checklist = :id ORDER BY ordem ASC, id_pergunta ASC");
    $stmtP->bindParam(':id', $checklistAtual['id_checklist'], PDO::PARAM_INT);
    $stmtP->execute();
    $perguntasAtuais = $stmtP->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Checklist - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm" style="max-width: 800px; width: 100%;">
            <h1 class="h4 text-center mb-1">Editar Checklist</h1>
            <p class="text-muted text-center small mb-4">Altere dados do modelo, perguntas e categorias associadas</p>

            <!-- 1. BLOCO DE SELEÇÃO DINÂMICA DO CHECKLIST -->
            <form action="editarChecklist.php" method="get" class="mb-4 p-3 bg-light border rounded">
                <label for="select-checklist" class="form-label fw-bold">Selecione o Modelo de Checklist</label>
                <div class="input-group">
                    <select id="select-checklist" name="id_checklist" class="form-select" onchange="this.form.submit()" required>
                        <?php if (empty($listaChecklists)): ?>
                            <option value="" disabled selected>Nenhum checklist ativo cadastrado</option>
                        <?php else: ?>
                            <?php foreach ($listaChecklists as $c): 
                                $sLabel = match($c['status']) {
                                    'ajuste_solicitado' => ' [AJUSTE SOLICITADO]',
                                    'pendente_aprovacao' => ' [PENDENTE]',
                                    default => ''
                                };
                            ?>
                                <option value="<?= $c['id_checklist'] ?>" <?= ($c['id_checklist'] == $id_selecionado) ? 'selected' : '' ?>>
                                    #<?= $c['id_checklist'] ?> - <?= htmlspecialchars($c['titulo']) ?><?= $sLabel ?> (<?= ucfirst(htmlspecialchars($c['categoria'] ?? 'Geral')) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Carregar Formulário</button>
                </div>
            </form>

            <hr class="my-4">

            <?php if ($checklistAtual): ?>

            <?php if ($checklistAtual['status'] === 'ajuste_solicitado' && !empty($checklistAtual['motivo_ajuste'])): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Parecer do Gestor Administrador (Orientações de Ajuste):</h6>
                    <p class="mb-0 small"><?= nl2br(htmlspecialchars($checklistAtual['motivo_ajuste'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if (($_SESSION['perfil_usuario'] ?? '') === 'supervisor'): ?>
                <div class="alert alert-info py-2 small mb-4">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Nota de Governança:</strong> Ao salvar este modelo, ele retornará automaticamente para o status <span class="badge bg-warning text-dark">Pendente de Aprovação</span> para validação prévia do Gestor.
                </div>
            <?php endif; ?>

            <!-- 2. FORMULÁRIO DINÂMICO DE EDIÇÃO -->
            <form action="../backend/processar_edicao_checklist.php" method="post" id="formEditarChecklist">

                <input type="hidden" name="id_checklist" value="<?= $checklistAtual['id_checklist'] ?>">

                <!-- Informações Básicas do Checklist -->
                <div class="mb-3">
                    <label for="tituloChecklist" class="form-label fw-bold">Título do Checklist</label>
                    <input type="text" id="tituloChecklist" name="titulo" class="form-control" value="<?= htmlspecialchars($checklistAtual['titulo']) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="categoriaChecklist" class="form-label fw-bold">Categoria Padrão</label>
                    <select id="categoriaChecklist" name="categoria" class="form-select" required>
                        <option value="eletrico" <?= (($checklistAtual['categoria'] ?? '') === 'eletrico') ? 'selected' : '' ?>>Elétrico</option>
                        <option value="mecanico" <?= (($checklistAtual['categoria'] ?? '') === 'mecanico') ? 'selected' : '' ?>>Mecânico</option>
                        <option value="estetico" <?= (($checklistAtual['categoria'] ?? '') === 'estetico') ? 'selected' : '' ?>>Estético</option>
                        <option value="documental" <?= (($checklistAtual['categoria'] ?? '') === 'documental') ? 'selected' : '' ?>>Documental / Segurança</option>
                        <option value="outros" <?= (($checklistAtual['categoria'] ?? '') === 'outros') ? 'selected' : '' ?>>Outros</option>
                    </select>
                </div>

                <h2 class="h6 fw-bold mb-3">Perguntas Vinculadas ao Checklist</h2>

                <!-- Container onde as perguntas salvas são carregadas -->
                <div id="containerPerguntas">
                    <?php if (empty($perguntasAtuais)): ?>
                        <div class="alert alert-warning" id="alertaSemPerguntas">Este checklist ainda não possui perguntas salvas. Adicione perguntas abaixo.</div>
                    <?php else: ?>
                        <?php foreach ($perguntasAtuais as $idx => $p): 
                            $num = $idx + 1;
                        ?>
                        <div class="card p-3 mb-3 bg-white border pergunta-item" id="pergunta_<?= $num ?>">
                            <input type="hidden" name="perguntas[<?= $num ?>][id_pergunta]" value="<?= $p['id_pergunta'] ?>">
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary">Pergunta <?= $num ?></span>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerPergunta('pergunta_<?= $num ?>')">Excluir</button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Texto da Pergunta</label>
                                <input type="text" name="perguntas[<?= $num ?>][texto]" class="form-control" value="<?= htmlspecialchars($p['texto_pergunta']) ?>" required>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-5 mb-2">
                                    <label class="form-label fw-semibold">Categoria da Pergunta</label>
                                    <input type="text" name="perguntas[<?= $num ?>][categoria]" class="form-control" value="<?= htmlspecialchars($p['categoria']) ?>" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-semibold">Tipo de Resposta</label>
                                    <select name="perguntas[<?= $num ?>][tipo]" class="form-select" required>
                                        <option value="sim_nao" <?= ($p['tipo_resposta'] === 'sim_nao') ? 'selected' : '' ?>>Sim / Não</option>
                                        <option value="texto" <?= ($p['tipo_resposta'] === 'texto') ? 'selected' : '' ?>>Texto (Observação)</option>
                                        <option value="numero" <?= ($p['tipo_resposta'] === 'numero') ? 'selected' : '' ?>>Número (Km/Pressão)</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="perguntas[<?= $num ?>][obrigatorio]" value="1" id="req_<?= $num ?>" <?= ($p['obrigatorio'] == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="req_<?= $num ?>">Obrigatória</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Botão para Acrescentar Novas Perguntas -->
                <div class="mb-4 text-center">
                    <button type="button" class="btn btn-outline-success w-100" onclick="adicionarPergunta()">
                        + Acrescentar Nova Pergunta
                    </button>
                </div>

                <!-- Botões de Ação -->
                <div class="d-grid gap-2">
                    <input type="submit" value="Salvar Alterações" class="btn btn-primary">
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">Nenhum modelo de checklist ativo para edição.</div>
                <div class="d-grid">
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Voltar ao Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script JavaScript Dinâmico -->
    <script>
        let contadorPerguntas = <?= count($perguntasAtuais) > 0 ? count($perguntasAtuais) : 0 ?>;

        function adicionarPergunta() {
            contadorPerguntas++;
            const container = document.getElementById('containerPerguntas');
            const alerta = document.getElementById('alertaSemPerguntas');
            if (alerta) {
                alerta.remove();
            }
            
            const novoHtml = `
                <div class="card p-3 mb-3 bg-white border pergunta-item" id="pergunta_${contadorPerguntas}">
                    <input type="hidden" name="perguntas[${contadorPerguntas}][id_pergunta]" value="nova">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success">Nova Pergunta ${contadorPerguntas}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerPergunta('pergunta_${contadorPerguntas}')">Excluir</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Texto da Pergunta</label>
                        <input type="text" name="perguntas[${contadorPerguntas}][texto]" class="form-control" placeholder="Digite o enunciado da pergunta..." required>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-5 mb-2">
                            <label class="form-label fw-semibold">Categoria da Pergunta</label>
                            <input type="text" name="perguntas[${contadorPerguntas}][categoria]" class="form-control" placeholder="Ex: Elétrico, Freios..." required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-semibold">Tipo de Resposta</label>
                            <select name="perguntas[${contadorPerguntas}][tipo]" class="form-select" required>
                                <option value="sim_nao" selected>Sim / Não</option>
                                <option value="texto">Texto (Observação)</option>
                                <option value="numero">Número (Km/Pressão)</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="perguntas[${contadorPerguntas}][obrigatorio]" value="1" id="req_${contadorPerguntas}" checked>
                                <label class="form-check-label" for="req_${contadorPerguntas}">Obrigatória</label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', novoHtml);
        }

        function removerPergunta(idElemento) {
            const listaPerguntas = document.querySelectorAll('.pergunta-item');
            if (listaPerguntas.length > 1) {
                document.getElementById(idElemento).remove();
            } else {
                alert('O checklist precisa ter pelo menos uma pergunta.');
            }
        }
    </script>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

