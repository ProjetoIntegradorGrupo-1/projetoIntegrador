import re

file_path = r'C:\Users\PC\.gemini\antigravity\brain\9a1d4d49-9e85-4897-af1a-12e18c33c5b7\.system_generated\steps\2014\content.md'

with open(file_path, 'r', encoding='utf-8') as f:
    text = f.read()

print("File size:", len(text))

# Let's search for "Kanban 2" or project fields or issues
matches = re.findall(r'\[Task\].*?', text)
print("Found [Task] mentions:", len(matches))

# Look for embedded JSON or script tags with project data
scripts = re.findall(r'<script[^>]*>(.*?)</script>', text, re.DOTALL)
print("Found script tags:", len(scripts))

for i, s in enumerate(scripts):
    if 'items' in s or 'fields' in s or 'projects' in s:
        print(f"Script {i} contains keywords, len={len(s)}")
        with open(f'scratch/script_{i}.txt', 'w', encoding='utf-8') as out:
            out.write(s[:5000])

