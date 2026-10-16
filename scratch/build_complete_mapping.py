import csv
import json

with open('scratch/github_issues_inventory.csv', 'r', encoding='utf-8') as f:
    issues = list(csv.DictReader(f))

# Load the 128 task catalog entries we extracted
with open('scratch/mapped_issues_summary.json', 'r', encoding='utf-8') as f:
    mapped_data = json.load(f)

# Priority option IDs: P0: 79628723, P1: 0a877460, P2: da944a9c
# Size option IDs: XS: 6c6483d2, S: f784b110, M: 7515a9f1, L: 817d0097, XL: db339eb2
# Fluxo option IDs: Fluxo 1: 84546fa3, Fluxo 2: 03b8ba2e, Fluxo 3: 3ededd86

# Default sensible fallbacks for the initial issues (#1 - #40) based on their titles:
initial_defaults = {
    1: {'prio': 'P2', 'tempo': 'XS (0,5h)', 'fluxo': 'Fluxo 1'},
    2: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    3: {'prio': 'P0', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    4: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    5: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    6: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    7: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    8: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    9: {'prio': 'P0', 'tempo': 'L (4h)', 'fluxo': 'Fluxo 1'},
    10: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    11: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    12: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    13: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    14: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    15: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    16: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    17: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    18: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    19: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    20: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    21: {'prio': 'P2', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    22: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    23: {'prio': 'P0', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    24: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    25: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    26: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    27: {'prio': 'P0', 'tempo': 'L (4h)', 'fluxo': 'Fluxo 1'},
    28: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    29: {'prio': 'P0', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    30: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    31: {'prio': 'P1', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    32: {'prio': 'P0', 'tempo': 'L (4h)', 'fluxo': 'Fluxo 1'},
    33: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'},
    34: {'prio': 'P1', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    35: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    36: {'prio': 'P1', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    37: {'prio': 'P0', 'tempo': 'M (2h)', 'fluxo': 'Fluxo 1'},
    38: {'prio': 'P0', 'tempo': 'L (4h)', 'fluxo': 'Fluxo 1'},
    39: {'prio': 'P0', 'tempo': 'L (4h)', 'fluxo': 'Fluxo 1'},
    40: {'prio': 'P1', 'tempo': 'S (1h)', 'fluxo': 'Fluxo 1'}
}

# Create a lookup from mapped_data
mapped_dict = {int(x['number']): x for x in mapped_data['mapped']}

final_full_mapping = []

for iss in issues:
    num = int(iss['Number'])
    title = iss['Title']
    assignees = iss['Assignees']
    state = iss['State']
    
    if num in mapped_dict:
        m = mapped_dict[num]
        prio = m['prioridade']
        tempo = m['tempo']
        fluxo = m['fluxo']
    elif num in initial_defaults:
        d = initial_defaults[num]
        prio = d['prio']
        tempo = d['tempo']
        fluxo = d['fluxo']
    else:
        prio = 'P1'
        tempo = 'S (1h)'
        fluxo = 'Fluxo 1'

    # Normalization
    if 'P0' in prio:
        prio_norm = 'P0'
    elif 'P2' in prio:
        prio_norm = 'P2'
    else:
        prio_norm = 'P1'

    if '4h' in tempo or '3h' in tempo or 'L' in tempo:
        size_norm = 'L (4h)'
        hours = 4.0
    elif '2h' in tempo or '2.5h' in tempo or 'M' in tempo:
        size_norm = 'M (2h)'
        hours = 2.0
    elif '0,5h' in tempo or 'XS' in tempo:
        size_norm = 'XS (0,5h)'
        hours = 0.5
    else:
        size_norm = 'S (1h)'
        hours = 1.0

    if 'Fluxo 2' in fluxo:
        fluxo_norm = 'Fluxo 2'
    elif 'Fluxo 3' in fluxo:
        fluxo_norm = 'Fluxo 3'
    else:
        fluxo_norm = 'Fluxo 1'

    final_full_mapping.append({
        'number': num,
        'title': title,
        'assignees': assignees,
        'state': state,
        'priority': prio_norm,
        'size': size_norm,
        'estimate_hours': hours,
        'fluxo': fluxo_norm
    })

print(f"Total mapped issues in final list: {len(final_full_mapping)}")
with open('scratch/kanban_mapping_completo.json', 'w', encoding='utf-8') as out:
    json.dump(final_full_mapping, out, indent=2, ensure_ascii=False)

print("Saved to scratch/kanban_mapping_completo.json")

