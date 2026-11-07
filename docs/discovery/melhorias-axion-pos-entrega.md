# Discovery — Melhorias Pós-Entrega do Sistema Axion (Web, UX, Validações, Buscas e Seed)

**Status:** PROBLEM_READY
**Classificacao de Processo:** NORMAL PROCESS (com override explícito do usuário para implementação direta após G0)
**Origem:** hibrido (solicitação do usuário/professor + análise do código PHP/HTML atual)

## Intent
Atender ao pacote de 8 melhorias exigidas pelo professor/cliente após a entrega inicial do sistema **Axion** (gestão de frotas, motoristas, clientes, supervisores e checklists), elevando a usabilidade desktop/web, a integridade de dados nos formulários, a capacidade de consulta/busca e fornecendo uma base de dados populada para demonstração real de uso.

## Problem
1. **Interface restrita ao formato mobile:** As telas atuais (como `oqfazer.php`, `caduser.html`, `cadcar.html`) utilizam cards centralizados estreitos (`max-width: 500px` a `800px`), subaproveitando telas desktop/web.
2. **Navegação inicial burocrática:** A tela inicial pós-login (`oqfazer.php`) exige selecionar uma entre 10 opções via *radio buttons* e clicar em "Confirmar" (`processar_opcao.php`) para abrir uma tela, sem organização visual nem separação por nível de acesso.
3. **Ausência de navegação por abas e controle visual por perfil:** Embora a sessão armazene `$_SESSION['perfil_usuario']` (`supervisor`, `motorista`, `cliente`), a navegação não organiza as áreas em abas/menus condizentes com o nível de acesso.
4. **Falta de validação, máscaras e automação em formulários:**
   - Campos como CPF, Telefone, CEP e Placa não possuem máscara de digitação nem validação algorítmica (ex.: dígitos verificadores de CPF).
   - A categoria da CNH é um campo de texto livre (`<input type="text">`) em vez de um seletor padronizado (*dropdown*).
   - O preenchimento de endereço é manual, sem consulta automática de CEP em API gratuita.
5. **Ausência de área de busca/consulta:** Não há tela ou seção para pesquisar e filtrar veículos, usuários e checklists cadastrados.
6. **Banco de dados vazio para demonstração:** Não há script de carga (*seed*) no repositório que popule o banco `axion_db` com volume realista para avaliação do professor (200 carros, 100 motoristas, 250 clientes, 13 supervisores e múltiplos checklists).

## Users / Stakeholders relevantes
- **Professor / Avaliador (Cliente):** Avalia a qualidade visual web, facilidade de navegação, validações em tempo real, buscas e o sistema rodando com volume realista de dados.
- **Supervisores:** Acesso completo (gestão de usuários, veículos, modelos/preenchimento de checklists e buscas gerais).
- **Motoristas:** Acesso focado em visualização de veículos, preenchimento e consulta de checklists.
- **Clientes:** Acesso focado na consulta de veículos/checklists vinculados e visualização cadastral.

## Current Situation
- Stack atual: PHP 8+ com PDO/MySQL (`axion_db` em `conexao.php`) + HTML5 + Bootstrap 5.3 via CDN.
- Autenticação em `processar_login.php` grava `id_usuario`, `nome_usuario` e `perfil_usuario` na sessão PHP.
- `oqfazer.php` renderiza um formulário com 10 *radio buttons* que redirecionam via `processar_opcao.php`.
- Tabelas identificadas no código PHP atual: `Usuarios`, `Veiculos`, `Checklists` (além dos fluxos de perguntas/respostas e não conformidades).

## Desired Outcome
- Uma interface **Web Desktop moderna e responsiva** (com cabeçalho, indicadores rápidos e abas de navegação ajustadas ao perfil logado).
- **Acesso direto em 1 clique** por botões/cards de ação na tela inicial e nas abas, eliminando os *radio buttons* + botão de confirmação.
- **Formulários inteligentes** com máscaras (CPF, CEP, Telefone, Placa, CNH), validação real de CPF (frontend + backend), *dropdown* para Categoria CNH e preenchimento automático de endereço via API gratuita **ViaCEP**.
- **Central de Busca e Listagem** com filtros para Carros, Usuários e Checklists.
- **Banco de dados populado** automaticamente via script SQL/PHP com **200 carros, 100 motoristas, 250 clientes, 13 supervisores e um conjunto amplo de checklists** (modelos e vistorias preenchidas).

## Success Criteria
- [ ] Interface web utiliza layout amplo em desktop (Dashboard/Navbar/Abas) sem perder a responsividade mobile.
- [ ] `oqfazer.php` permite abrir qualquer funcionalidade diretamente com 1 clique (botões/cards) e exibe abas filtradas/adaptadas conforme `$_SESSION['perfil_usuario']`.
- [ ] Campos de CPF, Telefone, CEP e Placa possuem máscara automática e CPF inválido é bloqueado no formulário e no PHP.
- [ ] Campo Categoria CNH é um menu *dropdown* (`<select>`) com as categorias oficiais (`A`, `B`, `C`, `D`, `E`, `AB`, `AC`, `AD`, `AE`).
- [ ] Ao digitar um CEP válido de 8 dígitos, Logradouro, Bairro, Cidade e UF são preenchidos automaticamente via ViaCEP (`https://viacep.com.br/ws/{cep}/json/`).
- [ ] Área de busca permite pesquisar e filtrar Carros, Usuários (por nome, CPF, perfil) e Checklists (por título, categoria, criador/status).
- [ ] Script de *seed* cria/atualiza a estrutura necessária em `axion_db` e insere 200 veículos, 100 motoristas, 250 clientes, 13 supervisores e múltiplos checklists prontos para uso e busca.

## Scope

### IN
- Reformulação visual Web/Desktop do painel principal (`oqfazer.php`) e padronização visual das páginas internas.
- Substituição dos *radio buttons* por cards/botões de ação direta.
- Navegação por abas (Ex.: *Visão Geral / Ações Rápidas*, *Veículos*, *Usuários*, *Checklists*, *Buscas*) respeitando níveis de acesso (`supervisor`, `motorista`, `cliente`).
- Máscaras de entrada e validação de CPF (algoritmo módulo 11) onde aplicável (`caduser.html`, `editarUsuario.html`, `cadcar.html`, `editarVeiculo.html`, etc.).
- Menu *dropdown* para Categoria da CNH.
- Integração com API gratuita ViaCEP para preenchimento automático de endereço nos formulários de usuário.
- Área/tela de busca integrada para Carros, Usuários e Checklists (conectada ao MySQL via PDO).
- Script de banco de dados (DDL + Seed em SQL/PHP) populando 200 carros, 100 motoristas, 250 clientes, 13 supervisores e dezenas de checklists variados.

### OUT
- Migração de stack para frameworks SPA (React/Vue) ou substituição do PHP/MySQL.
- Integração com APIs pagas de validação governamental (Serpro/Detran).

### LATER
- Exportação de relatórios de checklists em PDF/Excel.
- Gráficos analíticos avançados de telemetria de frota.

## Constraints / Feasibility
- Manter a stack atual (PHP + PDO MySQL + HTML/JS + Bootstrap 5.3) para fácil execução no XAMPP/ambiente do professor.
- A API de CEP deve ser gratuita, sem necessidade de chave de API (ViaCEP atende perfeitamente via `fetch` no navegador).
- O script de população do banco deve ser rápido, idempotente (ou fácil de reexecutar) e gerar CPFs matematicamente válidos e placas no padrão Mercosul/antigo.

## Assumptions
- [ASSUMPTION A1 — Confirmada e Ajustada] **Níveis de acesso nas abas (incluindo Admin):**
  - **Admin:** privilégios totais (superiores/iguais ao supervisor, incluindo administração do sistema, carga/reset de banco *seed*, gestão de supervisores e auditoria completa).
  - **Supervisor:** visualiza todas as abas operacionais (Início/Dashboard, Busca Geral, Usuários, Veículos, Checklists).
  - **Motorista:** visualiza Início, Veículos (consulta), Checklists (preencher/consultar) e Busca (veículos e checklists).
  - **Cliente:** visualiza Início, Veículos (consulta) e Checklists (consulta/busca).
- [ASSUMPTION A2 — Confirmada] **Estrutura do Banco (`axion_db`):** O script SQL do banco e o executor `seed.php` incluirão todas as colunas necessárias (CNH, validade, categoria CNH, CEP, endereço completo, telefone, data de nascimento, gênero em `Usuarios`; marca, modelo, cor, km rodado, renavam, chassi, status em `Veiculos`; perguntas, subperguntas e execuções/preenchimentos em `Checklists`) e os PHPs serão atualizados para que o cadastro, a busca e o *seed* fiquem completos.
- [ASSUMPTION A3 — Confirmada] **Volume de Dados no Seed:** 200 carros, 100 motoristas, 250 clientes, 13 supervisores, 2 admins + modelos de checklists variados e dezenas de vistorias preenchidas.

## Risks
- **Risco 1:** Diferença entre as tabelas locais atuais do DBeaver do usuário e os campos dos formulários HTML.
  - *Mitigação:* Fornecer um script `seed.php` (executável pelo navegador com 1 clique) + arquivo `database/axion_schema_seed.sql` que cria/adapta as tabelas de forma segura (`CREATE TABLE IF NOT EXISTS` / `ALTER TABLE` seguro) antes de popular os dados.
- **Risco 2:** Indisponibilidade momentânea de internet ao testar ViaCEP.
  - *Mitigação:* Caso a API não responda, exibir aviso amigável e manter os campos de endereço editáveis manualmente.

## Open Questions
- Nenhuma pergunta bloqueante aberta.

## Review & Adjust

- [x] Usuario revisou a proposta completa
- [x] Alteracoes solicitadas foram incorporadas
- [x] Decisoes relevantes foram confirmadas
- [x] Nao existem perguntas bloqueantes abertas

### User adjustments
- **A1:** Incluir o perfil `admin` com os mesmos privilégios (ou privilégios superiores) aos do `supervisor`.
- **A2:** Incluir todas as colunas necessárias no script SQL do banco e atualizar os PHPs para que a busca e o *seed* fiquem completos.
- **A3:** Discovery aprovado com override explícito do usuário (Regra 18 do `AGENTS.md`) autorizando ir direto para a implementação do código.

## Gate G0 — PROBLEM READY

**Status:** APPROVED

**Aprovado pelo usuario:** sim
