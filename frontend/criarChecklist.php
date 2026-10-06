<?php
// frontend/criarChecklist.php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Apenas Gestores e Supervisores têm permissão para criar modelos de checklist
autorizarAcesso(['gestor', 'supervisor']);

$perfilLogado = $_SESSION['perfil_usuario'] ?? 'supervisor';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Checklist - Axion Frotas</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">
    <div class="container py-4" style="max-width: 820px;">
        <div class="card shadow-sm p-4 border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="oqfazer.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar ao Menu
                </a>
                <span class="badge <?php echo $perfilLogado === 'gestor' ? 'bg-danger' : 'bg-primary'; ?> px-3 py-2 text-uppercase">
                    <i class="bi <?php echo $perfilLogado === 'gestor' ? 'bi-shield-check' : 'bi-person-badge'; ?>"></i>
                    Sessão: <?php echo htmlspecialchars($perfilLogado); ?>
                </span>
            </div>

            <h1 class="h4 text-center mb-1 fw-bold">Criar Novo Checklist</h1>
            <p class="text-muted text-center mb-3">Monte as perguntas de vistoria, tipos de resposta e disciplinas técnicas</p>

            <?php if ($perfilLogado === 'supervisor'): ?>
                <div class="alert alert-warning py-2 px-3 small d-flex align-items-center mb-4">
                    <i class="bi bi-hourglass-split me-2 fs-5"></i>
                    <div>
                        <strong>Fluxo de Governança:</strong> Como <strong>Supervisor</strong>, este checklist será criado no estado <span class="badge bg-warning text-dark">Pendente de Aprovação</span> e encaminhado para validação prévia do <strong>Gestor Administrador</strong> antes de ser liberado aos motoristas e clientes.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success py-2 px-3 small d-flex align-items-center mb-4">
                    <i class="bi bi-check-all me-2 fs-5"></i>
                    <div>
                        <strong>Privilégio de Gestor Administrador:</strong> O checklist criado por você entrará imediatamente no estado <span class="badge bg-success">Ativo</span> para a frota.
                    </div>
                </div>
            <?php endif; ?>

            <form action="../backend/criar_checklist_dinamico.php" method="POST" id="formChecklist">
                
                <!-- Título do Checklist -->
                <div class="mb-4">
                    <label for="titulo" class="form-label fw-bold">Título do Modelo de Checklist</label>
                    <input type="text" id="titulo" name="titulo" class="form-control form-control-lg" placeholder="Ex: Vistoria Diária de Segurança - Caminhões Pesados" required>
                </div>

                <hr class="mb-4">
                <h5 class="fw-bold mb-3 d-flex align-items-center justify-content-between">
                    <span>Perguntas do Checklist</span>
                    <small class="text-muted fw-normal fs-6">Configure perguntas e subcondições</small>
                </h5>
                
                <!-- Container principal das perguntas -->
                <div id="container-perguntas">
                    
                    <!-- Bloco da Pergunta 1 -->
                    <div class="card mb-3 p-3 border pergunta-item" data-index="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary fs-6">Pergunta 1</span>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remover-pergunta" disabled>Excluir Pergunta 1</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Texto da Pergunta</label>
                            <input type="text" name="perguntas[0][texto]" class="form-control" placeholder="Ex: O veículo possui alguma avaria na lataria ou para-choque?" required>
                        </div>

                        <div class="row align-items-end mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Tipo de Resposta</label>
                                <select name="perguntas[0][tipo]" class="form-select" required>
                                    <option value="sim_nao">Sim / Não</option>
                                    <option value="texto">Texto Livre</option>
                                    <option value="numero">Número</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check pb-2">
                                    <input class="form-check-input" type="checkbox" name="perguntas[0][obrigatorio]" value="1" checked id="obrigatorio_0">
                                    <label class="form-check-label" for="obrigatorio_0">Resposta Obrigatória</label>
                                </div>
                            </div>
                        </div>

                        <!-- Categoria da Pergunta -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-success">Categoria (Disciplina Técnica Responsável)</label>
                            <select name="perguntas[0][categoria]" class="form-select border-success" required>
                                <option value="" selected disabled>Selecione a disciplina responsável...</option>
                                <option value="mecanica">Mecânica</option>
                                <option value="eletrica">Elétrica</option>
                                <option value="estetica">Estética</option>
                                <option value="iluminacao">Iluminação</option>
                                <option value="interior">Interior</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>

                        <!-- Área de Subperguntas Condicionais -->
                        <div class="container-subperguntas border-top pt-2 mt-2"></div>
                        
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-subpergunta">+ Adicionar Subpergunta (Condicional)</button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-success w-100 mb-4 fw-bold py-2" id="btn-add-pergunta">
                    <i class="bi bi-plus-circle me-1"></i> Adicionar Nova Pergunta Principal
                </button>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary fw-bold py-2">
                        <i class="bi bi-save-fill me-1"></i> Salvar Modelo de Checklist
                    </button>
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let perguntaIndex = 1;

        // 1. Adicionar nova Pergunta Principal
        document.getElementById('btn-add-pergunta').addEventListener('click', function() {
            const container = document.getElementById('container-perguntas');
            const novaPergunta = document.createElement('div');
            
            novaPergunta.classList.add('card', 'mb-3', 'p-3', 'border', 'pergunta-item');
            novaPergunta.setAttribute('data-index', perguntaIndex);

            novaPergunta.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-primary fs-6">Pergunta ${perguntaIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remover-pergunta">Excluir Pergunta ${perguntaIndex + 1}</button>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Texto da Pergunta</label>
                    <input type="text" name="perguntas[${perguntaIndex}][texto]" class="form-control" placeholder="Ex: O nível do óleo e fluídos está adequado?" required>
                </div>

                <div class="row align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tipo de Resposta</label>
                        <select name="perguntas[${perguntaIndex}][tipo]" class="form-select" required>
                            <option value="sim_nao">Sim / Não</option>
                            <option value="texto">Texto Livre</option>
                            <option value="numero">Número</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check pb-2">
                            <input class="form-check-input" type="checkbox" name="perguntas[${perguntaIndex}][obrigatorio]" value="1" checked id="obrigatorio_${perguntaIndex}">
                            <label class="form-check-label" for="obrigatorio_${perguntaIndex}">Resposta Obrigatória</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-success">Categoria (Disciplina Técnica Responsável)</label>
                    <select name="perguntas[${perguntaIndex}][categoria]" class="form-select border-success" required>
                        <option value="" selected disabled>Selecione a disciplina responsável...</option>
                        <option value="mecanica">Mecânica</option>
                        <option value="eletrica">Elétrica</option>
                        <option value="estetica">Estética</option>
                        <option value="iluminacao">Iluminação</option>
                        <option value="interior">Interior</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="container-subperguntas border-top pt-2 mt-2"></div>
                
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-subpergunta">+ Adicionar Subpergunta (Condicional)</button>
                </div>
            `;

            container.appendChild(novaPergunta);
            perguntaIndex++;
            atualizarBotoesRemover();
        });

        // 2. Delegação de eventos para botões dentro das perguntas
        document.getElementById('container-perguntas').addEventListener('click', function(e) {
            
            // Remover Pergunta Principal
            if(e.target.classList.contains('btn-remover-pergunta')) {
                e.target.closest('.pergunta-item').remove();
                atualizarBotoesRemover();
            }

            // Adicionar Subpergunta
            if(e.target.classList.contains('btn-add-subpergunta')) {
                const perguntaCard = e.target.closest('.pergunta-item');
                const idxPergunta = perguntaCard.getAttribute('data-index');
                const containerSub = perguntaCard.querySelector('.container-subperguntas');
                
                const subIndex = containerSub.querySelectorAll('.subpergunta-item').length;

                const htmlSubpergunta = `
                    <div class="card bg-white border-info mb-2 p-2 subpergunta-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-info text-dark">Subpergunta</span>
                            <button type="button" class="btn btn-sm text-danger p-0 fw-bold btn-remover-subpergunta">&times; Remover</button>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Se a resposta for:</label>
                                <input type="text" name="perguntas[${idxPergunta}][subperguntas][${subIndex}][condicao]" class="form-control form-control-sm" placeholder="Ex: Não" required>
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="form-label small">Perguntar:</label>
                                <input type="text" name="perguntas[${idxPergunta}][subperguntas][${subIndex}][texto]" class="form-control form-control-sm" placeholder="Ex: Descreva o motivo da inconformidade" required>
                            </div>
                        </div>
                    </div>
                `;
                containerSub.insertAdjacentHTML('beforeend', htmlSubpergunta);
            }

            // Remover Subpergunta
            if(e.target.classList.contains('btn-remover-subpergunta')) {
                e.target.closest('.subpergunta-item').remove();
            }
        });

        // 3. Atualiza a numeração das perguntas principais
        function atualizarBotoesRemover() {
            const perguntas = document.querySelectorAll('.pergunta-item');
            perguntas.forEach((pergunta, index) => {
                const badge = pergunta.querySelector('.badge:not(.bg-info)');
                const btnRemover = pergunta.querySelector('.btn-remover-pergunta');
                
                badge.textContent = `Pergunta ${index + 1}`;
                btnRemover.textContent = `Excluir Pergunta ${index + 1}`;
                
                if(perguntas.length === 1) {
                    btnRemover.disabled = true;
                } else {
                    btnRemover.disabled = false;
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

