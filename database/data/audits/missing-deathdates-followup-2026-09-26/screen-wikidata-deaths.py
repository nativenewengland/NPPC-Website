import csv
import json
import re
from pathlib import Path

source_root = Path(__file__).resolve().parents[1] / "missing-birthdates-restart-2026-09-26"
root = Path(__file__).resolve().parent
root.mkdir(parents=True, exist_ok=True)

records = json.loads((source_root / "wikidata-candidates.json").read_text(encoding="utf-8"))
rows = []
for profile in records:
    if profile.get("death_date"):
        continue
    candidates = profile.get("candidates", [])
    for candidate in candidates:
        dod = candidate.get("dod") or ""
        if not re.match(r"^[+-]?\d{1,4}-", dod):
            continue
        rows.append({
            "profile_id": profile["id"],
            "name": profile["name"],
            "slug": profile["slug"],
            "era": profile.get("era") or "",
            "candidate_count": len(candidates),
            "qid": candidate["item"].rsplit("/", 1)[-1],
            "dob": candidate.get("dob") or "",
            "dod": dod,
            "candidate_description": candidate.get("description") or "",
            "profile_description": re.sub(r"\s+", " ", profile.get("description") or "")[:700],
        })

rows.sort(key=lambda row: (row["candidate_count"], row["name"], row["qid"]))
(root / "wikidata-death-candidates.json").write_text(
    json.dumps(rows, indent=2, ensure_ascii=False) + "\n", encoding="utf-8"
)
with (root / "wikidata-death-candidates.csv").open("w", newline="", encoding="utf-8-sig") as handle:
    writer = csv.DictWriter(handle, fieldnames=rows[0].keys())
    writer.writeheader()
    writer.writerows(rows)

unique = [row for row in rows if row["candidate_count"] == 1]
print(json.dumps({"candidate_rows": len(rows), "unique_label_candidates": len(unique)}, indent=2))
for index, row in enumerate(unique[:250], 1):
    print(
        f"{index:03} | {row['name']} | {row['era']} | {row['qid']} | {row['dob']} | {row['dod']} | "
        f"{row['candidate_description']} | PROFILE: {row['profile_description'][:300]}"
    )
