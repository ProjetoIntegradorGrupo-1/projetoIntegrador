; Script de Instalador Profissional Inno Setup
; Projeto Integrador IFES - Equipe AXION

[Setup]
AppName=AXION - Sistema de Vistoria e Checklist Veicular
AppVersion=1.0.0
AppPublisher=Equipe AXION (IFES)
AppPublisherURL=https://github.com/ProjetoIntegradorGrupo-1
DefaultDirName={drive:C}\xampp\htdocs\projetoIntegrador
DefaultGroupName=AXION
OutputDir=c:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador
OutputBaseFilename=Instalador_Axion
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=lowest
DisableProgramGroupPage=yes

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"

[Files]
Source: "c:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\frontend\*"; DestDir: "{app}\frontend"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "c:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\backend\*"; DestDir: "{app}\backend"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "c:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\database\*"; DestDir: "{app}\database"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "c:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\Axion_Instalador_e_Iniciador_Local.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "c:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\Axion_Aiven.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "c:\Users\PC\Desktop\ExerciciosHtml\projetoIntegrador\README.md"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{autoprograms}\AXION\Iniciar AXION (Local)"; Filename: "{app}\Axion_Instalador_e_Iniciador_Local.exe"
Name: "{autoprograms}\AXION\Iniciar AXION (Nuvem Aiven)"; Filename: "{app}\Axion_Aiven.exe"
Name: "{autodesktop}\AXION (Local)"; Filename: "{app}\Axion_Instalador_e_Iniciador_Local.exe"; Tasks: desktopicon
Name: "{autodesktop}\AXION (Nuvem Aiven)"; Filename: "{app}\Axion_Aiven.exe"; Tasks: desktopicon

[Run]
Filename: "{app}\Axion_Instalador_e_Iniciador_Local.exe"; Description: "Configurar banco de dados e iniciar AXION agora"; Flags: nowait postinstall skipifsilent

