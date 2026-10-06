<?php
// backend/processar_cadastro_checklist.php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa           = strtoupper(trim($_POST['placadoveiculo'] ?? ''));
    $nome_motorista  = trim($_POST['nomedomotorista'] ?? '');
    $data_vistoria   = trim($_POST['datavistoria'] ?? '');
    $hora_vistoria   = trim($_POST['horavistoria'] ?? '');
    $km_rodado       = intval($_POST['kmrodado'] ?? 0);
    $nome_vistoriador= trim($_POST['nomevistoriador'] ?? '');
    $id_vistoriador  = $_SESSION['id_usuario'];

    if (empty($placa) || empty($nome_motorista) || empty($data_vistoria) || empty($hora_vistoria)) {
        echo "<script>
                alert('Preencha todos os campos obrigatórios da vistoria.');
                window.history.back();
              </script>";
        exit;
    }

    try {
        // 1. Busca se o veículo já está cadastrado para associar o ID
        $stmtVeiculo = $pdo->prepare("SELECT id_veiculo FROM Veiculos WHERE placa = :placa LIMIT 1");
        $stmtVeiculo->bindParam(':placa', $placa);
        $stmtVeiculo->execute();
        $veiculo = $stmtVeiculo->fetch(PDO::FETCH_ASSOC);
        $id_veiculo = $veiculo ? $veiculo['id_veiculo'] : null;

        // 2. Busca o modelo de checklist selecionado ou o primeiro ativo cadastrado
        $id_checklist_post = intval($_POST['id_checklist'] ?? 0);
        if ($id_checklist_post > 0) {
            $id_checklist = $id_checklist_post;
        } else {
            $stmtCheck = $pdo->query("SELECT id_checklist FROM Checklists WHERE status = 'ativo' ORDER BY id_checklist ASC LIMIT 1");
            $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            $id_checklist = $check ? $check['id_checklist'] : 1;
        }

        // 3. Insere a nova vistoria com status pendente
        $sql = "INSERT INTO Vistorias (id_checklist, id_veiculo, placa_veiculo, nome_motorista, data_vistoria, hora_vistoria, km_rodado, nome_vistoriador, id_vistoriador, status)
                VALUES (:id_checklist, :id_veiculo, :placa, :nome_motorista, :data_vistoria, :hora_vistoria, :km_rodado, :nome_vistoriador, :id_vistoriador, 'pendente')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_checklist', $id_checklist, PDO::PARAM_INT);
        $stmt->bindValue(':id_veiculo', $id_veiculo, $id_veiculo ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':placa', $placa);
        $stmt->bindParam(':nome_motorista', $nome_motorista);
        $stmt->bindParam(':data_vistoria', $data_vistoria);
        $stmt->bindParam(':hora_vistoria', $hora_vistoria);
        $stmt->bindParam(':km_rodado', $km_rodado, PDO::PARAM_INT);
        $stmt->bindParam(':nome_vistoriador', $nome_vistoriador);
        $stmt->bindParam(':id_vistoriador', $id_vistoriador, PDO::PARAM_INT);
        $stmt->execute();

        $id_vistoria = $pdo->lastInsertId();
        $_SESSION['id_vistoria_ativa'] = $id_vistoria;

        // Redireciona para a execução da vistoria (Modo Híbrido: Campo ou Lista)
        header("Location: ../frontend/vistoria.php?id_vistoria=" . $id_vistoria);
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao iniciar vistoria: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/cadcheck.html");
    exit;
}
?>

