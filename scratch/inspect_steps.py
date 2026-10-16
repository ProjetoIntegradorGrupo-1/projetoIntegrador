import json

transcript_path = 'C:/Users/PC/.gemini/antigravity/brain/9a1d4d49-9e85-4897-af1a-12e18c33c5b7/.system_generated/logs/transcript_full.jsonl'

steps_to_check = [1692, 1694, 1696, 1698, 1700, 1702, 1704, 1706, 1708, 1710, 1712, 1714, 1716, 1718, 1722, 1735, 1761, 1770, 1779]

with open(transcript_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        idx = data.get('step_index')
        if idx in steps_to_check:
            content = data.get('content', '')
            lines = content.split('\n')
            preview = [l for l in lines if '|' in l or 'Task' in l][:5]
            print(f"=== Step {idx} (Length: {len(content)}) ===")
            print('\n'.join(preview))
            print()

