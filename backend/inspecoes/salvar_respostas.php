<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../php/oqfazer.php');
    exit;
}

$id_inspecao = $_POST['id_inspecao'] ?? null;
$respostas = $_POST['respostas'] ?? [];
$observacao_geral = trim($_POST['observacao_geral'] ?? '');
$acao = $_POST['acao'] ?? 'salvar_rascunho';

if (!$id_inspecao) {
    die("ID da inspeção não fornecido.");
}

try {
    $pdo->beginTransaction();

    // 1. Guardar cada resposta iterando sobre o array
    $sqlResposta = "INSERT INTO Respostas (id_inspecao, id_pergunta, valor_resposta, observacao) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE valor_resposta = VALUES(valor_resposta), observacao = VALUES(observacao)";

    $stmtResp = $pdo->prepare($sqlResposta);
    // Variável preparada fora do loop para otimização
    $stmtGetId = $pdo->prepare("SELECT id_resposta FROM Respostas WHERE id_inspecao = ? AND id_pergunta = ?");

    foreach ($respostas as $id_pergunta => $dados) {
        $valor = trim($dados['valor'] ?? '');
        $obs = trim($dados['observacao'] ?? '');
        $ocorrencia = trim($dados['ocorrencia'] ?? '');

        if (!empty($valor)) {
            // 1. Guardar a resposta principal
            $stmtResp->execute([$id_inspecao, $id_pergunta, $valor, $obs]);

            // Recuperar o ID exato da resposta para fazer as ligações
            $stmtGetId->execute([$id_inspecao, $id_pergunta]);
            $id_resposta = $stmtGetId->fetchColumn();

            // 2. Registar Ocorrência (se o campo foi preenchido)
            if (!empty($ocorrencia)) {
                $sqlOcorrencia = "INSERT INTO Ocorrencias (id_inspecao, id_resposta, descricao, status) VALUES (?, ?, ?, 'aberta')";
                $pdo->prepare($sqlOcorrencia)->execute([$id_inspecao, $id_resposta, $ocorrencia]);
            }

            // 3. Processar Upload de Evidências (Múltiplos Ficheiros)
            $campo_ficheiro = "evidencias_" . $id_pergunta;

            if (isset($_FILES[$campo_ficheiro]) && $_FILES[$campo_ficheiro]['name'][0] !== '') {
                $total_ficheiros = count($_FILES[$campo_ficheiro]['name']);

                // Cria a pasta de destino se não existir
                $diretorio_uploads = __DIR__ . '/../../uploads/';
                if (!is_dir($diretorio_uploads)) {
                    mkdir($diretorio_uploads, 0777, true);
                }

                for ($i = 0; $i < $total_ficheiros; $i++) {
                    $nome_original = basename($_FILES[$campo_ficheiro]['name'][$i]);
                    // Gera um nome único para evitar sobreposição de ficheiros com o mesmo nome
                    $nome_seguro = uniqid() . '_' . $nome_original;
                    $caminho_completo = $diretorio_uploads . $nome_seguro;

                    if (move_uploaded_file($_FILES[$campo_ficheiro]['tmp_name'][$i], $caminho_completo)) {
                        $sqlEvidencia = "INSERT INTO Evidencias (id_resposta, caminho_arquivo, nome_arquivo) VALUES (?, ?, ?)";
                        $pdo->prepare($sqlEvidencia)->execute([$id_resposta, 'uploads/' . $nome_seguro, $nome_original]);
                    }
                }
            }
        }
    }
}