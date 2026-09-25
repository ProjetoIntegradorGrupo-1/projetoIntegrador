<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Apenas administradores e gestores podem criar guiões de inspeção
exigirPerfil(['administrador', 'gestor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/criarChecklist.html');
    exit;
}

$titulo = trim($_POST['titulo'] ?? '');
$categoria = trim($_POST['categoria'] ?? 'Geral');
$perguntas = $_POST['perguntas'] ?? [];
$id_criador = $_SESSION['id_usuario'];

if (empty($titulo) || empty($perguntas)) {
    header('Location: ../../Frontend/criarChecklist.html?erro=dados_invalidos');
    exit;
}

try {
    // Inicia a transação. Se algo falhar, o rollback anula a inserção incompleta.
    $pdo->beginTransaction();

    // 1. Inserir o modelo mestre na tabela Checklists
    $sqlChecklist = "INSERT INTO Checklists (id_criador, titulo, categoria, status) VALUES (?, ?, ?, 'ativo')";
    $stmtCheck = $pdo->prepare($sqlChecklist);
    $stmtCheck->execute([$id_criador, $titulo, $categoria]);
    
    $id_checklist = $pdo->lastInsertId();

    // 2. Preparar a instrução para inserir as perguntas
    $sqlPergunta = "INSERT INTO Perguntas (id_checklist, texto_pergunta, tipo_resposta, obrigatorio, ordem) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmtPergunta = $pdo->prepare($sqlPergunta);

    // 3. Iterar sobre o array recebido do Frontend e gravar cada pergunta associada ao ID do checklist
    $ordem = 1;
    foreach ($perguntas as $p) {
        
        $texto = trim($p['texto'] ?? '');
        $tipo = trim($p['tipo'] ?? 'sim_nao');
        $obrigatorio = isset($p['obrigatorio']) ? 1 : 0;

        if (!empty($texto)) {
            $stmtPergunta->execute([$id_checklist, $texto, $tipo, $obrigatorio, $ordem]);
            $ordem++;
        }
        
    }

    $pdo->commit();
    header('Location: ../php/oqfazer.php?sucesso=checklist_criado');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao criar checklist dinâmico: " . $e->getMessage());
    header('Location: ../../Frontend/criarChecklist.html?erro=falha_banco');
    exit;
}