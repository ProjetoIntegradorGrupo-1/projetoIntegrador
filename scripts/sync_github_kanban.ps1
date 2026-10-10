<#
.SYNOPSIS
    Sincroniza automaticamente os campos customizados de Prioridade, Tempo e Fluxo no GitHub Projects (Kanban 2).
.DESCRIPTION
    Script de automação para o Projeto Integrador IFES (Equipe AXION).
    Lê o mapeamento consolidado de todas as 133 issues e aplica via GitHub GraphQL API:
    - Priority (P0, P1, P2)
    - Size / Tempo (XS, S, M, L)
    - Estimate (horas numéricas)
    - Fluxos (Fluxo 1, Fluxo 2, Fluxo 3)
.PARAMETER Token
    Personal Access Token do GitHub com escopo 'project' habilitado (https://github.com/settings/tokens).
#>

param(
    [string]$Token
)

$ErrorActionPreference = "Stop"

# 1. Obter Token
if (-not $Token) {
    if ($env:GITHUB_TOKEN) {
        $Token = $env:GITHUB_TOKEN
    } else {
        $Token = Read-Host "Informe seu GitHub Personal Access Token (com escopo 'project')"
    }
}

if (-not $Token) {
    Write-Error "Token do GitHub não fornecido. O escopo 'project' é obrigatório para modificar campos customizados do GitHub Projects."
}

$headers = @{
    "Authorization" = "bearer $Token"
    "User-Agent"    = "Axion-Kanban-Sync"
    "Content-Type"  = "application/json"
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " AXION - Sincronizador Automático de Kanban (GitHub Projects)" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 2. Carregar arquivo de mapeamento consolidado
$mappingFile = Join-Path $PSScriptRoot "..\scratch\kanban_mapping_completo.json"
if (-not (Test-Path $mappingFile)) {
    Write-Error "Arquivo de mapeamento '$mappingFile' não encontrado."
}
$tasksMapping = Get-Content $mappingFile -Raw -Encoding UTF8 | ConvertFrom-Json
Write-Host "[✓] Mapeamento de $($tasksMapping.Count) issues carregado com sucesso." -ForegroundColor Green

# 3. Consultar Projeto e Campos via GraphQL
Write-Host "[*] Conectando ao GitHub Projects v2..." -ForegroundColor Yellow

$queryProject = @"
{
  user(login: "ProjetoIntegradorGrupo-1") {
    projectV2(number: 4) {
      id
      title
      fields(first: 30) {
        nodes {
          ... on ProjectV2Field {
            id
            name
            dataType
          }
          ... on ProjectV2SingleSelectField {
            id
            name
            dataType
            options {
              id
              name
            }
          }
        }
      }
    }
  }
}
"@

$body = @{ query = $queryProject } | ConvertTo-Json
try {
    $res = Invoke-RestMethod -Uri "https://api.github.com/graphql" -Headers $headers -Method Post -Body $body
} catch {
    Write-Error "Falha na requisição GraphQL: $_"
}

if ($res.errors) {
    Write-Host "`n[!] Erro retornado pela API do GitHub:" -ForegroundColor Red
    $res.errors | ForEach-Object { Write-Host " - $($_.message)" -ForegroundColor Red }
    if ($res.errors[0].type -eq "INSUFFICIENT_SCOPES") {
        Write-Host "`n--> MOTIVO: O token informado não possui a permissão 'project'." -ForegroundColor Yellow
        Write-Host "    Acesse https://github.com/settings/tokens, crie/edite seu token clássico e marque a caixa '[x] project'." -ForegroundColor Yellow
    }
    exit 1
}

$project = $res.data.user.projectV2
$projectId = $project.id
Write-Host "[✓] Projeto encontrado: '$($project.title)' (ID: $projectId)" -ForegroundColor Green

# 4. Mapear IDs de Campos e Opções
$fields = $project.fields.nodes

$fieldPriority = $fields | Where-Object { $_.name -eq "Priority" }
$fieldSize = $fields | Where-Object { $_.name -eq "Size" }
$fieldFluxos = $fields | Where-Object { $_.name -eq "Fluxos" }
$fieldEstimate = $fields | Where-Object { $_.name -eq "Estimate" }

if (-not $fieldPriority -or -not $fieldSize -or -not $fieldFluxos) {
    Write-Error "Não foi possível localizar os campos Priority, Size ou Fluxos no projeto."
}

Write-Host "    - Campo Priority: $($fieldPriority.id)" -ForegroundColor Gray
Write-Host "    - Campo Size: $($fieldSize.id)" -ForegroundColor Gray
Write-Host "    - Campo Fluxos: $($fieldFluxos.id)" -ForegroundColor Gray

# 5. Buscar todos os itens do projeto com paginação
Write-Host "[*] Carregando todos os itens do quadro..." -ForegroundColor Yellow
$allItems = @()
$hasNextPage = $true
$cursor = $null

while ($hasNextPage) {
    $afterClause = if ($cursor) { ", after: `"$cursor`"" } else { "" }
    $queryItems = @"
    {
      node(id: `"$projectId`") {
        ... on ProjectV2 {
          items(first: 50$afterClause) {
            pageInfo {
              hasNextPage
              endCursor
            }
            nodes {
              id
              content {
                ... on Issue {
                  number
                  title
                }
              }
            }
          }
        }
      }
    }
"@
    $bodyItems = @{ query = $queryItems } | ConvertTo-Json
    $resItems = Invoke-RestMethod -Uri "https://api.github.com/graphql" -Headers $headers -Method Post -Body $bodyItems
    $page = $resItems.data.node.items
    $allItems += $page.nodes
    $hasNextPage = $page.pageInfo.hasNextPage
    $cursor = $page.pageInfo.endCursor
}

Write-Host "[✓] Total de itens no Kanban: $($allItems.Count)" -ForegroundColor Green

# Criar dicionário de itens por número de issue
$itemsByIssueNumber = @{}
foreach ($it in $allItems) {
    if ($it.content -and $it.content.number) {
        $itemsByIssueNumber[$it.content.number] = $it
    }
}

# 6. Atualizar cada item
Write-Host "`n[*] Iniciando atualização dos campos customizados..." -ForegroundColor Cyan
$successCount = 0
$skippedCount = 0

foreach ($task in $tasksMapping) {
    $num = $task.number
    if (-not $itemsByIssueNumber.ContainsKey($num)) {
        $skippedCount++
        continue
    }

    $item = $itemsByIssueNumber[$num]
    $itemId = $item.id

    # Buscar Option IDs correspondentes
    $prioOpt = $fieldPriority.options | Where-Object { $_.name -eq $task.priority }
    $sizeOpt = $fieldSize.options | Where-Object { $_.name -like "$($task.size)*" -or $_.name -eq $task.size }
    $fluxoOpt = $fieldFluxos.options | Where-Object { $_.name -eq $task.fluxo }

    # Mutação para Priority
    if ($prioOpt) {
        $mutPrio = @"
        mutation {
          updateProjectV2ItemFieldValue(input: {
            projectId: `"$projectId`"
            itemId: `"$itemId`"
            fieldId: `"$($fieldPriority.id)`"
            value: { singleSelectOptionId: `"$($prioOpt.id)`" }
          }) { projectV2Item { id } }
        }
"@
        $bodyP = @{ query = $mutPrio } | ConvertTo-Json
        $null = Invoke-RestMethod -Uri "https://api.github.com/graphql" -Headers $headers -Method Post -Body $bodyP
    }

    # Mutação para Size
    if ($sizeOpt) {
        $mutSize = @"
        mutation {
          updateProjectV2ItemFieldValue(input: {
            projectId: `"$projectId`"
            itemId: `"$itemId`"
            fieldId: `"$($fieldSize.id)`"
            value: { singleSelectOptionId: `"$($sizeOpt.id)`" }
          }) { projectV2Item { id } }
        }
"@
        $bodyS = @{ query = $mutSize } | ConvertTo-Json
        $null = Invoke-RestMethod -Uri "https://api.github.com/graphql" -Headers $headers -Method Post -Body $bodyS
    }

    # Mutação para Fluxos
    if ($fluxoOpt) {
        $mutFluxo = @"
        mutation {
          updateProjectV2ItemFieldValue(input: {
            projectId: `"$projectId`"
            itemId: `"$itemId`"
            fieldId: `"$($fieldFluxos.id)`"
            value: { singleSelectOptionId: `"$($fluxoOpt.id)`" }
          }) { projectV2Item { id } }
        }
"@
        $bodyF = @{ query = $mutFluxo } | ConvertTo-Json
        $null = Invoke-RestMethod -Uri "https://api.github.com/graphql" -Headers $headers -Method Post -Body $bodyF
    }

    # Mutação para Estimate (Horas)
    if ($fieldEstimate -and $task.estimate_hours) {
        $mutEst = @"
        mutation {
          updateProjectV2ItemFieldValue(input: {
            projectId: `"$projectId`"
            itemId: `"$itemId`"
            fieldId: `"$($fieldEstimate.id)`"
            value: { number: $($task.estimate_hours) }
          }) { projectV2Item { id } }
        }
"@
        $bodyE = @{ query = $mutEst } | ConvertTo-Json
        $null = Invoke-RestMethod -Uri "https://api.github.com/graphql" -Headers $headers -Method Post -Body $bodyE
    }

    $successCount++
    Write-Host "[✓] Issue #$num atualizada: Prioridade=$($task.priority) | Tempo=$($task.size) | Fluxo=$($task.fluxo)" -ForegroundColor Gray
}

Write-Host "`n==========================================================" -ForegroundColor Green
Write-Host " Concluído! $successCount cards atualizados com sucesso no Kanban." -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green

