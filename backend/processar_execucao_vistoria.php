<?php
// backend/processar_execucao_vistoria.php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

try {
    $id_vistoria = intval($_POST['id_vistoria'] ?? 0);
    if ($id_vistoria <= 0) {
        throw new Exception('ID de vistoria inválido ou não informado.');
    }

    // 1. Busca dados da vistoria existente
    $stmtV = $pdo->prepare("SELECT v.*, ve.marca_modelo, ve.id_veiculo AS veiculo_id 
                            FROM Vistorias v 
                            LEFT JOIN Veiculos ve ON v.id_veiculo = ve.id_veiculo 
                            WHERE v.id_vistoria = :id LIMIT 1");
    $stmtV->execute([':id' => $id_vistoria]);
    $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

    if (!$vistoria) {
        throw new Exception('Vistoria não encontrada no sistema.');
    }

    // 2. Processa as respostas dos itens do checklist
    // As respostas podem vir em JSON puro ou array serializado
    $rawRespostas = $_POST['respostas'] ?? '[]';
    $respostas = is_string($rawRespostas) ? json_decode($rawRespostas, true) : $rawRespostas;

    if (!is_array($respostas)) {
        $respostas = [];
    }

    $uploadDir = __DIR__ . '/uploads/evidencias/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $temNaoConformidade = false;
    $temFalhaCritica = false;
    $descricoesAvarias = [];

    // Prepara inserção em RespostasVistoria
    $stmtDeleteResp = $pdo->prepare("DELETE FROM RespostasVistoria WHERE id_vistoria = :id");
    $stmtDeleteResp->execute([':id' => $id_vistoria]);

    $stmtInsertResp = $pdo->prepare("INSERT INTO RespostasVistoria 
        (id_vistoria, id_pergunta, id_subpergunta, valor_resposta, conforme) 
        VALUES (:id_vistoria, :id_pergunta, :id_subpergunta, :valor_resposta, :conforme)");

    $stmtInsertEvidencia = $pdo->prepare("INSERT INTO EvidenciasVistoria 
        (id_vistoria, tipo_evidencia, caminho_arquivo, nome_original) 
        VALUES (:id_vistoria, 'foto', :caminho, :nome_original)");

    foreach ($respostas as $resp) {
        $idPergunta = intval($resp['id_pergunta'] ?? 0);
        if ($idPergunta <= 0) continue;

        $valorResposta = trim($resp['valor_resposta'] ?? '');
        $conforme = isset($resp['conforme']) ? ($resp['conforme'] === null ? null : (intval($resp['conforme']) ? 1 : 0)) : null;
        $idSubpergunta = !empty($resp['id_subpergunta']) ? intval($resp['id_subpergunta']) : null;
        $criticidade = strtolower(trim($resp['criticidade'] ?? 'media'));
        $obs = trim($resp['observacao'] ?? '');

        if ($conforme === 0) {
            $temNaoConformidade = true;
            if ($criticidade === 'critica' || $criticidade === 'alta') {
                $temFalhaCritica = true;
            }
            $textoAvaria = "• Pergunta #" . $idPergunta;
            if (!empty($resp['texto_pergunta'])) {
                $textoAvaria .= " (" . trim($resp['texto_pergunta']) . ")";
            }
            if (!empty($obs)) {
                $textoAvaria .= ": " . $obs;
            }
            $textoAvaria .= " [Criticidade: " . ucfirst($criticidade) . "]";
            $descricoesAvarias[] = $textoAvaria;
        }

        $stmtInsertResp->execute([
            ':id_vistoria'     => $id_vistoria,
            ':id_pergunta'     => $idPergunta,
            ':id_subpergunta'  => $idSubpergunta,
            ':valor_resposta'  => $valorResposta ?: ($conforme === 1 ? 'Conforme' : ($conforme === 0 ? 'Não Conforme' : 'N/A')),
            ':conforme'        => $conforme
        ]);
    }

    // 3. Processa uploads de fotos de evidência
    // Procura por arquivos nos formatos: $_FILES['foto_pergunta_{id}'] ou $_FILES['fotos_evidencias']
    if (!empty($_FILES)) {
        foreach ($_FILES as $key => $fileInfo) {
            if (is_array($fileInfo['name'])) {
                // Múltiplos arquivos
                for ($i = 0; $i < count($fileInfo['name']); $i++) {
                    if ($fileInfo['error'][$i] === UPLOAD_ERR_OK) {
                        $nomeOriginal = basename($fileInfo['name'][$i]);
                        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            $novoNome = uniqid('evid_' . $id_vistoria . '_', true) . '.' . $ext;
                            if (move_uploaded_file($fileInfo['tmp_name'][$i], $uploadDir . $novoNome)) {
                                $stmtInsertEvidencia->execute([
                                    ':id_vistoria'     => $id_vistoria,
                                    ':caminho'         => 'uploads/evidencias/' . $novoNome,
                                    ':nome_original'   => $nomeOriginal
                                ]);
                            }
                        }
                    }
                }
            } else {
                // Arquivo individual
                if ($fileInfo['error'] === UPLOAD_ERR_OK) {
                    $nomeOriginal = basename($fileInfo['name']);
                    $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $novoNome = uniqid('evid_' . $id_vistoria . '_', true) . '.' . $ext;
                        if (move_uploaded_file($fileInfo['tmp_name'], $uploadDir . $novoNome)) {
                            $stmtInsertEvidencia->execute([
                                ':id_vistoria'     => $id_vistoria,
                                ':caminho'         => 'uploads/evidencias/' . $novoNome,
                                ':nome_original'   => $nomeOriginal
                            ]);
                        }
                    }
                }
            }
        }
    }

    // 4. Assinaturas Digitais em Base64
    $assMotorista   = trim($_POST['assinatura_motorista'] ?? '');
    $assVistoriador = trim($_POST['assinatura_vistoriador'] ?? '');

    // Se não foram enviadas, preserva as que já estavam no banco
    if (empty($assMotorista)) {
        $assMotorista = $vistoria['assinatura_motorista'];
    }
    if (empty($assVistoriador)) {
        $assVistoriador = $vistoria['assinatura_vistoriador'];
    }

    // 5. Determinação do Status Final da Vistoria
    if ($temFalhaCritica) {
        $statusFinal = 'rejeitado';
    } elseif ($temNaoConformidade) {
        $statusFinal = 'aprovado_com_restricoes';
    } else {
        $statusFinal = 'aprovado';
    }

    $descricaoConsolidada = !empty($descricoesAvarias) 
        ? implode("\n", $descricoesAvarias) 
        : ($vistoria['descricao_nao_conformidade'] ?? '');

    // Atualiza a tabela Vistorias
    $stmtUpdateV = $pdo->prepare("UPDATE Vistorias 
        SET status = :status,
            descricao_nao_conformidade = :desc_nao_conf,
            assinatura_motorista = :ass_mot,
            assinatura_vistoriador = :ass_vist
        WHERE id_vistoria = :id");
    
    $stmtUpdateV->execute([
        ':status'        => $statusFinal,
        ':desc_nao_conf' => $descricaoConsolidada,
        ':ass_mot'       => $assMotorista,
        ':ass_vist'      => $assVistoriador,
        ':id'            => $id_vistoria
    ]);

    // 6. Integração com o Módulo de Triagem e Ocorrências (Etapa 2)
    // Se houver não conformidade, gera ocorrência na tabela Ocorrencias
    if ($temNaoConformidade || $statusFinal === 'aprovado_com_restricoes' || $statusFinal === 'rejeitado') {
        $checkOc = $pdo->prepare("SELECT COUNT(*) FROM Ocorrencias WHERE id_vistoria = :id");
        $checkOc->execute([':id' => $id_vistoria]);
        if ($checkOc->fetchColumn() == 0) {
            $codOc = 'OC-2026-' . str_pad($id_vistoria, 3, '0', STR_PAD_LEFT);
            
            // Pega a primeira foto enviada como evidência
            $stmtFoto = $pdo->prepare("SELECT caminho_arquivo FROM EvidenciasVistoria WHERE id_vistoria = :id LIMIT 1");
            $stmtFoto->execute([':id' => $id_vistoria]);
            $fotoRow = $stmtFoto->fetch(PDO::FETCH_ASSOC);
            $fotoEvidencia = $fotoRow ? $fotoRow['caminho_arquivo'] : 'uploads/evidencias/exemplo_avaria.png';

            $criticidadeGeral = $temFalhaCritica ? 'alta' : 'media';
            $statusVeiculo = $temFalhaCritica ? 'retido_oficina' : 'retido_oficina';
            $acaoRecomendada = $temFalhaCritica 
                ? 'Veículo impedido de circular. Encaminhar imediatamente para guincho/oficina credenciada.'
                : 'Aprovação com restrição. Agendar correção mecânica/elétrica nas próximas 48 horas.';

            $insertOc = $pdo->prepare("INSERT INTO Ocorrencias 
                (codigo_ocorrencia, id_vistoria, id_veiculo, placa_veiculo, modelo_veiculo, subsistema, descricao_falha, criticidade, status, status_veiculo, acao_recomendada, foto_evidencia, local_patio, fiscal_responsavel)
                VALUES (:cod, :idv, :idvei, :placa, :mod, 'Inspeção de Campo', :falha, :crit, 'aberta', :stat_vei, :acao, :foto, 'Pátio Operacional', :fiscal)");
            
            $insertOc->execute([
                ':cod'      => $codOc,
                ':idv'      => $id_vistoria,
                ':idvei'    => $vistoria['id_veiculo'],
                ':placa'    => $vistoria['placa_veiculo'],
                ':mod'      => $vistoria['marca_modelo'] ?? 'Veículo Operacional',
                ':falha'    => $descricaoConsolidada ?: 'Avarias detectadas na vistoria veicular.',
                ':crit'     => $criticidadeGeral,
                ':stat_vei' => $statusVeiculo,
                ':acao'     => $acaoRecomendada,
                ':foto'     => $fotoEvidencia,
                ':fiscal'   => $_SESSION['nome_usuario'] ?? $vistoria['nome_vistoriador'] ?? 'Inspetor'
            ]);

            // Se for falha crítica, retém o veículo
            if ($vistoria['id_veiculo']) {
                $stmtVei = $pdo->prepare("UPDATE Veiculos SET status = 'manutencao' WHERE id_veiculo = :idv");
                $stmtVei->execute([':idv' => $vistoria['id_veiculo']]);
            }
        }
    }

    echo json_encode([
        'sucesso'     => true,
        'mensagem'    => 'Vistoria e respostas registradas com sucesso!',
        'id_vistoria' => $id_vistoria,
        'status'      => $statusFinal,
        'redirect'    => '../frontend/laudoVistoria.php?id_vistoria=' . $id_vistoria
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso'  => false,
        'mensagem' => 'Erro ao processar vistoria: ' . $e->getMessage()
    ]);
}

