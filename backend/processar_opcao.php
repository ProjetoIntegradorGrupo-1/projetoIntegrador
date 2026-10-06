<?php
// processar_opcao.php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['opcao'])) {
    $opcao = $_POST['opcao'];

    // Mapeia a escolha para a página correspondente
    switch ($opcao) {
        case 'dashboard':
            header("Location: ../frontend/dashboard.php");
            break;
        case 'cadastrar_usuario':
            header("Location: ../frontend/caduser.html");
            break;
        case 'cadastrar_veiculo':
            header("Location: ../frontend/cadcar.html");
            break;
        case 'criar_checklist':
            header("Location: ../frontend/criarChecklist.html");
            break;
        case 'editar_usuario':
            header("Location: ../frontend/editarUsuario.php");
            break;
        case 'editar_veiculo':
            header("Location: ../frontend/editarVeiculo.php");
            break;
        case 'editar_checklist':
            header("Location: ../frontend/editarChecklist.php");
            break;
        case 'excluir_usuario':
            header("Location: ../frontend/excluirUsuario.php");
            break;
        case 'excluir_veiculo':
            header("Location: ../frontend/excluirVeiculo.php");
            break;
        case 'excluir_checklist':
            header("Location: ../frontend/excluirChecklist.php");
            break;
        case 'preencher_checklist':
            header("Location: ../frontend/cadcheck.html");
            break;
        default:
            header("Location: ../frontend/oqfazer.php?erro=opcao_invalida");
            break;
    }
    exit;
} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>