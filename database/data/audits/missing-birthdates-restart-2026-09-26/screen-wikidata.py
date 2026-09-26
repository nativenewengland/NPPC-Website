import json
import time
import urllib.parse
import urllib.request
from pathlib import Path


ROOT = Path(__file__).resolve().parent
ENDPOINT = "https://query.wikidata.org/sparql"
UA = "NPPC missing birthdate research/1.0 (politicalprisonercoalition.org)"
BATCH_SIZE = 80


def sparql(query, attempts=7):
    data = urllib.parse.urlencode({"query": query, "format": "json"}).encode()
    for attempt in range(attempts):
        try:
            request = urllib.request.Request(
                ENDPOINT,
                data=data,
                headers={"User-Agent": UA, "Accept": "application/sparql-results+json"},
            )
            with urllib.request.urlopen(request, timeout=90) as response:
                return json.load(response)
        except Exception:
            if attempt + 1 == attempts:
                raise
            time.sleep(min(45, 3 * (attempt + 1)))


inventory = json.loads((ROOT / "post-inventory.json").read_text(encoding="utf-8-sig"))
profiles = inventory["profiles"]
results = []
checkpoint = ROOT / "wikidata-checkpoint.json"
if checkpoint.exists():
    results = json.loads(checkpoint.read_text(encoding="utf-8"))

for offset in range(len(results), len(profiles), BATCH_SIZE):
    batch = profiles[offset : offset + BATCH_SIZE]
    names = " ".join(json.dumps(p["name"], ensure_ascii=False) + "@en" for p in batch)
    query = f'''SELECT ?wantedLabel ?item ?itemLabel ?itemDescription ?dob ?dod WHERE {{
      VALUES ?wantedLabel {{ {names} }}
      ?item rdfs:label ?wantedLabel.
      FILTER(LANG(?wantedLabel) = "en")
      OPTIONAL {{ ?item wdt:P569 ?dob. }}
      OPTIONAL {{ ?item wdt:P570 ?dod. }}
      SERVICE wikibase:label {{ bd:serviceParam wikibase:language "en". }}
    }}'''
    bindings = sparql(query)["results"]["bindings"]
    by_name = {}
    for row in bindings:
        by_name.setdefault(row["wantedLabel"]["value"], []).append(
            {
                "item": row["item"]["value"],
                "label": row.get("itemLabel", {}).get("value"),
                "description": row.get("itemDescription", {}).get("value"),
                "dob": row.get("dob", {}).get("value"),
                "dod": row.get("dod", {}).get("value"),
            }
        )
    for profile in batch:
        candidates = [c for c in by_name.get(profile["name"], []) if c.get("dob")]
        if candidates:
            results.append(
                {
                    "id": profile["id"],
                    "name": profile["name"],
                    "slug": profile["slug"],
                    "death_date": profile.get("death_date"),
                    "era": profile.get("era"),
                    "description": profile.get("description"),
                    "cases": profile.get("cases", []),
                    "candidates": candidates,
                }
            )
    checkpoint.write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"screened {min(offset + BATCH_SIZE, len(profiles))}/{len(profiles)}; candidates {len(results)}", flush=True)
    time.sleep(0.5)

(ROOT / "wikidata-candidates.json").write_text(
    json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8"
)
print(json.dumps({"profiles": len(profiles), "candidate_profiles": len(results)}, indent=2))
