import json
import re

transcript_path = 'C:/Users/PC/.gemini/antigravity/brain/9a1d4d49-9e85-4897-af1a-12e18c33c5b7/.system_generated/logs/transcript_full.jsonl'

found_steps = []
with open(transcript_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        if data.get('source') == 'MODEL':
            content = data.get('content', '')
            if 'Fluxo 1' in content and 'Prioridade' in content and 'Tempo' in content:
                found_steps.append((data.get('step_index'), len(content)))

print("Found steps:", found_steps)

