import re

file_path = r'C:\Users\PC\.gemini\antigravity\brain\9a1d4d49-9e85-4897-af1a-12e18c33c5b7\.system_generated\steps\2014\content.md'
with open(file_path, 'r', encoding='utf-8') as f:
    text = f.read()

# Look for csrf tokens or meta tags
meta_csrf = re.findall(r'<meta[^>]*csrf[^>]*>', text, re.IGNORECASE)
print("CSRF meta tags:", meta_csrf)

# Look for memex urls or apis
memex_urls = re.findall(r'https://[^"\'\s]*memex[^"\'\s]*', text)
print("Memex URLs found:", set(memex_urls))

# Look for api endpoints
api_matches = re.findall(r'/api/[^"\'\s]*|/graphql[^"\'\s]*', text)
print("API endpoints found:", set(api_matches[:10]))

