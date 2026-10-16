import json

with open('scratch/script_match_15.txt', 'r', encoding='utf-8') as f:
    fields = json.loads(f.read())

for field in fields:
    print(f"ID: {field.get('id')} | Name: {field.get('name')} | Type: {field.get('dataType')} | DB_ID: {field.get('databaseId')}")
    if field.get('settings') and 'options' in field['settings']:
        for opt in field['settings']['options']:
            print(f"   Option: ID={opt.get('id')} | Name='{opt.get('name')}'")

