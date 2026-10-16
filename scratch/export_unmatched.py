import json

with open('scratch/mapped_issues_summary.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

with open('scratch/unmatched_list.txt', 'w', encoding='utf-8') as out:
    for u in data['unmatched']:
        out.write(f"#{u['number']}: {u['title']}\n")

print(f"Written {len(data['unmatched'])} unmatched issues to scratch/unmatched_list.txt")

