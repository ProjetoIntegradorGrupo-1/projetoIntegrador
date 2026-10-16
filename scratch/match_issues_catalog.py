import csv
import json
import re

with open('scratch/github_issues_inventory.csv', 'r', encoding='utf-8') as f:
    issues = list(csv.DictReader(f))

# Load our catalog of tasks extracted from all steps in transcript
transcript_path = 'C:/Users/PC/.gemini/antigravity/brain/9a1d4d49-9e85-4897-af1a-12e18c33c5b7/.system_generated/logs/transcript_full.jsonl'

# We will collect all (title_fragment -> {tempo, fluxo, prioridade})
catalog = {}

with open(transcript_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        content = data.get('content', '')
        if not content:
            continue
        
        # Regex for markdown table lines:
        # | # | [Task] Title | Assignee | Tempo | Fluxo | Prioridade | Status |
        matches = re.findall(r'\|\s*\*?#?(\d+)\*?\s*\|\s*`?\[?Task\]?\s*([^|`]+)`?\s*\|\s*([^|]+)\|\s*`?([^|`]+)`?\s*\|\s*([^|]+)\|\s*`?([^|`]+)`?\s*\|\s*([^|]+)\|', content)
        for m in matches:
            t_num = m[0].strip()
            raw_title = m[1].strip()
            tempo = m[3].strip()
            fluxo = m[4].strip()
            prio = m[5].strip()
            # clean values
            catalog[raw_title.lower()] = {
                'source_num': t_num,
                'title': raw_title,
                'tempo': tempo,
                'fluxo': fluxo,
                'prioridade': prio
            }

        # Regex for card blocks:
        # #### X. Card #Y: Title
        # * **Título:** `[Task] ...`
        # * **Tempo Estimado:** `...`
        # * **Fluxo:** `...`
        # * **Prioridade:** `...`
        cards = re.split(r'####\s*\d+\.\s*Card\s*#(\d+):', content)
        if len(cards) > 1:
            for i in range(1, len(cards), 2):
                c_num = cards[i]
                block = cards[i+1]
                tit = re.search(r'\*\s*\*\*Título:\*\*\s*`?([^`\n]+)`?', block)
                tem = re.search(r'\*\s*\*\*Tempo Estimado:\*\*\s*`?([^`\n]+)`?', block)
                flu = re.search(r'\*\s*\*\*Fluxo:\*\*\s*`?([^`\n]+)`?', block)
                pri = re.search(r'\*\s*\*\*Prioridade:\*\*\s*`?([^`\n]+)`?', block)
                if tit and tem and flu and pri:
                    t_clean = tit.group(1).replace('[Task]', '').strip()
                    catalog[t_clean.lower()] = {
                        'source_num': c_num,
                        'title': tit.group(1).strip(),
                        'tempo': tem.group(1).strip(),
                        'fluxo': flu.group(1).strip(),
                        'prioridade': pri.group(1).strip()
                    }

print(f"Catalog entries: {len(catalog)}")

# Match each issue in GitHub (1 to 133)
mapped_results = []
unmatched = []

for iss in issues:
    num = iss['Number']
    title = iss['Title']
    clean_title = title.replace('[Task]', '').strip().lower()
    
    # Try exact or substring match in catalog
    match = None
    if clean_title in catalog:
        match = catalog[clean_title]
    else:
        # try finding best match
        best_overlap = 0
        best_entry = None
        for k, v in catalog.items():
            words_iss = set(clean_title.split())
            words_cat = set(k.split())
            overlap = len(words_iss.intersection(words_cat))
            if overlap > best_overlap and overlap >= 3:
                best_overlap = overlap
                best_entry = v
        if best_entry:
            match = best_entry

    if match:
        mapped_results.append({
            'number': num,
            'title': title,
            'tempo': match['tempo'],
            'fluxo': match['fluxo'],
            'prioridade': match['prioridade'],
            'matched_to': match['title']
        })
    else:
        unmatched.append({
            'number': num,
            'title': title
        })

print(f"Mapped issues: {len(mapped_results)}")
print(f"Unmatched issues: {len(unmatched)}")
with open('scratch/mapped_issues_summary.json', 'w', encoding='utf-8') as out:
    json.dump({'mapped': mapped_results, 'unmatched': unmatched}, out, indent=2, ensure_ascii=False)

