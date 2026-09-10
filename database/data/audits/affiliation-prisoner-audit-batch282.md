# Attica Brothers: first custody pass, batch 282

Reviewed September 10, 2026 against 8,786 production identities, including hidden records and aliases, and proposed entries in batches 271–281. Production was read-only. This is a first pass through named leads, not a claim that the Attica roster is complete.

## Proposed addition

**Anthony Williams, also called Tony Williams:** one profile and one pretrial custody case. The contemporary defense newsletter explicitly documents two months attributable to the Attica indictment. The batch stores that duration in the existing months field. It preserves existing identities in full if a match appears before deployment.

Sources inspected as original page images:

- [Attica News, March 1973, printed page 5](https://freedomarchives.org/Documents/Finder/DOC510_scans/Attica/510.Prisons.AtticaNewsVol1No1.pdf#page=5), “Charges Dropped Against Tony Williams.”
- [Attica News, June 26, 1975, PDF page 4](https://freedomarchives.org/Documents/Finder/DOC510_scans/Attica/510.Prisons.AtticaNewsVol2No26.pdf#page=4), Akil's death notice identifying Anthony Williams. These are defense publications, identified as such in the payload; the notice's speculation about negligence is not asserted as fact.
- [Freedom Archives catalog](https://freedomarchives.net/search/search.php?collection_id=144&media=PDF&no_digital=1&subject%5B%5D=Prison) confirms issue dates.

No jail endpoints, birth date or death date are invented. The notification date in the later notice is not a death date. Both current-custody and released flags are false: the notice establishes that he died imprisoned. His new biography states that fact without pretending an exact date is known. The earlier underlying sentence is excluded from this case's total. The existing duration formatter displays two months; its internal approximate day equivalent is not presented as an observed day count.

No verified portrait was found. The nearby photographs in these issues belong to Charles Pernasilice, Martin Sostre and the adjoining Joann Little material, not Williams. No personal/support website was verified. Coordinates mark Buffalo approximately, where the documented jail episode occurred; no modern institution is substituted for the historical jail.

## Existing identities and unresolved leads

The four profiles carrying the Attica Brothers label include John Boncore Hill/Dacajewiah, Charles Pernasilice, Eric Thompson/Jomo and Akil Al-Jundi/Herbert Scott Dean. Cross-label checks also found Shango/Bernard Stroble and two apparent Frank Smith/Big Black records. These existing records are not edited or merged in this batch.

The primary indictment table in the March 1973 issue, printed pages 6–7, lists maximum exposure, not time served. It is a lead list, not a basis for adding every named defendant. Likewise, being an Attica inmate or a civil claimant alone does not establish imprisonment for activism.

| Lead | Evidence reviewed | Still needed |
| --- | --- | --- |
| Vernon LaFranque / LeFranque / LaFrenque | Named in the January 28, 1973 Auburn letter; November 20, 1973 contraband bail hearing; acquitted December 19, 1974 | Individual duration of additional detention or punitive segregation attributable to the uprising. Ten years behind bars at the hearing includes an earlier sentence and cannot be used as the political-custody total. |
| Otis McGaughy / McGaughey | Named in the Auburn letter and later court settlement claim | Duration and boundaries of retaliation-related confinement; neither injuries nor their lasting symptoms establish that duration. |
| Mariano Gonzalez / Dalou Asahi | Named indictment roster and later trial reporting | Verified identity, actual individual detention interval, and outcome. |
| Joseph Little | Defense attorney's retrospective account describes obtaining release | Individual dates/duration and relation to the uprising prosecution. |
| Other roster defendants | Original names and aliases retained for further checking | Confirm multi-day custody tied to activism, distinguish ordinary sentences, then check all inventory and pending aliases. |

Additional sources for these open leads:

- [Baxter Smith, The Militant, December 7, 1973, printed page 16 / PDF page 20](https://www.themilitant.com/1973/3745/MIL3745.pdf#page=20). The caption identifies LaFranque in a white coat; a potential portrait is held until case eligibility is established.
- [The Militant, January 17, 1975, news brief](https://www.themilitant.com/1975/3901/MIL3901.pdf): LaFranque acquittal and subsequent dismissals. Acquittal is not assumed to mean physical release.
- [Al-Jundi v. Mancusi settlement materials, August 2000](https://clearinghouse-umich-production.s3.amazonaws.com/media/doc/78629.pdf): individual injury claims, including McGaughey and LaFranque, do not by themselves resolve the additional-custody question.
- [NY State Archives Attica chronology](https://www.archives.nysed.gov/research/topic-attica-timeline).
- [Brooklyn Law School: After Attica](https://lawnotes.brooklaw.edu/issue/fall-2021/after-attica/).
- [Fifth Estate, April 1976: Attica Victory Trials](https://www.fifthestate.org/archive/271-april-1976/attica-victory-trials/).

## Validation and continuation

The batch checks the reviewed identity, case text, documented duration, blank endpoints, source references and allowed fields before any write. It rejects ambiguous aliases, preserves all existing profiles/cases, supports a dry run, and invalidates the standard API/museum/tracker caches after application. The standard PsySH storage wrapper avoids the earlier server permission problem.

Validation passed: 107 assertions using the actual models in a separate SQLite in-memory database with fake storage/cache; production query-only preview found one missing identity and no existing match (B282-OK). Checks covered dry run, replay, existing fields/cases, aliases, ambiguity, documented-month display, missing endpoints, rejection of a fabricated death/release date, and cache invalidation. Shell syntax and zero-apostrophe tinker-body checks passed. Deployment remains manual, in batch order. Continue immediately to Beaver 55 while retaining these Attica leads for later research.
