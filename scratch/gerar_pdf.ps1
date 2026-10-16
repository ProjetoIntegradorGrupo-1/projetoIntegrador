$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$url = 'http://localhost/projetoIntegrador/docs/relatorio_prompts_ia.html'

Start-Process -FilePath $chrome -ArgumentList @('--headless=new', '--no-sandbox', '--disable-gpu', '--print-to-pdf=C:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\prompt.pdf', $url) -Wait
Copy-Item 'C:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\prompt.pdf' 'C:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\docs\prompt.pdf'

Get-Item 'prompt.pdf', 'docs\prompt.pdf' | Select-Object Name, Length

