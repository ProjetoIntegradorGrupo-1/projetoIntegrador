<?php
/**
 * backend/fotos.php
 * Salvar e servir fotos de usuários e de veículos (arquivos em uploads/usuarios e uploads/veiculos).
 * O navegador já recorta e reduz a imagem antes de enviar; aqui só conferimos se é mesmo uma imagem.
 */

const AXION_FOTO_TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

/** Pasta de cada tipo de foto. */
function axion_pasta_fotos(string $tipo): string {
    return __DIR__ . '/../uploads/' . ($tipo === 'veiculo' ? 'veiculos' : 'usuarios');
}

/**
 * Salva o arquivo enviado no campo $campo e devolve o nome do arquivo salvo.
 * Devolve null se nenhum arquivo foi enviado; lança RuntimeException com a mensagem se for inválido.
 */
function axion_receber_foto(string $campo, string $tipo, int $id, int $maxMb = 3): ?string {
    $arq = $_FILES[$campo] ?? null;
    if (!$arq || ($arq['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($arq['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível receber a foto.');
    if ($arq['size'] > $maxMb * 1024 * 1024) throw new RuntimeException("A foto pode ter no máximo $maxMb MB.");
    $info = @getimagesize($arq['tmp_name']);
    $ext = $info ? (AXION_FOTO_TIPOS[$info['mime']] ?? null) : null;
    if (!$ext) throw new RuntimeException('Envie uma foto em JPG, PNG ou WEBP.');
    $dir = axion_pasta_fotos($tipo);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $nome = $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($arq['tmp_name'], "$dir/$nome")) throw new RuntimeException('Não foi possível salvar a foto.');
    return $nome;
}

/** Apaga o arquivo de uma foto antiga (se existir). */
function axion_apagar_foto(string $tipo, ?string $arquivo): void {
    if (!$arquivo) return;
    $caminho = axion_pasta_fotos($tipo) . '/' . basename($arquivo);
    if (is_file($caminho)) @unlink($caminho);
}

/** Envia a foto para o navegador. */
function axion_servir_foto(string $tipo, ?string $arquivo): void {
    $caminho = $arquivo ? axion_pasta_fotos($tipo) . '/' . basename($arquivo) : null;
    if (!$caminho || !is_file($caminho)) { http_response_code(404); exit; }
    $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
    header('Content-Type: ' . (array_flip(AXION_FOTO_TIPOS)[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($caminho));
    header('Cache-Control: private, max-age=86400');
    readfile($caminho);
    exit;
}
