# Product Backlog - Sistema Axion

**Produto:** Axion - Sistema de Vistoria e Checklist Veicular  
**Product Owner:** Time Axion / Projeto Integrador  
**Última atualização:** 2026-10-06  
**Framework:** ScrumAIDev (Maturidade Nível 0-1)

---

## Visão do Produto

> O **Axion** é uma plataforma corporativa web para gestão de frotas e conformidade operacional de veículos, permitindo a supervisores e gestores desenhar modelos dinâmicos de inspeção e aos motoristas e vistoriadores preencher vistorias em campo com registro fotográfico de não conformidades e coleta de assinaturas digitais na tela, garantindo rastreabilidade e segurança jurídica.

Use `Spec`, `Contract` e `BDD` para rastrear os artefatos técnicos quando a User Story exigir governança adicional. Quando não se aplicar, use `-`.

---

## Backlog Items

### Alta Prioridade

#### [EPIC-001] Gestão de Acesso, Autenticação e Perfis

**Objetivo:** Prover autenticação segura via sessão PHP, controle baseado em perfis (gestor, supervisor, motorista, cliente), redefinição de senha e manutenção do cadastro de operadores com soft delete.  
**Valor de negócio:** Alto (Segurança e Rastreabilidade)  
**Estimativa:** 13 pontos  
**GitHub Epic Issue:** -

##### User Stories

| ID | Issue | Título | Story Points | Status | Sprint | Spec | Contract | BDD |
|----|-------|--------|--------------|--------|--------|------|----------|-----|
| US-001 | - | Autenticação Segura com Criptografia BCrypt e Sessão PHP | 3 | Concluído | Sprint 01 | - | - | - |
| US-002 | - | Recuperação e Redefinição de Senha com Tokens Temporários | 5 | Concluído | Sprint 01 | - | - | - |
| US-003 | - | Cadastro, Edição e Inativação Lógica de Usuários (Soft Delete) | 5 | Concluído | Sprint 01 | - | - | - |

---

#### [EPIC-004] Execução de Vistorias, Evidências e Assinatura Digital

**Objetivo:** Permitir aos motoristas e vistoriadores selecionar um checklist ativo, inspecionar o veículo, registrar não conformidades com upload de fotos e finalizar com assinaturas digitais via canvas HTML5.  
**Valor de negócio:** Altíssimo (Core Business Operacional)  
**Estimativa:** 21 pontos  
**GitHub Epic Issue:** -

##### User Stories

| ID | Issue | Título | Story Points | Status | Sprint | Spec | Contract | BDD |
|----|-------|--------|--------------|--------|--------|------|----------|-----|
| US-006 | - | Abertura e Preenchimento de Vistoria com Associação de Veículo e Checklist | 8 | Concluído | Sprint 01 | - | - | - |
| US-007 | - | Registro de Não Conformidades com Evidências Fotográficas e Descrição | 5 | Concluído | Sprint 01 | - | - | - |
| US-008 | - | Coleta de Assinatura Digital em Canvas e Encerramento da Vistoria | 8 | Concluído | Sprint 01 | - | - | - |

---

### Média Prioridade

#### [EPIC-002] Gestão da Frota Veicular

**Objetivo:** Manter o inventário de veículos da empresa com placa, modelo, chassi, renavam, odômetro atualizado e status operacional.  
**Valor de negócio:** Médio (Controle Patrimonial)  
**Estimativa:** 8 pontos  
**GitHub Epic Issue:** -

##### User Stories

| ID | Issue | Título | Story Points | Status | Sprint | Spec | Contract | BDD |
|----|-------|--------|--------------|--------|--------|------|----------|-----|
| US-004 | - | Cadastro, Consulta, Edição e Inativação de Veículos da Frota | 8 | Concluído | Sprint 01 | - | - | - |

---

#### [EPIC-003] Modelagem de Checklists Dinâmicos

**Objetivo:** Permitir que supervisores modelem formulários de inspeção personalizados contendo perguntas obrigatórias, tipos variados (Sim/Não, texto, numérico) e subperguntas condicionais.  
**Valor de negócio:** Alto (Flexibilidade Operacional)  
**Estimativa:** 13 pontos  
**GitHub Epic Issue:** -

##### User Stories

| ID | Issue | Título | Story Points | Status | Sprint | Spec | Contract | BDD |
|----|-------|--------|--------------|--------|--------|------|----------|-----|
| US-005 | - | Criação e Edição Dinâmica de Modelos de Checklist e Perguntas | 13 | Concluído | Sprint 01 | - | - | - |

---

### Baixa Prioridade

#### [EPIC-005] Relatórios, Dashboard e Auditoria de Vistorias

**Objetivo:** Painel gerencial analítico com indicadores de conformidade da frota, alertas de veículos em manutenção e exportação de relatórios em PDF.  
**Valor de negócio:** Médio  
**Estimativa:** 13 pontos  
**GitHub Epic Issue:** -

##### User Stories

| ID | Issue | Título | Story Points | Status | Sprint | Spec | Contract | BDD |
|----|-------|--------|--------------|--------|--------|------|----------|-----|
| US-009 | - | Dashboard com Indicadores de Conformidade e Alertas de Pátio | 8 | Backlog | Sprint 02 | - | - | - |
| US-010 | - | Exportação de Vistoria Concluída com Evidências e Assinaturas em PDF | 5 | Backlog | Sprint 02 | - | - | - |

---

## Technical Debt & Bugs

| ID | Descrição | Prioridade | Estimativa | Impacto | Status |
|----|-----------|------------|------------|---------|--------|
| BUG-001 | Inconsistência de caminhos relativos de include e action entre raiz e subpastas | Alta | 3 | Formulários não enviavam dados para os backends corretos | Resolvido (Fase 1) |
| BUG-002 | Formulário `assinaturaChecklist.html` não possuía tag `<form>` nem inputs para salvar assinaturas | Crítica | 5 | O fluxo de vistoria não persistia no banco | Resolvido (Fase 3) |
| DEBT-001 | Arquivos misturados na raiz sem separação arquitetural frontend/backend | Alta | 5 | Dificuldade de manutenção e colisão de rotas | Resolvido (Fase 2 - Abordagem B) |
| DEBT-002 | Telas de edição e exclusão com dados estáticos codificados em HTML | Alta | 5 | Não era possível gerenciar dados reais do banco `axion_db` | Resolvido (Fase 3) |

---

## Spikes & Research

| ID | Tópico | Objetivo | Time-box | Status |
|----|--------|----------|----------|--------|
| SPIKE-001 | Geração de Laudo PDF em PHP | Avaliar biblioteca leve (FPDF vs TCPDF vs Dompdf) para gerar laudos com fotos e assinaturas | 1 dia | Pendente |
| SPIKE-002 | Modo Offline PWA para Vistorias | Pesquisar adoção de Service Worker e IndexedDB para preenchimento de vistoria sem conexão | 2 dias | Pendente |

---

## Roadmap Overview

### Sprint 01 (Atual - Estabilização e Arquitetura Canônica)
- [x] Estruturação canônica ScrumAIDev (`frontend/`, `backend/`, `database/`, `docs/`)
- [x] Modelagem do banco `axion_db` com 9 tabelas relacionais (`schema.sql` e `seeds.sql`)
- [x] Implementação de todos os endpoints transacionais PDO
- [x] Dinamização completa das telas de Edição e Exclusão (Usuários, Veículos, Checklists)
- [x] Conexão do fluxo de vistoria completa (Abertura -> Não Conformidades -> Assinatura)

### Sprint 02 (Governança e Relatórios)
- [ ] Geração de laudo de vistoria em PDF para download
- [ ] Painel gerencial e histórico de vistorias com filtros por placa/data
- [ ] Especificação formal de Contratos OpenAPI (`docs/contracts/`) e BDD (`docs/bdd/`)

---

## IA Insights

### Priorização Realizada
- A estabilização das rotas e do banco de dados relacional foi o pré-requisito fundamental para viabilizar as telas de edição e exclusão dinâmicas.
- A adoção de Soft Delete (`ativo=0` ou `status='inativo'`) garante conformidade com a LGPD e evita erros de integridade referencial com a tabela `Vistorias`.

### Riscos Mitigados
- **Integridade Referencial:** Exclusão de veículos e checklists agora preserva o histórico de vistorias já realizadas.
- **Roteamento Apache/XAMPP:** O roteador `index.php` na raiz evita necessidade de configurações complexas no `httpd.conf` ou `.htaccess`.

---

## Glossário e Definições

| Termo | Definição |
|-------|-----------|
| **Vistoria** | Execução de um checklist aplicado a um veículo específico em data/hora determinadas. |
| **Não Conformidade** | Item do checklist verificado que não atende aos padrões de segurança ou funcionamento. |
| **Evidência Fotográfica** | Imagem digital anexada para comprovar danos ou avarias constatadas. |
| **Assinatura Digital (Canvas)** | Desenho vetorial da assinatura manuscrita do motorista/vistoriador convertido em Base64 PNG. |
| **Soft Delete** | Inativação lógica do registro no banco mantendo o dado armazenado para fins históricos e de auditoria. |

