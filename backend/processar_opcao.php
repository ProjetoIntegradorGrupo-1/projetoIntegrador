<?php
// backend/processar_opcao.php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

$perfil = $_SESSION['perfil_usuario'] ?? 'motorista';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['opcao'])) {
    $opcao = $_POST['opcao'];

    // MATRIZ DE AUTORIZAÇÃO RBAC
    $permissoes = [
        'dashboard'           => ['gestor', 'supervisor'],
        'ocorrencias'         => ['gestor', 'supervisor'],
        'gestao_checklists'   => ['gestor', 'supervisor'],
        'cadastrar_usuario'   => ['gestor', 'supervisor'],
        'cadastrar_veiculo'   => ['gestor', 'supervisor'],
        'criar_checklist'     => ['gestor', 'supervisor'],
        'editar_usuario'      => ['gestor'],
        'editar_veiculo'      => ['gestor', 'supervisor'],
        'editar_checklist'    => ['gestor', 'supervisor'],
        'excluir_usuario'     => ['gestor'],
        'excluir_veiculo'     => ['gestor'],
        'excluir_checklist'   => ['gestor'],
        'preencher_checklist' => ['gestor', 'supervisor', 'motorista', 'cliente']
    ];

    // Se o usuário tentar acessar recurso não permitido para o seu papel
    if (isset($permissoes[$opcao]) && !in_array($perfil, $permissoes[$opcao])) {
        header("Location: ../frontend/oqfazer.php?erro=acesso_negado");
        exit;
    }

    // Mapeia a escolha para a página correspondente
    switch ($opcao) {
        case 'dashboard':
            header("Location: ../frontend/dashboard.php");
            break;
        case 'ocorrencias':
            header("Location: ../frontend/ocorrencias.php");
            break;
        case 'gestao_checklists':
            header("Location: ../frontend/gestaoChecklists.php");
            break;
        case 'cadastrar_usuario':
            header("Location: ../frontend/caduser.php");
            break;
        case 'cadastrar_veiculo':
            header("Location: ../frontend/cadcar.php");
            break;
        case 'criar_checklist':
            header("Location: ../frontend/criarChecklist.php");
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
            header("Location: ../frontend/cadcheck.php");
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