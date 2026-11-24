<?php
// conexao.php
// Padrão do XAMPP. Para mudar sem alterar este arquivo (ex.: MySQL do XAMPP em outra porta),
// crie um "config.local.php" ao lado deste arquivo (ele não vai para o GitHub). Exemplo:
//   <?php $porta = 3307;
// Horário de Brasília para datas/saudações (o MySQL usa o horário do próprio computador)
date_default_timezone_set('America/Sao_Paulo');

$host    = 'localhost';
$porta   = 3306;
$dbname  = 'axion_db';
$usuario = 'root'; // Padrão do XAMPP
$senha   = '';     // Padrão do XAMPP (vazio)

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

require_once __DIR__ . '/database/seed.php';

try {
    // Conecta ao servidor MySQL e garante que o banco axion_db exista
    $pdo = new PDO("mysql:host=$host;port=$porta;charset=utf8mb4", $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    // Banco e PHP no mesmo fuso: "hoje" é o mesmo dia nos dois
    $pdo->exec("SET time_zone = '" . date('P') . "'");

    // Garante tabelas/colunas atualizadas. NÃO popula mais dados fictícios automaticamente
    // (antes, se houvesse menos de 50 usuários ou carros, o banco inteiro era apagado e recriado,
    // o que trocava os IDs e fazia a sessão "virar" outro usuário).
    axion_garantir_estrutura($pdo);
    if ((int)$pdo->query("SELECT COUNT(*) FROM Usuarios")->fetchColumn() === 0) {
        axion_criar_contas_base($pdo);
    }
} catch (PDOException $e) {
    http_response_code(500);
    die("Erro de conexão com o banco de dados: " . htmlspecialchars($e->getMessage()));
}
