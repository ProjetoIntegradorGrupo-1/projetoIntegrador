<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';

// Garante que ninguém entra aqui digitando o link direto
exigirLogin();

// Se não veio de um formulário POST, devolve para o menu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../php/oqfazer.php');
    exit;
}

$opcao = $_POST['opcao'] ?? '';
$perfil = $_SESSION['perfil_usuario'] ?? '';

// Roteamento baseado na opção escolhida e no perfil do usuário
switch ($opcao) {
    case 'cadastrar_usuario':
        if ($perfil === 'administrador') {
            header('Location: ../../Frontend/caduser.html');
            exit;
        }
        break;

    case 'cadastrar_veiculo':
        if ($perfil === 'administrador' || $perfil === 'gestor') {
            header('Location: ../../Frontend/cadcar.html');
            exit;
        }
        break;

    case 'criar_checklist':
        if ($perfil === 'administrador' || $perfil === 'gestor') {
            header('Location: ../../Frontend/criarChecklist.html');
            exit;
        }
        break;

    case 'realizar_inspecao':
        // Redireciona para a página que carrega as listas da base de dados
        header('Location: ../inspecoes/nova_inspecao.php');
        exit;
    case 'ver_historico':
        header('Location: ../inspecoes/historico.php');
        exit;

    case 'ver_dashboard':
        if ($perfil === 'administrador' || $perfil === 'gestor') {
            header('Location: ../dashboard/index.php');
            exit;
        }
        break;
    default:
        header('Location: ../php/oqfazer.php?erro=opcao_invalida');
        exit;
}

// Se o código chegou até aqui, o usuário tentou uma opção para a qual não tem permissão
header('Location: ../php/oqfazer.php?erro=sem_permissao');
exit;