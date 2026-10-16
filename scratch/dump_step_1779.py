import json

transcript_path = 'C:/Users/PC/.gemini/antigravity/brain/9a1d4d49-9e85-4897-af1a-12e18c33c5b7/.system_generated/logs/transcript_full.jsonl'

with open(transcript_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        idx = data.get('step_index')
        if idx == 1779:
            with open('scratch/step_1779_content.md', 'w', encoding='utf-8') as out:
                out.write(data.get('content', ''))
            print("Successfully saved step 1779 content to scratch/step_1779_content.md")
            break

