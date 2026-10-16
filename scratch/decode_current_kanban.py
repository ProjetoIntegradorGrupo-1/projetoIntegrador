import json

with open('scratch/current_kanban_items.json', 'r', encoding='utf-8') as f:
    items = json.load(f)

# Priority mapping: 79628723=P0, 0a877460=P1, da944a9c=P2
prio_map = {'79628723': 'P0', '0a877460': 'P1', 'da944a9c': 'P2'}
# Size mapping: 6c6483d2='XS (0,5h)', f784b110='S (1h)', 7515a9f1='M (2h)', 817d0097='L (4h)', db339eb2='XL(8h)'
size_map = {'6c6483d2': 'XS (0,5h)', 'f784b110': 'S (1h)', '7515a9f1': 'M (2h)', '817d0097': 'L (4h)', 'db339eb2': 'XL(8h)'}
# Fluxo mapping: 84546fa3='Fluxo 1', 03b8ba2e='Fluxo 2', 3ededd86='Fluxo 3', 32eaaee9='Fluxo 4', 331103a9='Fluxo 5'
fluxo_map = {'84546fa3': 'Fluxo 1', '03b8ba2e': 'Fluxo 2', '3ededd86': 'Fluxo 3', '32eaaee9': 'Fluxo 4', '331103a9': 'Fluxo 5'}

with open('scratch/kanban_current_decoded.txt', 'w', encoding='utf-8') as out:
    for it in sorted(items, key=lambda x: x.get('issue') or 0):
        iss = it.get('issue')
        tit = it.get('title')
        p = prio_map.get(it.get('priority'), 'None')
        s = size_map.get(it.get('size'), 'None')
        f = fluxo_map.get(it.get('fluxo'), 'None')
        out.write(f"#{iss}: {tit}\n   Priority: {p} | Size: {s} | Fluxo: {f} | ItemId: {it.get('itemId')}\n")

print("Saved to scratch/kanban_current_decoded.txt")

