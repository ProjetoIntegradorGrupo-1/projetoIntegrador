import csv
import json
import re

# Load issues from inventory
issues = []
with open('scratch/github_issues_inventory.csv', 'r', encoding='utf-8') as f:
    reader = csv.DictReader(f)
    for row in reader:
        issues.append(row)

print(f"Total issues loaded from CSV: {len(issues)}")

# Now let's scan all transcript files or step content to find mappings for tasks
transcript_path = 'C:/Users/PC/.gemini/antigravity/brain/9a1d4d49-9e85-4897-af1a-12e18c33c5b7/.system_generated/logs/transcript_full.jsonl'

task_mappings = {}

with open(transcript_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        content = data.get('content', '')
        if not content:
            continue
        # Check markdown table rows or card definitions
        # e.g. | **#129** | `[Task] ...` | ... | `S (1h)` | Fluxo 1 | `P0` |
        table_matches = re.findall(r'\|\s*\*?#?(\d+)\*?\s*\|\s*`?\[?Task\]?\s*([^|`]+)`?\s*\|\s*([^|]+)\|\s*`?([^|`]+)`?\s*\|\s*([^|]+)\|\s*`?([^|`]+)`?\s*\|\s*([^|]+)\|', content)
        for m in table_matches:
            num = int(m[0])
            title = m[1].strip()
            assignee = m[2].strip()
            tempo = m[3].strip()
            fluxo = m[4].strip()
            prioridade = m[5].strip()
            task_mappings[num] = {
                'title': title,
                'assignee': assignee,
                'tempo': tempo,
                'fluxo': fluxo,
                'prioridade': prioridade
            }
        
        # Also check card format e.g.
        # #### 1. Card #161: ...
        # * **Título:** `...`
        # * **Tempo Estimado:** `L (3h)`
        # * **Fluxo:** `Fluxo 1 e Fluxo 3`
        # * **Prioridade:** `P0`
        card_blocks = re.split(r'####\s*\d+\.\s*Card\s*#(\d+):', content)
        if len(card_blocks) > 1:
            for i in range(1, len(card_blocks), 2):
                c_num = int(card_blocks[i])
                block = card_blocks[i+1]
                t_match = re.search(r'\*\s*\*\*Tempo Estimado:\*\*\s*`?([^`\n]+)`?', block)
                f_match = re.search(r'\*\s*\*\*Fluxo:\*\*\s*`?([^`\n]+)`?', block)
                p_match = re.search(r'\*\s*\*\*Prioridade:\*\*\s*`?([^`\n]+)`?', block)
                tit_match = re.search(r'\*\s*\*\*Título:\*\*\s*`?([^`\n]+)`?', block)
                if t_match and f_match and p_match:
                    task_mappings[c_num] = {
                        'title': tit_match.group(1).strip() if tit_match else '',
                        'tempo': t_match.group(1).strip(),
                        'fluxo': f_match.group(1).strip(),
                        'prioridade': p_match.group(1).strip()
                    }

print(f"Total task mappings found in transcript: {len(task_mappings)}")
sample_keys = sorted(task_mappings.keys())[:10]
print("Sample keys:", sample_keys)
for k in sample_keys:
    print(f"Task #{k}: {task_mappings[k]}")

