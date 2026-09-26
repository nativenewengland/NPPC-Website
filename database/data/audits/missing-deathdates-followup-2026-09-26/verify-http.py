import json
import sys
import urllib.request

entries = json.load(open(sys.argv[1], encoding='utf-8'))
results = []
for entry in entries:
    url = 'https://politicalprisonercoalition.org/prisoner/' + entry['slug']
    request = urllib.request.Request(url, headers={'User-Agent': 'NPPC-audit/1.0'})
    with urllib.request.urlopen(request, timeout=30) as response:
        results.append({'slug': entry['slug'], 'status': response.status})
if any(item['status'] != 200 for item in results):
    raise SystemExit(json.dumps(results, indent=2))
print(json.dumps({'verified_count': len(results), 'results': results}, indent=2))
