using System;
using System.Diagnostics;
using System.IO;
using System.Text;
using System.Threading;

namespace AxionAivenLauncher
{
    class Program
    {
        private static Process phpProcess = null;

        static void Main(string[] args)
        {
            Console.OutputEncoding = Encoding.UTF8;
            Console.Title = "AXION - Sistema de Vistoria (Nuvem Aiven Cloud)";

            PrintHeader();

            string projectRoot = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\', '/');
            if (File.Exists(Path.Combine(projectRoot, "..", "backend", "conexao.php")))
            {
                projectRoot = Path.GetFullPath(Path.Combine(projectRoot, ".."));
            }

            Console.ForegroundColor = ConsoleColor.Cyan;
            Console.WriteLine("[INFO] Diretório do projeto: " + projectRoot);
            Console.ResetColor();

            // 1. Localizar PHP
            string phpPath = FindPhpExecutable();
            if (string.IsNullOrEmpty(phpPath))
            {
                PrintError("PHP não foi encontrado em C:\\xampp\\php\\php.exe nem no PATH do sistema.");
                Console.WriteLine("Por favor, instale o XAMPP ou adicione o PHP ao PATH do Windows.");
                WaitExit();
                return;
            }
            PrintSuccess("Runtime PHP localizado: " + phpPath);

            // 2. Verificar Certificado SSL da Aiven
            string caCertPath = Path.Combine(projectRoot, "database", "ca.pem");
            if (File.Exists(caCertPath))
            {
                PrintSuccess("Certificado SSL da Nuvem localizado: database\\ca.pem");
            }
            else
            {
                PrintWarning("Certificado ca.pem não encontrado em database\\ca.pem.");
                Console.WriteLine("   -> A conexão SSL com a Aiven pode falhar sem o certificado.");
            }

            // 3. Ativar modo Nuvem
            Console.ForegroundColor = ConsoleColor.Yellow;
            Console.WriteLine("[CONFIGURAÇÃO] Ativando modo Aiven Cloud (USE_AIVEN=1)...");
            Console.ResetColor();
            Console.WriteLine("   • Host: mysql-364b144d-isabela-29db.j.aivencloud.com");
            Console.WriteLine("   • Porta: 28487 (SSL)");
            Console.WriteLine("   • Base: defaultdb");
            Console.WriteLine("   • Vantagem: Não requer MySQL rodando nesta máquina!");

            // 4. Iniciar Servidor Web embutido com variável USE_AIVEN=1
            try
            {
                ProcessStartInfo phpPsi = new ProcessStartInfo();
                phpPsi.FileName = phpPath;
                phpPsi.Arguments = "-S localhost:8000 -t \"" + projectRoot + "\"";
                phpPsi.WorkingDirectory = projectRoot;
                phpPsi.UseShellExecute = false;
                phpPsi.CreateNoWindow = true;

                // Injeta variável de ambiente para que conexao.php conecte na nuvem
                phpPsi.EnvironmentVariables["USE_AIVEN"] = "1";

                phpProcess = Process.Start(phpPsi);

                Thread.Sleep(1200);

                PrintSuccess("Servidor Web PHP ativo em: http://localhost:8000");

                // Abrir navegador
                string appUrl = "http://localhost:8000/frontend/index.html";
                Console.WriteLine("   -> Abrindo navegador padrão em: " + appUrl);
                Process.Start(new ProcessStartInfo(appUrl) { UseShellExecute = true });
            }
            catch (Exception ex)
            {
                PrintError("Erro ao iniciar servidor PHP: " + ex.Message);
                WaitExit();
                return;
            }

            PrintFooter();

            AppDomain.CurrentDomain.ProcessExit += (s, e) => Cleanup();
            Console.CancelKeyPress += (s, e) => { Cleanup(); Environment.Exit(0); };

            while (true)
            {
                ConsoleKeyInfo key = Console.ReadKey(true);
                if (key.Key == ConsoleKey.Q || (key.Modifiers == ConsoleModifiers.Control && key.Key == ConsoleKey.C))
                {
                    break;
                }
            }

            Cleanup();
        }

        private static void Cleanup()
        {
            try
            {
                if (phpProcess != null && !phpProcess.HasExited)
                {
                    phpProcess.Kill();
                }
            }
            catch { }
        }

        private static string FindPhpExecutable()
        {
            string defaultXampp = @"C:\xampp\php\php.exe";
            if (File.Exists(defaultXampp)) return defaultXampp;

            string[] paths = Environment.GetEnvironmentVariable("PATH").Split(';');
            foreach (string p in paths)
            {
                string candidate = Path.Combine(p.Trim(), "php.exe");
                if (File.Exists(candidate)) return candidate;
            }
            return null;
        }

        private static void PrintHeader()
        {
            Console.ForegroundColor = ConsoleColor.Cyan;
            Console.WriteLine(@"========================================================================");
            Console.WriteLine(@"        AXION - SISTEMA DE VISTORIA E CHECKLIST VEICULAR                ");
            Console.WriteLine(@"        Modo Cloud: Banco de Dados em Nuvem (Aiven MySQL com SSL)       ");
            Console.WriteLine(@"========================================================================");
            Console.ResetColor();
            Console.WriteLine();
        }

        private static void PrintFooter()
        {
            Console.WriteLine();
            Console.ForegroundColor = ConsoleColor.Cyan;
            Console.WriteLine(@"========================================================================");
            Console.WriteLine(@" [NUVEM ATIVA] Sistema conectado ao banco Aiven MySQL em produção!      ");
            Console.WriteLine(@" Acesse no navegador: http://localhost:8000                             ");
            Console.WriteLine(@"========================================================================");
            Console.ResetColor();
            Console.ForegroundColor = ConsoleColor.White;
            Console.WriteLine(@" Contas Homologadas na Nuvem:");
            Console.WriteLine(@"  • Administrador : Admin@teste.com       (senha: senha_teste)");
            Console.WriteLine(@"  • Gestor        : Gestor@teste.com      (senha: senha_teste)");
            Console.WriteLine(@"  • Funcionário   : funcionario@teste.com (senha: senha_teste)");
            Console.WriteLine();
            Console.ForegroundColor = ConsoleColor.DarkGray;
            Console.WriteLine(@" Pressione 'Q' ou CTRL+C para encerrar o servidor.");
            Console.ResetColor();
        }

        private static void PrintSuccess(string msg)
        {
            Console.ForegroundColor = ConsoleColor.Green;
            Console.WriteLine(" [OK] " + msg);
            Console.ResetColor();
        }

        private static void PrintWarning(string msg)
        {
            Console.ForegroundColor = ConsoleColor.Yellow;
            Console.WriteLine(" [AVISO] " + msg);
            Console.ResetColor();
        }

        private static void PrintError(string msg)
        {
            Console.ForegroundColor = ConsoleColor.Red;
            Console.WriteLine(" [ERRO] " + msg);
            Console.ResetColor();
        }

        private static void WaitExit()
        {
            Console.WriteLine("\nPressione qualquer tecla para sair...");
            Console.ReadKey();
        }
    }
}

