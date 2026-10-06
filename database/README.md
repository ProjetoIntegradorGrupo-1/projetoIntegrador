# Banco de Dados - Sistema Axion

Este diretório contém a modelagem relacional completa e os scripts de inicialização do banco de dados MySQL para o **Sistema Axion**.

---

## 📂 Arquivos Disponíveis

| Arquivo | Descrição |
|---|---|
| [`schema.sql`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/database/schema.sql) | DDL completo para criação do banco `axion_db` e das 9 tabelas relacionais com chaves primárias, estrangeiras e índices. |
| [`seeds.sql`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/database/seeds.sql) | Carga inicial com usuários padrões (administrador, supervisor, motorista com senhas hash bcrypt), veículos e modelo de checklist com subpergunta condicional. |

---

## 🚀 Como Importar no XAMPP / MySQL

### Opção 1: Via phpMyAdmin (Navegador)
1. Abra o **XAMPP Control Panel** e inicie o serviço **MySQL** (e **Apache**).
2. Acesse no navegador: [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Clique na aba **Importar** (Import).
4. Selecione o arquivo `database/schema.sql` e clique em **Executar**.
5. Repita o processo selecionando o arquivo `database/seeds.sql`.

### Opção 2: Via Terminal / Linha de Comando (PowerShell)
Execute no terminal na raiz do projeto:

```powershell
# Criação do banco e tabelas:
Get-Content database/schema.sql | C:\xampp\mysql\bin\mysql.exe -u root

# Inserção dos dados de teste:
Get-Content database/seeds.sql | C:\xampp\mysql\bin\mysql.exe -u root
```

---

## 🔑 Credenciais Padrão Inseridas pelas Seeds

| Perfil | E-mail | CPF / Matrícula | Senha |
|---|---|---|---|
| **Gestor / Admin** | `admin@axion.com` | `000.000.000-00` | `admin123` |
| **Supervisor** | `supervisor@axion.com` | `111.111.111-11` | `mudar123` |
| **Motorista** | `motorista@axion.com` | `222.222.222-22` | `mudar123` |

> Todas as senhas foram geradas utilizando o algoritmo padrão do PHP (`password_hash`), 100% compatíveis com a verificação de login em `processar_login.php`.

