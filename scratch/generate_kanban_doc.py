import json

with open('scratch/kanban_mapping_completo.json', 'r', encoding='utf-8') as f:
    tasks = json.load(f)

md_lines = [
    "# Tabela Consolidada de Métricas do Kanban (133 Issues)\n",
    "**Projeto Integrador IFES — Equipe AXION**  ",
    "**GitHub Projects:** [Kanban 2](https://github.com/users/ProjetoIntegradorGrupo-1/projects/4)  \n",
    "Esta tabela consolida todas as **133 tarefas** do repositório, mapeando os atributos formais de **Prioridade (P0, P1, P2)**, **Fluxo (Fluxo 1, Fluxo 2, Fluxo 3)** e **Tempo / Estimativa (XS, S, M, L)** em total conformidade com a prática de engenharia e gestão ágil do projeto.\n",
    "| Issue | Título da Tarefa | Responsável(is) | Prioridade | Tempo / Tamanho | Horas | Fluxo | Status |",
    "| :---: | :--- | :--- | :---: | :---: | :---: | :---: | :---: |"
]

for t in tasks:
    assignees = t['assignees'] if t['assignees'] else "—"
    state = "**Done**" if t['state'] == 'closed' else "*Backlog*"
    md_lines.append(f"| **#{t['number']}** | `{t['title']}` | {assignees} | **{t['priority']}** | `{t['size']}` | {t['estimate_hours']}h | `{t['fluxo']}` | {state} |")

md_content = "\n".join(md_lines)

with open('docs/kanban_tabela_completa.md', 'w', encoding='utf-8') as out:
    out.write(md_content)

print(f"Generated docs/kanban_tabela_completa.md with {len(tasks)} tasks.")

