<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Diagramas Acadêmicos - Axion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Roboto+Mono:wght@600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #ffffff; padding: 20px; }
        .font-mono { font-family: 'Roboto Mono', monospace; }
        .canvas-card { border-radius: 10px; border: 1px solid #cbd5e1; padding: 14px; background: #f8fafc; height: 100%; }
        .canvas-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0284c7; margin-bottom: 6px; }
    </style>
</head>
<body>

    <?php if (($_GET['tipo'] ?? '') === 'matriz'): ?>
    <!-- MATRIZ DE SELEÇÃO -->
    <div id="matrizContainer" style="width: 900px; padding: 25px; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <h2 class="h4 fw-bold text-dark mb-1">PROJETO INTEGRADOR • MATRIZ DE SELEÇÃO DE IDEIAS</h2>
                <small class="text-muted">Avaliação Ponderada Multicritério — Dinâmica Prática 5–5–5</small>
            </div>
            <span class="badge bg-primary px-3 py-2 fs-6">Engenharia de Requisitos</span>
        </div>

        <table class="table table-bordered align-middle text-center mb-3">
            <thead class="table-dark">
                <tr>
                    <th class="text-start ps-3" style="width: 35%;">Critérios de Avaliação (Peso 0 a 2)</th>
                    <th style="width: 32%;">Ideia 1: Gerenciador de Checklists (Axion)</th>
                    <th style="width: 33%;">Ideia 2: Fichas Médicas para Cuidadores</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start ps-3 fw-semibold">1. Afinidade Técnica da Equipe</td>
                    <td class="bg-success-subtle text-success fw-bold font-mono">2 (Alta)</td>
                    <td class="text-muted font-mono">1 (Média)</td>
                </tr>
                <tr>
                    <td class="text-start ps-3 fw-semibold">2. Domínio e Clareza do Processo</td>
                    <td class="bg-success-subtle text-success fw-bold font-mono">2 (Claro / Pátio e Frota)</td>
                    <td class="text-danger-subtle text-danger font-mono">0 (Baixo / Complexidade Médica)</td>
                </tr>
                <tr>
                    <td class="text-start ps-3 fw-semibold">3. Relevância do Problema Real</td>
                    <td class="bg-success-subtle text-success fw-bold font-mono">1 (Médio / Relevante)</td>
                    <td class="text-muted font-mono">1 (Médio / Relevante)</td>
                </tr>
                <tr>
                    <td class="text-start ps-3 fw-semibold">4. Valor Agregado do Software</td>
                    <td class="bg-success-subtle text-success fw-bold font-mono">1 (Redução de Fraude e Custo)</td>
                    <td class="text-muted font-mono">1 (Acompanhamento)</td>
                </tr>
                <tr>
                    <td class="text-start ps-3 fw-semibold">5. Viabilidade de Desenvolvimento no Semestre</td>
                    <td class="bg-success-subtle text-success fw-bold font-mono">2 (Totalmente Viável)</td>
                    <td class="text-danger-subtle text-danger font-mono">1 (Risco de Escopo/LGPD)</td>
                </tr>
                <tr class="table-light">
                    <td class="text-start ps-3 fw-bold fs-6">PONTUAÇÃO FINAL CONSOLIDADA</td>
                    <td class="bg-primary text-white fw-bold font-mono fs-5">8 PONTOS (SELECIONADA)</td>
                    <td class="bg-secondary-subtle text-secondary fw-bold font-mono fs-5">4 PONTOS</td>
                </tr>
            </tbody>
        </table>

        <div class="p-3 bg-light rounded border text-muted small">
            <strong>Decisão da Equipe:</strong> A Ideia 1 (Gerenciador de Checklists Veiculares Axion) foi selecionada unanimemente por apresentar maior viabilidade técnica, processo operacional bem delimitado e oportunidade clara de automação de fluxo de triagem de frota.
        </div>
    </div>

    <?php elseif (($_GET['tipo'] ?? '') === 'pmc'): ?>
    <!-- PROJECT MODEL CANVAS (PMC) -->
    <div id="pmcContainer" style="width: 1200px; padding: 25px; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <div>
                <h2 class="h4 fw-bold text-dark mb-0">PROJECT MODEL CANVAS (PMC) — AXION FROTAS</h2>
                <small class="text-muted">Planejamento Estruturado do Projeto Integrador</small>
            </div>
            <span class="badge bg-dark font-mono px-3 py-2">Versão 2.0 • 2026</span>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-4">
                <div class="canvas-card">
                    <div class="canvas-title">1. Pitch</div>
                    <p class="small text-dark mb-0">Para frotistas e gestores operacionais que sofrem com perdas e fraudes em papel, o Axion é uma plataforma web/mobile que audita vistorias em tempo real com triagem direta para oficina.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="canvas-card">
                    <div class="canvas-title">2. Justificativas (Problema)</div>
                    <p class="small text-dark mb-0">• Fichas de papel ilegíveis e extraviadas.<br>• Falta de comprovação antifraude com timestamp.<br>• Sistemas de mercado caros (R$ 50/carro/mês).<br>• Dificuldade de triar avarias graves.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="canvas-card">
                    <div class="canvas-title">3. Objetivos SMART</div>
                    <p class="small text-dark mb-0">• Reduzir em 80% o tempo de preenchimento.<br>• Eliminar 100% das vistorias em papel.<br>• Rastreabilidade total com Laudo Técnico e hash.<br>• Módulo de despacho em menos de 2 cliques.</p>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-4">
                <div class="canvas-card">
                    <div class="canvas-title">4. Benefícios</div>
                    <p class="small text-dark mb-0">• Conformidade com Lei de Fitts e WCAG 2.1.<br>• Agilidade de operação com luvas no pátio.<br>• Laudos com validade probatória e assinatura.<br>• Redução de custos de manutenção corretiva.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="canvas-card">
                    <div class="canvas-title">5. Produto / Entregáveis</div>
                    <p class="small text-dark mb-0">• Dashboard executivo com KPIs e filtros.<br>• Vistoria de campo com botões gigantes.<br>• Módulo de triagem e O.S. (Master-Detail).<br>• Emissor de Laudo Oficial com espelho.</p>
                </div>
            </div>
            <div class="col-4">
                <div class="canvas-card">
                    <div class="canvas-title">6. Requisitos & Acessibilidade</div>
                    <p class="small text-dark mb-0">• Contraste de cores AAA (WCAG).<br>• Fontes monoespaçadas (Roboto Mono).<br>• Banco de dados relacional 3FN (MySQL).<br>• Sessão segura e hash BCrypt.</p>
                </div>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-3">
                <div class="canvas-card">
                    <div class="canvas-title">7. Stakeholders & Usuários</div>
                    <p class="small text-dark mb-0">• Gestor de Frota (auditoria)<br>• Vistoriador de Campo (coleta)<br>• Motorista (ciência e assinatura)<br>• Mecânico da Oficina (reparo)</p>
                </div>
            </div>
            <div class="col-3">
                <div class="canvas-card">
                    <div class="canvas-title">8. Equipe do Projeto</div>
                    <p class="small text-dark mb-0">• Estela do Nascimento<br>• Vanderci Lucas<br>• Isabela Pereira<br>• Ana Klara Jardim<br>• Gustavo Meneis</p>
                </div>
            </div>
            <div class="col-3">
                <div class="canvas-card">
                    <div class="canvas-title">9. Riscos & Mitigações</div>
                    <p class="small text-dark mb-0">• Queda de internet no pátio (mitigado por auto-save progressivo).<br>• Dificuldade de toque (mitigado por botões gigantes de 72px).</p>
                </div>
            </div>
            <div class="col-3">
                <div class="canvas-card">
                    <div class="canvas-title">10. Linha do Tempo</div>
                    <p class="small text-dark mb-0">• Trab. 1: Protótipo e Wireframes.<br>• Trab. 2: Front-end HTML/CSS + WCAG.<br>• Trab. 3: Back-end PHP + MySQL completo.</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>

