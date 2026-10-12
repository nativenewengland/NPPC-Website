# 1950s missing-fields review: batch 430

October12,2026. Four missing dates on four existing case rows, with day precision.

| Profile | Missing field added |
| --- | --- |
| Dorothy Forest | Sentenced June4,1954 |
| Claude Lightfoot | Sentenced February15,1955 |
| Marcus A.Murphy | Arrested September17,1952 |
| James Frederick Forest | Arrested September17,1952, on existing pretrial episode only |

## Original evidence

- U.S.AttorneysBulletin June25,1954 PDFpage5/printedpage4, UnitedStatesvJamesForest report: conviction May28; sentencing June4,1954. DorothyForest is individually named and her three-year sentence distinguished from the others' five-year terms. Original PDF downloaded and page visually checked.
- MedfordMailTribune February15,1955 page11: UnitedPress explicitly dates ClaudeLightfoot's sentence that day and separates January26 verdict. Original scan and masthead visually checked. Secondary accounts conflating the dates or giving a different fine are not imported.
- PampaDailyNews September17,1952 page1: AssociatedPress explicitly announces the eighteen arrests that day and individually identifies JamesFrederickForest at home inStLouis and MarcusAlphonseMurphy at his farm nearCharleston. Both match existing Missouri SmithAct records. Original12-pagePDF downloaded; page1 and masthead visually inspected. James's arrest date goes only on the existing1952pretrial case, not duplicated on the1954post-sentencing episode.

Exact sources, identity and expected-value guards appear in fixes/batch430.json.

## Unresolved research and preserved discrepancies

- Forest, Manewitz and Murphy's pretrial detention: a contemporary December22 report preserved in FBI RosenbergPart118 says all three were confined inStLouisCityJail since September17. Local original download returnedHTTP403; full-file research tool access also failed. This is a detention lead for further primary-page verification, not an applied incarceration or release date in this batch.
- Original203F2d83 appellate opinion confirms all four male defendants eventually posted bail and were released before April8,1953. September30,1952 is Sentner's bail reduction and prompt furnishing of bond; it does not establish a independently reported exact release day. Neither that date nor November15 bail reductions are entered as release endpoints.
- MarcusMurphy's existing charges prose describes a1953indictment, whereas the original JusticeDepartment bulletin gives September24,1952. Populated prose is preserved under missing-only scope. His existing1954sentence remains unchanged.
- James's populated custody-start and bail-release dates remain at their existing month precision. The new exact arrest does not overwrite them, alter counters or imply an uninterrupted sentence.
- Lightfoot was free on appeal bond in contemporary1955reporting. His sentence date does not establish five years actually served or a custody endpoint. Existing vital dates, photo and case prose preserved.
- Dorothy's arrest is already populated. Her individual pretrial/post-sentence release days and portrait remain unresolved. DorothyDelacy genealogy dates and the1923-24schoolgroup photograph have not been independently matched; the schoolgroup predates this defendant's childhood and is excluded as a namesake. DorothyForestHuxen1929-2012 obituary is also unrelated and excluded.
- SaulWellman's populated September16 arrest conflicts with the September17 roundup. It remains unchanged and requires separate correction authorization/source review.

Only four missing dates and their new precision keys change. Existing precision keys, populated dates, biographies, case prose, photos, vital dates, counters, flags and sort order remain unchanged. No prisoner/case created or removed. Exhaustive1950sreview remains inprogress. Live verification is recorded separately.
