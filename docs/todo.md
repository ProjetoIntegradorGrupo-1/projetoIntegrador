# TODO - Backlog Técnico Operacional (Axion)

Backlog técnico vivo do time Axion. Use este arquivo para acompanhar tarefas operacionais que implementam ou sustentam User Stories.

- Product Backlog: `docs/product_backlog.md`
- User Stories: `docs/stories/US-XXX.md`

---

## Concluídos (Sprint 01 - Estabilização e Arquitetura Canônica)

| ID | US | Título | Módulo | Status | Concluído em |
|---|---|---|---|---|---|
| F-001 | US-001 | Hotfix de caminhos relativos em formulários e imports | Frontend | Concluído | 2026-10-05 |
| B-001 | US-001 | Criptografia BCrypt em senhas e controle de sessão | Backend | Concluído | 2026-10-05 |
| D-001 | Todas | Modelagem do schema SQL relacional com 9 tabelas (`axion_db`) | Database | Concluído | 2026-10-05 |
| D-002 | Todas | Script de carga inicial com seeds de usuários, veículos e checklist | Database | Concluído | 2026-10-05 |
| A-001 | Todas | Reestruturação arquitetural canônica (`frontend/`, `backend/`, `database/`) | Arquitetura | Concluído | 2026-10-05 |
| B-002 | US-006 | Endpoint transacional para abertura de vistoria (`processar_cadastro_checklist.php`) | Backend | Concluído | 2026-10-05 |
| B-003 | US-006 | Endpoint de upload de evidências e não conformidades (`processar_detalhes_checklist.php`) | Backend | Concluído | 2026-10-05 |
| B-004 | US-006 | Endpoint para persistência de assinaturas Base64 e encerramento (`processar_checklist_final.php`) | Backend | Concluído | 2026-10-05 |
| B-005 | US-002 | Endpoint de redefinição de senha com tokens temporários (`processar_nova_senha.php`) | Backend | Concluído | 2026-10-05 |
| B-006 | US-003 | Processadores de edição e exclusão de usuários com soft delete | Backend | Concluído | 2026-10-05 |
| B-007 | US-004 | Processadores de edição e inativação de veículos com soft delete | Backend | Concluído | 2026-10-05 |
| B-008 | US-005 | Processadores de edição e inativação de checklist com sincronização de perguntas | Backend | Concluído | 2026-10-06 |
| F-002 | US-003 | Dinamização das telas de Edição e Exclusão de Usuários via PDO (`editarUsuario.php`, `excluirUsuario.php`) | Frontend | Concluído | 2026-10-05 |
| F-003 | US-004 | Dinamização das telas de Edição e Exclusão de Veículos via PDO (`editarVeiculo.php`, `excluirVeiculo.php`) | Frontend | Concluído | 2026-10-06 |
| F-004 | US-005 | Dinamização das telas de Edição e Exclusão de Checklists via PDO (`editarChecklist.php`, `excluirChecklist.php`) | Frontend | Concluído | 2026-10-06 |
| F-005 | US-006 | Integração de envio de formulário com canvas em `assinaturaChecklist.html` | Frontend | Concluído | 2026-10-05 |
| B-009 | Todas | Atualização de rotas em `processar_opcao.php` para apontar para telas PHP dinâmicas | Backend | Concluído | 2026-10-06 |
| P-001 | Todas | Formalização do Product Backlog INVEST em `docs/product_backlog.md` e User Stories | Governança | Concluído | 2026-10-06 |
| F-006 | US-009 | Dashboard analítico com KPIs, filtros em tempo real e histórico de vistorias (`dashboard.php`) | Frontend | Concluído | 2026-10-06 |
| F-007 | US-010 | Emissão e formatação de Laudo Oficial de Vistoria em PDF / Impressão A4 (`laudoVistoria.php`) | Frontend | Concluído | 2026-10-06 |

---

## Próximas Tarefas (Sprint 02 / Backlog Técnico)

- [ ] **[Q-001]** Elaboração de casos de teste automatizados e suite E2E com Cypress ou Playwright
- [ ] **[C-001]** Especificação formal de Contratos OpenAPI (`docs/contracts/`) e cenários BDD (`docs/bdd/`)
