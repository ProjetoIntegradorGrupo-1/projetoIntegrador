<?php
// scratch/render_preview.php
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['nome_usuario'] = 'Administrador Geral';
$_SESSION['perfil'] = 'administrador';
$_SESSION['email'] = 'admin@axion.com';

$page = $_GET['page'] ?? '';
$allowed = [
    'dashboard'   => __DIR__ . '/../frontend/dashboard.php',
    'ocorrencias' => __DIR__ . '/../frontend/ocorrencias.php',
    'cadcheck'    => __DIR__ . '/../frontend/cadcheck.php',
    'vistoria'    => __DIR__ . '/../frontend/vistoria.php',
    'laudo'       => __DIR__ . '/../frontend/laudoVistoria.php',
    'oqfazer'     => __DIR__ . '/../frontend/oqfazer.php'
];

if (isset($allowed[$page])) {
    include $allowed[$page];
} else {
    echo "Página não permitida.";
}

