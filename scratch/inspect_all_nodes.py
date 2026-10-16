import json

with open('scratch/script_match_13.txt', 'r', encoding='utf-8') as f:
    data = json.loads(f.read())

print("Groups and their total counts:")
for g in data.get('groups', {}).get('nodes', []):
    print(f"Group: {g.get('groupValue')} | Count: {g.get('totalCount', {}).get('value')} | FieldSum: {g.get('fieldMetrics')}")

for idx, g in enumerate(data.get('groupedItems', [])):
    nodes = g.get('nodes', [])
    print(f"\nGroupedItem {idx} (nodes={len(nodes)}):")
    for n in nodes[:5]:
        issue_num = None
        title = None
        cols = {}
        for col in n.get('memexProjectColumnValues', []):
            cid = str(col.get('memexProjectColumnId'))
            val = col.get('value')
            if cid == 'Title' and isinstance(val, dict):
                issue_num = val.get('number')
                title = val.get('title', {}).get('raw')
            else:
                cols[cid] = val
        print(f"  Issue #{issue_num}: '{title}' | Cols: {cols}")

