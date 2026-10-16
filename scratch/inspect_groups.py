import json

with open('scratch/script_match_13.txt', 'r', encoding='utf-8') as f:
    data = json.loads(f.read())

print("Groups:", data.get('groups'))
for g in data.get('groupedItems', []):
    print("Group keys:", g.keys())
    for k, v in g.items():
        if isinstance(v, list):
            print(f"  List key '{k}', len={len(v)}")
        else:
            print(f"  Key '{k}': {v}")

