<?php
// frontend/vistoria.php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

require_once __DIR__ . '/../backend/conexao.php';

// Função auxiliar para sanitizar strings e prevenir mojibake
function sanitizar(?string $str): string {
    if ($str === null || $str === '') return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$id_vistoria = intval($_GET['id_vistoria'] ?? $_SESSION['id_vistoria_ativa'] ?? 0);

// Se não houver ID informado, busca a última vistoria pendente criada pelo usuário
if ($id_vistoria <= 0) {
    $stmtUltima = $pdo->prepare("SELECT id_vistoria FROM Vistorias WHERE id_vistoriador = :id AND status = 'pendente' ORDER BY id_vistoria DESC LIMIT 1");
    $stmtUltima->execute([':id' => $_SESSION['id_usuario']]);
    $ult = $stmtUltima->fetch(PDO::FETCH_ASSOC);
    if ($ult) {
        $id_vistoria = intval($ult['id_vistoria']);
    } else {
        // Redireciona para abertura de nova vistoria se nenhuma estiver em andamento
        header("Location: cadcheck.php");
        exit;
    }
}

// 1. Busca dados da Vistoria e do Veículo
$sqlVistoria = "SELECT v.*, 
                       c.titulo AS titulo_checklist, c.categoria AS categoria_checklist,
                       ve.marca, ve.modelo, ve.ano, ve.cor, ve.marca_modelo, ve.km_rodado AS km_veiculo_cadastrado
                FROM Vistorias v
                LEFT JOIN Checklists c ON v.id_checklist = c.id_checklist
                LEFT JOIN Veiculos ve ON v.id_veiculo = ve.id_veiculo
                WHERE v.id_vistoria = :id LIMIT 1";

$stmtV = $pdo->prepare($sqlVistoria);
$stmtV->execute([':id' => $id_vistoria]);
$vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

if (!$vistoria) {
    echo "<script>alert('Vistoria não encontrada.'); window.location.href = 'cadcheck.php';</script>";
    exit;
}

$id_checklist = intval($vistoria['id_checklist'] ?? 1);

// 2. Busca as Perguntas cadastradas para este Checklist
$sqlPerguntas = "SELECT p.id_pergunta, p.id_checklist, p.texto_pergunta, p.tipo_resposta, p.obrigatorio, p.categoria, p.ordem,
                        sub.id_subpergunta, sub.condicao_resposta, sub.texto_pergunta AS texto_subpergunta, sub.tipo_resposta AS tipo_subpergunta
                 FROM Perguntas p
                 LEFT JOIN Subperguntas sub ON p.id_pergunta = sub.id_pergunta_pai
                 WHERE p.id_checklist = :id
                 ORDER BY p.ordem ASC, p.id_pergunta ASC";

$stmtP = $pdo->prepare($sqlPerguntas);
$stmtP->execute([':id' => $id_checklist]);
$rawPerguntas = $stmtP->fetchAll(PDO::FETCH_ASSOC);

// Agrupa perguntas e subperguntas
$perguntas = [];
foreach ($rawPerguntas as $row) {
    $idP = $row['id_pergunta'];
    if (!isset($perguntas[$idP])) {
        $perguntas[$idP] = [
            'id_pergunta'    => $idP,
            'texto_pergunta' => $row['texto_pergunta'],
            'tipo_resposta'  => $row['tipo_resposta'],
            'obrigatorio'    => $row['obrigatorio'],
            'categoria'      => $row['categoria'],
            'ordem'          => $row['ordem'],
            'subperguntas'   => []
        ];
    }
    if (!empty($row['id_subpergunta'])) {
        $perguntas[$idP]['subperguntas'][] = [
            'id_subpergunta'    => $row['id_subpergunta'],
            'condicao_resposta' => $row['condicao_resposta'],
            'texto_subpergunta' => $row['texto_subpergunta'],
            'tipo_subpergunta'  => $row['tipo_subpergunta']
        ];
    }
}
$perguntas = array_values($perguntas);

// Fallback de perguntas se o modelo não tiver nenhuma
if (empty($perguntas)) {
    $perguntas = [
        [
            'id_pergunta'    => 1,
            'texto_pergunta' => 'O veículo possui alguma avaria visível na lataria ou pintura?',
            'tipo_resposta'  => 'sim_nao',
            'obrigatorio'    => 1,
            'categoria'      => 'estetica',
            'ordem'          => 1,
            'subperguntas'   => []
        ],
        [
            'id_pergunta'    => 2,
            'texto_pergunta' => 'Os faróis, lanternas e setas estão operando corretamente?',
            'tipo_resposta'  => 'sim_nao',
            'obrigatorio'    => 1,
            'categoria'      => 'eletrica',
            'ordem'          => 2,
            'subperguntas'   => []
        ],
        [
            'id_pergunta'    => 3,
            'texto_pergunta' => 'Nível do óleo e fluido de arrefecimento estão dentro do padrão?',
            'tipo_resposta'  => 'sim_nao',
            'obrigatorio'    => 1,
            'categoria'      => 'mecanica',
            'ordem'          => 3,
            'subperguntas'   => []
        ],
        [
            'id_pergunta'    => 4,
            'texto_pergunta' => 'Os pneus apresentam desgaste excessivo ou corte lateral visível?',
            'tipo_resposta'  => 'sim_nao',
            'obrigatorio'    => 1,
            'categoria'      => 'pneus',
            'ordem'          => 4,
            'subperguntas'   => []
        ],
        [
            'id_pergunta'    => 5,
            'texto_pergunta' => 'Os cintos de segurança e itens obrigatórios (estepe, macaco) estão disponíveis?',
            'tipo_resposta'  => 'sim_nao',
            'obrigatorio'    => 1,
            'categoria'      => 'interior',
            'ordem'          => 5,
            'subperguntas'   => []
        ],
        [
            'id_pergunta'    => 6,
            'texto_pergunta' => 'Informe o valor atual do hodômetro no painel:',
            'tipo_resposta'  => 'numero',
            'obrigatorio'    => 1,
            'categoria'      => 'mecanica',
            'ordem'          => 6,
            'subperguntas'   => []
        ]
    ];
}

// 3. Busca respostas já gravadas (se houver) para retomar vistoria
$stmtResp = $pdo->prepare("SELECT * FROM RespostasVistoria WHERE id_vistoria = :id");
$stmtResp->execute([':id' => $id_vistoria]);
$respostasExistentes = $stmtResp->fetchAll(PDO::FETCH_ASSOC);
$mapaRespostas = [];
foreach ($respostasExistentes as $re) {
    $mapaRespostas[$re['id_pergunta']] = [
        'valor_resposta' => $re['valor_resposta'],
        'conforme'       => $re['conforme']
    ];
}

$modeloVeiculo = $vistoria['marca_modelo'] ?? ($vistoria['marca'] . ' ' . $vistoria['modelo']);
if (empty(trim($modeloVeiculo))) $modeloVeiculo = 'Veículo Inspecionado';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Execução de Vistoria #<?= $vistoria['id_vistoria'] ?> - Axion</title>

    <!-- Google Fonts: Roboto Mono e Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --cor-primaria: #0284c7;
            --cor-primaria-hover: #0369a1;
            --cor-conforme: #16a34a;
            --cor-conforme-hover: #15803d;
            --cor-nao-conforme: #dc2626;
            --cor-nao-conforme-hover: #b91c1c;
            --cor-na: #64748b;
            --cor-na-hover: #475569;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
        }

        .font-mono {
            font-family: 'Roboto Mono', monospace !important;
            letter-spacing: 0.02em;
        }

        /* Barra de Controle Superior e Toggle Híbrido */
        .top-app-bar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .toggle-hybrid-container {
            background-color: #e2e8f0;
            border-radius: 9999px;
            padding: 3px;
            display: inline-flex;
            gap: 2px;
        }

        .toggle-hybrid-btn {
            border: none;
            background: transparent;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .toggle-hybrid-btn.active {
            background-color: #ffffff;
            color: #0f172a;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        /* Barra de Progresso Foco de Campo */
        .progress-indicator {
            height: 6px;
            background-color: #e2e8f0;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, #0284c7, #10b981);
            transition: width 0.3s ease;
        }

        /* MODO CAMPO: Layout Card de Foco (Lei de Fitts e Sol Forte) */
        .card-modo-campo {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            max-width: 650px;
            margin: 0 auto;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        /* Botões Gigantes para Uso com Luvas e Dedão */
        .btn-acao-gigante {
            min-height: 72px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.18rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border: 2px solid transparent;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            touch-action: manipulation;
        }

        .btn-acao-gigante:active {
            transform: scale(0.97);
        }

        .btn-gigante-conforme {
            background-color: var(--cor-conforme);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3);
        }

        .btn-gigante-conforme:hover, .btn-gigante-conforme:focus {
            background-color: var(--cor-conforme-hover);
            color: #ffffff;
        }

        .btn-gigante-conforme.selected {
            background-color: #14532d;
            border-color: #86efac;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.35);
        }

        .btn-gigante-nao-conforme {
            background-color: var(--cor-nao-conforme);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }

        .btn-gigante-nao-conforme:hover, .btn-gigante-nao-conforme:focus {
            background-color: var(--cor-nao-conforme-hover);
            color: #ffffff;
        }

        .btn-gigante-nao-conforme.selected {
            background-color: #7f1d1d;
            border-color: #fca5a5;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.35);
        }

        .btn-gigante-na {
            min-height: 52px;
            background-color: var(--cor-na);
            color: #ffffff;
            font-size: 0.95rem;
        }

        .btn-gigante-na:hover, .btn-gigante-na:focus {
            background-color: var(--cor-na-hover);
            color: #ffffff;
        }

        .btn-gigante-na.selected {
            background-color: #1e293b;
            border-color: #cbd5e1;
            box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.3);
        }

        /* Gaveta Retrátil de Evidência Fotográfica */
        .gaveta-evidencia {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 14px;
            padding: 16px;
            margin-top: 18px;
            animation: fadeIn 0.25s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .thumb-preview {
            max-height: 140px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            object-fit: cover;
        }

        /* MODO LISTA: Cards Empilhados com Rolagem Contínua */
        .card-modo-lista-item {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 14px;
            padding: 18px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .card-modo-lista-item.respondido-conforme {
            border-left: 5px solid var(--cor-conforme);
        }

        .card-modo-lista-item.respondido-nao-conforme {
            border-left: 5px solid var(--cor-nao-conforme);
            background-color: #fffafb;
        }

        .card-modo-lista-item.respondido-na {
            border-left: 5px solid var(--cor-na);
        }

        /* Canvas de Assinatura */
        .canvas-assinatura {
            border: 2px dashed #94a3b8;
            border-radius: 10px;
            cursor: crosshair;
            background-color: #ffffff;
            width: 100%;
            height: 150px;
            touch-action: none;
        }
    </style>
</head>

<body>

    <!-- 1. BARRA SUPERIOR DE CONTROLE E STATUS -->
    <header class="top-app-bar px-3 py-2">
        <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-2 max-w-1000 mx-auto" style="max-width: 900px;">
            
            <!-- Identificação do Veículo -->
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-2 py-1" title="Sair da Vistoria" onclick="return confirm('Deseja sair? Suas respostas preenchidas serão salvas.')">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark font-mono px-2 py-1 fs-6"><?= sanitizar($vistoria['placa_veiculo']) ?></span>
                        <strong class="text-dark small d-none d-sm-inline"><?= sanitizar($modeloVeiculo) ?></strong>
                    </div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">
                        Odômetro: <span class="font-mono"><?= number_format($vistoria['km_rodado'], 0, ',', '.') ?> km</span> • Motorista: <?= sanitizar($vistoria['nome_motorista']) ?>
                    </small>
                </div>
            </div>

            <!-- ALTERNADOR HÍBRIDO (Modo Campo vs Modo Lista) -->
            <div class="d-flex align-items-center gap-2">
                <div class="toggle-hybrid-container" role="group" aria-label="Alternador de Modo de Visualização">
                    <button type="button" id="btnModoCampo" class="toggle-hybrid-btn active" onclick="alternarModo('campo')">
                        <i class="bi bi-phone"></i>
                        <span>Modo Campo</span>
                    </button>
                    <button type="button" id="btnModoLista" class="toggle-hybrid-btn" onclick="alternarModo('lista')">
                        <i class="bi bi-card-checklist"></i>
                        <span>Modo Lista</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- BARRA DE PROGRESSO PERSISTENTE -->
        <div class="progress-indicator mt-2">
            <div id="barraProgresso" class="progress-bar-fill h-100" style="width: 0%;"></div>
        </div>
    </header>

    <!-- SUB-BARRA DE ESTATÍSTICA E CONTROLE DE AVANÇO -->
    <div class="bg-white border-bottom py-1 px-3 mb-3">
        <div class="d-flex justify-content-between align-items-center max-w-1000 mx-auto small text-muted" style="max-width: 900px;">
            <div>
                <span id="badgeProgressoTexto" class="fw-semibold text-primary">Item 1 de <?= count($perguntas) ?> (0%)</span>
                <span class="mx-1">•</span>
                <span id="badgeCategoriaAtual" class="badge bg-secondary-subtle text-secondary text-uppercase" style="font-size: 0.7rem;">Mecânica</span>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="chkAutoAvanco" checked>
                <label class="form-check-label" for="chkAutoAvanco" style="font-size: 0.78rem;">Avanço Rápido</label>
            </div>
        </div>
    </div>

    <!-- CORPO PRINCIPAL -->
    <main class="container py-2 pb-5" style="max-width: 900px;">

        <!-- =================================================================== -->
        <!-- MODO 1: MODO CAMPO (1 PERGUNTA POR TELA COM BOTÕES GIGANTES)        -->
        <!-- =================================================================== -->
        <section id="secaoModoCampo" class="d-block">
            
            <div class="card card-modo-campo p-4 p-md-5">

                <!-- Cabeçalho do Card de Pergunta -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span id="campoCategoriaBadge" class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-6">
                        <i class="bi bi-tools me-1"></i> <span id="campoCategoriaNome">Categoria</span>
                    </span>
                    <span id="campoNumeroItem" class="font-mono text-muted small fw-bold">ITEM #1 / <?= count($perguntas) ?></span>
                </div>

                <!-- Pergunta em Destaque -->
                <h1 id="campoTextoPergunta" class="h3 fw-bold text-dark mb-4 text-center py-2" style="line-height: 1.35;">
                    Texto da pergunta...
                </h1>

                <!-- ÁREA DE RESPOSTA (BOTÕES GIGANTES OU INPUT NUMÉRICO) -->
                <div id="campoAreaBotoes" class="d-flex flex-column gap-3 mb-3">
                    
                    <!-- Botão GIGANTE: CONFORME (Verde Esmeralda) -->
                    <button type="button" class="btn btn-acao-gigante btn-gigante-conforme" onclick="responderPerguntaAtual(1)">
                        <i class="bi bi-check-circle-fill fs-2"></i>
                        <span>Conforme (OK)</span>
                    </button>

                    <!-- Botão GIGANTE: NÃO CONFORME (Vermelho Carmesim) -->
                    <button type="button" class="btn btn-acao-gigante btn-gigante-nao-conforme" onclick="responderPerguntaAtual(0)">
                        <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                        <span>Não Conforme (Avaria)</span>
                    </button>

                    <!-- Botão: NÃO SE APLICA (Cinza Neutro) -->
                    <button type="button" class="btn btn-acao-gigante btn-gigante-na" onclick="responderPerguntaAtual(null)">
                        <i class="bi bi-dash-circle fs-4"></i>
                        <span>Não se Aplica (N/A)</span>
                    </button>
                </div>

                <!-- ÁREA DE RESPOSTA PARA PERGUNTAS NUMÉRICAS (HODÔMETRO / PRESSÃO) -->
                <div id="campoAreaNumero" class="d-none mb-3 text-center">
                    <label class="form-label text-muted small fw-bold">Digite o valor numérico medido:</label>
                    <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="ajustarValorNumero(-100)">-100</button>
                        <input type="number" id="campoInputNumero" class="form-control form-control-lg text-center font-mono fs-2 fw-bold" style="max-width: 240px;" placeholder="0">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="ajustarValorNumero(+100)">+100</button>
                    </div>
                    <button type="button" class="btn btn-primary btn-lg w-100 py-3 fw-bold" onclick="salvarValorNumeroCampo()">
                        <i class="bi bi-check-lg me-1"></i> Confirmar Valor
                    </button>
                </div>

                <!-- GAVETA DE EVIDÊNCIA FOTOGRÁFICA (Abre quando seleciona Não Conforme) -->
                <div id="campoGavetaEvidencia" class="gaveta-evidencia d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-danger small">
                            <i class="bi bi-camera-fill me-1"></i> Registrar Evidência da Não Conformidade
                        </strong>
                        <span class="badge bg-danger text-white">Obrigatório</span>
                    </div>

                    <!-- Botão de Captura de Câmera / Foto -->
                    <div class="mb-3">
                        <label for="campoInputFoto" class="btn btn-outline-danger btn-lg w-100 d-flex align-items-center justify-content-center gap-2 py-3">
                            <i class="bi bi-camera-fill fs-3"></i>
                            <span id="campoLabelFoto">Tirar Foto com a Câmera</span>
                        </label>
                        <input type="file" id="campoInputFoto" accept="image/*" capture="environment" class="d-none" onchange="aoSelecionarFotoCampo(this)">
                        
                        <!-- Preview da Foto Tirada -->
                        <div id="campoPreviewContainer" class="text-center mt-2 d-none">
                            <img id="campoFotoPreview" src="" alt="Foto Avaria" class="thumb-preview mb-1">
                            <div>
                                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none" onclick="removerFotoCampo()">
                                    <i class="bi bi-trash me-1"></i> Remover Foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Nível de Criticidade da Avaria -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Gravidade / Criticidade:</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill btn-crit" onclick="definirCriticidadeCampo('baixa', this)">Baixa</button>
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark flex-fill btn-crit active" onclick="definirCriticidadeCampo('media', this)">Média</button>
                            <button type="button" class="btn btn-sm btn-outline-danger flex-fill btn-crit" onclick="definirCriticidadeCampo('alta', this)">Alta</button>
                            <button type="button" class="btn btn-sm btn-danger flex-fill btn-crit" onclick="definirCriticidadeCampo('critica', this)">Crítica</button>
                        </div>
                    </div>

                    <!-- Observação Detalhada da Avaria -->
                    <div class="mb-2">
                        <label for="campoTextoObs" class="form-label small fw-bold text-dark mb-1" id="campoLabelObs">Descrição da Avaria:</label>
                        <textarea id="campoTextoObs" class="form-control" rows="2" placeholder="Descreva o defeito observado (ex: trinca na lente, pneu rasgado)..."></textarea>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-sm btn-danger px-3" onclick="confirmarAvariaCampo()">
                            Salvar Avaria e Continuar <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- NAVEGAÇÃO INFERIOR NO MODO CAMPO -->
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <button type="button" id="btnCampoAnterior" class="btn btn-outline-secondary px-3 py-2 fw-semibold" onclick="navegarCampo(-1)">
                        <i class="bi bi-chevron-left me-1"></i> Anterior
                    </button>
                    
                    <span id="campoContadorRodape" class="small text-muted font-mono">1 de <?= count($perguntas) ?></span>

                    <button type="button" id="btnCampoProximo" class="btn btn-primary px-3 py-2 fw-semibold" onclick="navegarCampo(+1)">
                        Próximo <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>

            </div>

        </section>

        <!-- =================================================================== -->
        <!-- MODO 2: MODO LISTA COMPLETA (ROLAGEM CONTÍNUA / INSTAGRAM-STYLE)   -->
        <!-- =================================================================== -->
        <section id="secaoModoLista" class="d-none">
            
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold text-dark mb-0">Checklist Completo de Itens</h2>
                <span class="badge bg-light text-dark border font-mono">Total: <?= count($perguntas) ?> itens</span>
            </div>

            <div id="containerListaCompleta">
                <!-- Cards de perguntas renderizados via JavaScript -->
            </div>

            <!-- Botão no Fim do Modo Lista -->
            <div class="text-center py-4">
                <button type="button" class="btn btn-success btn-lg px-5 py-3 fw-bold shadow-sm" onclick="abrirModalAssinatura()">
                    <i class="bi bi-pen-fill me-2"></i> Concluir Checklist e Ir para Assinaturas
                </button>
            </div>

        </section>

    </main>

    <!-- =================================================================== -->
    <!-- MODAL / ETAPA FINAL: REVISÃO DE ITENS E ASSINATURAS DIGITAIS        -->
    <!-- =================================================================== -->
    <div class="modal fade" id="modalFinalizar" tabindex="-1" aria-labelledby="modalFinalizarLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="modalFinalizarLabel">
                        <i class="bi bi-clipboard2-check me-2 text-success"></i> Revisão e Encerramento da Vistoria
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-4">

                    <!-- RESUMO ESTATÍSTICO -->
                    <div class="row g-2 text-center mb-4">
                        <div class="col-4">
                            <div class="p-3 bg-light rounded border">
                                <span class="d-block text-muted small fw-bold text-uppercase">Total Itens</span>
                                <span class="fs-4 fw-bold text-dark font-mono" id="revTotalItens"><?= count($perguntas) ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-success-subtle rounded border border-success-subtle">
                                <span class="d-block text-success small fw-bold text-uppercase">Conformes</span>
                                <span class="fs-4 fw-bold text-success font-mono" id="revConformes">0</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-danger-subtle rounded border border-danger-subtle">
                                <span class="d-block text-danger small fw-bold text-uppercase">Não Conformes</span>
                                <span class="fs-4 fw-bold text-danger font-mono" id="revNaoConformes">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- LISTAGEM DE NÃO CONFORMIDADES (SE HOUVER) -->
                    <div id="revBoxAvarias" class="mb-4 d-none">
                        <h6 class="fw-bold text-danger mb-2">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Avarias Registradas que Gerarão Triagem / Oficina:
                        </h6>
                        <ul id="revListaAvarias" class="list-group small mb-3"></ul>
                    </div>

                    <!-- ASSINATURAS DIGITAIS EM CANVAS -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-pen me-1 text-primary"></i> Assinaturas Digitais em Tela Sensível
                    </h6>

                    <div class="row g-3">
                        <!-- Assinatura do Motorista -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">Assinatura do Motorista (<?= sanitizar($vistoria['nome_motorista']) ?>):</label>
                                <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" onclick="limparCanvas('canvasMotorista')">
                                    <i class="bi bi-eraser"></i> Limpar
                                </button>
                            </div>
                            <canvas id="canvasMotorista" class="canvas-assinatura"></canvas>
                        </div>

                        <!-- Assinatura do Vistoriador -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">Assinatura do Vistoriador (<?= sanitizar($_SESSION['nome_usuario'] ?? $vistoria['nome_vistoriador']) ?>):</label>
                                <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" onclick="limparCanvas('canvasVistoriador')">
                                    <i class="bi bi-eraser"></i> Limpar
                                </button>
                            </div>
                            <canvas id="canvasVistoriador" class="canvas-assinatura"></canvas>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-top bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Revisar Perguntas</button>
                    <button type="button" id="btnSalvarFinal" class="btn btn-success fw-bold px-4 py-2" onclick="transmitirVistoria()">
                        <i class="bi bi-check2-circle me-1"></i> Finalizar Vistoria e Emitir Laudo
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script de Gestão de Estado Híbrido e Interação -->
    <script>
        // Dados PHP passados para o escopo JavaScript
        const ID_VISTORIA = <?= $id_vistoria ?>;
        const PERGUNTAS = <?= json_encode($perguntas, JSON_UNESCAPED_UNICODE) ?>;
        const RESPOSTAS_PREVIAS = <?= json_encode($mapaRespostas, JSON_UNESCAPED_UNICODE) ?>;

        // Estado Global da Vistoria
        let estado = {
            indiceAtual: 0,
            modo: 'campo', // 'campo' ou 'lista'
            autoAvanco: true,
            respostas: {} // chave: id_pergunta => { conforme, valor_resposta, criticidade, obs, fotoFile }
        };

        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            // Recupera preferência de modo salva no localStorage (ou padrão 'campo' para mobile)
            const modoSalvo = localStorage.getItem('axion_modo_vistoria');
            if (modoSalvo === 'lista' || modoSalvo === 'campo') {
                estado.modo = modoSalvo;
            }

            // Hidrata respostas prévias se existirem
            PERGUNTAS.forEach(p => {
                const prev = RESPOSTAS_PREVIAS[p.id_pergunta];
                if (prev) {
                    estado.respostas[p.id_pergunta] = {
                        id_pergunta: p.id_pergunta,
                        texto_pergunta: p.texto_pergunta,
                        conforme: prev.conforme !== null ? parseInt(prev.conforme) : null,
                        valor_resposta: prev.valor_resposta || '',
                        criticidade: 'media',
                        obs: '',
                        fotoFile: null
                    };
                }
            });

            // Configura toggle de auto-avanço
            const chkAuto = document.getElementById('chkAutoAvanco');
            chkAuto.addEventListener('change', (e) => {
                estado.autoAvanco = e.target.checked;
            });

            // Renderiza ambas as visões
            renderizarModoCampo();
            renderizarModoLista();
            atualizarProgressoGeral();

            // Aplica visualização do modo inicial
            aplicarVisualizacaoModo(estado.modo);

            // Inicializa eventos de desenho nos canvas de assinatura
            iniciarCanvas('canvasMotorista');
            iniciarCanvas('canvasVistoriador');
        });

        // Alternância Híbrida (Modo Campo vs Modo Lista)
        function alternarModo(novoModo) {
            estado.modo = novoModo;
            localStorage.setItem('axion_modo_vistoria', novoModo);
            aplicarVisualizacaoModo(novoModo);
        }

        function aplicarVisualizacaoModo(modo) {
            const btnCampo = document.getElementById('btnModoCampo');
            const btnLista = document.getElementById('btnModoLista');
            const secCampo = document.getElementById('secaoModoCampo');
            const secLista = document.getElementById('secaoModoLista');

            if (modo === 'campo') {
                btnCampo.classList.add('active');
                btnLista.classList.remove('active');
                secCampo.classList.remove('d-none');
                secLista.classList.add('d-none');
                renderizarModoCampo();
            } else {
                btnLista.classList.add('active');
                btnCampo.classList.remove('active');
                secLista.classList.remove('d-none');
                secCampo.classList.add('d-none');
                renderizarModoLista();
            }
        }

        // =====================================================================
        // MODO CAMPO: Lógica de Exibição 1 Pergunta por Tela
        // =====================================================================
        function renderizarModoCampo() {
            if (estado.indiceAtual >= PERGUNTAS.length) {
                abrirModalAssinatura();
                return;
            }

            const p = PERGUNTAS[estado.indiceAtual];
            const resp = estado.respostas[p.id_pergunta];

            document.getElementById('campoNumeroItem').innerText = `ITEM #${estado.indiceAtual + 1} / ${PERGUNTAS.length}`;
            document.getElementById('campoContadorRodape').innerText = `${estado.indiceAtual + 1} de ${PERGUNTAS.length}`;
            document.getElementById('campoTextoPergunta').innerText = p.texto_pergunta;
            document.getElementById('campoCategoriaNome').innerText = p.categoria ? p.categoria.toUpperCase() : 'GERAL';
            document.getElementById('badgeCategoriaAtual').innerText = p.categoria ? p.categoria.toUpperCase() : 'GERAL';

            // Atualiza botões Anterior / Próximo
            document.getElementById('btnCampoAnterior').disabled = (estado.indiceAtual === 0);
            const btnProx = document.getElementById('btnCampoProximo');
            if (estado.indiceAtual === PERGUNTAS.length - 1) {
                btnProx.innerHTML = `Concluir <i class="bi bi-check2-all ms-1"></i>`;
                btnProx.className = 'btn btn-success px-3 py-2 fw-semibold';
            } else {
                btnProx.innerHTML = `Próximo <i class="bi bi-chevron-right ms-1"></i>`;
                btnProx.className = 'btn btn-primary px-3 py-2 fw-semibold';
            }

            // Trata pergunta numérica vs botões Conforme/Não Conforme
            const areaBotoes = document.getElementById('campoAreaBotoes');
            const areaNumero = document.getElementById('campoAreaNumero');
            const gaveta = document.getElementById('campoGavetaEvidencia');

            // Limpa seleções visuais
            document.querySelectorAll('#campoAreaBotoes .btn-acao-gigante').forEach(b => b.classList.remove('selected'));

            if (p.tipo_resposta === 'numero') {
                areaBotoes.classList.add('d-none');
                areaNumero.classList.remove('d-none');
                gaveta.classList.add('d-none');
                const valNum = resp ? resp.valor_resposta : '0';
                document.getElementById('campoInputNumero').value = valNum;
            } else {
                areaBotoes.classList.remove('d-none');
                areaNumero.classList.add('d-none');

                if (resp) {
                    if (resp.conforme === 1) {
                        document.querySelector('.btn-gigante-conforme').classList.add('selected');
                        gaveta.classList.add('d-none');
                    } else if (resp.conforme === 0) {
                        document.querySelector('.btn-gigante-nao-conforme').classList.add('selected');
                        gaveta.classList.remove('d-none');
                        carregarDadosGavetaCampo(resp);
                    } else {
                        document.querySelector('.btn-gigante-na').classList.add('selected');
                        gaveta.classList.add('d-none');
                    }
                } else {
                    gaveta.classList.add('d-none');
                }
            }

            atualizarProgressoGeral();
        }

        function responderPerguntaAtual(conformeValor) {
            const p = PERGUNTAS[estado.indiceAtual];
            const gaveta = document.getElementById('campoGavetaEvidencia');

            // Registra no estado
            if (!estado.respostas[p.id_pergunta]) {
                estado.respostas[p.id_pergunta] = {
                    id_pergunta: p.id_pergunta,
                    texto_pergunta: p.texto_pergunta,
                    conforme: conformeValor,
                    valor_resposta: conformeValor === 1 ? 'Conforme' : (conformeValor === 0 ? 'Não Conforme' : 'N/A'),
                    criticidade: 'media',
                    obs: '',
                    fotoFile: null
                };
            } else {
                estado.respostas[p.id_pergunta].conforme = conformeValor;
                estado.respostas[p.id_pergunta].valor_resposta = conformeValor === 1 ? 'Conforme' : (conformeValor === 0 ? 'Não Conforme' : 'N/A');
            }

            // Atualiza botões
            document.querySelectorAll('#campoAreaBotoes .btn-acao-gigante').forEach(b => b.classList.remove('selected'));

            if (conformeValor === 1) {
                document.querySelector('.btn-gigante-conforme').classList.add('selected');
                gaveta.classList.add('d-none');
                sincronizarItemComModoLista(p.id_pergunta);
                atualizarProgressoGeral();

                if (estado.autoAvanco) {
                    setTimeout(() => navegarCampo(+1), 300);
                }
            } else if (conformeValor === 0) {
                document.querySelector('.btn-gigante-nao-conforme').classList.add('selected');
                gaveta.classList.remove('d-none');
                carregarDadosGavetaCampo(estado.respostas[p.id_pergunta]);
                sincronizarItemComModoLista(p.id_pergunta);
                atualizarProgressoGeral();
            } else {
                document.querySelector('.btn-gigante-na').classList.add('selected');
                gaveta.classList.add('d-none');
                sincronizarItemComModoLista(p.id_pergunta);
                atualizarProgressoGeral();

                if (estado.autoAvanco) {
                    setTimeout(() => navegarCampo(+1), 300);
                }
            }
        }

        function navegarCampo(delta) {
            const novo = estado.indiceAtual + delta;
            if (novo < 0) return;
            if (novo >= PERGUNTAS.length) {
                abrirModalAssinatura();
                return;
            }
            estado.indiceAtual = novo;
            renderizarModoCampo();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Lógica da gaveta de avarias no modo campo
        function carregarDadosGavetaCampo(resp) {
            document.getElementById('campoTextoObs').value = resp.obs || '';
            definirCriticidadeCampo(resp.criticidade || 'media');
            if (resp.fotoPreviewSrc) {
                document.getElementById('campoFotoPreview').src = resp.fotoPreviewSrc;
                document.getElementById('campoPreviewContainer').classList.remove('d-none');
                document.getElementById('campoLabelFoto').innerText = 'Trocar Foto';
            } else {
                document.getElementById('campoPreviewContainer').classList.add('d-none');
                document.getElementById('campoLabelFoto').innerText = 'Tirar Foto com a Câmera';
            }
        }

        function definirCriticidadeCampo(crit, btnElement) {
            const p = PERGUNTAS[estado.indiceAtual];
            if (estado.respostas[p.id_pergunta]) {
                estado.respostas[p.id_pergunta].criticidade = crit;
            }
            document.querySelectorAll('#campoGavetaEvidencia .btn-crit').forEach(b => b.classList.remove('active', 'border-dark'));
            if (btnElement) {
                btnElement.classList.add('active', 'border-dark');
            }
        }

        function aoSelecionarFotoCampo(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const p = PERGUNTAS[estado.indiceAtual];
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('campoFotoPreview').src = e.target.result;
                    document.getElementById('campoPreviewContainer').classList.remove('d-none');
                    document.getElementById('campoLabelFoto').innerText = 'Foto Capturada (Trocar)';

                    if (estado.respostas[p.id_pergunta]) {
                        estado.respostas[p.id_pergunta].fotoFile = file;
                        estado.respostas[p.id_pergunta].fotoPreviewSrc = e.target.result;
                    }
                    sincronizarItemComModoLista(p.id_pergunta);
                };
                reader.readAsDataURL(file);
            }
        }

        function removerFotoCampo() {
            const p = PERGUNTAS[estado.indiceAtual];
            document.getElementById('campoInputFoto').value = '';
            document.getElementById('campoPreviewContainer').classList.add('d-none');
            document.getElementById('campoLabelFoto').innerText = 'Tirar Foto com a Câmera';
            if (estado.respostas[p.id_pergunta]) {
                estado.respostas[p.id_pergunta].fotoFile = null;
                estado.respostas[p.id_pergunta].fotoPreviewSrc = null;
            }
            sincronizarItemComModoLista(p.id_pergunta);
        }

        function confirmarAvariaCampo() {
            const p = PERGUNTAS[estado.indiceAtual];
            const obs = document.getElementById('campoTextoObs').value.trim();
            if (estado.respostas[p.id_pergunta]) {
                estado.respostas[p.id_pergunta].obs = obs;
            }
            sincronizarItemComModoLista(p.id_pergunta);
            navegarCampo(+1);
        }

        function ajustarValorNumero(delta) {
            const input = document.getElementById('campoInputNumero');
            let val = parseInt(input.value) || 0;
            val = Math.max(0, val + delta);
            input.value = val;
        }

        function salvarValorNumeroCampo() {
            const p = PERGUNTAS[estado.indiceAtual];
            const val = document.getElementById('campoInputNumero').value.trim();
            estado.respostas[p.id_pergunta] = {
                id_pergunta: p.id_pergunta,
                texto_pergunta: p.texto_pergunta,
                conforme: 1,
                valor_resposta: val,
                criticidade: 'baixa',
                obs: '',
                fotoFile: null
            };
            sincronizarItemComModoLista(p.id_pergunta);
            atualizarProgressoGeral();
            navegarCampo(+1);
        }

        // =====================================================================
        // MODO LISTA: Lógica de Exibição Contínua (Scroll / Instagram-style)
        // =====================================================================
        function renderizarModoLista() {
            const container = document.getElementById('containerListaCompleta');
            container.innerHTML = '';

            PERGUNTAS.forEach((p, index) => {
                const resp = estado.respostas[p.id_pergunta];
                let classeBorda = '';
                if (resp) {
                    if (resp.conforme === 1) classeBorda = 'respondido-conforme';
                    else if (resp.conforme === 0) classeBorda = 'respondido-nao-conforme';
                    else classeBorda = 'respondido-na';
                }

                const card = document.createElement('div');
                card.id = `cardLista_${p.id_pergunta}`;
                card.className = `card card-modo-lista-item ${classeBorda}`;

                let htmlResposta = '';
                if (p.tipo_resposta === 'numero') {
                    const valNum = resp ? resp.valor_resposta : '';
                    htmlResposta = `
                        <div class="row g-2 align-items-center mt-2">
                            <div class="col-sm-6">
                                <input type="number" class="form-control font-mono" id="inputListaNum_${p.id_pergunta}" value="${valNum}" placeholder="Valor medido..." onchange="salvarNumeroLista(${p.id_pergunta}, this.value)">
                            </div>
                            <div class="col-sm-6">
                                <span class="badge bg-secondary-subtle text-secondary">Registro Numérico</span>
                            </div>
                        </div>
                    `;
                } else {
                    const isConf = resp && resp.conforme === 1 ? 'btn-success text-white' : 'btn-outline-success';
                    const isNConf = resp && resp.conforme === 0 ? 'btn-danger text-white' : 'btn-outline-danger';
                    const isNA = resp && resp.conforme === null ? 'btn-secondary text-white' : 'btn-outline-secondary';

                    const gavetaAberta = (resp && resp.conforme === 0) ? '' : 'd-none';

                    htmlResposta = `
                        <div class="btn-group w-100 my-2" role="group">
                            <button type="button" class="btn ${isConf} py-2 fw-semibold" onclick="responderLista(${p.id_pergunta}, 1)">
                                <i class="bi bi-check-lg me-1"></i> Conforme
                            </button>
                            <button type="button" class="btn ${isNConf} py-2 fw-semibold" onclick="responderLista(${p.id_pergunta}, 0)">
                                <i class="bi bi-x-lg me-1"></i> Não Conforme
                            </button>
                            <button type="button" class="btn ${isNA} py-2" onclick="responderLista(${p.id_pergunta}, null)">
                                N/A
                            </button>
                        </div>

                        <!-- Gaveta Não Conforme na Lista -->
                        <div id="gavetaLista_${p.id_pergunta}" class="gaveta-evidencia ${gavetaAberta}">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-7">
                                    <input type="text" class="form-control form-control-sm mb-2" id="obsLista_${p.id_pergunta}" value="${resp ? (resp.obs || '') : ''}" placeholder="Descrição da avaria..." oninput="atualizarObsLista(${p.id_pergunta}, this.value)">
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="setCriticidadeLista(${p.id_pergunta}, 'baixa')">Baixa</button>
                                        <button type="button" class="btn btn-xs btn-outline-warning text-dark btn-sm" onclick="setCriticidadeLista(${p.id_pergunta}, 'media')">Média</button>
                                        <button type="button" class="btn btn-xs btn-outline-danger btn-sm" onclick="setCriticidadeLista(${p.id_pergunta}, 'alta')">Alta</button>
                                    </div>
                                </div>
                                <div class="col-md-5 text-end">
                                    <label class="btn btn-outline-danger btn-sm w-100 mb-1">
                                        <i class="bi bi-camera me-1"></i> Anexar Foto
                                        <input type="file" accept="image/*" capture="environment" class="d-none" onchange="aoSelecionarFotoLista(${p.id_pergunta}, this)">
                                    </label>
                                    <div id="previewListaContainer_${p.id_pergunta}" class="${resp && resp.fotoPreviewSrc ? '' : 'd-none'}">
                                        <img src="${resp && resp.fotoPreviewSrc ? resp.fotoPreviewSrc : ''}" class="thumb-preview w-100" style="max-height: 80px;" alt="Avaria">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                card.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-secondary-subtle text-secondary small text-uppercase">${p.categoria || 'Geral'}</span>
                        <span class="text-muted small font-mono">#${index + 1}</span>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-2">${p.texto_pergunta}</h3>
                    ${htmlResposta}
                `;

                container.appendChild(card);
            });
        }

        function responderLista(idPergunta, conformeValor) {
            const p = PERGUNTAS.find(item => item.id_pergunta === idPergunta);
            if (!p) return;

            if (!estado.respostas[idPergunta]) {
                estado.respostas[idPergunta] = {
                    id_pergunta: idPergunta,
                    texto_pergunta: p.texto_pergunta,
                    conforme: conformeValor,
                    valor_resposta: conformeValor === 1 ? 'Conforme' : (conformeValor === 0 ? 'Não Conforme' : 'N/A'),
                    criticidade: 'media',
                    obs: '',
                    fotoFile: null
                };
            } else {
                estado.respostas[idPergunta].conforme = conformeValor;
                estado.respostas[idPergunta].valor_resposta = conformeValor === 1 ? 'Conforme' : (conformeValor === 0 ? 'Não Conforme' : 'N/A');
            }

            sincronizarItemComModoLista(idPergunta);
            atualizarProgressoGeral();
        }

        function salvarNumeroLista(idPergunta, valor) {
            const p = PERGUNTAS.find(item => item.id_pergunta === idPergunta);
            estado.respostas[idPergunta] = {
                id_pergunta: idPergunta,
                texto_pergunta: p ? p.texto_pergunta : '',
                conforme: 1,
                valor_resposta: valor,
                criticidade: 'baixa',
                obs: '',
                fotoFile: null
            };
            sincronizarItemComModoLista(idPergunta);
            atualizarProgressoGeral();
        }

        function atualizarObsLista(idPergunta, val) {
            if (estado.respostas[idPergunta]) {
                estado.respostas[idPergunta].obs = val;
            }
        }

        function setCriticidadeLista(idPergunta, crit) {
            if (estado.respostas[idPergunta]) {
                estado.respostas[idPergunta].criticidade = crit;
            }
        }

        function aoSelecionarFotoLista(idPergunta, input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (estado.respostas[idPergunta]) {
                        estado.respostas[idPergunta].fotoFile = file;
                        estado.respostas[idPergunta].fotoPreviewSrc = e.target.result;
                    }
                    const cont = document.getElementById(`previewListaContainer_${idPergunta}`);
                    if (cont) {
                        cont.innerHTML = `<img src="${e.target.result}" class="thumb-preview w-100" style="max-height: 80px;" alt="Avaria">`;
                        cont.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        // =====================================================================
        // SINCRONIZAÇÃO EM TEMPO REAL ENTRE OS DOIS MODOS
        // =====================================================================
        function sincronizarItemComModoLista(idPergunta) {
            const card = document.getElementById(`cardLista_${idPergunta}`);
            const resp = estado.respostas[idPergunta];
            if (!card || !resp) return;

            card.classList.remove('respondido-conforme', 'respondido-nao-conforme', 'respondido-na');
            if (resp.conforme === 1) card.classList.add('respondido-conforme');
            else if (resp.conforme === 0) card.classList.add('respondido-nao-conforme');
            else card.classList.add('respondido-na');

            // Atualiza botões no card da lista
            const btnConf = card.querySelector('.btn-outline-success, .btn-success');
            const btnNConf = card.querySelector('.btn-outline-danger, .btn-danger');
            const btnNA = card.querySelector('.btn-outline-secondary, .btn-secondary');
            const gaveta = document.getElementById(`gavetaLista_${idPergunta}`);

            if (btnConf && btnNConf && btnNA) {
                btnConf.className = resp.conforme === 1 ? 'btn btn-success text-white py-2 fw-semibold' : 'btn btn-outline-success py-2 fw-semibold';
                btnNConf.className = resp.conforme === 0 ? 'btn btn-danger text-white py-2 fw-semibold' : 'btn btn-outline-danger py-2 fw-semibold';
                btnNA.className = resp.conforme === null ? 'btn btn-secondary text-white py-2' : 'btn btn-outline-secondary py-2';
            }

            if (gaveta) {
                if (resp.conforme === 0) gaveta.classList.remove('d-none');
                else gaveta.classList.add('d-none');
            }
        }

        function atualizarProgressoGeral() {
            const total = PERGUNTAS.length;
            const preenchidos = Object.keys(estado.respostas).length;
            const pct = Math.round((preenchidos / total) * 100);

            document.getElementById('barraProgresso').style.width = `${pct}%`;
            document.getElementById('badgeProgressoTexto').innerText = `Respondidos: ${preenchidos} de ${total} (${pct}%)`;
        }

        // =====================================================================
        // REVISÃO FINAL E TRANSMISSÃO DA VISTORIA
        // =====================================================================
        function abrirModalAssinatura() {
            const total = PERGUNTAS.length;
            let conformes = 0;
            let naoConformes = 0;
            const listaAvarias = document.getElementById('revListaAvarias');
            listaAvarias.innerHTML = '';

            Object.values(estado.respostas).forEach(r => {
                if (r.conforme === 1) conformes++;
                else if (r.conforme === 0) {
                    naoConformes++;
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-danger d-flex justify-content-between align-items-center';
                    li.innerHTML = `
                        <div>
                            <strong>${r.texto_pergunta}</strong>
                            <div class="text-muted small">${r.obs || 'Avaria constatada no componente.'}</div>
                        </div>
                        <span class="badge bg-danger text-uppercase">${r.criticidade || 'Média'}</span>
                    `;
                    listaAvarias.appendChild(li);
                }
            });

            document.getElementById('revTotalItens').innerText = total;
            document.getElementById('revConformes').innerText = conformes;
            document.getElementById('revNaoConformes').innerText = naoConformes;

            const boxAvarias = document.getElementById('revBoxAvarias');
            if (naoConformes > 0) boxAvarias.classList.remove('d-none');
            else boxAvarias.classList.add('d-none');

            const modalEl = document.getElementById('modalFinalizar');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            // Ajusta o tamanho dos canvas ao abrir o modal
            setTimeout(() => {
                redimensionarCanvas('canvasMotorista');
                redimensionarCanvas('canvasVistoriador');
            }, 300);
        }

        // =====================================================================
        // CANVAS DE ASSINATURA DIGITAL EM TOUCH / MOUSE
        // =====================================================================
        const canvasMap = {};

        function iniciarCanvas(id) {
            const canvas = document.getElementById(id);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            canvasMap[id] = { canvas, ctx, desenhando: false, temAssinatura: false };

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            }

            function comecar(e) {
                e.preventDefault();
                canvasMap[id].desenhando = true;
                const pos = getPos(e);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            }

            function desenhar(e) {
                if (!canvasMap[id].desenhando) return;
                e.preventDefault();
                const pos = getPos(e);
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#0f172a';
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
                canvasMap[id].temAssinatura = true;
            }

            function parar() {
                canvasMap[id].desenhando = false;
            }

            canvas.addEventListener('mousedown', comecar);
            canvas.addEventListener('mousemove', desenhar);
            canvas.addEventListener('mouseup', parar);
            canvas.addEventListener('mouseleave', parar);

            canvas.addEventListener('touchstart', comecar, { passive: false });
            canvas.addEventListener('touchmove', desenhar, { passive: false });
            canvas.addEventListener('touchend', parar);
        }

        function redimensionarCanvas(id) {
            const item = canvasMap[id];
            if (!item) return;
            const rect = item.canvas.getBoundingClientRect();
            item.canvas.width = rect.width;
            item.canvas.height = rect.height;
        }

        function limparCanvas(id) {
            const item = canvasMap[id];
            if (!item) return;
            item.ctx.clearRect(0, 0, item.canvas.width, item.canvas.height);
            item.temAssinatura = false;
        }

        // =====================================================================
        // TRANSMISSÃO VIA AJAX / FORMDATA PARA O BACKEND
        // =====================================================================
        async function transmitirVistoria() {
            const btn = document.getElementById('btnSalvarFinal');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Transmitindo...`;

            try {
                const formData = new FormData();
                formData.append('id_vistoria', ID_VISTORIA);

                // Serializa as respostas
                const arrayRespostas = Object.values(estado.respostas).map(r => ({
                    id_pergunta: r.id_pergunta,
                    texto_pergunta: r.texto_pergunta,
                    conforme: r.conforme,
                    valor_resposta: r.valor_resposta,
                    criticidade: r.criticidade || 'media',
                    observacao: r.obs || ''
                }));
                formData.append('respostas', JSON.stringify(arrayRespostas));

                // Anexa fotos capturadas
                Object.values(estado.respostas).forEach(r => {
                    if (r.fotoFile) {
                        formData.append(`foto_pergunta_${r.id_pergunta}`, r.fotoFile);
                    }
                });

                // Assinaturas em Base64
                if (canvasMap['canvasMotorista'] && canvasMap['canvasMotorista'].temAssinatura) {
                    formData.append('assinatura_motorista', canvasMap['canvasMotorista'].canvas.toDataURL());
                }
                if (canvasMap['canvasVistoriador'] && canvasMap['canvasVistoriador'].temAssinatura) {
                    formData.append('assinatura_vistoriador', canvasMap['canvasVistoriador'].canvas.toDataURL());
                }

                const response = await fetch('../backend/processar_execucao_vistoria.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.sucesso) {
                    window.location.href = result.redirect || `laudoVistoria.php?id_vistoria=${ID_VISTORIA}`;
                } else {
                    alert('Erro ao finalizar vistoria: ' + (result.mensagem || 'Ocorreu um erro desconhecido.'));
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Finalizar Vistoria e Emitir Laudo`;
                }

            } catch (err) {
                console.error(err);
                alert('Erro de comunicação com o servidor ao transmitir vistoria.');
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Finalizar Vistoria e Emitir Laudo`;
            }
        }
    </script>
</body>

</html>

