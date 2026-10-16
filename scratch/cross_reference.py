import csv

with open('scratch/github_issues_inventory.csv', 'r', encoding='utf-8') as f:
    issues = list(csv.DictReader(f))

with open('scratch/repo_issues_list.txt', 'w', encoding='utf-8') as out:
    out.write(f"Total issues in repo: {len(issues)}\n")
    for iss in issues:
        out.write(f"#{iss['Number']}: {iss['Title']} (State: {iss['State']}, Assignees: {iss['Assignees']})\n")

print("Saved to scratch/repo_issues_list.txt")

