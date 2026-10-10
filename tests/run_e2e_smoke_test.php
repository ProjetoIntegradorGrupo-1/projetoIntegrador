<?php
/**
 * Teste Funcional Ponta a Ponta (E2E Smoke Test) - Sistema Axion
 * Valida a jornada completa através de requisições HTTP e verificação em banco MySQL
 */

// Detecta automaticamente se usa Apache do XAMPP (porta 80) ou servidor embutido (8080)
$baseUrl = 'http://localhost/projetoIntegrador';
$chTest = @curl_init('http://127.0.0.1:8080');
if ($chTest) {
    curl_setopt($chTest, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chTest, CURLOPT_TIMEOUT, 1);
    if (@curl_exec($chTest) !== false) {
        $baseUrl = 'http://127.0.0.1:8080';
    }
    @curl_close($chTest);
}
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

echo "====================================================================\n";
echo "INICIANDO TESTES FUNCIONAIS PONTA A PONTA (SMOKE TEST) - AXION\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($description, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "[SUCESSO] $description\n";
        if ($details) echo "          Detalhe: $details\n";
        $passCount++;
    } else {
        echo "[FALHA]   $description\n";
        if ($details) echo "          Erro: $details\n";
        $failCount++;
    }
}

// Helper para cURL
function request($url, $method = 'GET', $data = [], $isMultipart = false) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isMultipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
}

// Conexão direta ao banco para checagem cruzada
require_once __DIR__ . '/../backend/conexao.php';

// -------------------------------------------------------------
// TESTE 1: Roteador Raiz index.php
// -------------------------------------------------------------
echo "1. Testando Roteador de Entrada (index.php)...\n";
$res1 = request("$baseUrl/index.php");
$redirectedToIndexHtml = ($res1['code'] === 302 && strpos($res1['headers'], 'frontend/index.html') !== false);
assertTest("Roteador raiz redireciona para frontend/index.html (HTTP 302)", $redirectedToIndexHtml, "HTTP {$res1['code']}");

// -------------------------------------------------------------
// TESTE 2: Tentativa de Login Inválido
// -------------------------------------------------------------
echo "\n2. Testando Autenticação com Credencial Inválida...\n";
$res2 = request("$baseUrl/backend/processar_login.php", 'POST', [
    'txtuser' => 'admin@axion.com',
    'txtsenha' => 'senha_errada_123'
]);
$loginFalhou = ($res2['code'] === 302 && strpos($res2['headers'], 'erro=credenciais_invalidas') !== false);
assertTest("Login com senha incorreta é barrado e redireciona com erro", $loginFalhou, "Headers: " . trim($res2['headers']));

// -------------------------------------------------------------
// TESTE 3: Login Válido e Abertura de Sessão
// -------------------------------------------------------------
echo "\n3. Testando Autenticação Válida (admin@axion.com)...\n";
$res3 = request("$baseUrl/backend/processar_login.php", 'POST', [
    'txtuser' => 'admin@axion.com',
    'txtsenha' => 'admin123'
]);
$loginSucesso = ($res3['code'] === 302 && strpos($res3['headers'], 'frontend/oqfazer.php') !== false);
assertTest("Login bem-sucedido autentica hash BCrypt e redireciona para o menu", $loginSucesso, "HTTP {$res3['code']}");

// -------------------------------------------------------------
// TESTE 4: Acesso à Página Protegida com Sessão Ativa
// -------------------------------------------------------------
echo "\n4. Verificando Acesso à Página Protegida com Sessão...\n";
$res4 = request("$baseUrl/frontend/oqfazer.php");
$acessoProtegido = ($res4['code'] === 200 && strpos($res4['body'], 'Administrador Geral') !== false);
assertTest("Menu 'oqfazer.php' reconhece o usuário autenticado na sessão", $acessoProtegido);

// -------------------------------------------------------------
// TESTE 5: Criação Dinâmica de Modelo de Checklist
// -------------------------------------------------------------
echo "\n5. Testando Criação de Checklist com Múltiplas Perguntas...\n";
$checklistTitulo = "Vistoria E2E Teste Automatizado " . time();
$dadosChecklist = [
    'titulo' => $checklistTitulo,
    'perguntas' => [
        1 => ['texto' => 'Nível do óleo está no limite seguro?', 'tipo' => 'sim_nao', 'categoria' => 'Mecânica', 'obrigatorio' => '1'],
        2 => ['texto' => 'Faróis dianteiros acendendo normalmente?', 'tipo' => 'sim_nao', 'categoria' => 'Elétrica', 'obrigatorio' => '1'],
        3 => ['texto' => 'Informe a pressão dos pneus dianteiros (PSI)', 'tipo' => 'numero', 'categoria' => 'Pneus', 'obrigatorio' => '1']
    ]
];
$res5 = request("$baseUrl/backend/criar_checklist_dinamico.php", 'POST', $dadosChecklist);
$stmtC = $pdo->prepare("SELECT id_checklist, titulo FROM Checklists WHERE titulo = :t LIMIT 1");
$stmtC->execute([':t' => $checklistTitulo]);
$checklistCriado = $stmtC->fetch(PDO::FETCH_ASSOC);

assertTest("Modelo de Checklist criado no banco axion_db", !empty($checklistCriado), "ID: " . ($checklistCriado['id_checklist'] ?? 'N/A'));

if ($checklistCriado) {
    $stmtP = $pdo->prepare("SELECT COUNT(*) FROM Perguntas WHERE id_checklist = :id");
    $stmtP->execute([':id' => $checklistCriado['id_checklist']]);
    $totalPerg = $stmtP->fetchColumn();
    assertTest("Perguntas associadas vinculadas na tabela Perguntas (3 esperadas)", $totalPerg == 3, "Total: $totalPerg");
}

// -------------------------------------------------------------
// TESTE 6: Abertura de Vistoria (Passo 1 do Fluxo)
// -------------------------------------------------------------
echo "\n6. Testando Abertura de Vistoria Veicular...\n";
$dadosVistoria = [
    'placadoveiculo'   => 'ABC-1D23',
    'nomedomotorista'  => 'João Silva Motorista',
    'nomevistoriador'  => 'Administrador Geral',
    'kmrodado'         => 45500,
    'datavistoria'     => date('Y-m-d'),
    'horavistoria'     => date('H:i')
];
$res6 = request("$baseUrl/backend/processar_cadastro_checklist.php", 'POST', $dadosVistoria);
$vistoriaAberta = ($res6['code'] === 302 && (strpos($res6['headers'], 'vistoria.php') !== false || strpos($res6['headers'], 'detalhesNaoConformidade.html') !== false));
assertTest("Abertura de vistoria aceita dados e redireciona para vistoria.php", $vistoriaAberta);

// Verifica última vistoria no banco
$stmtV = $pdo->query("SELECT id_vistoria, status, placa_veiculo FROM Vistorias ORDER BY id_vistoria DESC LIMIT 1");
$ultimaVistoria = $stmtV->fetch(PDO::FETCH_ASSOC);
$idVistoria = $ultimaVistoria['id_vistoria'] ?? 0;
assertTest("Registro criado em Vistorias com status 'pendente'", ($ultimaVistoria['status'] ?? '') === 'pendente', "ID: $idVistoria");

// -------------------------------------------------------------
// TESTE 7: Registro de Não Conformidade e Upload de Evidência
// -------------------------------------------------------------
echo "\n7. Testando Registro de Não Conformidade e Upload de Foto...\n";
$dummyImgPath = __DIR__ . '/dummy_evidence.jpg';
file_put_contents($dummyImgPath, 'fake_jpeg_binary_content_for_testing');

$cfile = new CURLFile($dummyImgPath, 'image/jpeg', 'evidencia_teste.jpg');
$dadosNaoConf = [
    'id_vistoria' => $idVistoria,
    'descricao_detalhada' => 'Lâmpada do farol esquerdo queimada durante vistoria de teste.',
    'foto_evidencia' => $cfile
];
$res7 = request("$baseUrl/backend/processar_detalhes_checklist.php", 'POST', $dadosNaoConf, true);
if (file_exists($dummyImgPath)) unlink($dummyImgPath);

$stmtE = $pdo->prepare("SELECT COUNT(*) FROM EvidenciasVistoria WHERE id_vistoria = :id");
$stmtE->execute([':id' => $idVistoria]);
$totalEvidencias = $stmtE->fetchColumn();

assertTest("Evidência fotográfica persistida na tabela EvidenciasVistoria", $totalEvidencias > 0, "Evidências: $totalEvidencias");

// -------------------------------------------------------------
// TESTE 8: Assinatura Digital em Canvas e Encerramento
// -------------------------------------------------------------
echo "\n8. Testando Assinaturas Digitais em Canvas e Conclusão...\n";
$fakeSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
$dadosAssinatura = [
    'id_vistoria' => $idVistoria,
    'assinatura_motorista' => $fakeSignature,
    'assinatura_vistoriador' => $fakeSignature
];
$res8 = request("$baseUrl/backend/processar_checklist_final.php", 'POST', $dadosAssinatura);
$conclusaoOk = ($res8['code'] === 302 && strpos($res8['headers'], 'sucesso=checklist_concluido') !== false);
assertTest("Vistoria finalizada com assinaturas e redirecionada com sucesso", $conclusaoOk);

$stmtVistoriaFinal = $pdo->prepare("SELECT status, assinatura_motorista, assinatura_vistoriador FROM Vistorias WHERE id_vistoria = :id");
$stmtVistoriaFinal->execute([':id' => $idVistoria]);
$vFinal = $stmtVistoriaFinal->fetch(PDO::FETCH_ASSOC);

assertTest("Status da vistoria atualizado para 'aprovado_com_restricoes' (devido à avaria)", $vFinal['status'] === 'aprovado_com_restricoes');
assertTest("Assinaturas digitais Base64 gravadas no banco", !empty($vFinal['assinatura_motorista']) && !empty($vFinal['assinatura_vistoriador']));

// -------------------------------------------------------------
// TESTE 9: Telas Dinâmicas de Edição e Exclusão
// -------------------------------------------------------------
echo "\n9. Testando Telas Dinâmicas de Edição e Exclusão...\n";
$resTelaVeiculo = request("$baseUrl/frontend/editarVeiculo.php");
assertTest("Tela de Edição de Veículo renderiza via PDO com status 200", $resTelaVeiculo['code'] === 200 && strpos($resTelaVeiculo['body'], 'Gol') !== false);

$resTelaChecklist = request("$baseUrl/frontend/editarChecklist.php");
assertTest("Tela de Edição de Checklist renderiza via PDO com status 200", $resTelaChecklist['code'] === 200);

$resTelaUsuario = request("$baseUrl/frontend/editarUsuario.php");
assertTest("Tela de Edição de Usuário renderiza via PDO com status 200", $resTelaUsuario['code'] === 200 && strpos($resTelaUsuario['body'], 'Administrador Geral') !== false);

// -------------------------------------------------------------
// TESTE 10: Edição Dinâmica de Veículo
// -------------------------------------------------------------
echo "\n10. Testando Edição de Veículo via POST...\n";
$novoKm = 48200;
$dadosEdicaoVeiculo = [
    'id_veiculo' => 1,
    'marca'      => 'Volkswagen',
    'modelo'     => 'Gol 1.0 Flex',
    'ano'        => 2022,
    'placa'      => 'ABC-1D23',
    'cor'        => 'Cinza Platinum',
    'kmrodado'   => $novoKm,
    'renavam'    => '12345678901',
    'chassi'     => '9BWZZZ377VT000000'
];
$res10 = request("$baseUrl/backend/processar_edicao_veiculo.php", 'POST', $dadosEdicaoVeiculo);
$stmtVCheck = $pdo->query("SELECT km_rodado, cor FROM Veiculos WHERE id_veiculo = 1");
$veiculoAtualizado = $stmtVCheck->fetch(PDO::FETCH_ASSOC);

assertTest("Veículo atualizado com sucesso no banco (km: $novoKm, cor: Cinza Platinum)", 
    $veiculoAtualizado['km_rodado'] == $novoKm && $veiculoAtualizado['cor'] === 'Cinza Platinum');

// -------------------------------------------------------------
// TESTE 11: Dashboard e Histórico de Vistorias (US-009)
// -------------------------------------------------------------
echo "\n11. Testando Dashboard e Histórico de Vistorias (US-009)...\n";
$res11 = request("$baseUrl/frontend/dashboard.php");
$dashboardOk = ($res11['code'] === 200 && strpos($res11['body'], 'Painel de Vistorias e Conformidade') !== false && strpos($res11['body'], 'Taxa de Conformidade') !== false);
assertTest("Dashboard renderiza com KPIs e histórico de vistorias (HTTP 200)", $dashboardOk);

// -------------------------------------------------------------
// TESTE 12: Emissão de Laudo Oficial de Vistoria (US-010)
// -------------------------------------------------------------
echo "\n12. Testando Emissão de Laudo Oficial de Vistoria (US-010)...\n";
$res12 = request("$baseUrl/frontend/laudoVistoria.php?id_vistoria=$idVistoria");
$laudoOk = ($res12['code'] === 200 && strpos($res12['body'], 'LAUDO OFICIAL DE VISTORIA') !== false && strpos($res12['body'], 'ABC-1D23') !== false);
assertTest("Laudo Técnico Oficial gerado com dados do veículo e protocolo (HTTP 200)", $laudoOk);
assertTest("Laudo contém evidência de não conformidade e declaração de assinaturas", 
    strpos($res12['body'], 'Lâmpada do farol esquerdo queimada') !== false && strpos($res12['body'], 'Assinaturas Digitais') !== false);

// -------------------------------------------------------------
// TESTE 13: Execução de Vistoria no Modo Híbrido (Passo 3)
// -------------------------------------------------------------
echo "\n13. Testando Tela de Vistoria no Modo Híbrido (Campo / Lista)...\n";
$res13 = request("$baseUrl/frontend/vistoria.php?id_vistoria=$idVistoria");
$vistoriaHibridaOk = ($res13['code'] === 200 && strpos($res13['body'], 'Modo Campo') !== false && strpos($res13['body'], 'Modo Lista') !== false);
assertTest("Tela de Vistoria Híbrida renderiza com botões gigantes e alternador de modo (HTTP 200)", $vistoriaHibridaOk);

// Testa envio de respostas para processar_execucao_vistoria.php
$dadosExecucao = [
    'id_vistoria' => $idVistoria,
    'respostas' => json_encode([
        ['id_pergunta' => 1, 'conforme' => 1, 'valor_resposta' => 'Conforme'],
        ['id_pergunta' => 2, 'conforme' => 0, 'valor_resposta' => 'Não Conforme', 'observacao' => 'Avaria constatada em teste', 'criticidade' => 'alta'],
        ['id_pergunta' => 3, 'conforme' => 1, 'valor_resposta' => 'Conforme']
    ]),
    'assinatura_motorista' => $fakeSignature,
    'assinatura_vistoriador' => $fakeSignature
];
$resExec = request("$baseUrl/backend/processar_execucao_vistoria.php", 'POST', $dadosExecucao);
$jsonExec = json_decode($resExec['body'], true);
assertTest("Backend processa respostas e persiste na tabela RespostasVistoria", !empty($jsonExec['sucesso']) && $jsonExec['sucesso'] === true);

$stmtContResp = $pdo->prepare("SELECT COUNT(*) FROM RespostasVistoria WHERE id_vistoria = :id");
$stmtContResp->execute([':id' => $idVistoria]);
$totalRespSalvas = $stmtContResp->fetchColumn();
assertTest("Respostas persistidas com sucesso no banco relacional (esperadas 3)", $totalRespSalvas == 3, "Total: $totalRespSalvas");

// -------------------------------------------------------------
// TESTE 14: Matriz RBAC - Restrição de Acesso para Perfil Motorista
// -------------------------------------------------------------
echo "\n14. Testando Barramento RBAC para Perfil Motorista (motorista@axion.com)...\n";
if (file_exists($cookieFile)) unlink($cookieFile);

// Login como Motorista
$resLogMot = request("$baseUrl/backend/processar_login.php", 'POST', [
    'txtuser' => 'motorista@axion.com',
    'txtsenha' => 'mudar123'
]);
assertTest("Login com perfil motorista realizado com sucesso", $resLogMot['code'] === 302);

// Tentativa de acessar dashboard
$resMotDash = request("$baseUrl/frontend/dashboard.php");
assertTest("Motorista é barrado ao tentar acessar dashboard.php", 
    $resMotDash['code'] === 302 && strpos($resMotDash['headers'], 'erro=acesso_negado') !== false);

// Tentativa de acessar ocorrências
$resMotOcorr = request("$baseUrl/frontend/ocorrencias.php");
assertTest("Motorista é barrado ao tentar acessar ocorrencias.php", 
    $resMotOcorr['code'] === 302 && strpos($resMotOcorr['headers'], 'erro=acesso_negado') !== false);

// Tentativa de acessar exclusão de veículos
$resMotExcV = request("$baseUrl/frontend/excluirVeiculo.php");
assertTest("Motorista é barrado ao tentar acessar excluirVeiculo.php", 
    $resMotExcV['code'] === 302 && strpos($resMotExcV['headers'], 'erro=acesso_negado') !== false);

// Tentativa de acessar exclusão de usuários
$resMotExcU = request("$baseUrl/frontend/excluirUsuario.php");
assertTest("Motorista é barrado ao tentar acessar excluirUsuario.php", 
    $resMotExcU['code'] === 302 && strpos($resMotExcU['headers'], 'erro=acesso_negado') !== false);

// Tentativa de acessar governança de checklists
$resMotGestC = request("$baseUrl/frontend/gestaoChecklists.php");
assertTest("Motorista é barrado ao tentar acessar gestaoChecklists.php", 
    $resMotGestC['code'] === 302 && strpos($resMotGestC['headers'], 'erro=acesso_negado') !== false);

// Tentativa de post para exclusão de veículo
$resMotPostExc = request("$baseUrl/backend/processar_exclusao_veiculo.php", 'POST', ['id_veiculo' => 1]);
assertTest("POST de exclusão de veículo por motorista é bloqueado pelo middleware RBAC", 
    $resMotPostExc['code'] === 302 && strpos($resMotPostExc['headers'], 'erro=acesso_negado') !== false);

// Tentativa de post para cadastro de supervisor
$resMotPostCadSup = request("$baseUrl/backend/processar_cadastro_supervisor.php", 'POST', [
    'nome' => 'Hacker', 'sobrenome' => 'Test', 'email' => 'hack@axion.com', 'cpf' => '00000000000'
]);
assertTest("POST de cadastro de supervisor por motorista é bloqueado pelo middleware RBAC", 
    $resMotPostCadSup['code'] === 302 && strpos($resMotPostCadSup['headers'], 'erro=acesso_negado') !== false);


// -------------------------------------------------------------
// TESTE 15: Matriz RBAC - Permissões e Limitações do Supervisor
// -------------------------------------------------------------
echo "\n15. Testando Permissões e Limitações do Supervisor (supervisor@axion.com)...\n";
if (file_exists($cookieFile)) unlink($cookieFile);

// Login como Supervisor
$resLogSup = request("$baseUrl/backend/processar_login.php", 'POST', [
    'txtuser' => 'supervisor@axion.com',
    'txtsenha' => 'mudar123'
]);
assertTest("Login com perfil supervisor realizado com sucesso", $resLogSup['code'] === 302);

// Supervisor acessa dashboard
$resSupDash = request("$baseUrl/frontend/dashboard.php");
assertTest("Supervisor tem permissão para acessar dashboard.php (HTTP 200)", $resSupDash['code'] === 200);

// Supervisor acessa ocorrências
$resSupOcorr = request("$baseUrl/frontend/ocorrencias.php");
assertTest("Supervisor tem permissão para acessar ocorrencias.php (HTTP 200)", $resSupOcorr['code'] === 200);

// Supervisor acessa gestão de checklists
$resSupGestC = request("$baseUrl/frontend/gestaoChecklists.php");
assertTest("Supervisor tem permissão para acessar gestaoChecklists.php (HTTP 200)", $resSupGestC['code'] === 200);

// Supervisor tenta acessar exclusão de veículos -> BARRADO
$resSupExcV = request("$baseUrl/frontend/excluirVeiculo.php");
assertTest("Supervisor é barrado ao tentar acessar excluirVeiculo.php", 
    $resSupExcV['code'] === 302 && strpos($resSupExcV['headers'], 'erro=acesso_negado') !== false);

// Supervisor tenta acessar exclusão de usuários -> BARRADO
$resSupExcU = request("$baseUrl/frontend/excluirUsuario.php");
assertTest("Supervisor é barrado ao tentar acessar excluirUsuario.php", 
    $resSupExcU['code'] === 302 && strpos($resSupExcU['headers'], 'erro=acesso_negado') !== false);

// Supervisor tenta POST de exclusão de usuário -> BARRADO
$resSupPostExcU = request("$baseUrl/backend/processar_exclusao_usuario.php", 'POST', ['id_usuario' => 1]);
assertTest("POST de exclusão de usuário por supervisor é bloqueado por RBAC", 
    $resSupPostExcU['code'] === 302 && strpos($resSupPostExcU['headers'], 'erro=acesso_negado') !== false);

// Supervisor tenta cadastrar outro supervisor -> BARRADO
$resSupCadSup = request("$baseUrl/backend/processar_cadastro_supervisor.php", 'POST', [
    'nome' => 'Sup', 'sobrenome' => 'Novo', 'email' => 'supnovo@axion.com', 'cpf' => '11122233344'
]);
assertTest("POST de cadastro de supervisor por supervisor é bloqueado por RBAC", 
    $resSupCadSup['code'] === 302 && strpos($resSupCadSup['headers'], 'erro=acesso_negado') !== false);

// Supervisor cadastra um novo motorista -> SUCESSO
$cpfRand = rand(100, 999) . '.' . rand(100, 999) . '.' . rand(100, 999) . '-' . rand(10, 99);
$emailRand = 'motorista_' . uniqid() . '@axion.com';
$resSupCadMot = request("$baseUrl/backend/processar_cadastro_motorista.php", 'POST', [
    'nome' => 'Motorista', 'sobrenome' => 'Teste RBAC', 'email' => $emailRand, 'cpf' => $cpfRand
]);
assertTest("Supervisor consegue cadastrar novo motorista operacional com sucesso", 
    $resSupCadMot['code'] === 302 && strpos($resSupCadMot['headers'], 'sucesso=motorista_cadastrado') !== false);


// -------------------------------------------------------------
// TESTE 16: Ciclo Completo de Governança de Checklist
// (Proposição Supervisor -> Rejeição Gestor -> Ajuste Supervisor -> Aprovação Gestor -> Liberação Frota)
// -------------------------------------------------------------
echo "\n16. Testando Ciclo de Governança de Checklists (Supervisor propõe ➔ Gestor avalia)...\n";

// Supervisor cria proposta de checklist
$tituloGov = 'Checklist Carga Perigosa ' . uniqid();
$dadosPropChecklist = [
    'titulo' => $tituloGov,
    'perguntas' => [
        0 => ['texto' => 'EPI químico conferido?', 'tipo' => 'sim_nao', 'categoria' => 'seguranca', 'obrigatorio' => 1],
        1 => ['texto' => 'Válvula de alívio sem vazamento?', 'tipo' => 'sim_nao', 'categoria' => 'mecanica', 'obrigatorio' => 1]
    ]
];
$resCriarProp = request("$baseUrl/backend/criar_checklist_dinamico.php", 'POST', $dadosPropChecklist);
assertTest("Supervisor submete proposta de checklist", $resCriarProp['code'] === 302);

// Busca no banco e confirma status inicial como 'pendente_aprovacao'
$stmtChkGov = $pdo->prepare("SELECT * FROM Checklists WHERE titulo = :t LIMIT 1");
$stmtChkGov->execute([':t' => $tituloGov]);
$chkGov = $stmtChkGov->fetch(PDO::FETCH_ASSOC);
$idChkGov = $chkGov ? $chkGov['id_checklist'] : 0;
assertTest("Checklist proposto nasce com status 'pendente_aprovacao'", 
    $chkGov && $chkGov['status'] === 'pendente_aprovacao', "ID: $idChkGov, Status: " . ($chkGov['status'] ?? 'N/A'));

// Alterna para sessão de Motorista e verifica se o checklist pendente está oculto
if (file_exists($cookieFile)) unlink($cookieFile);
request("$baseUrl/backend/processar_login.php", 'POST', ['txtuser' => 'motorista@axion.com', 'txtsenha' => 'mudar123']);
$resCadCheckMot = request("$baseUrl/frontend/cadcheck.php");
assertTest("Motorista NÃO visualiza checklist pendente na tela de vistoria", 
    strpos($resCadCheckMot['body'], $tituloGov) === false);

// Alterna para sessão do Gestor para solicitar ajuste
if (file_exists($cookieFile)) unlink($cookieFile);
request("$baseUrl/backend/processar_login.php", 'POST', ['txtuser' => 'admin@axion.com', 'txtsenha' => 'admin123']);

$resDevolver = request("$baseUrl/backend/processar_aprovacao_checklist.php", 'POST', [
    'id_checklist' => $idChkGov,
    'acao' => 'solicitar_ajuste',
    'motivo_ajuste' => 'Adicionar pergunta sobre certificado MOPP e kit de contenção de derramamento.'
]);
$jsonDevolver = json_decode($resDevolver['body'], true);
assertTest("Gestor devolve proposta com parecer de solicitação de ajuste", 
    !empty($jsonDevolver['sucesso']) && $jsonDevolver['sucesso'] === true);

// Checa banco: status 'ajuste_solicitado' e motivo persistido
$stmtChkGov->execute([':t' => $tituloGov]);
$chkGovDevolvido = $stmtChkGov->fetch(PDO::FETCH_ASSOC);
assertTest("Banco registra status 'ajuste_solicitado' e parecer técnico do gestor", 
    $chkGovDevolvido['status'] === 'ajuste_solicitado' && strpos($chkGovDevolvido['motivo_ajuste'], 'MOPP') !== false);

// Alterna para sessão de Supervisor e aplica o ajuste
if (file_exists($cookieFile)) unlink($cookieFile);
request("$baseUrl/backend/processar_login.php", 'POST', ['txtuser' => 'supervisor@axion.com', 'txtsenha' => 'mudar123']);

$resSalvarAjuste = request("$baseUrl/backend/processar_edicao_checklist.php", 'POST', [
    'id_checklist' => $idChkGov,
    'titulo' => $tituloGov . ' (Rev. 1)',
    'categoria' => 'seguranca',
    'perguntas' => [
        0 => ['id_pergunta' => '', 'texto' => 'Certificado MOPP válido e presente?', 'tipo' => 'sim_nao', 'categoria' => 'documental', 'obrigatorio' => 1]
    ]
]);
assertTest("Supervisor salva ajustes solicitados", $resSalvarAjuste['code'] === 302);

// Checa banco: após edição pelo supervisor, o status volta automaticamente para 'pendente_aprovacao'
$stmtChkGovId = $pdo->prepare("SELECT * FROM Checklists WHERE id_checklist = :id");
$stmtChkGovId->execute([':id' => $idChkGov]);
$chkGovReavaliar = $stmtChkGovId->fetch(PDO::FETCH_ASSOC);
assertTest("Após ajustes do supervisor, checklist transiciona para 'pendente_aprovacao' para nova validação", 
    $chkGovReavaliar['status'] === 'pendente_aprovacao');

// Alterna novamente para Gestor para APROVAR
if (file_exists($cookieFile)) unlink($cookieFile);
request("$baseUrl/backend/processar_login.php", 'POST', ['txtuser' => 'admin@axion.com', 'txtsenha' => 'admin123']);

$resAprovar = request("$baseUrl/backend/processar_aprovacao_checklist.php", 'POST', [
    'id_checklist' => $idChkGov,
    'acao' => 'aprovar'
]);
$jsonAprovar = json_decode($resAprovar['body'], true);
assertTest("Gestor valida e aprova checklist em definitivo", 
    !empty($jsonAprovar['sucesso']) && $jsonAprovar['sucesso'] === true && $jsonAprovar['novo_status'] === 'ativo');

// Checa banco: status 'ativo', aprovado_por gravado e data_aprovacao carimbada
$stmtChkGovId->execute([':id' => $idChkGov]);
$chkGovAprovado = $stmtChkGovId->fetch(PDO::FETCH_ASSOC);
assertTest("Banco confirma checklist 'ativo' com carimbo do aprovador e timestamp", 
    $chkGovAprovado['status'] === 'ativo' && !empty($chkGovAprovado['aprovado_por']) && !empty($chkGovAprovado['data_aprovacao']));

// Alterna para sessão de Motorista e confirma que AGORA o checklist está disponível
if (file_exists($cookieFile)) unlink($cookieFile);
request("$baseUrl/backend/processar_login.php", 'POST', ['txtuser' => 'motorista@axion.com', 'txtsenha' => 'mudar123']);
$resCadCheckMotFinal = request("$baseUrl/frontend/cadcheck.php");
assertTest("Motorista AGORA visualiza o checklist aprovado na listagem de vistorias", 
    strpos($resCadCheckMotFinal['body'], $tituloGov) !== false);

// Limpa cookie de teste
if (file_exists($cookieFile)) unlink($cookieFile);

// -------------------------------------------------------------
// RESUMO FINAL
// -------------------------------------------------------------
echo "\n====================================================================\n";
echo "RESUMO DOS TESTES FUNCIONAIS:\n";
echo "Sucessos: $passCount\n";
echo "Falhas:   $failCount\n";
echo "Taxa de Sucesso: " . round(($passCount / ($passCount + $failCount)) * 100, 1) . "%\n";
echo "====================================================================\n";

exit($failCount === 0 ? 0 : 1);

