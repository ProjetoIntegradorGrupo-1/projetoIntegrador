import re
import json

file_path = r'C:\Users\PC\.gemini\antigravity\brain\9a1d4d49-9e85-4897-af1a-12e18c33c5b7\.system_generated\steps\2014\content.md'

with open(file_path, 'r', encoding='utf-8') as f:
    text = f.read()

scripts = re.findall(r'<script[^>]*>(.*?)</script>', text, re.DOTALL)

for i, s in enumerate(scripts):
    if 'Fluxo' in s or 'Prioridade' in s or 'Tempo' in s or 'P0' in s:
        print(f"Found in script {i}, len={len(s)}")
        with open(f'scratch/script_match_{i}.txt', 'w', encoding='utf-8') as out:
            out.write(s)

