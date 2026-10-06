# Guia de Configuração e Execução de Testes — Sistema Axion

Este documento fornece o passo a passo completo para que qualquer integrante da equipe possa configurar o ambiente local (XAMPP), obter os arquivos mais atualizados e executar testes manuais e automatizados no **Axion (Sistema de Vistoria e Checklist Veicular)**.

---

## 1. Pré-Requisitos do Ambiente

- **XAMPP** instalado (com PHP 8.1+ e MySQL/MariaDB).
- **Git** instalado no computador.
- Navegador moderno (Google Chrome, Microsoft Edge ou Mozilla Firefox).

---

## 2. Como Obter os Arquivos Mais Atualizados

Todas as correções arquiteturais, novos endpoints, telas dinâmicas, dashboard e emissão de laudos estão consolidados na branch temática:
```bash
git checkout feature/integracao-scrumaidev
git pull origin feature/integracao-scrumaidev
```

### Onde posicionar a pasta do projeto:
Para que o Apache do XAMPP consiga servir os arquivos, o projeto deve estar dentro de `htdocs` ou linkado a ele:

#### Opção A (Diretamente no htdocs - Padrão):
Copie ou clone o repositório na pasta:
```text
C:\xampp\htdocs\projetoIntegrador
```

#### Opção B (Se o projeto estiver na Área de Trabalho/Desktop):
Abra o **PowerShell como Administrador** e crie o vínculo simbólico (Junction):
```powershell
New-Item -ItemType Junction -Path "C:\xampp\htdocs\projetoIntegrador" -Target "C:\Caminho\Ate\projetoIntegrador"
```

---

## 3. Configuração do Banco de Dados (`axion_db`)

1. Abra o **XAMPP Control Panel** e inicie os módulos:
   - **Apache** (clique em *Start*)
   - **MySQL** (clique em *Start*)
2. Abra o navegador e acesse o phpMyAdmin: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
3. No menu superior, clique na aba **SQL** e importe ou execute os dois scripts localizados na pasta `database/` do projeto:

   - **Passo 1 — Criar Estrutura das Tabelas:**
     Abra o arquivo [`database/schema.sql`](../database/schema.sql), copie o conteúdo, cole na aba SQL do phpMyAdmin e clique em **Executar**.
     *(Isso criará a base `axion_db` com 9 tabelas relacionais e constraints).*

   - **Passo 2 — Inserir Carga Inicial (Seeds):**
     Abra o arquivo [`database/seeds.sql`](../database/seeds.sql), copie o conteúdo, cole na aba SQL do phpMyAdmin e clique em **Executar**.
     *(Isso criará os usuários de teste, veículos e checklists com senhas seguras em BCrypt).*

---

## 4. Credenciais de Acesso para Testes

O banco inicial já vem com usuários pré-configurados para testar os diferentes perfis do sistema:

| Perfil | E-mail de Login | Senha | Função no Sistema |
|---|---|---|---|
| **Gestor / Administrador** | `admin@axion.com` | `admin123` | Acesso completo a todas as funções e cadastros |
| **Supervisor** | `supervisor@axion.com` | `mudar123` | Criação/edição de checklists e vistorias |
| **Motorista** | `motorista@axion.com` | `mudar123` | Preenchimento de checklist e assinatura digital |

---

## 5. Roteiro de Testes Funcionais Ponta a Ponta

Acesse no navegador: **[http://localhost/projetoIntegrador/](http://localhost/projetoIntegrador/)**

---

### Teste 1: Autenticação e Segurança de Sessão
1. Tente logar com uma senha incorreta: o sistema deve exibir aviso amigável de erro e não liberar o acesso.
2. Faça login com `admin@axion.com` / `admin123`: você será direcionado para o menu principal ([`frontend/oqfazer.php`](../frontend/oqfazer.php)).
3. Tente acessar qualquer tela interna colando o link direto em uma aba anônima: o sistema deve barrar e redirecionar para a tela de login.

---

### Teste 2: Modelagem Dinâmica de Checklists
1. No menu principal, selecione **"Criar Checklist"**.
2. Preencha o título do checklist (ex: *"Vistoria Preventiva Rodoviária"*).
3. Adicione perguntas dinâmicas clicando em **"+ Adicionar Pergunta"**:
   - Defina categorias (ex: *Mecânica, Elétrica, Pneus*).
   - Teste perguntas do tipo *Sim/Não*, *Texto* e *Número*.
4. Salve o checklist e confirme se ele aparece na tela de **Editar Checklist** ([`frontend/editarChecklist.php`](../frontend/editarChecklist.php)) com todas as perguntas carregadas do banco.

---

### Teste 3: Execução de Vistoria e Registro de Avarias
1. No menu principal, selecione **"Preencher Checklist"**.
2. Selecione a placa de um veículo cadastrado (ex: `ABC-1D23` ou `XYZ9876`), informe o motorista condutor, quilometragem atual e avance.
3. Na tela de **Não Conformidade** ([`frontend/detalhesNaoConformidade.html`](../frontend/detalhesNaoConformidade.html)):
   - Descreva uma avaria simulada (ex: *"Farol dianteiro esquerdo com lente trincada"*).
   - Selecione uma foto/imagem do seu computador para anexar como evidência.
   - Avance para a próxima etapa.

---

### Teste 4: Assinatura Digital em Canvas e Conclusão
1. Na tela de **Assinatura** ([`frontend/assinaturaChecklist.html`](../frontend/assinaturaChecklist.html)):
   - Desenhe a assinatura do motorista no primeiro quadro canvas usando o mouse ou touch screen.
   - Desenhe a assinatura do vistoriador no segundo quadro canvas.
   - Se errar o traço, teste o botão *"Limpar"*.
2. Clique em **"Concluir Vistoria"**:
   - O sistema salvará as assinaturas em Base64 no MySQL, vinculará as fotos e atualizará o status da vistoria para `aprovado_com_restricoes`.

---

### Teste 5: Dashboard Analítico e Emissão do Laudo em PDF
1. No menu principal, clique no botão azul: **"📊 Ver Dashboard e Histórico de Vistorias"** (ou acesse [`frontend/dashboard.php`](../frontend/dashboard.php)).
2. Observe os indicadores no topo (Total de Vistorias, Conformes, Com Restrições, Frota Ativa e Taxa de Conformidade).
3. Teste os **filtros de pesquisa**:
   - Digite a placa do veículo para filtrar.
   - Filtre por status (ex: *Com Restrições*).
4. Na tabela de vistorias, localize a vistoria recém-criada e clique no botão **"Laudo"**:
   - A página oficial do laudo técnico abrirá ([`frontend/laudoVistoria.php`](../frontend/laudoVistoria.php)).
   - Verifique os dados do veículo, a descrição da avaria, a foto anexada e as assinaturas digitais desenhadas.
5. Clique no botão **"Imprimir / Salvar em PDF"** no topo da página:
   - A janela de impressão abrirá formatada para papel A4, sem menus nem botões, pronta para salvar em arquivo PDF!

---

### Teste 6: Telas de Edição e Soft Delete (Inativação Lógica)
1. **Editar Veículo:** Acesse `frontend/editarVeiculo.php`, selecione um veículo, altere a quilometragem ou a cor e salve.
2. **Inativar Veículo:** Acesse `frontend/excluirVeiculo.php`, selecione um veículo e confirme a inativação. Verifique que o histórico de vistorias passadas continua intacto no banco.

---

## 6. Como Rodar a Suíte de Testes Automatizada via Terminal

Para quem preferir validar todas as regras do sistema de forma instantânea via linha de comando, disponibilizamos um executor E2E que roda 19 asserções em ~1 segundo:

1. Certifique-se de que o Apache e o MySQL estão rodando no XAMPP.
2. Abra o terminal na raiz do projeto e execute:
```bash
php scratch/run_e2e_smoke_test.php
```
3. O terminal exibirá o relatório com 100% de aprovação de todos os endpoints e transações PDO.

---

## 7. Rastreabilidade e Documentação do Projeto

- **Backlog do Produto (Épicos e US):** [`docs/product_backlog.md`](product_backlog.md)
- **Histórias Detalhadas INVEST:** [`docs/stories/`](stories/)
- **Quadro de Tarefas Técnicas (TODO):** [`docs/todo.md`](todo.md)
- **Manifesto do Framework ScrumAIDev:** [`docs/project_manifest.md`](project_manifest.md)
