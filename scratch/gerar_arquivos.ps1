$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'

# 1. Matriz de Selecao
Start-Process -FilePath $chrome -ArgumentList @('--headless=new', '--no-sandbox', '--disable-gpu', '--window-size=960,650', '--screenshot=C:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\arquivos\matrizSelecao.png', 'http://localhost/projetoIntegrador/scratch/gerar_diagramas.php?tipo=matriz') -Wait

# 2. PMC
Start-Process -FilePath $chrome -ArgumentList @('--headless=new', '--no-sandbox', '--disable-gpu', '--window-size=1260,780', '--screenshot=C:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\arquivos\PMC\PMC.png', 'http://localhost/projetoIntegrador/scratch/gerar_diagramas.php?tipo=pmc') -Wait

Get-ChildItem -Recurse arquivos | Select-Object Name, Length

