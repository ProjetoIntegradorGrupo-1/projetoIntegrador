# Axion — Gerenciador de Checklists e Vistorias de Veículos

Projeto Integrador (IFES) — sistema web para gestão de frota, modelos de checklist, vistorias com assinatura digital e locação de veículos.

1. [Componentes](#1-componentes)
2. [Ideias Selecionadas, Matriz de Seleção e Opportunity Card](#2-ideias-selecionadas-matriz-de-seleção-e-opportunity-card)
   - [2.1 Ideias Selecionadas pelo Grupo](#21-ideias-selecionadas-pelo-grupo)
   - [2.2 Matriz de Seleção](#22-matriz-de-seleção)
   - [2.3 Opportunity Card da Ideia Selecionada](#23-opportunity-card-da-ideia-selecionada)
3. [Minimundo](#3-minimundo)
   - [3.1 Regras de negócio](#31-regras-de-negócio)
   - [3.2 Requisitos funcionais](#32-requisitos-funcionais)
   - [3.3 Requisitos não funcionais](#33-requisitos-não-funcionais)
4. [Validação da Ideia](#4-validação-da-ideia)
5. [Personas e Histórias de Usuário](#5-personas-e-histórias-de-usuário)
6. [Protótipos e Telas do Sistema](#6-protótipos-e-telas-do-sistema)
7. [Modelo Conceitual](#7-modelo-conceitual)
8. [Descrição dos Dados](#8-descrição-dos-dados)
9. [Rastreabilidade dos Artefatos](#9-rastreabilidade-dos-artefatos)
10. [PMC](#10-pmc)
11. [Como executar o sistema](#11-como-executar-o-sistema)

---

## 1. COMPONENTES

### Integrantes do grupo

- **Ana Klara Jardim** — anaklaraifesjd@gmail.com
- **Estela do Nascimento** — estela.nv2025@gmail.com
- **Gustavo Meneis** — ifesmeneis277@gmail.com
- **Isabela Pereira** — isabela.pbarreto.av@gmail.com
- **Vanderci Lucas** — in.form.magic@outlook.com

---

## 2. IDEIAS SELECIONADAS, MATRIZ DE SELEÇÃO E OPPORTUNITY CARD

### 2.1 Ideias Selecionadas pelo Grupo

Após a realização da Dinâmica Prática 5–5–5 (problemas, soluções atuais e pessoas), foram selecionadas as duas principais ideias consideradas pelo grupo.

#### Ideia 1

**Título:** Gerenciador de checklists

**Descrição:**
Sistema de gerenciamento de checklists destinado a empresas que trabalham com frotas de veículos e precisam realizar inspeções periódicas para acompanhar suas condições. A proposta busca facilitar a criação, a organização e a execução dessas inspeções, permitindo que checklists com diferentes finalidades, como retirada e devolução de veículos, sejam gerenciados de forma prática e padronizada.

#### Ideia 2

**Título:** Software para fichas médicas

**Descrição:**
Software para registrar fichas de pessoas que precisam de atenção especial, como idosos, pessoas com deficiência e pessoas com transtornos mentais que necessitam de acompanhamento especializado. As fichas seriam usadas por profissionais de saúde e cuidadores para direcionar melhor os cuidados com cada pessoa.

---

### 2.2 Matriz de Seleção

#### Ideia 1 — Gerenciador de checklists

| Critério | Nota |
|---|---|
| Afinidade | 2 |
| Processo | 2 |
| Problema | 1 |
| Valor do software | 1 |
| Viabilidade | 2 |
| **Nota final** | **8** |

#### Ideia 2 — Software para fichas médicas

| Critério | Nota |
|---|---|
| Afinidade | 1 |
| Processo | 0 |
| Problema | 2 |
| Valor do software | 1 |
| Viabilidade | 0 |
| **Nota final** | **4** |

#### Ideia selecionada

**Gerenciador de checklists**

**Justificativa:**
A ideia foi selecionada por ser viável dentro do prazo disponível, por não envolver dados sensíveis de saúde e porque um integrante do grupo trabalha em um local que precisa de um sistema de checklist melhor.

---

### 2.3 Opportunity Card da Ideia Selecionada

<img width="1152" height="648" alt="Opportunity Card" src="https://github.com/user-attachments/assets/825cd1d5-2eec-452e-9fa0-96d57b3d5449" />

| Item | Definição do grupo |
|---|---|
| **1. Área de afinidade/contexto** | Empresas que operam em turno ininterrupto de revezamento e possuem frotas de veículos que necessitam de inspeção e monitoramento periódicos, como empresas de transporte e locadoras de veículos. |
| **2. Problema percebido** | Perda de informações coletadas em papel, necessidade de pranchetas na área operacional e problemas com caligrafia. |
| **3. Quem possui o problema** | Usuários finais, como trabalhadores da área operacional, motoristas e vigilantes, que realizam as inspeções e registram as informações dos veículos. |
| **4. Como é resolvido hoje** | Formulários de checklist em papel e/ou software genérico, com limitação de espaço na tela e excesso de toques para responder cada pergunta. |
| **5. Soluções semelhantes** | Intelatrac. |
| **6. Lacuna inicial** | Software otimizado para celular, em que todas as perguntas aparecem completamente legíveis, com as opções de resposta logo abaixo da pergunta (botões de opção). |
| **7. Pessoas acessíveis** | Motoristas, vigilantes e operadores do Terminal Aquaviário da Barra do Riacho (Transpetro). |
| **8. Hipótese de oportunidade** | Otimizar o tempo e a qualidade das respostas dos checklists operacionais. Em uma unidade como o TABR, com 237 perguntas no checklist, são necessários de 3 a 4 toques no celular por pergunta. |
| **9. Momento/oportunidade** | Um dos integrantes é funcionário da Transpetro. Pelo contrato de trabalho, caso a empresa adote a solução apresentada pelo funcionário, ele não será remunerado pela aplicação desenvolvida. |
| **10. Principal incerteza/desafio** | Apresentar a solução aos órgãos decisórios das potenciais empresas clientes. |

---

## 3. MINIMUNDO

O **Axion** é um sistema web para empresas que mantêm uma frota própria de veículos e também alugam veículos para clientes. Ele substitui as fichas de papel por checklists digitais, registra as vistorias com assinatura de quem participou e acompanha o ciclo das locações.

O sistema possui quatro perfis de usuário. O **administrador** gerencia toda a operação: usuários, veículos e modelos de checklist, sendo também o responsável por aprovar ou rejeitar os modelos criados pelos supervisores. O **supervisor** coordena uma equipe de motoristas e clientes, cadastra veículos, cria modelos de checklist e atua como vistoriador. O **motorista** realiza as vistorias dos veículos da frota. O **cliente** cria a própria conta, que só é liberada após a aprovação de um gestor, solicita locações e participa das vistorias de retirada e devolução do veículo alugado.

Cada **veículo** é cadastrado com placa, marca, modelo, ano, cor, quilometragem, chassi, Renavam e foto. Ele tem uma finalidade (frota ou locação) e uma situação (disponível, em rota, em manutenção ou locado).

Os **modelos de checklist** têm título, categoria (elétrico, mecânico, estético, segurança, pré-viagem, locação ou outros), descrição e uma lista ordenada de perguntas. Cada pergunta pode ser do tipo sim/não, texto ou número, ser obrigatória ou não, trazer uma orientação para quem responde e, no caso de sim/não, indicar a resposta esperada. Uma pergunta também pode ter subperguntas que só aparecem conforme a resposta da pergunta principal. Modelos criados ou editados por um supervisor ficam pendentes até o administrador aprová-los.

Na **vistoria**, o usuário escolhe um modelo aprovado e um veículo, informa a quilometragem e responde às perguntas. Quando uma resposta difere da esperada, a vistoria é marcada como **não conforme**, e é obrigatório descrever o problema, podendo-se anexar uma foto como evidência. A vistoria só é concluída quando o motorista (ou o cliente) e o supervisor vistoriador assinam digitalmente na tela. Quem ainda não assinou pode recusá-la, informando o motivo. Ao concluir uma vistoria com avaria, o supervisor pode enviar o veículo para a manutenção. As vistorias concluídas podem ser impressas ou salvas em PDF.

Na **locação**, o cliente escolhe um veículo disponível e as datas de retirada e devolução. Um gestor aprova ou recusa o pedido. Com a locação aprovada, o veículo fica como locado, e o cliente realiza com o supervisor a vistoria de retirada e, depois, a de devolução. A devolução é registrada com a quilometragem final, e o veículo volta a ficar disponível ou segue para a manutenção, caso a vistoria tenha apontado avaria.

Todas as vistorias e locações ficam guardadas como histórico, e o painel inicial mostra indicadores e gráficos de acordo com o perfil de quem está conectado.

---

### 3.1 Regras de negócio

| Código | Regra |
|---|---|
| **RN01 — Acesso por perfil** | Há quatro perfis: administrador, supervisor, motorista e cliente. O supervisor só gerencia motoristas e clientes e não pode criar ou promover usuários a administrador ou supervisor. Ninguém pode alterar o próprio perfil nem desativar a própria conta. |
| **RN02 — Aprovação de cadastro de cliente** | O cliente que se cadastra sozinho fica com a conta pendente e não consegue entrar até um administrador ou supervisor aprová-lo e associá-lo a um supervisor. |
| **RN03 — Aprovação de modelos** | Modelos de checklist criados ou editados por supervisor ficam pendentes e só podem ser usados após aprovação do administrador. A rejeição exige um motivo. Modelos do administrador já nascem aprovados. |
| **RN04 — Preservação do histórico** | Modelos, usuários e veículos que já participaram de vistorias ou locações não são apagados: modelos são arquivados, usuários são desativados e a exclusão de veículos é bloqueada. |
| **RN05 — Conformidade** | Perguntas obrigatórias precisam ser respondidas. Em perguntas sim/não, a resposta diferente da esperada torna a vistoria não conforme, e a descrição do problema passa a ser obrigatória antes da assinatura. |
| **RN06 — Assinatura dupla** | A vistoria só é concluída com as assinaturas do motorista (ou cliente) e do supervisor vistoriador, que não podem ser idênticas. As respostas podem ser corrigidas apenas enquanto ninguém assinou. Quem ainda não assinou pode recusar a vistoria, informando o motivo. |
| **RN07 — Restrição por finalidade** | O motorista só vistoria veículos da frota. Veículos de locação são vistoriados pelo cliente, junto com o supervisor, na retirada e na devolução. |
| **RN08 — Manutenção** | Ao concluir uma vistoria não conforme, o supervisor pode enviar o veículo para a manutenção. Em locações, o veículo só vai para a manutenção quando a devolução é registrada. |
| **RN09 — Solicitação de locação** | A retirada não pode ser em data passada nem a mais de 180 dias. A devolução não pode ser anterior à retirada, cada locação tem no máximo 90 dias e a CNH do cliente precisa estar válida até a data de devolução. |
| **RN10 — Ciclo da locação** | O pedido nasce pendente e pode ser cancelado pelo cliente enquanto estiver pendente. Só pode ser aprovado se o veículo estiver disponível. A retirada só pode ser feita a partir da data marcada, e a devolução só é registrada depois da vistoria de devolução concluída. |
| **RN11 — Senhas** | O sistema não envia e-mails. Em caso de esquecimento, o administrador ou o supervisor redefine a senha para a senha padrão, e o usuário a troca em "Meu perfil". |

### 3.2 Requisitos funcionais

| Código | Requisito |
|---|---|
| RF01 | Autenticar usuários por e-mail e senha e encerrar a sessão (logout). |
| RF02 | Cadastrar, consultar, editar, desativar e excluir usuários, com validação de CPF, máscaras e preenchimento automático do endereço pelo CEP. |
| RF03 | Permitir o autocadastro de clientes e a aprovação ou recusa desses cadastros por um gestor. |
| RF04 | Cadastrar, consultar, editar e excluir veículos, com foto, finalidade (frota/locação) e situação. |
| RF05 | Criar, editar, arquivar e excluir modelos de checklist com perguntas ordenadas dos tipos sim/não, texto e número, incluindo subperguntas condicionais. |
| RF06 | Aprovar ou rejeitar, com motivo, os modelos de checklist criados por supervisores. |
| RF07 | Registrar vistorias com veículo, modelo, quilometragem, respostas e observações, identificando automaticamente a não conformidade. |
| RF08 | Registrar a descrição da não conformidade e anexar uma foto como evidência. |
| RF09 | Coletar a assinatura digital do motorista/cliente e do vistoriador e permitir a recusa da vistoria com motivo. |
| RF10 | Enviar o veículo para a manutenção ao concluir uma vistoria com avaria. |
| RF11 | Permitir que o cliente solicite, acompanhe e cancele locações. |
| RF12 | Permitir que o gestor aprove, recuse ou cancele locações e registre a devolução do veículo. |
| RF13 | Registrar as vistorias de retirada e de devolução vinculadas à locação. |
| RF14 | Buscar e filtrar veículos, usuários, checklists, vistorias e locações. |
| RF15 | Exibir um painel com indicadores e gráficos adequados ao perfil conectado. |
| RF16 | Imprimir ou salvar em PDF o relatório de uma vistoria. |
| RF17 | Permitir que o usuário consulte os próprios dados, troque a senha e a foto de perfil, e que o gestor redefina a senha de outro usuário. |

### 3.3 Requisitos não funcionais

| Código | Requisito |
|---|---|
| RNF01 | O sistema é web e roda em servidor PHP 8 com banco MySQL/MariaDB (por exemplo, XAMPP). |
| RNF02 | A interface é responsiva (Bootstrap 5), podendo ser usada no computador e no celular. |
| RNF03 | As senhas são armazenadas com hash (`password_hash`), nunca em texto puro. |
| RNF04 | Os formulários são protegidos contra envio por outros sites (token CSRF), e as consultas ao banco usam comandos preparados (PDO). |
| RNF05 | Cada ação é verificada no servidor de acordo com o perfil do usuário, não apenas escondida na tela. |
| RNF06 | Operações que alteram mais de uma tabela (assinatura, aprovação e devolução de locação) são feitas em transação. |
| RNF07 | Os dados de vistorias concluídas são preservados como histórico (ver RN04). |

---

## 4. VALIDAÇÃO DA IDEIA

A validação foi feita por meio de um formulário on-line. Não houve entrevista individual com usuários.

### 4.1 Formulário

https://docs.google.com/forms/d/e/1FAIpQLSfhAbcdeDb946Aj8rsI9r5QmwfeYjps1cm14KZ6DWnaAxJ94Q/viewform

### 4.2 Resultados da validação

https://docs.google.com/forms/d/1tMPbrHBxVMcUGsD_-dncfP1PgJmC2txoj9QXeERONJU/edit#responses

#### Síntese dos resultados

A pesquisa contou com 23 participantes, dos quais 18 já trabalharam em empresas que realizavam vistorias. Entre eles, 15 relataram que as inspeções acontecem diariamente ou várias vezes ao dia.

Os principais problemas apontados foram a dificuldade de extrair indicadores e relatórios, a demora para as informações chegarem aos gestores e a falta de comprovação visual das ocorrências. Também foram destacadas a necessidade de fotos, de histórico das inspeções, de integração com outros sistemas e de funcionamento offline.

Além disso, 11 participantes acreditam que a digitalização dos checklists pode gerar alto impacto na produtividade ou na redução de custos.

#### Conclusão da validação

Os resultados reforçaram a proposta inicial e ajudaram a priorizar os recursos: evidência fotográfica, histórico das inspeções, indicadores no painel e relatório da vistoria em PDF. A integração com outros sistemas e o funcionamento offline foram identificados como necessidades, mas ficaram fora do escopo desta versão.

---

## 5. PERSONAS E HISTÓRIAS DE USUÁRIO

### 5.1 Personas

#### Persona 1 — Administrador

<img width="395" height="781" alt="Persona Administrador" src="https://github.com/user-attachments/assets/8ebf3fe4-fdee-48b5-a330-f5733d4995a8" />

**Descrição:**
Atua nos bastidores da plataforma, garantindo que usuários, veículos e modelos de checklist estejam organizados e adequados às necessidades da empresa. É quem aprova os modelos criados pelos supervisores e acompanha os indicadores de todas as equipes.

#### Persona 2 — Usuário operacional (motorista)

<img width="399" height="770" alt="Persona Usuário" src="https://github.com/user-attachments/assets/de1a2fae-b745-407b-93a2-3d56955e55a3" />

**Descrição:**
É o perfil mais próximo da atividade prática. Usa o sistema durante a rotina de trabalho para registrar as condições dos veículos de forma padronizada e documentada.

#### Perfis do sistema

| Perfil | O que faz no Axion |
|---|---|
| **Administrador** | Gerencia todos os usuários, veículos e modelos; aprova ou rejeita modelos de checklist; vê os indicadores de todas as equipes. |
| **Supervisor** | Gerencia os motoristas e clientes da sua equipe; cadastra veículos; cria modelos (sujeitos a aprovação); é o vistoriador que assina as vistorias; aprova locações e cadastros de clientes. |
| **Motorista** | Realiza e assina vistorias dos veículos da frota e acompanha as suas pendências. |
| **Cliente** | Cria a própria conta, solicita locações e realiza e assina as vistorias de retirada e devolução. |

---

### 5.2 Histórias de Usuário

As histórias abaixo refletem as funcionalidades implementadas na versão atual do sistema.

**Acesso e usuários**

- **HU01 — Realizar login:** Como usuário, quero entrar no sistema com e-mail e senha para acessar as funções do meu perfil.
- **HU02 — Cadastrar usuário:** Como administrador ou supervisor, quero cadastrar usuários com seu perfil para que tenham acesso ao sistema.
- **HU03 — Gerenciar usuários:** Como administrador ou supervisor, quero editar, desativar ou excluir usuários e redefinir senhas para manter os cadastros atualizados.
- **HU04 — Autocadastro de cliente:** Como cliente, quero criar minha própria conta para poder solicitar locações.
- **HU05 — Aprovar cadastro de cliente:** Como supervisor, quero aprovar ou recusar os cadastros de clientes para liberar o acesso apenas a quem a empresa aceita.
- **HU06 — Meu perfil:** Como usuário, quero consultar meus dados e trocar minha senha e foto.

**Veículos**

- **HU07 — Gerenciar veículos:** Como supervisor, quero cadastrar, editar e excluir veículos, indicando finalidade e situação, para manter a frota atualizada.

**Modelos de checklist**

- **HU08 — Criar checklist:** Como supervisor, quero criar um modelo de checklist com perguntas de diferentes tipos para definir o que será verificado na vistoria.
- **HU09 — Subperguntas condicionais:** Como supervisor, quero adicionar perguntas que aparecem conforme a resposta de outra para detalhar um problema só quando ele existir.
- **HU10 — Editar e arquivar checklist:** Como supervisor, quero editar ou retirar de uso um modelo sem perder as vistorias já feitas com ele.
- **HU11 — Aprovar checklist:** Como administrador, quero aprovar ou rejeitar, com motivo, os modelos criados pelos supervisores para garantir a padronização.

**Vistorias**

- **HU12 — Realizar vistoria:** Como motorista, quero escolher um veículo e um modelo aprovado e responder às perguntas para registrar a condição do veículo.
- **HU13 — Registrar não conformidade:** Como motorista, quero descrever o problema encontrado e anexar uma foto para comprovar a avaria.
- **HU14 — Assinar vistoria:** Como motorista ou supervisor, quero assinar a vistoria na tela para confirmar oficialmente o seu resultado.
- **HU15 — Recusar vistoria:** Como participante que ainda não assinou, quero recusar a vistoria com um motivo quando discordar do registro.
- **HU16 — Enviar para manutenção:** Como supervisor, quero enviar para a manutenção o veículo cuja vistoria apontou avaria.
- **HU17 — Consultar e filtrar vistorias:** Como supervisor, quero buscar e filtrar as vistorias e imprimir o relatório em PDF para acompanhar o histórico dos veículos.

**Locações**

- **HU18 — Solicitar locação:** Como cliente, quero escolher um veículo disponível e as datas para solicitar uma locação.
- **HU19 — Avaliar locação:** Como supervisor, quero aprovar ou recusar os pedidos de locação para controlar os veículos disponíveis.
- **HU20 — Vistoria de retirada e devolução:** Como cliente, quero fazer as vistorias de retirada e devolução com o supervisor para registrar o estado do veículo.
- **HU21 — Registrar devolução:** Como supervisor, quero registrar a devolução com a quilometragem final para encerrar a locação.

**Painel**

- **HU22 — Painel de indicadores:** Como administrador ou supervisor, quero ver indicadores e gráficos da frota, das vistorias e das equipes para acompanhar a operação.

---

## 6. PROTÓTIPOS E TELAS DO SISTEMA

Nesta etapa foram desenvolvidos os protótipos para representar a interface e identificar as informações que deveriam ser armazenadas e processadas. A versão final do sistema é responsiva: as mesmas telas se adaptam ao computador e ao celular, por isso não há um protótipo mobile separado.

### 6.1 Protótipos iniciais

#### Tela 1 — Login

<img width="583" height="820" alt="Protótipo da tela de login" src="https://github.com/user-attachments/assets/efcb1b12-8e05-4fe7-93f2-438bf2e56e25" />

**Descrição:**
Tela de entrada do sistema, onde o usuário informa e-mail e senha. Na versão final, ela também dá acesso ao autocadastro de clientes e à recuperação de senha.

#### Tela 2 — Inspetor

<img width="773" height="705" alt="Protótipo da tela do inspetor" src="https://github.com/user-attachments/assets/41d373f5-7001-453f-bde9-b66e238603f5" />

**Descrição:**
Protótipo inicial da área de quem realiza as inspeções, com os checklists disponíveis e o preenchimento das perguntas. Na versão final, essas funções estão na aba **Vistorias** do painel.

Protótipo inicial navegável (Lovable): https://check-ride-mentor.lovable.app/

### 6.2 Telas implementadas

| Tela | Arquivo | Objetivo |
|---|---|---|
| Login | `index.html` | Entrar no sistema. |
| Autocadastro de cliente | `cadastroCliente.html` | Cliente cria a conta (fica pendente). |
| Esqueci a senha | `esqueciSenha.html` | Orienta o usuário a pedir a redefinição ao gestor. |
| Painel principal | `oqfazer.php` | Abas por perfil: Início (indicadores), Veículos, Usuários, Checklists, Aprovações, Vistorias e Locações. |
| Cadastro / edição / exclusão de usuário | `caduser.html`, `editarUsuario.html`, `excluirUsuario.html` | Gestão de usuários. |
| Cadastro / edição / exclusão de veículo | `cadcar.html`, `editarVeiculo.html`, `excluirVeiculo.html` | Gestão de veículos. |
| Criar / editar / ver / excluir checklist | `criarChecklist.html`, `editarChecklist.html`, `verChecklist.html`, `excluirChecklist.html` | Gestão e aprovação de modelos. |
| Preencher vistoria | `cadcheck.html` | Responder ao checklist de um veículo. |
| Não conformidade | `detalhesNaoConformidade.html` | Descrever a avaria e anexar a foto. |
| Assinatura | `assinaturaChecklist.html` | Assinar a vistoria na tela. |
| Ver vistoria | `verVistoria.html` | Detalhes, assinaturas, recusa e impressão em PDF. |
| Meu perfil | `meuPerfil.html` | Dados, senha e foto do usuário. |

### 6.3 Quais perguntas podem ser respondidas pelo sistema?

| Pergunta | Onde é respondida |
|---|---|
| Quantas vistorias foram feitas no período e quantas estão pendentes de assinatura? | Painel (Início) |
| Quais vistorias apontaram não conformidade e quais veículos foram para a manutenção? | Painel e aba Vistorias (filtros) |
| Qual é a situação da frota (disponível, em rota, em manutenção, locado)? | Painel (gráfico "Frota por situação") |
| Como as equipes de cada supervisor se comparam em vistorias aprovadas? | Painel do administrador |
| Qual é o histórico de vistorias de um veículo ou de um motorista? | Aba Vistorias (busca por placa ou nome) |
| Quais locações estão pendentes, em andamento ou atrasadas? | Aba Locações (filtros) |
| Qual é o histórico de locações e avarias de um cliente? | Ficha do cliente |
| Quais modelos de checklist aguardam aprovação ou foram rejeitados? | Aba Aprovações / Checklists |

Cada vistoria pode ser impressa ou salva em PDF pela tela **Ver vistoria**.

---

## 7. MODELO CONCEITUAL

O modelo conceitual foi desenvolvido a partir dos requisitos, das histórias de usuário, dos protótipos e das regras de negócio identificados nas etapas anteriores.

### 7.1 Diagrama

<img width="2800" height="1820" alt="modelo_conceitual_axion" src="https://github.com/user-attachments/assets/437c626f-8813-44b3-9f3f-9ba9c730e5f4" />

**Ferramenta e notação:** brModelo, notação de Peter Chen (Diagrama Entidade-Relacionamento).

### 7.2 Principais entidades

As três principais entidades do sistema são:

1. **Usuário** — pessoa que acessa o sistema, com perfil de administrador, supervisor, motorista ou cliente. Participa das vistorias (como motorista/cliente ou vistoriador), cria modelos de checklist e solicita ou avalia locações.
2. **Veículo** — bem da empresa, de finalidade frota ou locação, cuja condição é acompanhada pelas vistorias.
3. **Checklist (modelo)** — roteiro de perguntas usado para inspecionar os veículos.

Completam o modelo: **Pergunta**, **Vistoria (execução do checklist)**, **Resposta** e **Locação**.

### 7.3 Relacionamentos e cardinalidades

| Relacionamento | Cardinalidade |
|---|---|
| Supervisor **coordena** Motorista/Cliente | 1 : N |
| Usuário **cria** Checklist | 1 : N |
| Checklist **contém** Pergunta | 1 : N |
| Pergunta **tem subpergunta** Pergunta (autorrelacionamento) | 1 : N |
| Checklist **é usado em** Vistoria | 1 : N |
| Veículo **passa por** Vistoria | 1 : N |
| Motorista/Cliente **participa de** Vistoria | 1 : N |
| Supervisor **vistoria** Vistoria | 1 : N |
| Vistoria **possui** Resposta | 1 : N |
| Pergunta **é respondida em** Resposta | 1 : N |
| Cliente **solicita** Locação | 1 : N |
| Veículo **é alugado em** Locação | 1 : N |
| Locação **tem** Vistoria (retirada e devolução) | 1 : 0..2 |

### 7.4 Principais fluxos de informação

#### Fluxo 1 — Administrador

O administrador cadastra supervisores, motoristas e veículos e recebe os modelos de checklist enviados pelos supervisores, aprovando-os ou rejeitando-os com motivo. Pelo painel, acompanha os indicadores de todas as equipes, a situação da frota e os modelos pendentes.

#### Fluxo 2 — Supervisor

O supervisor cadastra os motoristas da sua equipe e os veículos, cria modelos de checklist e os envia para aprovação. Aprova os cadastros de clientes e os pedidos de locação. Como vistoriador, confere e assina as vistorias dos motoristas e dos clientes, podendo recusá-las ou enviar o veículo para a manutenção. Ao fim da locação, registra a devolução.

#### Fluxo 3 — Motorista

O motorista escolhe um veículo da frota e um modelo aprovado, responde às perguntas e, se houver não conformidade, descreve o problema e anexa uma foto. Em seguida, assina a vistoria, que fica aguardando a assinatura do supervisor.

#### Fluxo 4 — Cliente

O cliente cria a conta e aguarda a aprovação. Depois, solicita a locação de um veículo disponível. Aprovado o pedido, realiza com o supervisor a vistoria de retirada e, ao fim do período, a vistoria de devolução. Acompanha tudo em "Minhas locações".

### 7.5 Qualidade e clareza

O modelo foi desenvolvido buscando:

- utilizar nomes coerentes para entidades e atributos;
- reduzir informações redundantes e minimizar valores nulos;
- evitar tuplas espúrias, aplicando os conceitos de normalização;
- manter a coerência entre o modelo e os requisitos do sistema.

Há uma redundância intencional: a vistoria guarda uma cópia da placa, do nome do motorista e do vistoriador, e cada resposta guarda o texto da pergunta. Assim, o histórico continua fiel ao que foi registrado no dia, mesmo que o modelo, o veículo ou o usuário seja alterado depois.

---

## 8. DESCRIÇÃO DOS DADOS

O banco `axion_db` possui sete tabelas:

| Tabela | Descrição |
|---|---|
| **Usuarios** | Pessoas que acessam o sistema: nome, e-mail, senha (hash), CPF/matrícula, perfil, CNH (número, categoria e validade), endereço, telefone, foto, situação da conta (ativo, pendente, inativo) e o supervisor responsável. |
| **Veiculos** | Veículos da empresa: placa, marca, modelo, ano, cor, quilometragem, Renavam, chassi, foto, situação (ativo, em rota, manutenção, locado) e finalidade (frota ou locação). |
| **Checklists** | Modelos de checklist: título, categoria, descrição, criador, se está ativo ou arquivado e dados da aprovação (situação, motivo da rejeição, quem avaliou e quando). |
| **Checklist_Perguntas** | Perguntas de cada modelo: texto, tipo (sim/não, texto, número), ordem, obrigatoriedade, resposta esperada, orientação e, nas subperguntas, a pergunta principal e a resposta que faz a subpergunta aparecer. |
| **Checklist_Execucoes** | Vistorias realizadas: modelo, veículo, motorista/cliente, vistoriador, data, hora, quilometragem, conformidade, observações, descrição da não conformidade, foto de evidência, assinaturas e datas, dados da recusa, envio para manutenção e, quando for o caso, a locação e o tipo (retirada ou devolução). |
| **Checklist_Respostas** | Respostas de cada vistoria: pergunta, texto da pergunta no momento da vistoria, resposta dada, resposta esperada e se ficou conforme. |
| **Locacoes** | Pedidos de locação: veículo, cliente, datas de retirada e devolução, observação, situação (pendente, ativa, devolvida, recusada, cancelada), motivo da recusa, quem avaliou, data da devolução, quilometragem na devolução e quem recebeu o veículo. |

### Exemplos de campos

- **Usuarios.perfil:** define o que o usuário pode fazer no sistema (`admin`, `supervisor`, `motorista` ou `cliente`).
- **Checklist_Perguntas.condicao_pai:** resposta da pergunta principal que faz a subpergunta aparecer.
- **Checklist_Execucoes.status_conformidade:** indica se a vistoria ficou `conforme` ou `nao_conforme`.

---

## 9. RASTREABILIDADE DOS ARTEFATOS

A rastreabilidade relaciona as histórias de usuário às telas e às tabelas, garantindo a coerência entre as etapas.

### 9.1 História de Usuário × Tela

| História de Usuário | Tela | Objetivo |
|---|---|---|
| HU01 — Realizar login | `index.html` | Entrar no sistema. |
| HU02, HU03 — Cadastrar e gerenciar usuários | `caduser.html`, `editarUsuario.html`, `excluirUsuario.html` | Manter os cadastros de usuários. |
| HU04 — Autocadastro de cliente | `cadastroCliente.html` | Cliente cria a conta. |
| HU05 — Aprovar cadastro de cliente | `oqfazer.php` (aba Usuários) | Liberar ou recusar o acesso. |
| HU06 — Meu perfil | `meuPerfil.html` | Dados, senha e foto. |
| HU07 — Gerenciar veículos | `cadcar.html`, `editarVeiculo.html`, `excluirVeiculo.html` | Manter a frota. |
| HU08, HU09, HU10 — Criar, editar e arquivar checklist | `criarChecklist.html`, `editarChecklist.html`, `excluirChecklist.html` | Montar os modelos e suas perguntas. |
| HU11 — Aprovar checklist | `verChecklist.html`, `oqfazer.php` (aba Aprovações) | Aprovar ou rejeitar modelos. |
| HU12 — Realizar vistoria | `cadcheck.html` | Responder ao checklist. |
| HU13 — Registrar não conformidade | `detalhesNaoConformidade.html` | Descrever a avaria e anexar a foto. |
| HU14, HU16 — Assinar vistoria e enviar para manutenção | `assinaturaChecklist.html` | Concluir a vistoria. |
| HU15, HU17 — Recusar, consultar e imprimir vistoria | `verVistoria.html`, `oqfazer.php` (aba Vistorias) | Histórico e relatório. |
| HU18, HU20 — Solicitar locação e fazer vistorias | `oqfazer.php` (aba Locações), `cadcheck.html` | Ciclo da locação do cliente. |
| HU19, HU21 — Avaliar locação e registrar devolução | `oqfazer.php` (aba Locações) | Ciclo da locação do gestor. |
| HU22 — Painel de indicadores | `oqfazer.php` (aba Início) | Acompanhar a operação. |

### 9.2 Tela × Modelo Conceitual

| História de Usuário | Tela | Dados envolvidos | Tabela |
|---|---|---|---|
| HU01–HU06 | Login, cadastros, Meu perfil | E-mail, senha, perfil, CPF, CNH, endereço, situação | Usuarios |
| HU07 | Cadastro de veículo | Placa, marca, modelo, ano, quilometragem, finalidade, situação | Veiculos |
| HU08–HU11 | Criar/editar/ver checklist | Título, categoria, perguntas, tipos, subperguntas, aprovação | Checklists, Checklist_Perguntas |
| HU12 | Preencher vistoria | Veículo, quilometragem, respostas | Checklist_Execucoes, Checklist_Respostas |
| HU13 | Não conformidade | Descrição da avaria, foto | Checklist_Execucoes |
| HU14–HU16 | Assinatura, Ver vistoria | Assinaturas, recusa, envio para manutenção | Checklist_Execucoes, Veiculos |
| HU17 | Aba Vistorias | Filtros por placa, motorista e situação | Checklist_Execucoes |
| HU18–HU21 | Aba Locações | Cliente, veículo, datas, situação, quilometragem final | Locacoes, Checklist_Execucoes, Veiculos |
| HU22 | Painel | Totais de vistorias, frota, usuários e modelos | Todas |

---

## 10. PMC

<img width="3618" height="2031" alt="PMC_Axion" src="https://github.com/user-attachments/assets/eef26cd4-c789-4e08-87a9-6c2bae719f57" />


---

## DOCUMENTAÇÃO COMPLEMENTAR

- `database/axion_db_completo.sql` — banco com dados de exemplo.
- `database/seed.php` — script que popula o banco com volume maior de dados para demonstração.
- `docs/` — documentação de processo, discovery e requisitos usada durante o desenvolvimento.

---

## 11. COMO EXECUTAR O SISTEMA

### Pré-requisitos

- XAMPP (Apache + PHP 8 + MySQL/MariaDB).

### Passo a passo

1. Copie a pasta do projeto para `C:\xampp\htdocs\projetoIntegrador`.
2. No XAMPP Control Panel, clique em **Start** no **Apache** e no **MySQL**.
3. Escolha como quer o banco:
   - **Com os dados de exemplo:** no phpMyAdmin (`http://localhost/phpmyadmin`), vá em **Importar** e escolha `database/axion_db_completo.sql`. Ele traz veículos de frota e de locação, modelos de checklist, vistorias e locações.
   - **Banco vazio:** não importe nada. Na primeira vez que o sistema abre, ele cria o banco `axion_db`, as tabelas e as quatro contas de teste.
4. Abra `http://localhost/projetoIntegrador/`.

> ⚠️ O arquivo `axion_db_completo.sql` apaga e recria as tabelas (`DROP TABLE`). Não o importe sobre um banco que você já está usando sem fazer backup antes.

### Contas de teste

Todas usam a senha `mudar123`:

| Perfil | E-mail |
|---|---|
| Administrador | `admin@axion.com` |
| Supervisor | `supervisor@axion.com` |
| Motorista | `motorista@axion.com` |
| Cliente | `cliente@axion.com` |

Novos clientes também podem criar a própria conta pela tela de login; um gestor precisa aprovar.

### Porta diferente do MySQL

Se o MySQL do seu XAMPP usar outra porta (ex.: 3307), crie um arquivo `config.local.php` na raiz com `<?php $porta = 3307;`. Esse arquivo não vai para o GitHub.
