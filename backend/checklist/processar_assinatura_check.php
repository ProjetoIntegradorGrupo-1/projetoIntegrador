<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Garante que o usuário está autenticado (ajuste os perfis se necessário)
verificarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/assinaturaChecklist.php');
    exit;
}

// Captura os dados enviados pelo front-end
$id_checklist = filter_input(INPUT_POST, 'id_checklist', FILTER_VALIDATE_INT);
$assinatura_base64 = $_POST['assinatura'] ?? ''; // Dado vindo do canvas em base64

if (!$id_checklist || empty($assinatura_base64)) {
    header('Location: ../../Frontend/assinaturaChecklist.php?erro=dados_invalidos');
    exit;
}

try {
    // Opção 1: Salvar a string Base64 diretamente no banco (certifique-se de que a coluna aceita TEXT/LONGTEXT)
    // Opção 2 (Mais limpa): Converter o Base64 em arquivo de imagem PNG e salvar o caminho no banco.
    
    // Vamos processar e salvar a imagem em uma pasta de uploads para manter o banco leve:
    $pasta_uploads = __DIR__ . '/../uploads/assinaturas/';
    if (!file_exists($pasta_uploads)) {
        mkdir($pasta_uploads, 0755, true);
    }

    // Remove o cabeçalho do base64 (ex: "data:image/png;base64,")
    $dados_imagem = explode(',', $assinatura_base64);
    if (count($dados_imagem) < 2) {
        header('Location: ../../Frontend/assinaturaChecklist.php?erro=formato_assinatura_invalido');
        exit;
    }
    
    $imagem_binaria = base64_decode($dados_imagem[1]);
    $nome_arquivo = 'assinatura_' . $id_checklist . '_' . time() . '.png';
    $caminho_completo = $pasta_uploads . $nome_arquivo;
    $caminho_relativo = 'uploads/assinaturas/' . $nome_arquivo;

    // Salva o arquivo fisicamente no servidor
    if (file_put_contents($caminho_completo, $imagem_binaria) === false) {
        throw new Exception("Falha ao salvar o arquivo de assinatura no servidor.");
    }

    // Atualiza o registro no banco de dados vinculando o caminho da assinatura e alterando o status para finalizado/concluído
    $sql = "UPDATE Checklists SET assinatura = ?, status = 'concluido' WHERE id_checklist = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$caminho_relativo, $id_checklist]);

    header('Location: ../php/oqfazer.php?sucesso=checklist_assinado');
    exit;

} catch (Exception $e) {
    error_log("Erro ao salvar assinatura do checklist: " . $e->getMessage());
    header('Location: ../../Frontend/assinaturaChecklist.php?erro=falha_banco');
    exit;
}