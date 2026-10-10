# ========================================================================
# AXION - Sistema de Vistoria e Checklist Veicular
# Script PowerShell de Instalação e Inicialização Automática Local
# ========================================================================

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$Host.UI.RawUI.WindowTitle = "AXION - Sistema de Vistoria e Checklist Veicular (Local)"

Write-Host "========================================================================" -ForegroundColor Green
Write-Host "        AXION - SISTEMA DE VISTORIA E CHECKLIST VEICULAR                " -ForegroundColor Green
Write-Host "        Iniciador e Instalador Automático Local (ScrumAIDev / IFES)     " -ForegroundColor Green
Write-Host "========================================================================" -ForegroundColor Green
Write-Host ""

$projectRoot = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path "$projectRoot\backend\conexao.php")) {
    $projectRoot = $PSScriptRoot
}

Write-Host "[INFO] Diretório do projeto: $projectRoot" -ForegroundColor Cyan

# 1. Localizar PHP
$phpPath = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpPath)) {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCmd) { $phpPath = $phpCmd.Source }
    else {
        Write-Host " [ERRO] PHP não localizado. Instale o XAMPP ou adicione php.exe ao PATH." -ForegroundColor Red
        pause
        exit 1
    }
}
Write-Host " [OK] PHP localizado: $phpPath" -ForegroundColor Green

# 2. Localizar MySQL
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
$mysqldPath = "C:\xampp\mysql\bin\mysqld.exe"
if (-not (Test-Path $mysqlPath)) {
    $mysqlCmd = Get-Command mysql -ErrorAction SilentlyContinue
    if ($mysqlCmd) { $mysqlPath = $mysqlCmd.Source }
}

# 3. Verificar se MySQL está rodando na porta 3306
Write-Host "[ETAPA 1/3] Verificando serviço MySQL local (porta 3306)..." -ForegroundColor Yellow
$tcpClient = New-Object System.Net.Sockets.TcpClient
$asyncResult = $tcpClient.BeginConnect("127.0.0.1", 3306, $null, $null)
$mysqlRunning = $asyncResult.AsyncWaitHandle.WaitOne(800)
$tcpClient.Close()

if (-not $mysqlRunning -and (Test-Path $mysqldPath)) {
    Write-Host "   -> MySQL não está em execução. Iniciando serviço..." -ForegroundColor Gray
    Start-Process -FilePath $mysqldPath -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini --standalone" -WindowStyle Hidden
    Start-Sleep -Seconds 3
    $mysqlRunning = $true
}

if ($mysqlRunning) {
    Write-Host " [OK] Serviço MySQL está ativo e respondendo na porta 3306." -ForegroundColor Green
} else {
    Write-Host " [AVISO] Inicie o MySQL no Painel de Controle do XAMPP." -ForegroundColor Yellow
}

# 4. Configurar banco axion_db
if ($mysqlRunning -and (Test-Path $mysqlPath)) {
    Write-Host "[ETAPA 2/3] Verificando integridade do banco de dados axion_db..." -ForegroundColor Yellow
    $dbCheck = & $mysqlPath -u root -e "SHOW DATABASES LIKE 'axion_db';"
    if ($dbCheck -notmatch "axion_db") {
        Write-Host "   -> Criando banco axion_db..." -ForegroundColor Gray
        & $mysqlPath -u root -e "CREATE DATABASE IF NOT EXISTS axion_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        
        $schemaFile = "$projectRoot\database\schema.sql"
        if (Test-Path $schemaFile) {
            Write-Host "   -> Importando schema.sql..." -ForegroundColor Gray
            Get-Content $schemaFile | & $mysqlPath -u root axion_db
        }
        
        $seedsFile = "$projectRoot\database\seeds.sql"
        if (Test-Path $seedsFile) {
            Write-Host "   -> Importando seeds.sql..." -ForegroundColor Gray
            Get-Content $seedsFile | & $mysqlPath -u root axion_db
        }
        Write-Host " [OK] Banco de dados axion_db criado e tabelas/seeds carregados!" -ForegroundColor Green
    } else {
        Write-Host " [OK] Banco de dados axion_db já existe e está pronto." -ForegroundColor Green
    }
}

# 5. Iniciar Servidor Web
Write-Host "[ETAPA 3/3] Inicializando servidor web em http://localhost:8000..." -ForegroundColor Yellow
$phpProcess = Start-Process -FilePath $phpPath -ArgumentList "-S localhost:8000 -t `"$projectRoot`"" -WorkingDirectory $projectRoot -PassThru -WindowStyle Hidden

Start-Sleep -Seconds 1
Write-Host " [OK] Servidor Web PHP ativo em: http://localhost:8000" -ForegroundColor Green

$appUrl = "http://localhost:8000/frontend/index.html"
Write-Host "   -> Abrindo navegador padrão em: $appUrl" -ForegroundColor Cyan
Start-Process $appUrl

Write-Host ""
Write-Host "========================================================================" -ForegroundColor Green
Write-Host " [SISTEMA EM EXECUÇÃO] Acesse no navegador: http://localhost:8000       " -ForegroundColor Green
Write-Host "========================================================================" -ForegroundColor Green
Write-Host " Contas de Acesso Homologadas:" -ForegroundColor White
Write-Host "  • Administrador : Admin@teste.com       (senha: senha_teste)" -ForegroundColor White
Write-Host "  • Gestor        : Gestor@teste.com      (senha: senha_teste)" -ForegroundColor White
Write-Host "  • Funcionário   : funcionario@teste.com (senha: senha_teste)" -ForegroundColor White
Write-Host ""
Write-Host " Pressione qualquer tecla para encerrar o servidor..." -ForegroundColor DarkGray

[Console]::ReadKey($true) | Out-Null
Stop-Process -Id $phpProcess.Id -Force -ErrorAction SilentlyContinue
Write-Host "Servidor encerrado."

