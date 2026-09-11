<?php
// processar_opcao.php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['opcao'])) {
    $opcao = $_POST['opcao'];

    // Mapeia a escolha para a página correspondente
    switch ($opcao) {
        case 'cadastrar_usuario':
            header("Location: caduser.html"); // Ajuste o nome se necessário
            break;
        case 'cadastrar_veiculo':
            header("Location: cadcar.html");
            break;
        case 'criar_checklist':
            header("Location: criarChecklist.html");
            break;
        case 'editar_usuario':
            header("Location: editarUsuario.html");
            break;
        case 'editar_veiculo':
            header("Location: editarVeiculo.html");
            break;
        case 'editar_checklist':
            header("Location: editarChecklist.html");
            break;
        case 'excluir_usuario':
            header("Location: excluirUsuario.html");
            break;
        case 'excluir_veiculo':
            header("Location: excluirVeiculo.html");
            break;
        case 'excluir_checklist':
            header("Location: excluirChecklist.html");
            break;
        case 'preencher_checklist':
            header("Location: preencherChecklist.html");
            break;
        default:
            header("Location: oqfazer.php?erro=opcao_invalida");
            break;
    }
    exit;
} else {
    header("Location: oqfazer.php");
    exit;
}
?>