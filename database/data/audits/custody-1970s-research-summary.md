# 1970s custody research review

The individual research pass covers **1,152 unique prisoner profiles**, split into three disjoint sets of 384. The starting inventory contained 1,204 target cases and 907 profiles flagged for potential gaps. It included custody or exile spanning the 1970s, dates or case text within the decade, and era matches; not every match represented a new 1970s imprisonment.

The accompanying `custody-1970s-research-ledger.json` preserves each profile’s findings, evidence, source links, proposed changes, and unresolved questions. It combines the latest three agent findings files and records their hashes. All 1,152 records have an individual finding; this is completion of the individual search pass, **not exhaustion of every archive or resolution of every missing field**.

## What the review established

Research produced supported exact dates, month-level dates, actual time-served totals, corrections to conflated custody episodes, and confirmation of fields already present. It also identified release dates incorrectly copied from acquittals or other court events, years spent at liberty counted as imprisonment, and cases mixing separate proceedings. Numbered batch payloads contain the final guarded changes. Their live-verification audits, rather than a research recommendation’s status, establish deployment.

Bios and unrelated profile fields were outside this custody-update scope. A sentence, verdict, release order, or planned parole date was not treated automatically as physical admission or release. Where a source only established a month, day-level precision was not invented. Unknown endpoints and identity conflicts were held for further research.

## Limits and unresolved work

Many individual searches found only an existing date, a partial lead, or no stronger evidence for a missing endpoint. **223 profiles have no retained source URL in their finding**; these remain limited-search records, not verified corrections. The presence of a URL in the other 929 records likewise does not mean the source supplied a usable missing date. Short or unavailable archival passages, name collisions, incomplete case identification, and conflicting retrospective accounts remain documented limitations.

Examples of held issues include Tom Stevens’s exact arrest-day lead awaiting original-page verification, unverified physical-release dates where sources only give parole decisions, and later custody intervals whose admission or release was not corroborated. The underlying per-profile findings retain these distinctions. Prepared recommendations can differ from final reviewed payloads; those payloads control applied values.

## Verified live changes

Final verification at **2026-10-16 01:00:17 UTC** confirmed **96 profiles, 104 distinct cases, and 106 case operations** across **26 batches (771–796)**. Every changed field matched its final intended value, unrelated profile fields were preserved, and every batch replayed with zero changes. These counts include Robert Robideau’s correction in batch 796.

`custody-1970s-final-live-verification.json` records the final checks. `custody-1970s-changed-profiles.md` lists the affected profiles, changed fields, and batches. The progress ledger now marks the individual research pass complete while retaining unresolved findings; completion does not mean that every missing date was found or every archive exhausted.
