$chromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$baseDir = 'C:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\docs\screenshots'

$items = @(
    @{ Name = '03_triagem_ocorrencias_master_detail.png'; Url = 'http://localhost/projetoIntegrador/scratch/render_preview.php?page=ocorrencias'; W = 1440; H = 900 },
    @{ Name = '04_abertura_vistoria_cadcheck.png'; Url = 'http://localhost/projetoIntegrador/scratch/render_preview.php?page=cadcheck'; W = 1280; H = 800 },
    @{ Name = '05_vistoria_modo_campo.png'; Url = 'http://localhost/projetoIntegrador/scratch/render_preview.php?page=vistoria&id_vistoria=1'; W = 1080; H = 950 },
    @{ Name = '06_laudo_tecnico_oficial.png'; Url = 'http://localhost/projetoIntegrador/scratch/render_preview.php?page=laudo&id_vistoria=1'; W = 1280; H = 1200 },
    @{ Name = '07_menu_operacional.png'; Url = 'http://localhost/projetoIntegrador/scratch/render_preview.php?page=oqfazer'; W = 1280; H = 800 }
)

foreach ($item in $items) {
    $outPath = Join-Path $baseDir $item.Name
    $winSize = "--window-size=$($item.W),$($item.H)"
    $argList = @('--headless=new', '--no-sandbox', '--disable-gpu', $winSize, "--screenshot=$outPath", $item.Url)
    Start-Process -FilePath $chromePath -ArgumentList $argList -Wait
    Write-Host "Capturado: $($item.Name)"
}

Get-ChildItem $baseDir | Select-Object Name, Length

