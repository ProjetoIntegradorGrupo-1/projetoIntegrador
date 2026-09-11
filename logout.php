<?php
// logout.php
session_start(); // Inicia a sessão atual

// Apaga todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão no servidor
session_destroy();

// Redireciona de volta para a tela de login
header("Location: index.html");
exit;
?>