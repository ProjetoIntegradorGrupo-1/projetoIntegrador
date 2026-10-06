<?php
// salvar_checklist_dinamico.php
session_start();

// Ajuste o nível de pastas conforme o local onde este arquivo está salvo
require_once 'conexao.php'; 

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $id_criador = $_SESSION['id_usuario'];
    $titulo     = trim($_POST['titulo'] ?? '');
    
    // O array de perguntas estruturado no HTML chega aqui
    $perguntas  = $_POST['perguntas'] ?? []; 

    // Validação básica
    if (empty($titulo) || empty($perguntas)) {
        echo "<script>alert('O título e ao menos uma pergunta são obrigatórios.'); window.history.back();</script>";
        exit;
    }

    try {
        // Inicia uma transação: ou salva tudo (checklist + perguntas), ou não salva nada
        $pdo->beginTransaction();

        // 1. Grava o cabeçalho do Checklist (A categoria não existe mais aqui)
        $sqlChecklist = "INSERT INTO Checklists (id_criador, titulo) VALUES (:id_criador, :titulo)";
        $stmtChecklist = $pdo->prepare($sqlChecklist);
        $stmtChecklist->bindParam(':id_criador', $id_criador, PDO::PARAM_INT);
        $stmtChecklist->bindParam(':titulo', $titulo);
        $stmtChecklist->execute();

        // Resgata o ID gerado pelo banco para atrelar as perguntas a este checklist
        $id_checklist = $pdo->lastInsertId();

        // 2. Prepara a gravação das perguntas (Agora com a coluna categoria incluída)
        $sqlPergunta = "INSERT INTO Perguntas (id_checklist, texto_pergunta, tipo_resposta, obrigatorio, categoria) 
                        VALUES (:id_checklist, :texto, :tipo, :obrigatorio, :categoria)";
        $stmtPergunta = $pdo->prepare($sqlPergunta);

        // Percorre todas as perguntas preenchidas no HTML e salva uma a uma
        foreach ($perguntas as $pergunta) {
            
            $texto = trim($pergunta['texto'] ?? '');
            $tipo = trim($pergunta['tipo'] ?? 'sim_nao');
            $categoria = trim($pergunta['categoria'] ?? '');
            
            // Tratamento para checkbox: se foi marcado ele existe no array, senão é falso (0)
            $obrigatorio = isset($pergunta['obrigatorio']) ? 1 : 0;

            // Só salva se a pergunta tiver texto e categoria preenchidos
            if (!empty($texto) && !empty($categoria)) {
                $stmtPergunta->bindParam(':id_checklist', $id_checklist, PDO::PARAM_INT);
                $stmtPergunta->bindParam(':texto', $texto);
                $stmtPergunta->bindParam(':tipo', $tipo);
                $stmtPergunta->bindParam(':obrigatorio', $obrigatorio, PDO::PARAM_INT);
                $stmtPergunta->bindParam(':categoria', $categoria);
                
                $stmtPergunta->execute();
            }
        }

        // Se tudo deu certo, confirma a gravação no banco
        $pdo->commit();

        // Redireciona para o menu com aviso de sucesso
        header("Location: ../frontend/oqfazer.php?sucesso=checklist_criado");
        exit;

    } catch (PDOException $e) {
        // Se der erro no meio do processo, desfaz tudo para não deixar dados quebrados
        $pdo->rollBack();
        echo "<script>
                alert('Erro ao salvar o checklist e as perguntas: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    // Se tentar acessar o arquivo diretamente via URL
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>