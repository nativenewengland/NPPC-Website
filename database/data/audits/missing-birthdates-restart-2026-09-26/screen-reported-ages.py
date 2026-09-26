import json
import re
from pathlib import Path

root = Path(__file__).resolve().parent
data = json.loads((root / "post-inventory.json").read_text(encoding="utf-8"))
age_pattern = re.compile(r"\b(?:aged?|was|then|about|approximately|roughly|nearly)?\s*(\d{1,2})[- ]year[- ]old\b|\baged?\s+(\d{1,2})\b", re.I)
year_pattern = re.compile(r"\b(17\d{2}|18\d{2}|19\d{2}|20\d{2})\b")
url_pattern = re.compile(r'href=["\'](https?://[^"\']+)', re.I)
rows = []
for profile in data["profiles"]:
    text = re.sub(r"\s+", " ", profile.get("description") or "")
    for match in age_pattern.finditer(text):
        age = int(match.group(1) or match.group(2))
        if not 12 <= age <= 95:
            continue
        start = max(text.rfind(".", 0, match.start()) + 1, 0)
        end_pos = text.find(".", match.end())
        end = len(text) if end_pos < 0 else end_pos + 1
        sentence = text[start:end].strip()
        years = [int(y) for y in year_pattern.findall(sentence)]
        if not years:
            # Include an adjacent sentence on either side when the age sentence omits the date.
            context = text[max(0, start - 260):min(len(text), end + 260)]
            years = [int(y) for y in year_pattern.findall(context)]
        if not years:
            case_years = []
            for case in profile.get("cases", []):
                for field in ("arrest_date", "incarceration_date", "release_date"):
                    value = case.get(field) or ""
                    if re.match(r"^\d{4}", value):
                        case_years.append(int(value[:4]))
            years = case_years
        if not years:
            continue
        # Choose the event year closest to the profile's era and compatible with the reported age.
        era_start = int((profile.get("era") or "0000s")[:4]) if re.match(r"^\d{4}s$", profile.get("era") or "") else years[0]
        event_year = min(years, key=lambda y: (abs(y - era_start), y))
        birth_year = event_year - age
        if not 1600 <= birth_year <= 2015:
            continue
        urls = url_pattern.findall(profile.get("body") or "")
        rows.append({
            "id": profile["id"],
            "name": profile["name"],
            "slug": profile["slug"],
            "era": profile.get("era"),
            "age": age,
            "event_year": event_year,
            "birth_year": birth_year,
            "sentence": sentence,
            "description": text[:500],
            "source_url": urls[0] if urls else None,
            "source_count": len(urls),
        })
        break

(root / "reported-age-candidates.json").write_text(json.dumps(rows, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
print(json.dumps({"candidates": len(rows), "with_sources": sum(bool(r["source_url"]) for r in rows)}, indent=2))
for i, row in enumerate(rows[:300], 1):
    print(f"{i:03} | {row['name']} | age {row['age']} in {row['event_year']} => {row['birth_year']} | {row['sentence']} | {row['source_url'] or 'NO SOURCE'}")
