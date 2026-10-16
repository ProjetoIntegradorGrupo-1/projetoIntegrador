import json

with open('scratch/script_match_13.txt', 'r', encoding='utf-8') as f:
    data = json.loads(f.read())

print("Total count:", data.get('totalCount'))
grouped_items = data.get('groupedItems', [])
print("Grouped items length:", len(grouped_items))

items_list = []
for group in grouped_items:
    items = group.get('items', [])
    for it in items:
        items_list.append(it)

print("Total extracted items:", len(items_list))
if items_list:
    sample = items_list[0]
    print("Sample item keys:", sample.keys())
    print("Sample item content:", json.dumps(sample, indent=2)[:1000])

