# 1950s missing-field research: batch 422

Research begun October 11, 2026; batch prepared October 12. Sources and per-field identity evidence are retained in `fixes/batch422.json`; crop coordinates, checksums and credits in `photos/CREDITS-batch422.md`.

## Inventory and scope

Read-only live inventory: 474 date-qualified cases for 438 people. Qualification means at least one arrest, sentence, incarceration, release or exile endpoint in 1950–1959, or a closed incarceration/exile interval crossing that decade. Adding profiles tagged `1950s` gives 722 candidate people. Their 851 total associated cases include other decades and must not be described as 851 independently identified 1950s cases.

Candidate-profile screening found 446 empty photos, 409 empty birth dates and 417 empty death dates. An empty death date does not establish that a person is deceased. These are screening counts, not a claim of individual research for all 722 candidates or proof that existing dates are correct. Earlier batches 408–410 were checked to avoid repeating completed research. Only seven cases dating from the 1951 Hawaii Seven prosecution are changed in this batch; profile metadata additions concern people already associated with 1950s records.

## Verified additions

| Person | Missing profile fields filled | Missing case fields filled |
| --- | --- | --- |
| Jack Hall | Captioned portrait | Sentenced July 3, 1953; no incarceration date inferred from his sentence |
| Jack Kimoto | Captioned portrait | Sentenced July 3; entered jail July 4, 1953 |
| Eileen Fujimoto | Captioned portrait; born 1920, year precision | Sentenced July 3; entered jail July 4, 1953 |
| Koji Ariyoshi | Captioned portrait | Sentenced July 3; entered jail July 4, 1953 |
| Dwight Freeman | Captioned portrait | Sentenced July 3; entered jail July 4, 1953 |
| Charles Fujimoto | Born 1917, year precision; existing photo preserved | Sentenced July 3; entered jail July 4, 1953 |
| John Reinecke | Born 1904, year precision; died June 11, 1982 | Sentenced July 3; entered jail July 4, 1953 |
| LaRue Spiker | Captioned portrait; born July 8, 1912; died November 5, 1995 | None |
| Mary Louise Gilbert | Died 1994, year precision | None |
| Abner Green | Captioned portrait | None |
| William “Bill” Heikkila | Captioned portrait | None |
| Stanley Nowak | Captioned portrait | None; no custody inferred from denaturalization proceedings |
| Charles Rowoldt | Captioned portrait | None |
| Maurice E. Travis | Died April 27, 1985 | None |
| Hugh Bryson | Died May 20, 1984; existing photo preserved | None |
| Vincent Andrulis | Born February 4, 1890; died December 10, 1972 | None; no custody inferred from deportation proceedings |

Total: 16 distinct people; 16 profile rows plus seven case rows; 10 photos, five birth dates, six death dates and 13 case dates. Precision metadata is updated only for newly filled dates, preserving every existing precision key. Year-only dates use January 1 solely as the established database storage convention, with explicit `year` precision.

## Custody interpretation and unresolved findings

The [Pacific Citizen of July 10, 1953, page 3](https://pacificcitizen.org/wp-content/uploads/archives-menu/Vol.037_%2302_Jul_10_1953.pdf) explicitly distinguishes sentencing on July 3 from jail admission on July 4. Six defendants could not post the increased $15,000 bond; Jack Hall remained free after the union posted his bond. These six incarceration dates identify a documented post-sentence detention episode, not necessarily first detention in the entire case or a continuously served five-year term. Release dates remain blank. January 16, 1958 is an appellate decision, not proof of release. Existing release flags, durations and custody-period fields are preserved.

The same newspaper reports a three-year sentence and $2,000 fine for Eileen, conflicting with the existing five-year/$10,000 sentence text. Her biography also dates conviction to July, while the contemporary newspaper says June 19. Those existing fields are outside this missing-field-only batch and are flagged rather than silently overwritten. Koji's existing birth-year value differs from a University of Hawaii summary; that populated field is preserved pending resolution. The Congressional Record supplies birth years for Charles and Eileen; it is not used to import political allegations.

The Hawaii group photo caption identifies six standing defendants and labels the seated people as defense attorneys. John Reinecke is not identified in that photo, so no seated person's image is assigned to him. Charles already has a portrait and is preserved. Newspaper portrait crops retain the original printed halftone; small Koji and Dwight crops remain low resolution. Spiker's source portrait is explicitly identified, but its decade caption appears inconsistent with her documented birth year; no photograph date is assigned.

Additional leads examined without unsupported additions:

- Joseph Felmet: death-month reporting needs primary-source examination; conflicting birthday leads remain unresolved.
- Irving Feiner: January 23 versus January 24, 2009 death-day conflict; no date selected without resolution.
- James Dolsen, Barbara Hartle, Sherman Labovitz and Elizabeth Moos: homonymous genealogy/obituary results do not establish identity.
- Lloyd Barenblatt: existing birth-year conflict requires independent verification; no replacement.
- Heriberto Marín: an Allentown obituary concerns a different person; discarded.
- Stanley Nowak and Vincent Andrulis: biographical metadata does not establish jail custody. No arrest/incarceration fields added from civil proceedings.
- Spiker and Gilbert: reported detention durations do not establish an exact release day; no arithmetic-derived date added.
- Maurice Travis and Hugh Bryson: union death notices establish identity and death dates; no birth date calculated from reported age.
- Eileen Fujimoto: exact-day genealogical entries do not sufficiently match her maiden/middle names; only the independently documented birth year is added.
- Hawaii Seven: retrospective summaries of a week in jail do not establish each person's release day; individual bail/release records are still needed.

This is a researched first follow-up batch, not completion of all remaining 1950s gaps. No biographies, case prose, charge descriptions, sentence totals, demographics, sort order or existing dates/photos are changed. No new people or cases are created.

## Guarded application and verification

The runner preflights all identities, expected missing values, existing date precision, custody guards, asset hashes and storage collisions before any writes. Query updates avoid model saving hooks. Conflicts abort the transaction; repeated runs recognize already applied values and write zero rows. Cache invalidation follows actual changes.

Test and live verification results are recorded separately in `audits/batch422-live-verification.txt` after execution. The numbered batch and assets must be committed before live application. Live application uses these committed files; no ad-hoc database changes replace this audit trail.
