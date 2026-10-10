using System;
using System.Diagnostics;
using System.IO;
using System.Net.Sockets;
using System.Text;
using System.Threading;

namespace AxionLauncher
{
    class Program
    {
        private static Process phpProcess = null;
        private static Process mysqlProcess = null;

        static void Main(string[] args)
        {
            Console.OutputEncoding = Encoding.UTF8;
            Console.Title = "AXION - Sistema de Vistoria e Checklist Veicular (Local)";

            PrintHeader();

            string projectRoot = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\', '/');
            // If running inside scripts or bin folder, navigate up to project root
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
            PrintSuccess("PHP localizado: " + phpPath);

            // 2. Localizar MySQL
            string mysqlPath = FindMysqlExecutable();
            string mysqldPath = FindMysqldExecutable();
            if (string.IsNullOrEmpty(mysqlPath))
            {
                PrintWarning("Cliente mysql.exe não encontrado em C:\\xampp\\mysql\\bin nem no PATH.");
            }
            else
            {
                PrintSuccess("MySQL CLI localizado: " + mysqlPath);
            }

            // 3. Garantir que o MySQL esteja em execução
            Console.ForegroundColor = ConsoleColor.Yellow;
            Console.WriteLine("[ETAPA 1/3] Verificando serviço MySQL local (porta 3306)...");
            Console.ResetColor();

            bool mysqlRunning = IsPortOpen("127.0.0.1", 3306);
            if (!mysqlRunning && !string.IsNullOrEmpty(mysqldPath))
            {
                Console.WriteLine("   -> MySQL não está em execução. Iniciando serviço automaticamente...");
                string iniPath = Path.Combine(Path.GetDirectoryName(mysqldPath), "my.ini");
                string myArgs = File.Exists(iniPath) ? "--defaults-file=\"" + iniPath + "\" --standalone" : "--standalone";
                
                try
                {
                    ProcessStartInfo psi = new ProcessStartInfo();
                    psi.FileName = mysqldPath;
                    psi.Arguments = myArgs;
                    psi.WindowStyle = ProcessWindowStyle.Hidden;
                    psi.CreateNoWindow = true;
                    psi.UseShellExecute = false;
                    mysqlProcess = Process.Start(psi);

                    // Aguardar inicialização
                    int attempts = 0;
                    while (attempts < 15)
                    {
                        Thread.Sleep(800);
                        if (IsPortOpen("127.0.0.1", 3306))
                        {
                            mysqlRunning = true;
                            break;
                        }
                        attempts++;
                    }
                }
                catch (Exception ex)
                {
                    PrintWarning("Falha ao inicializar mysqld automaticamente: " + ex.Message);
                }
            }

            if (mysqlRunning)
            {
                PrintSuccess("Serviço MySQL está ativo e respondendo na porta 3306.");
            }
            else
            {
                PrintWarning("Não foi possível conectar ao MySQL local na porta 3306.");
                Console.WriteLine("   -> Se estiver usando XAMPP, abra o Painel de Controle e inicie o MySQL.");
            }

            // 4. Configurar e popular Banco de Dados
            if (mysqlRunning && !string.IsNullOrEmpty(mysqlPath))
            {
                Console.ForegroundColor = ConsoleColor.Yellow;
                Console.WriteLine("[ETAPA 2/3] Verificando integridade do banco de dados axion_db...");
                Console.ResetColor();

                string schemaFile = Path.Combine(projectRoot, "database", "schema.sql");
                string seedsFile = Path.Combine(projectRoot, "database", "seeds.sql");

                bool dbExists = CheckDatabaseExists(mysqlPath, "axion_db");
                if (!dbExists)
                {
                    Console.WriteLine("   -> Banco axion_db não encontrado. Criando base de dados...");
                    ExecuteMysqlCommand(mysqlPath, "CREATE DATABASE IF NOT EXISTS axion_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                    if (File.Exists(schemaFile))
                    {
                        Console.WriteLine("   -> Importando estrutura de tabelas (schema.sql)...");
                        ImportSqlFile(mysqlPath, "axion_db", schemaFile);
                        PrintSuccess("Tabelas criadas com sucesso!");
                    }

                    if (File.Exists(seedsFile))
                    {
                        Console.WriteLine("   -> Inserindo dados iniciais e usuários de teste (seeds.sql)...");
                        ImportSqlFile(mysqlPath, "axion_db", seedsFile);
                        PrintSuccess("Dados iniciais inseridos com sucesso!");
                    }
                }
                else
                {
                    PrintSuccess("Banco de dados axion_db já existe e está pronto.");
                }
            }

            // 5. Iniciar Servidor Embutido do PHP
            Console.ForegroundColor = ConsoleColor.Yellow;
            Console.WriteLine("[ETAPA 3/3] Inicializando servidor web em http://localhost:8000...");
            Console.ResetColor();

            try
            {
                ProcessStartInfo phpPsi = new ProcessStartInfo();
                phpPsi.FileName = phpPath;
                phpPsi.Arguments = "-S localhost:8000 -t \"" + projectRoot + "\"";
                phpPsi.WorkingDirectory = projectRoot;
                phpPsi.UseShellExecute = false;
                phpPsi.CreateNoWindow = true;
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

            // Hook para encerramento gracioso
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

        private static bool IsPortOpen(string host, int port)
        {
            try
            {
                using (var client = new TcpClient())
                {
                    var result = client.BeginConnect(host, port, null, null);
                    bool success = result.AsyncWaitHandle.WaitOne(800);
                    if (success)
                    {
                        client.EndConnect(result);
                        return true;
                    }
                    return false;
                }
            }
            catch
            {
                return false;
            }
        }

        private static bool CheckDatabaseExists(string mysqlPath, string dbName)
        {
            try
            {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = mysqlPath;
                psi.Arguments = "-u root -e \"SHOW DATABASES LIKE '" + dbName + "';\"";
                psi.RedirectStandardOutput = true;
                psi.UseShellExecute = false;
                psi.CreateNoWindow = true;
                using (Process p = Process.Start(psi))
                {
                    string output = p.StandardOutput.ReadToEnd();
                    p.WaitForExit();
                    return output.Contains(dbName);
                }
            }
            catch
            {
                return false;
            }
        }

        private static void ExecuteMysqlCommand(string mysqlPath, string sql)
        {
            try
            {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = mysqlPath;
                psi.Arguments = "-u root -e \"" + sql + "\"";
                psi.UseShellExecute = false;
                psi.CreateNoWindow = true;
                using (Process p = Process.Start(psi))
                {
                    p.WaitForExit();
                }
            }
            catch { }
        }

        private static void ImportSqlFile(string mysqlPath, string dbName, string filePath)
        {
            try
            {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = mysqlPath;
                psi.Arguments = "-u root " + dbName;
                psi.RedirectStandardInput = true;
                psi.UseShellExecute = false;
                psi.CreateNoWindow = true;
                using (Process p = Process.Start(psi))
                {
                    using (StreamReader reader = new StreamReader(filePath, Encoding.UTF8))
                    {
                        string content = reader.ReadToEnd();
                        p.StandardInput.Write(content);
                        p.StandardInput.Flush();
                        p.StandardInput.Close();
                    }
                    p.WaitForExit();
                }
            }
            catch (Exception ex)
            {
                PrintWarning("Aviso na importação de " + Path.GetFileName(filePath) + ": " + ex.Message);
            }
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

        private static string FindMysqlExecutable()
        {
            string defaultXampp = @"C:\xampp\mysql\bin\mysql.exe";
            if (File.Exists(defaultXampp)) return defaultXampp;

            string[] paths = Environment.GetEnvironmentVariable("PATH").Split(';');
            foreach (string p in paths)
            {
                string candidate = Path.Combine(p.Trim(), "mysql.exe");
                if (File.Exists(candidate)) return candidate;
            }
            return null;
        }

        private static string FindMysqldExecutable()
        {
            string defaultXampp = @"C:\xampp\mysql\bin\mysqld.exe";
            if (File.Exists(defaultXampp)) return defaultXampp;
            return null;
        }

        private static void PrintHeader()
        {
            Console.ForegroundColor = ConsoleColor.Green;
            Console.WriteLine(@"========================================================================");
            Console.WriteLine(@"        AXION - SISTEMA DE VISTORIA E CHECKLIST VEICULAR                ");
            Console.WriteLine(@"        Iniciador e Instalador Automático Local (ScrumAIDev / IFES)     ");
            Console.WriteLine(@"========================================================================");
            Console.ResetColor();
            Console.WriteLine();
        }

        private static void PrintFooter()
        {
            Console.WriteLine();
            Console.ForegroundColor = ConsoleColor.Green;
            Console.WriteLine(@"========================================================================");
            Console.WriteLine(@" [SISTEMA EM EXECUÇÃO] Acesse no navegador: http://localhost:8000       ");
            Console.WriteLine(@"========================================================================");
            Console.ResetColor();
            Console.ForegroundColor = ConsoleColor.White;
            Console.WriteLine(@" Contas de Acesso Homologadas:");
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

