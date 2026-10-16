<?php
require_once __DIR__ . '/../backend/conexao.php';

try {
    $pdo->beginTransaction();

    // 1. Usuarios
    $stmt = $pdo->prepare("UPDATE `Usuarios` SET `cidade` = :cidade WHERE `id_usuario` = 1");
    $stmt->execute([':cidade' => 'Vitória']);

    $stmt = $pdo->prepare("UPDATE `Usuarios` SET `nome` = :nome WHERE `id_usuario` = 3");
    $stmt->execute([':nome' => 'João Silva']);

    // 2. Checklists
    $stmt = $pdo->prepare("UPDATE `Checklists` SET `titulo` = :titulo, `categoria` = :categoria, `descricao` = :desc WHERE `id_checklist` = 1");
    $stmt->execute([
        ':titulo' => 'Vistoria Diária de Segurança',
        ':categoria' => 'Segurança Geral',
        ':desc' => 'Checklist padrão diário pré-operação da frota'
    ]);

    // 3. Perguntas
    $perguntas = [
        1 => 'O veículo possui alguma avaria visível na lataria ou pintura?',
        2 => 'Os faróis, lanternas e setas estão operando corretamente?',
        3 => 'Nível do óleo e fluido de arrefecimento estão dentro do padrão?',
        4 => 'Os cintos de segurança e itens obrigatórios (estepe, macaco) estão disponíveis?',
        5 => 'Informe o valor atual do hodômetro:'
    ];
    $stmt = $pdo->prepare("UPDATE `Perguntas` SET `texto_pergunta` = :txt WHERE `id_pergunta` = :id");
    foreach ($perguntas as $id => $txt) {
        $stmt->execute([':txt' => $txt, ':id' => $id]);
    }

    // 4. Subperguntas
    $stmt = $pdo->prepare("UPDATE `Subperguntas` SET `texto_pergunta` = :txt WHERE `id_subpergunta` = 1");
    $stmt->execute([':txt' => 'Especifique o tipo de avaria e a localização exata na lataria:']);

    $pdo->commit();
    echo "Sucesso: Todos os dados foram corrigidos com codificação UTF-8 pura!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erro ao atualizar: " . $e->getMessage() . "\n";
}

