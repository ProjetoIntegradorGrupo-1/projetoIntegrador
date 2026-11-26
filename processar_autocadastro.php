<?php
// processar_autocadastro.php
// Cadastro feito pelo próprio cliente (sem login). A conta nasce "pendente":
// o admin ou um supervisor confere os dados, escolhe o supervisor responsável e libera o acesso.
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/backend/auth.php';

header('Content-Type: application/json; charset=utf-8');
$responder = function (bool $ok, string $msg, int $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(['sucesso' => $ok, ($ok ? 'mensagem' : 'erro') => $msg], JSON_UNESCAPED_UNICODE);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') $responder(false, 'Método inválido.', 405);
// Campo escondido que só robôs preenchem
if (!empty($_POST['site'])) $responder(true, 'Cadastro enviado.');

$nome          = trim($_POST['nome'] ?? '');
$sobrenome     = trim($_POST['sobrenome'] ?? '');
$email         = mb_strtolower(trim($_POST['email'] ?? ''));
$cpf           = trim($_POST['cpf'] ?? '');
$dtNasc        = $_POST['dtNascimento'] ?? '';
$telefone      = trim($_POST['telefone'] ?? '');
$cnh           = preg_replace('/\D/', '', $_POST['cnh'] ?? '');
$categoriaCnh  = strtoupper(trim($_POST['categoria_cnh'] ?? ''));
$validadeCnh   = $_POST['validade_cnh'] ?? '';
$cep           = trim($_POST['cep'] ?? '');
$logradouro    = trim($_POST['logradouro'] ?? '');
$numero        = trim($_POST['numero'] ?? '');
$bairro        = trim($_POST['bairro'] ?? '');
$cidade        = trim($_POST['cidade'] ?? '');
$estado        = strtoupper(trim($_POST['estado'] ?? ''));
$senha         = (string)($_POST['senha'] ?? '');
$confirmar     = (string)($_POST['confirmar_senha'] ?? '');

$dataOk = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && date('Y-m-d', strtotime($d)) === $d;

if ($nome === '' || $sobrenome === '') $responder(false, 'Informe o nome e o sobrenome.', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $responder(false, 'E-mail inválido.', 422);
if (!axion_validar_cpf($cpf)) $responder(false, 'CPF inválido. Confira os números.', 422);
if (!$dataOk($dtNasc)) $responder(false, 'Informe a data de nascimento.', 422);
if ($dtNasc > date('Y-m-d', strtotime('-18 years'))) $responder(false, 'É preciso ter 18 anos ou mais para alugar.', 422);
if (strlen(preg_replace('/\D/', '', $telefone)) < 10) $responder(false, 'Informe um telefone com DDD.', 422);
if (strlen($cnh) !== 11) $responder(false, 'O número da CNH tem 11 dígitos.', 422);
if (!in_array($categoriaCnh, ['A', 'B', 'AB', 'C', 'D', 'E', 'AC', 'AD', 'AE'], true)) $responder(false, 'Escolha a categoria da CNH.', 422);
if (!$dataOk($validadeCnh)) $responder(false, 'Informe a validade da CNH.', 422);
if ($validadeCnh < date('Y-m-d')) $responder(false, 'Sua CNH está vencida. Com a CNH vencida não é possível alugar.', 422);
if (mb_strlen($senha) < 6) $responder(false, 'A senha precisa ter pelo menos 6 caracteres.', 422);
if ($senha !== $confirmar) $responder(false, 'A confirmação da senha não confere.', 422);

$digitos = preg_replace('/\D/', '', $cpf);
$cpfFormatado = substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);

$dup = $pdo->prepare("SELECT email = :e AS mesmo_email, status FROM Usuarios WHERE email = :e2 OR cpf_matricula = :c OR cpf_matricula = :d LIMIT 1");
$dup->execute([':e' => $email, ':e2' => $email, ':c' => $cpfFormatado, ':d' => $digitos]);
if ($ja = $dup->fetch()) {
    if ($ja['status'] === 'pendente') $responder(false, 'Já existe um cadastro com estes dados aguardando aprovação. Aguarde a empresa liberar o acesso.', 409);
    $responder(false, ($ja['mesmo_email'] ? 'Este e-mail' : 'Este CPF') . ' já tem cadastro. Use "Esqueci minha senha" se não lembrar a senha.', 409);
}

try {
    $pdo->prepare("INSERT INTO Usuarios (nome, sobrenome, email, senha, cpf_matricula, perfil, data_nascimento, telefone,
                       cnh, categoria_cnh, validade_cnh, cep, logradouro, numero, bairro, cidade, estado, status, id_supervisor)
                   VALUES (:nome, :sobrenome, :email, :senha, :cpf, 'cliente', :nasc, :tel,
                       :cnh, :cat, :val, :cep, :log, :num, :bairro, :cidade, :uf, 'pendente', NULL)")
        ->execute([
            ':nome' => trim("$nome $sobrenome"), ':sobrenome' => $sobrenome, ':email' => $email,
            ':senha' => password_hash($senha, PASSWORD_DEFAULT), ':cpf' => $cpfFormatado, ':nasc' => $dtNasc, ':tel' => $telefone,
            ':cnh' => $cnh, ':cat' => $categoriaCnh, ':val' => $validadeCnh, ':cep' => $cep, ':log' => $logradouro,
            ':num' => $numero, ':bairro' => $bairro, ':cidade' => $cidade, ':uf' => mb_substr($estado, 0, 2),
        ]);
} catch (PDOException $e) {
    $responder(false, 'Não foi possível concluir o cadastro. Tente de novo.', 500);
}
$responder(true, 'Cadastro enviado! A empresa vai conferir os seus dados e liberar o acesso. Depois disso é só entrar com o seu e-mail e senha.');
