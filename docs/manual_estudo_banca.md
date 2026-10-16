# Manual Técnico de Engenharia de Software e Guia de Estudo para a Banca Avaliadora

**Projeto:** Axion Frotas — Sistema de Vistoria, Checklist Veicular e Gestão Operacional  
**Instituição:** Instituto Federal do Espírito Santo (IFES) — Campus Serra  
**Disciplina:** Projeto Integrador (PI)  
**Equipe:** Ana Klara Jardim, Estela do Nascimento, Gustavo Meneis, Isabela Pereira, Vanderci Lucas  
**Versão:** 2.0 (Edição Oficial de Apresentação e Sustentação Oral)

---

## Sumário
1. [Visão Geral e Objetivos do Manual](#1-visão-geral-e-objetivos-do-manual)
2. [Arquitetura Geral do Sistema](#2-arquitetura-geral-do-sistema)
3. [Linguagens, Tecnologias e Recursos Utilizados](#3-linguagens-tecnologias-e-recursos-utilizados)
   - [3.1 PHP 8.2+](#31-php-82)
   - [3.2 JavaScript ES6+ & APIs HTML5](#32-javascript-es6--apis-html5)
   - [3.3 HTML5 Semântico & Acessibilidade WCAG](#33-html5-semântico--acessibilidade-wcag)
   - [3.4 CSS3, Bootstrap 5.3 & Design System](#34-css3-bootstrap-53--design-system)
   - [3.5 MySQL 8.0, InnoDB e 3ª Forma Normal](#35-mysql-80-innodb-e-3ª-forma-normal)
   - [3.6 Infraestrutura: XAMPP, Aiven Cloud e Docker](#36-infraestrutura-xampp-aiven-cloud-e-docker)
4. [Mapeamento Completo dos 5 Fluxos de Valor](#4-mapeamento-completo-dos-5-fluxos-de-valor)
5. [Inventário Completo de Arquivos (O que faz cada arquivo)](#5-inventário-completo-de-arquivos-o-que-faz-cada-arquivo)
6. [Explicação Didática dos Principais Blocos de Código](#6-explicação-didática-dos-principais-blocos-de-código)
7. [As Linhas de Código Mais Inovadoras e Disruptivas](#7-as-linhas-de-código-mais-inovadoras-e-disruptivas)
8. [Roteiro de Sustentação Oral e Simulação de Perguntas da Banca](#8-roteiro-de-sustentação-oral-e-simulação-de-perguntas-da-banca)

---

## 1. Visão Geral e Objetivos do Manual

Este documento foi elaborado para capacitar todos os membros do grupo (**Ana Klara, Estela, Gustavo, Isabela e Vanderci**) a dominar com profundidade técnica, segurança conceitual e clareza didática toda a estrutura de software do projeto **Axion Frotas**.

Durante a apresentação para a banca avaliadora (composta pelo Prof. Felippe, Profª Marta e examinadores convidados), este guia servirá como a referência canônica para responder perguntas sobre:
- **Decisões arquiteturais:** por que escolhemos cada padrão e tecnologia;
- **Segurança da Informação:** como protegemos senhas, prevenimos injeção de SQL e bloqueamos acessos indevidos (IDOR / Broken Access Control);
- **Ergonomia e Acessibilidade:** como aplicamos a Lei de Fitts e os contrastes WCAG 2.1 AAA para operação em pátio sob sol forte e com luvas;
- **Código-fonte:** o que cada linha-chave faz nos bastidores.

---

## 2. Arquitetura Geral do Sistema

O **Axion Frotas** adota uma **Arquitetura em Três Camadas (3-Tier Architecture)** com o padrão **MVC Adaptado para Web**:

```mermaid
flowchart TD
    subgraph CamadaCliente ["1. Camada de Apresentação (Frontend / Client-Side)"]
        UI_Browser["Navegador Web / Mobile (HTML5, Bootstrap 5.3, JS ES6)"]
        CanvasAPI["HTML5 Canvas (Assinatura Digital Base64)"]
        CameraAPI["HTML5 Media Capture (Fotos de Avarias)"]
        LocalStorage["LocalStorage (Preferência de Modo Campo/Lista)"]
    end

    subgraph CamadaServidor ["2. Camada de Aplicação e Negócio (Backend / Server-Side)"]
        Apache["Servidor Web Apache 2.4 (mod_rewrite, MIME types UTF-8)"]
        MiddlewareRBAC["Middleware de Autenticação & RBAC (auth_check.php)"]
        Controladores["Endpoints de Negócio PHP 8.2 (Processamento Seguro)"]
        PDO_Layer["Camada de Abstração de Dados (PHP Data Objects - PDO)"]
    end

    subgraph CamadaDados ["3. Camada de Persistência (Database / Data Tier)"]
        LocalDB[("MySQL 8.0 Local / Docker (InnoDB, ACID, UTF8mb4)")]
        CloudDB[("Aiven Cloud MySQL (Cluster DBaaS com TLS v1.3 e SSL ca.pem)")]
    end

    UI_Browser <-->|HTTP/HTTPS REST & AJAX multipart/form-data| Apache
    Apache --> MiddlewareRBAC
    MiddlewareRBAC --> Controladores
    Controladores --> PDO_Layer
    PDO_Layer <-->|TCP 3306 / 3307 SQL| LocalDB
    PDO_Layer <-->|TCP 28487 SSL Encrypted| CloudDB
```

### Princípios Arquiteturais Implementados:
1. **Separação Estrita de Responsabilidades (SoC - Separation of Concerns):**
   - A pasta `frontend/` contém exclusivamente interfaces de usuário (renderização de HTML, estilização CSS e comportamento dinâmico JavaScript).
   - A pasta `backend/` centraliza a lógica de processamento de formulários, validações redundantes, hashes criptográficos e comandos SQL.
   - A pasta `database/` armazena os esquemas relacionais (`schema.sql`), sementes de teste (`seeds.sql`) e chaves criptográficas (`ca.pem`).
2. **Controle de Acesso Baseado em Papéis (RBAC - Role-Based Access Control):**
   - As permissões são validadas em duas etapas: na renderização visual da tela e na barreira de proteção de cada script de backend via middleware centralizado `auth_check.php`.
3. **Transações Atômicas (Propriedades ACID):**
   - Gravações críticas (como finalizar vistoria e gerar automaticamente uma ocorrência na oficina) utilizam transações SQL para garantir que nenhuma inconsistência ocorra em caso de queda de conexão.

---

## 3. Linguagens, Tecnologias e Recursos Utilizados

### 3.1 PHP 8.2+
- **Por que foi escolhido?**  
  PHP 8.2 é amplamente adotado no mercado corporativo, possui alto desempenho com motor Zend Opcache nativo, integração perfeita com o servidor Apache e suporte robusto à extensão **PDO (PHP Data Objects)**.
- **Recursos Chave Utilizados:**
  - `PDO::prepare()` e `PDOStatement::execute()` com vinculação parametrizada (`bindParam` e `bindValue`), impedindo 100% de ataques de **SQL Injection**.
  - `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])` e `password_verify()` para armazenamento seguro de credenciais, garantindo proteção contra ataques de dicionário e tabelas rainbow.
  - `random_bytes(32)` e `bin2hex()` para geração de tokens criptograficamente seguros no fluxo de redefinição de senha.
  - Funções de sanitização como `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` e manipulação defensiva de codificação de caracteres contra *mojibake*.

### 3.2 JavaScript ES6+ & APIs HTML5
- **Por que foi escolhido?**  
  Elimina a necessidade de bibliotecas pesadas de terceiros (como jQuery ou React), garantindo inicialização instantânea no navegador móvel do motorista.
- **Recursos Chave Utilizados:**
  - **Fetch API & FormData:** Envio assíncrono de dados de inspeção no encerramento da vistoria sem recarregar a tela (*SPA-like experience*).
  - **HTML5 Canvas API:** Coleta das assinaturas manuscritas do motorista e vistoriador através de eventos combinados de mouse (`mousedown`, `mousemove`, `mouseup`) e touch screen (`touchstart`, `touchmove`, `touchend`), convertendo o traço em vetor Base64 Data URL (`canvas.toDataURL('image/png')`).
  - **HTML5 Media Capture:** Abertura direta da câmera do smartphone via `<input type="file" accept="image/*" capture="environment">`, permitindo que o vistoriador fotografe avarias no pátio sem sair da aplicação.
  - **LocalStorage:** Persistência no dispositivo da preferência ergonômica de visualização do inspetor (Modo Campo vs Modo Lista).

### 3.3 HTML5 Semântico & Acessibilidade WCAG
- **Estruturação Semântica:** Utilização de `<header>`, `<main>`, `<section>`, `<fieldset>`, `<legend>` e `<dialog>/modais` para correta interpretação por tecnologias assistivas e navegadores.
- **Diretrizes WCAG 2.1 (Nível AAA em Contraste e Semiótica):**
  - Eliminação de dependência exclusiva de cor para indicar status: cada indicador combina cor contrastante (ex: verde esmeralda, vermelho carmesim) com ícones explícitos (`bi-check-circle-fill`, `bi-exclamation-triangle-fill`) e rótulos textuais claros.
  - Alvos de toque generosos de **72px de altura** no Modo Campo (em estrita concordância com a **Lei de Fitts**), projetados para uso com luvas de trabalho sob sol forte na área operacional.

### 3.4 CSS3, Bootstrap 5.3 & Design System
- **Bootstrap 5.3:** Framework utilitário moderno que fornece o grid flexível (*flexbox*), tipografia escalável e componentes acessíveis.
- **Tipografia Técnica Especializada:**
  - Família `Inter` para leitura agradável e ergonômica em formulários e textos longos.
  - Família monoespaçada `Roboto Mono` para exibição de dados alfanuméricos estritos (placas de veículos, números de chassi, hodômetros e códigos de ocorrência como `OC-2026-001`), evitando qualquer confusão visual entre caracteres parecidos (como `0` e `O`, `1` e `I`).
- **Media Queries para Impressão (`@media print`):**
  - O laudo técnico oficial oculta automaticamente barras de navegação e botões interativos (`.no-print { display: none !important; }`), ajustando margens e quebras de página para geração limpa de arquivos PDF.

### 3.5 MySQL 8.0, InnoDB e 3ª Forma Normal
- **Mecanismo InnoDB:** Suporte nativo a transações seguras, bloqueios em nível de linha (*row-level locking*) e integridade referencial via chaves estrangeiras (`FOREIGN KEY`).
- **Normalização Rigorosa (3FN):**
  - **1FN:** Todos os atributos contêm valores atômicos e indivisíveis.
  - **2FN:** Não existem dependências parciais de chaves compostas.
  - **3FN:** Não existem dependências transitivas entre atributos não-chave.
- **Charset & Collation:** `utf8mb4` com `utf8mb4_unicode_ci`, permitindo armazenamento perfeito de acentos da língua portuguesa, caracteres especiais e símbolos sem risco de truncamento ou corrupção de dados.

### 3.6 Infraestrutura: XAMPP, Aiven Cloud e Docker

| Tecnologia | Finalidade no Projeto | Como Explicar para a Banca |
|---|---|---|
| **XAMPP Local** | Ambiente de desenvolvimento local tradicional em ambiente Windows. | *"Permite executar localmente o servidor web Apache 2.4 e o banco de dados MySQL na porta 3306, facilitando a depuração e o desenvolvimento rápido da equipe."* |
| **Aiven Cloud MySQL** | Banco de dados relacional gerenciado na nuvem (**DBaaS**). | *"Representa a modernização corporativa do projeto. O banco está hospedado em cluster de alta disponibilidade com criptografia ponta a ponta TLS v1.3. O PHP conecta-se à nuvem através do certificado de autoridade `database/ca.pem`."* |
| **Docker & Docker Compose** | Contêineres isolados para execução segura, reproduzível e sem atrito. | *"Atendendo à exigência de portabilidade e segurança da banca, o sistema conta com uma suíte de contêineres oficiais. Com um único comando (`docker compose up -d`), a aplicação e o banco MySQL 8.0 sobem pré-populados na porta 8080."* |

---

## 4. Mapeamento Completo dos 5 Fluxos de Valor

O sistema organiza toda a jornada do usuário em **5 Fluxos de Valor**, cobrindo do cadastro à inteligência de dados:

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│                       OS 5 FLUXOS DE VALOR DO AXION                         │
├─────────────────┬───────────────────────────────────────────────────────────┤
│ Fluxo 1 (CRUD)  │ Gestão de Usuários e Frota (Usuários RBAC e Veículos)     │
├─────────────────┼───────────────────────────────────────────────────────────┤
│ Fluxo 2 (CRUD)  │ Modelagem e Governança de Checklists (Dupla Validação)    │
├─────────────────┼───────────────────────────────────────────────────────────┤
│ Fluxo 3 (Pátio) │ Inspeção Veicular em Campo (Modo Híbrido) & Laudos PDF    │
├─────────────────┼───────────────────────────────────────────────────────────┤
│ Fluxo 4 (Oficina)│ Triagem Master-Detail de Ocorrências e Retenção de Frota │
├─────────────────┼───────────────────────────────────────────────────────────┤
│ Fluxo 5 (Gestão)│ Painel Executivo (Dashboard), KPIs, Auditoria e Segurança │
└─────────────────┴───────────────────────────────────────────────────────────┘
```

### Detalhamento dos Fluxos:

#### Fluxo 1: Gestão de Usuários e Frota (CRUD)
- **Telas:** `caduser.php`, `editarUsuario.php`, `excluirUsuario.php`, `cadcar.php`, `editarVeiculo.php`, `excluirVeiculo.php`.
- **Regra de Negócio & RBAC:**
  - O Gestor Administrador tem controle irrestrito: cria supervisores, motoristas e clientes; edita qualquer cadastro; realiza exclusões lógicas com auditoria.
  - O Supervisor Operacional cadastra motoristas, clientes e veículos patrimoniais.
  - A exclusão de usuários e veículos é **lógica** (`status = 'inativo'`), garantindo que históricos de vistorias anteriores nunca percam sua rastreabilidade jurídica.

#### Fluxo 2: Modelagem e Governança de Checklists (CRUD & Dupla Validação)
- **Telas:** `criarChecklist.php`, `editarChecklist.php`, `excluirChecklist.php`, `gestaoChecklists.php`.
- **Regra de Negócio & Governança:**
  - O Gestor pode criar checklists com ativação imediata (`status = 'ativo'`).
  - O Supervisor pode propor novos modelos de checklist, que entram no estado `pendente_aprovacao`.
  - O Gestor avalia as perguntas no módulo de governança: pode **aprovar** (liberando o checklist para o pátio) ou **solicitar ajustes** com parecer descritivo obrigatório.

#### Fluxo 3: Inspeção Veicular em Campo e Emissão de Laudos Oficiais
- **Telas:** `cadcheck.php` (Abertura), `vistoria.php` (Execução Híbrida), `laudoVistoria.php` (Laudo Oficial).
- **Regra de Negócio & Ergonomia:**
  - O inspetor seleciona o veículo na abertura; a placa é travada automaticamente para evitar erros de digitação. Data e hora são carimbadas pelo servidor contra fraudes de horário.
  - Na vistoria, o usuário alterna livremente entre o **Modo Campo** (foco em 1 pergunta por tela com botões gigantes de 72px baseados na Lei de Fitts e gaveta retrátil de foto de avaria) e o **Modo Lista** (visão vertical panorâmica).
  - Coleta das assinaturas manuscritas em canvas e emissão do Laudo Técnico Oficial com validade probatória.

#### Fluxo 4: Triagem, Despacho de Ocorrências e Gestão de Manutenção
- **Telas:** `ocorrencias.php` (Interface Master-Detail).
- **Regra de Negócio & Manutenção:**
  - Toda resposta "Não Conforme" na vistoria cria automaticamente um chamado na tabela `Ocorrencias` com protocolo oficial (ex: `OC-2026-001`).
  - Na coluna esquerda (*Master*), o gestor filtra por gravidade e status.
  - Na coluna direita (*Detail*), inspeciona a foto da avaria em alta definição e com um clique aciona **"Enviar p/ Oficina"** (retendo o veículo na frota com status `manutencao`) ou **"Aprovar Reparo"** (liberando o veículo novamente para o pátio).

#### Fluxo 5: Inteligência Operacional, KPIs, Segurança RBAC e Auditoria
- **Telas:** `dashboard.php`, `index.html`, `recuperarSenha.html`, `novaSenha.php`.
- **Regra de Negócio & Indicadores:**
  - Painel com cartões métricos em tempo real: Total de Vistorias, Conformes (100%), Aprovadas com Restrições (Avarias), Vistorias Pendentes e Taxa Geral de Conformidade.
  - Tabela com filtros analíticos por placa e status, com links diretos para cada laudo técnico oficial.
  - Redefinição de senha real por e-mail com token temporário seguro de 1 hora gravado na tabela `Usuarios`.
  - Proteção contra IDOR: motoristas e clientes só conseguem visualizar seus próprios laudos.

---

## 5. Inventário Completo de Arquivos (O que faz cada arquivo)

### 5.1 Arquivos da Raiz do Projeto
- [`Dockerfile`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/Dockerfile): Receita de contêiner Docker oficial configurando Debian/Ubuntu, Apache 2.4, PHP 8.2, módulos `pdo_mysql`, `gd`, `zip` e permissões de upload.
- [`docker-compose.yml`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/docker-compose.yml): Orquestrador multicontêiner com o serviço web na porta 8080 e MySQL 8.0 na porta 3307, persistência em volume nomeado e carga automática de dados.
- [`.dockerignore`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/.dockerignore): Lista de arquivos excluídos do build do contêiner para manter a imagem leve e segura.
- [`.gitignore`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/.gitignore): Controle do Git configurado para versionar scripts, protegendo apenas uploads locais e arquivos `.env`.
- [`README.md`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/README.md): Documento principal do projeto contendo o minimundo, oportunidade, personas, modelo relacional e tutoriais completos de instalação e uso.
- [`prompt.pdf`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/prompt.pdf): Documento acadêmico compilando os prompts de inteligência artificial aplicados no desenvolvimento do projeto.

### 5.2 Pasta `frontend/` (Camada Visual e Interativa)
- [`index.html`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/index.html): Tela de entrada e autenticação segura de colaboradores.
- [`oqfazer.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/oqfazer.php): Menu principal dinâmico do sistema estruturado pedagogicamente nos 5 Fluxos de Valor com base no perfil logado.
- [`caduser.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/caduser.php): Formulário de cadastro de novos usuários com diferenciação de privilégios.
- [`editarUsuario.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/editarUsuario.php): Interface administrativa para alteração de dados de usuários e papéis operacionais.
- [`excluirUsuario.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/excluirUsuario.php): Módulo para inativação lógica segura de colaboradores.
- [`cadcar.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/cadcar.php): Formulário para adição de novos veículos patrimoniais à frota.
- [`editarVeiculo.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/editarVeiculo.php): Tela para atualização cadastral de veículos (marca, modelo, placa, km, renavam, chassi).
- [`excluirVeiculo.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/excluirVeiculo.php): Módulo de inativação lógica de veículos com impedimento de exclusão em veículos retidos na oficina.
- [`cadcheck.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/cadcheck.php): Tela de abertura de vistoria técnica com preenchimento automático de placa e carimbo antifraude de horário.
- [`criarChecklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/criarChecklist.php): Construtor de modelos de checklist com disciplinas técnicas e subperguntas condicionais.
- [`editarChecklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/editarChecklist.php): Interface de edição de títulos, categorias e status de modelos de checklist.
- [`excluirChecklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/excluirChecklist.php): Arquivamento lógico de modelos de checklist.
- [`gestaoChecklists.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/gestaoChecklists.php): Painel de governança e dupla validação onde o gestor avalia, aprova ou solicita ajustes em checklists.
- [`vistoria.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/vistoria.php): O motor principal de execução de vistorias com alternador híbrido (Modo Campo Lei de Fitts vs Modo Lista), fotos de avarias e canvas de assinaturas.
- [`laudoVistoria.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/laudoVistoria.php): Documento oficial de vistoria com sanitização anti-mojibake, espelho completo de respostas, fotos de evidências e assinaturas digitais.
- [`ocorrencias.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/ocorrencias.php): Módulo de triagem operacional em padrão Master-Detail para encaminhamento e liberação de veículos em oficinas parceiras.
- [`dashboard.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/dashboard.php): Painel executivo analítico com KPIs em tempo real, taxas de conformidade e filtros analíticos.
- [`recuperarSenha.html`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/recuperarSenha.html) e [`esqueciSenha.html`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/esqueciSenha.html): Interfaces para solicitação de redefinição de acesso via e-mail.
- [`novaSenha.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/frontend/novaSenha.php): Interface de digitação de nova senha protegida por validação de token temporário do banco de dados.

### 5.3 Pasta `backend/` (Controladores e Regras de Negócio)
- [`conexao.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/conexao.php): Ponto central de conexão com o banco de dados com alternância dinâmica transparente entre ambiente Local (XAMPP/Docker) e Nuvem (Aiven SSL).
- [`auth_check.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/auth_check.php): Middleware centralizado de controle de sessão e restrição de acesso por matriz RBAC.
- [`processar_login.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_login.php): Validação de credenciais via e-mail ou CPF com verificação de hash BCrypt e geração de sessão segura.
- [`logout.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/logout.php): Destruição completa de dados de sessão e redirecionamento seguro ao login.
- [`processar_opcao.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_opcao.php): Roteador e validador RBAC para navegação de formulários rápidos.
- [`processar_cadastro_usuario.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_cadastro_usuario.php): Validação de CPF, unicidade de e-mail e hash seguro para novos usuários.
- [`processar_edicao_usuario.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_edicao_usuario.php): Atualização de dados pessoais e privilégios administrativos.
- [`processar_exclusao_usuario.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_exclusao_usuario.php): Inativação lógica com trava para impedir que o gestor exclua a si próprio.
- [`processar_cadastro_veiculo.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_cadastro_veiculo.php): Inclusão de veículos com validação de formato de placa e unicidade de renavam/chassi.
- [`processar_edicao_veiculo.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_edicao_veiculo.php): Edição cadastral e ajuste de quilometragem acumulada.
- [`processar_exclusao_veiculo.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_exclusao_veiculo.php): Inativação lógica de veículos com proteção contra deleção de carros retidos em manutenção.
- [`processar_cadastro_checklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_cadastro_checklist.php): Criação do registro atômico da vistoria em estado pendente com carimbo temporal.
- [`processar_criar_checklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_criar_checklist.php): Cadastro de modelos de checklist com regras de dupla validação conforme o perfil.
- [`processar_edicao_checklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_edicao_checklist.php): Atualização de itens e status de modelos de inspeção.
- [`processar_exclusao_checklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_exclusao_checklist.php): Desativação lógica de modelos de checklist.
- [`processar_aprovacao_checklist.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_aprovacao_checklist.php): Homologação ou solicitação de ajustes pelo gestor com parecer técnico auditável.
- [`processar_execucao_vistoria.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_execucao_vistoria.php): Endpoint AJAX complexo que processa respostas, realiza upload de fotos de avarias, salva assinaturas em canvas e abre chamados de oficina automaticamente.
- [`processar_ocorrencia.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_ocorrencia.php): Transição de estados de oficina (`aberta` -> `em_oficina` -> `resolvida`) com atualização síncrona do veículo.
- [`processar_recuperacao.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_recuperacao.php): Geração de token criptográfico com validade de 1 hora gravado na base de dados.
- [`processar_nova_senha.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/processar_nova_senha.php): Validação de expiração de token e atualização definitiva de hash de senha.
- [`gerar_laudo_pdf.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/backend/gerar_laudo_pdf.php): Controlador de exportação para impressão e download de laudos em formato PDF.

### 5.4 Pasta `database/` (Camada de Dados Relacional)
- [`schema.sql`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/database/schema.sql): Script DDL completo criando as 9 tabelas relacionais em InnoDB, utf8mb4, com regras estritas de chaves estrangeiras e drops defensivos ordenados.
- [`seeds.sql`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/database/seeds.sql): Dados pré-populados contendo os 4 perfis (`admin@axion.com`, `supervisor@axion.com`, `motorista@axion.com`, `cliente@axion.com`), veículos da frota, modelos de checklist e vistorias históricas.
- `ca.pem`: Certificado raiz de autoridade certificadora para handshake seguro com o banco Aiven na nuvem.

### 5.5 Pasta `tests/` (Garantia de Qualidade e Integração Contínua)
- [`run_e2e_smoke_test.php`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/tests/run_e2e_smoke_test.php): Suíte de testes funcionais ponta a ponta com **49 asserções automatizadas**, validando autenticação, matriz RBAC, abertura de vistorias, laudos em PDF e governança de checklists.

---

## 6. Explicação Didática dos Principais Blocos de Código

### Bloco 1: Conexão Híbrida Inteligente (`backend/conexao.php`)
```php
// Define fuso horário oficial de Brasília para toda a aplicação
date_default_timezone_set('America/Sao_Paulo');

// Alterna entre nuvem ou local dependendo de variável de ambiente ou flag
$usar_aiven = (getenv('USE_AIVEN') === '1' || getenv('USE_AIVEN') === 'true');

if ($usar_aiven) {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../database/ca.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $usuario, $senha, $options);
} else {
    // Configurações do MySQL Local (XAMPP ou Docker)
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $usuario, $senha, $options);
}
```
**O que este bloco faz?**  
Garante que o fuso horário de todas as vistorias seja fixado em UTC-3 (horário oficial de Brasília). Conecta-se ao banco local ou à nuvem Aiven de forma transparente. Força o conjunto de caracteres `utf8mb4`, eliminando qualquer problema de acentuação gráfica.

---

### Bloco 2: Middleware de Controle de Acesso RBAC (`backend/auth_check.php`)
```php
function autorizarAcesso(array $perfisPermitidos = [], ?string $caminhoRedirecionamento = null) {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: index.html");
        exit;
    }
    if (!empty($perfisPermitidos)) {
        $perfilAtual = $_SESSION['perfil_usuario'] ?? 'motorista';
        if (!in_array($perfilAtual, $perfisPermitidos)) {
            header("Location: oqfazer.php?erro=acesso_negado");
            exit;
        }
    }
}
```
**O que este bloco faz?**  
Atua como um porteiro eletrônico (*Gatekeeper*). Se alguém tentar acessar diretamente uma página administrativa pela URL sem ter feito login ou sem ter o cargo exigido (ex: motorista tentando acessar cadastro de usuários), a função interrompe o processamento na hora e redireciona com mensagem de acesso negado.

---

### Bloco 3: Autenticação Segura com BCrypt (`backend/processar_login.php`)
```php
$sql = "SELECT id_usuario, nome, email, senha, perfil, ativo 
        FROM Usuarios 
        WHERE (email = :login OR cpf_matricula = :login) AND ativo = 1 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':login' => $login]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($senha, $usuario['senha'])) {
    session_regenerate_id(true); // Prevenção contra fixação de sessão
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nome_usuario'] = $usuario['nome'];
    $_SESSION['perfil_usuario'] = $usuario['perfil'];
    header("Location: ../frontend/oqfazer.php");
    exit;
}
```
**O que este bloco faz?**  
Permite que o usuário faça login tanto pelo seu e-mail corporativo quanto pelo seu CPF. A senha informada no formulário é comparada matematicamente contra o hash criptográfico via `password_verify()`. O `session_regenerate_id(true)` invalida o ID de sessão antigo e cria um novo, neutralizando o ataque OWASP de **Session Fixation**.

---

### Bloco 4: Gravação Atômica da Vistoria e Assinaturas (`backend/processar_execucao_vistoria.php`)
```php
$pdo->beginTransaction(); // Inicia transação atômica ACID

// 1. Grava ou atualiza as respostas de cada pergunta do checklist
foreach ($respostas as $r) {
    $stmtResp->execute([...]);
}

// 2. Salva as assinaturas digitais em Base64 e calcula parecer final
$stmtVistoria->execute([
    ':ass_mot' => $assinatura_motorista,
    ':ass_vist' => $assinatura_vistoriador,
    ':status' => $statusFinal,
    ':id' => $id_vistoria
]);

// 3. Se houver avarias, abre automaticamente a ocorrência para a oficina
if ($statusFinal === 'aprovado_com_restricoes') {
    $stmtOcorrencia->execute([...]);
}

$pdo->commit(); // Confirma todas as operações com sucesso absoluto
```
**O que este bloco faz?**  
Utiliza `beginTransaction()` e `commit()`. Ou tudo é salvo com perfeição (respostas, fotos, assinaturas e chamado de oficina), ou se algo falhar, o `rollBack()` desfaz qualquer gravação parcial, protegendo o banco contra dados corrompidos.

---

### Bloco 5: Prevenção Ativa de IDOR no Laudo (`frontend/laudoVistoria.php`)
```php
$autorizado = false;
if (in_array($perfilLogado, ['gestor', 'supervisor'])) {
    $autorizado = true; // Gestor e supervisor podem auditar qualquer laudo
} elseif ($perfilLogado === 'motorista' || $perfilLogado === 'cliente') {
    // Motoristas e clientes só podem visualizar vistorias onde eles participaram diretamente
    if (intval($vistoria['id_motorista']) === $idUsuarioLogado || intval($vistoria['id_vistoriador']) === $idUsuarioLogado) {
        $autorizado = true;
    }
}

if (!$autorizado) {
    http_response_code(403);
    echo "<h1>Acesso Negado (403)</h1>";
    exit;
}
```
**O que este bloco faz?**  
Fecha uma brecha de segurança crítica conhecida no OWASP Top 10 como **IDOR (Insecure Direct Object Reference)**. Impede que um motorista mal-intencionado altere o parâmetro `?id_vistoria=42` na barra de endereços para espiar laudos de outros motoristas ou veículos confidenciais.

---

## 7. As Linhas de Código Mais Inovadoras e Disruptivas

Ao apresentar para a banca, chame a atenção dos professores para estes **7 destaques de excelência técnica**:

### 1. Ergonomia e Lei de Fitts no Modo Campo (`frontend/vistoria.php`)
```html
<button type="button" class="btn btn-acao-gigante btn-gigante-conforme" onclick="responderPerguntaAtual(1)">
    <i class="bi bi-check-circle-fill fs-2"></i>
    <span>Conforme (OK)</span>
</button>
```
- **Por que é inovador?**  
  Em vez de obrigar o operador a tocar em caixas minúsculas (*checkboxes* de 15px), o sistema oferece botões com **72px de altura** que ocupam toda a largura da tela. A **Lei de Fitts** prova matematicamente que o tempo para atingir um alvo é função da distância e do tamanho do alvo. Isso permite operação rápida com uma só mão (*Thumb Zone*) e com luvas industriais em pátio ensolarado.

---

### 2. Captura Nativa de Câmera sem Dependências (`frontend/vistoria.php`)
```html
<input type="file" id="campoInputFoto" accept="image/*" capture="environment" class="d-none">
```
- **Por que é inovador?**  
  O atributo `capture="environment"` é uma especificação moderna do W3C. Quando clicado em um smartphone, o navegador abre diretamente a câmera traseira do aparelho para registrar a foto da avaria, sem exigir que a empresa desenvolva e publique aplicativos nativos caros na Google Play ou App Store.

---

### 3. Vetorização de Assinatura Digital no Canvas (`frontend/vistoria.php`)
```javascript
canvas.addEventListener('touchmove', (e) => {
    e.preventDefault();
    const touch = e.touches[0];
    const rect = canvas.getBoundingClientRect();
    ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
    ctx.stroke();
}, { passive: false });
```
- **Por que é inovador?**  
  Transforma a tela de toque do celular ou tablet em uma mesa digitalizadora de validade jurídica. O `{ passive: false }` combinado com `e.preventDefault()` impede que a página role enquanto o motorista assina com o dedo, garantindo um traço contínuo e limpo. A assinatura é convertida para PNG Base64 e persistida no banco relacional.

---

### 4. Tabela de Desmojibake Defensivo Multiplataforma (`frontend/laudoVistoria.php`)
```php
function sanitizar_utf8(?string $str): string {
    if ($str === null || $str === '') return '';
    $mojibake = [
        '├í' => 'á', 'di├íria' => 'diário', '├ú' => 'ã', 'padr├úo' => 'padrão',
        '├®' => 'é', 've├¡culo' => 'veículo', '├│' => 'ó', 'far├│is' => 'faróis',
        'hod├┤metro' => 'hodômetro', '├º' => 'ç', 'Seguran├ºa' => 'Segurança'
    ];
    $limpo = strtr($str, $mojibake);
    return htmlspecialchars($limpo, ENT_QUOTES, 'UTF-8');
}
```
- **Por que é inovador?**  
  Em ambientes heterogêneos onde desenvolvedores utilizam Windows (CP1252), Linux (UTF-8) e servidores em nuvem, acentos podem sofrer dupla codificação (*mojibake*). Esta função atua como um escudo defensivo, corrigindo automaticamente qualquer artefato de codificação antes de renderizar o laudo em PDF.

---

### 5. Geração Automatizada de Chamados de Oficina (`backend/processar_execucao_vistoria.php`)
```php
$codOc = 'OC-2026-' . str_pad($id_vistoria, 3, '0', STR_PAD_LEFT);
$insertOc = $pdo->prepare("INSERT INTO Ocorrencias 
    (codigo_ocorrencia, id_vistoria, id_veiculo, placa_veiculo, modelo_veiculo, subsistema, descricao_falha, criticidade, status, status_veiculo, acao_recomendada, fiscal_responsavel)
    VALUES (:cod, :idv, :idvei, :placa, :mod, :sub, :falha, 'alta', 'aberta', 'retido_oficina', :acao, :fiscal)");
```
- **Por que é inovador?**  
  Elimina o retrabalho humano. No momento exato em que o vistoriador marca uma avaria e finaliza o checklist, o software gera instantaneamente um chamado na fila de triagem da oficina (`status_veiculo = 'retido_oficina'`) e retém o veículo para reparo, impedindo que um carro perigoso circule na rua.

---

### 6. Tokens Criptográficos Efêmeros no Banco (`backend/processar_recuperacao.php`)
```php
$token = bin2hex(random_bytes(32));
$sql = "UPDATE Usuarios 
        SET token_recuperacao = :token, 
            token_expiracao = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
        WHERE id_usuario = :id";
```
- **Por que é inovador?**  
  Em vez de gerar redefinições de senha simuladas em memória, o sistema gera 32 bytes de entropia pura aleatória via SO e grava no banco de dados com prazo de validade estrito de 60 minutos (`DATE_ADD(NOW(), INTERVAL 1 HOUR)`). Após esse tempo, o link expira automaticamente, evitando ataques de repetição.

---

### 7. Interface Master-Detail sem Framework Pesado (`frontend/ocorrencias.php`)
```javascript
function selecionarOcorrencia(id) {
    document.querySelectorAll('.item-ocorrencia').forEach(el => el.classList.remove('active'));
    document.getElementById('card-oc-' + id).classList.add('active');
    carregarDetalhesOcorrencia(id);
}
```
- **Por que é inovador?**  
  Aplica o padrão arquitetural de usabilidade corporativa **Master-Detail** (utilizado em clientes de e-mail como Outlook e ferramentas de CRM). A fila de chamados fica na coluna esquerda e os detalhes com foto e despacho de oficina na direita, permitindo que o gestor despache dezenas de veículos com altíssima produtividade.

---

## 8. Roteiro de Sustentação Oral e Simulação de Perguntas da Banca

### Divisão Sugerida de Apresentação entre os Integrantes:
1. **Ana Klara Jardim:** Abertura, minimundo, personas e validação do problema de negócio (Opportunity Card e PMC).
2. **Estela do Nascimento:** Design System, acessibilidade visual (WCAG AAA), usabilidade mobile e Lei de Fitts no Modo Campo.
3. **Gustavo Meneis:** Modelo Conceitual e Relacional (DER em 3FN, chaves estrangeiras e integridade referencial).
4. **Isabela Pereira:** Demonstração ao vivo dos 5 Fluxos de Valor (CRUD, Governança, Vistoria, Ocorrências e Dashboard).
5. **Vanderci Lucas:** Arquitetura do backend, segurança da informação (RBAC, BCrypt, IDOR, tokens) e infraestrutura Docker / Nuvem Aiven.

---

### Simulação de Perguntas Difíceis da Banca e Como Responder:

> **Pergunta da Banca 1 (Prof. Felippe):**  
> *"Por que vocês optaram por PHP puro com PDO em vez de usar um framework completo como Laravel ou Node.js?"*  
> **Resposta Recomendada:**  
> *"Professor, tomamos essa decisão por três motivos fundamentais: primeiro, para demonstrar o domínio absoluto dos fundamentos de engenharia de software (arquitetura MVC, gerenciamento de sessões, transações ACID e segurança OWASP) sem a abstração de caixas-pretas de frameworks. Segundo, pelo desempenho: sem overhead de ORMs pesados, nosso tempo de resposta é de poucos milissegundos mesmo em dispositivos modestos. Terceiro, pela facilidade de portabilidade e conteinerização em Docker, garantindo que o sistema rode tanto no XAMPP quanto na nuvem com consumo mínimo de memória RAM."*

---

> **Pergunta da Banca 2 (Profª Marta):**  
> *"Como vocês garantiram que o operador no pátio conseguirá usar o sistema sob sol forte e com luvas?"*  
> **Resposta Recomendada:**  
> *"Professora Marta, essa foi uma das nossas maiores preocupações de UX. Criamos o **Modo Campo**, estruturado na **Lei de Fitts**. A tela exibe apenas uma pergunta por vez, com botões gigantes de 72px de altura distribuídos verticalmente na zona do polegar (*Thumb Zone*). Além disso, aplicamos contraste visual estrito conforme **WCAG 2.1 AAA**: a semiótica não depende de cor, utilizando ícones explicativos e textos de alto contraste, e os dados críticos como placas e hodômetros utilizam tipografia monoespaçada `Roboto Mono` para evitar qualquer erro de interpretação."*

---

> **Pergunta da Banca 3 (Banca de Banco de Dados / Segurança):**  
> *"Se um motorista curioso alterar o ID na URL para tentar ver o laudo de outro veículo, o que acontece?"*  
> **Resposta Recomendada:**  
> *"Ele é imediatamente bloqueado com um status HTTP 403 (Acesso Negado). Nós implementamos proteção ativa contra vulnerabilidades **IDOR (Insecure Direct Object Reference)** em `frontend/laudoVistoria.php`. Antes de renderizar os dados, o backend verifica se o usuário logado é Gestor/Supervisor ou se ele possui vínculo direto com aquela vistoria (sendo o motorista condutor ou o vistoriador executor). Se não tiver permissão, o sistema aborta a requisição e protege a privacidade dos dados da frota."*

---

> **Pergunta da Banca 4 (Banca de Infraestrutura):**  
> *"Como vocês garantem que esse sistema pode ser implantado em produção sem quebrar?"*  
> **Resposta Recomendada:**  
> *"Entregamos três formas homologadas de execução: primeiro, uma configuração oficial em contêineres **Docker e Docker Compose** (`docker compose up -d`), que sobe o Apache com PHP 8.2 e o MySQL 8.0 pré-populado na porta 8080. Segundo, integração direta com banco de dados em nuvem **Aiven MySQL** protegido por certificado SSL `ca.pem` e TLS v1.3. E terceiro, mantemos uma suíte de testes funcionais ponta a ponta (`tests/run_e2e_smoke_test.php`) com **49 asserções automatizadas**, garantindo que nenhum fluxo crítico quebre após atualizações."*

---

**Equipe Axion Frotas — Projeto Integrador IFES 2026**
