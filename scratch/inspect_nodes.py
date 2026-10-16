import json

with open('scratch/script_match_13.txt', 'r', encoding='utf-8') as f:
    data = json.loads(f.read())

for g in data.get('groupedItems', []):
    nodes = g.get('nodes', [])
    if nodes:
        sample = nodes[0]
        print("Sample node keys:", sample.keys())
        print("Sample node content:", json.dumps(sample, indent=2)[:800])
        break

