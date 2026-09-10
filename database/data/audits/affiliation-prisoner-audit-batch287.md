# Batch 287 — Black Liberation Front research

Reviewed September 10, 2026. Three missing people and three custody cases. The current read-only inventory contains 8,786 profiles, including hidden identities and aliases; pending batches 271–286 were also checked. Existing records are preserved in full. No production data has been changed.

This pass distinguishes East Palo Alto’s organization from Pennsylvania prison organizing and the separate Harlem and British groups using the same name. It is not a complete BLF membership roster.

| Person | Basis for inclusion | Dates entered | Held unresolved |
| --- | --- | --- | --- |
| Clifford “Lumumba” Futch | Documented prolonged segregation and prison-rights litigation; political interpretation attributed to the lawyer’s report. | None: no verified boundaries for the administrative-custody case. | Vital dates, identified portrait, exact isolation endpoints and later status. |
| Keith King | BLF member; sentenced custody confirmed after the June 24, 1971 hearing. | Arrest May 4, 1971; sentenced June 24, 1971. | Exact statutory counts, final release, actual total served and vital dates. |
| Renard King | Named defendant supported by the BLF; actual detention documented before and after sentencing. | Arrest May 4, 1971; sentenced June 24, 1971. | Same date/count uncertainties; formal BLF membership unproven. Broader movement affiliation used. |

## Sources and case distinctions

**Futch:** [Jill Soffiyah Elijah’s conditions-of-confinement paper, p.5](https://www.freedomarchives.org/Documents/Pubs/Conditions%20of%20Confinement.pdf) identifies him in its Pennsylvania prison-organizing account and reports fourteen control-unit years. This figure remains attributed prose, not a new criminal sentence or exact duration counter. [Ford v. Beister, April 16, 1986](https://www.casemine.com/judgement/us/59148d99add7b04934545ecd), independently establishes SCI Dallas restricted custody and gives the officials’ security rationale. The opinion rejects most challenged claims but remands the religious-services issue; it does not find that all confinement was unlawful political punishment.

The new profile briefly contextualizes a separate prosecution without counting his original nonpolitical convictions as activism cases. [Commonwealth v. Futch, November 24, 1976](https://law.justia.com/cases/pennsylvania/supreme-court/1976/469-pa-422-0.html) reversed the death judgment because of excluded questions about witness-status bias. Contrary to a secondary account, the court did **not** hold that refusing racial-bias questions required reversal. [Bill Lofquist’s scholarly history](https://state-killings-in-the-steel-city.org/2018/02/12/clifford-b-futch/) reports the later 1981 acquittal and explains the separate convictions. Acquittal is not treated as a release. [Workers Viewpoint, March 8, 1980, p.2](https://www.marxists.org/history/erol/periodicals/workers-viewpoint/wv-5-8.pdf) corroborates the Lumumba alias; its characterization of the victim is not adopted as a judicial finding.

**King brothers:** [Pamoja Venceremos, May 18, 1971, p.3](https://www.marxists.org/history/erol/periodicals/pamoja-venceremos/v01n01-may-18-1971-PV.pdf) records the May 4 incident and Renard’s continuing jail detention; Keith obtained bail. [July 2, 1971, pp.12–13](https://www.marxists.org/history/erol/periodicals/pamoja-venceremos/v01n04-jul-02-1971-PV.pdf) records subsequent sentencing and asks readers to visit both in county jail. These contemporaneous reports establish actual custody, not merely imposed penalties. Scans were visually checked. Allegations of political repression and brutality are attributed to the movement paper, not presented as court findings.

Keith’s June 23–24 overnight traffic detention is distinct from the longer case and is not independently added. Neither the four- nor five-month penalty is assumed to have been served in full. The sources do not establish the precise statutory counts. Renard’s surname follows the explicit King-family identification; no unsourced middle name is added.

## Images, namesakes and preservation

- No photographs are added. The May newspaper contains uncaptained images whose individual identities could not be confirmed. A March 5, 1980 Pittsburgh clipping reproduced in Lofquist’s history shows **Phyllis Lynn Hill**, not Futch. It was rejected as a Futch portrait.
- No birth year is inferred from a reported age. Unrelated Texas/Florida Futch records and unlinked King obituaries are excluded. The later Renard McCain King federal firearm case is not linked to this person without independent evidence.
- Existing Chris Laury, Samuel Bridges, Walter Bowe, Khaleel Sayyed, Robert Collier and Michelle Duclos profiles remain unchanged. The existing Leo Hazzile record likely corresponds to Leo Bazile/Bazzile in the contemporary paper; it is flagged for later review, not duplicated or renamed.
- No research-source URL is entered as a personal support website.
- SCI Dallas is linked by its verified existing ID. No matching San Mateo county-jail record was found, so the King cases leave that relationship blank. Approximate coordinates identify Dallas, Pennsylvania and East Palo Alto communities, not exact premises or private residences.

## Status, validation and deployment

All three later statuses are unresolved. Both non-null custody/release flags remain false, the existing **Other** category; no person is asserted to be currently imprisoned, released or deceased. The current dashboard includes Other in its combined active/other total, but these profiles do not increment its specific current-custody flag. No code is changed to redefine that existing behavior.

No incarceration start or release endpoint is invented. No numeric imprisonment total is supplied. Idempotent deployment skips every existing identity, including hidden aliases, and aborts on ambiguity. Application API, museum and tracker caches are cleared only on application of the batch.

Validation: shell syntax and zero apostrophes in the single-quoted tinker block; **166 application-model assertions** in SQLite memory after purging production connections, with fake files/storage/cache; a separate production preview with query-only mode enforced. Checks cover new counts, preservation of existing bios/cases/fields, alias matching, repeat execution, date precision, absent durations and rejection of unsupported portraits, affiliations and custody claims.

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-287.sh --dry-run
sudo -u www-data bash database/data/run-batch-287.sh
```

Continue immediately with the broader Black Liberation Movement: W. L. Nolen and other named prison organizers, then remaining affiliations. Hold East Palo Alto arrest-only leads such as Nick Harper until actual multi-day detention is established. Neither group membership nor authorship of a prison-support statement alone establishes imprisonment for activism.
