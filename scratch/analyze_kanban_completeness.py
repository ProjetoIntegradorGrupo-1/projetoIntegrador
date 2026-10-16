import json

with open('scratch/script_match_13.txt', 'r', encoding='utf-8') as f:
    data = json.loads(f.read())

fields_summary = []
for idx, g in enumerate(data.get('groupedItems', [])):
    nodes = g.get('nodes', [])
    for n in nodes:
        item_id = n.get('id')
        issue_num = None
        title = None
        priority = None
        size = None
        fluxo = None
        status = None
        for col in n.get('memexProjectColumnValues', []):
            cid = str(col.get('memexProjectColumnId'))
            val = col.get('value')
            if cid == 'Title' and isinstance(val, dict):
                issue_num = val.get('number')
                title = val.get('title', {}).get('raw')
            elif cid == 'Status' and isinstance(val, dict):
                status = val.get('id')
            elif cid == '402863747' and isinstance(val, dict):
                priority = val.get('id')
            elif cid == '402863748' and isinstance(val, dict):
                size = val.get('id')
            elif cid == '411293438' and isinstance(val, dict):
                fluxo = val.get('id')
        fields_summary.append({
            'itemId': item_id,
            'issue': issue_num,
            'title': title,
            'status': status,
            'priority': priority,
            'size': size,
            'fluxo': fluxo
        })

print(f"Total items inspected: {len(fields_summary)}")
with open('scratch/current_kanban_items.json', 'w', encoding='utf-8') as out:
    json.dump(fields_summary, out, indent=2, ensure_ascii=False)

missing_priority = [x for x in fields_summary if not x['priority']]
missing_size = [x for x in fields_summary if not x['size']]
missing_fluxo = [x for x in fields_summary if not x['fluxo']]

print(f"Items missing Priority: {len(missing_priority)}")
print(f"Items missing Size (Tempo): {len(missing_size)}")
print(f"Items missing Fluxo: {len(missing_fluxo)}")

