# Guia de Apresentação e Melhoria dos Slides — Axion Frotas

Este documento foi elaborado para atender integralmente aos feedbacks da **Professora Marta** sobre os slides e a defesa do projeto, fornecendo a justificativa de negócio fundamentada e a padronização das figuras numeradas.

---

## 1. Justificativa Aprofundada do Projeto (Por que o Axion existe?)

### 1.1 O Contexto Atual (Sem o Sistema Axion)
Na grande maioria das operações logísticas, transportadoras de pequeno/médio porte e frotas públicas municipais, o controle diário de veículos ainda é realizado de forma arcaica:
- **Pranchetas de Papel e Caneta:** Motoristas e fiscais preenchem fichas físicas expostas a chuva, graxa, rasgos e perda no pátio.
- **Ilegibilidade da Caligrafia:** Letras indecifráveis dificultam saber se um pneu ou freio foi realmente inspecionado.
- **Vulnerabilidade a Fraudes (Sem Timestamp):** Fichas em papel permitem preenchimento retroativo ("assinar tudo na sexta-feira à tarde"), mascarando quando um dano realmente ocorreu.
- **Avarias Dispersas no WhatsApp:** Fotos de batidas, arranhões e pneus rasgados são enviadas em grupos de mensagens soltas, sem vínculo oficial com a placa do veículo, quilometragem ou histórico de ordens de serviço.
- **Falta de Triagem Operacional:** O gestor não sabe em tempo real se o veículo que está saindo para a estrada está retido por uma falha crítica ou apto a circular, aumentando riscos de acidentes e multas fiscais.

### 1.2 Limitações dos Sistemas Existentes de Mercado
Embora existam softwares corporativos de checklist veicular no mercado (como *Checkmob*, *Cobli*, *TOTVS Logística*, *Dootax* e *Trakur*):
- **Custo Proibitivo por Licença (SaaS Pago):** Cobram mensalidades entre **R$ 35,00 e R$ 90,00 por veículo/mês**, além de taxas de implantação que ultrapassam milhares de reais. Para uma frota de 50 veículos, o custo anual supera R$ 30.000,00.
- **Complexidade Excessiva e Rigidez:** Softwares genéricos exigem treinamentos longos e não se adaptam facilmente a rotinas rápidas de pátio operacional.
- **Falta de Soberania de Dados:** Os dados históricos de vistorias ficam retidos na nuvem de fornecedores proprietários.

### 1.3 A Proposta de Valor do Axion Frotas
O **Axion** surge como uma solução moderna, acessível e direcionada à realidade operacional brasileira:
1. **Custo Zero de Licenciamento Proprietário:** Plataforma web e mobile construída com tecnologias abertas (PHP, MySQL, Bootstrap 5).
2. **Ergonomia Máxima no Pátio (Modo Híbrido):** Botões gigantes com Lei de Fitts e conformidade WCAG 2.1 para uso com luvas de proteção sob sol forte.
3. **Triagem Operacional Integrada (Master-Detail):** Despacho de veículos com avaria diretamente para oficina em 2 cliques.
4. **Validade Probatória:** Laudos técnicos completos com carimbo de integridade temporal e assinaturas digitais coletadas em tela sensível (canvas).

---

## 2. Padronização e Numeração das Figuras nos Slides

Para garantir nota máxima na apresentação acadêmica, todos os slides devem utilizar texto justificado e figuras formalmente numeradas com legendas descritivas:

| Identificação | Título da Figura | Finalidade no Slide | Caminho do Artefato |
|---|---|---|---|
| **Figura 1** | Tabela de Histórico de Vistorias e KPIs com Tipografia Monoespaçada | Demonstrar varredura visual rápida com Roboto Mono e semiótica WCAG | [`docs/screenshots/02_dashboard_kpis.png`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/docs/screenshots/02_dashboard_kpis.png) |
| **Figura 2** | Módulo de Triagem e Despacho de Ocorrências (Layout Master-Detail) | Explicar o ciclo de vida da avaria: fila lateral, foto ampliada e despacho p/ oficina | [`docs/screenshots/03_triagem_ocorrencias_master_detail.png`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/docs/screenshots/03_triagem_ocorrencias_master_detail.png) |
| **Figura 3** | Execução de Vistoria no Modo Campo com Botões Gigantes | Ilustrar a aplicação da Lei de Fitts para uso com luvas e operação com uma mão | [`docs/screenshots/05_vistoria_modo_campo.png`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/docs/screenshots/05_vistoria_modo_campo.png) |
| **Figura 4** | Abertura Inteligente de Vistoria sem Redundância Visual | Mostrar preenchimento automático de placa e carimbo antifraude do servidor | [`docs/screenshots/04_abertura_vistoria_cadcheck.png`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/docs/screenshots/04_abertura_vistoria_cadcheck.png) |
| **Figura 5** | Laudo Oficial de Vistoria com Espelho Completo de Itens e Assinaturas | Comprovar o fechamento do checklist com valor probatório e assinaturas canvas | [`docs/screenshots/06_laudo_tecnico_oficial.png`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/docs/screenshots/06_laudo_tecnico_oficial.png) |
| **Figura 6** | Matriz Ponderada de Seleção de Ideias (Dinâmica 5-5-5) | Explicar a escolha do projeto através de critérios objetivos de engenharia | [`arquivos/matrizSelecao.png`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/arquivos/matrizSelecao.png) |
| **Figura 7** | Project Model Canvas (PMC) do Projeto Axion | Apresentar o planejamento estratégico de 10 blocos consolidados | [`arquivos/PMC/PMC.png`](file:///c:/Users/PC/Desktop/ExerciciosHtml/projetoIntegrador/arquivos/PMC/PMC.png) |

---

## 3. Roteiro Sugerido de Apresentação (Pitch de 5 Minutos)

1. **Slide 1 — Capa e Integrantes:** Apresentação formal da equipe e título do trabalho.
2. **Slide 2 — O Problema do Papel:** Destacar as dores do pátio (pranchetas, falta de fotos vinculadas, fraudes de horário).
3. **Slide 3 — Sistemas Pagos vs. Oportunidade Axion:** Mostrar que os softwares existentes cobram assinaturas caras inacessíveis para pequenas frotas.
4. **Slide 4 — A Solução Prática no Pátio (Figura 3 e 4):** Mostrar o celular com botões gigantes e o preenchimento sem redundância.
5. **Slide 5 — Gestão e Triagem para Oficina (Figura 2):** Apresentar a tela Master-Detail mostrando como a avaria vai para a oficina e retém o carro.
6. **Slide 6 — Dashboard e Laudo Oficial (Figura 1 e 5):** Mostrar os indicadores e o laudo técnico com assinaturas digitais.
7. **Slide 7 — Conclusão e Próximos Passos:** Conexão com o backend PHP e banco de dados relacional.

