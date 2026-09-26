# Missing birth dates restart — 2026-09-26

This pass regenerated the queue from the live database and researched identifiable profiles with strong biographical anchors. The opening inventory contained **6,262** profiles without a birth date. Exactly **100 profiles** were updated in three guarded live transactions. The closing inventory contains **6,153** profiles without a birth date. Nine other profiles were resolved concurrently, producing a net reduction of 109.

## Published dates

| Profile | Birth date stored | Precision | Evidence |
|---|---:|---|---|
| Vince Scotti Eirene | 1952-03-27 | day | Family-supplied obituary published by Pittsburgh Union Progress |
| Theodore Gibson | 1915-04-24 | day | Florida Civil Rights Museum biography |
| Otto Nathan | 1893-07-15 | day | American Jewish Archives biographical dictionary |
| Archie Brown | 1911-03-05 | day | NYU Special Collections finding aid for the Archie Brown Papers |
| William Weiner | 1893-09-05 | day | *Warszower v. United States*, 312 U.S. 342 (1941) |
| Clarence Adams | 1929-01-04 | day | *An American Dream*, University of Massachusetts Press |
| Denis Joseph Adelsberger | 1944 | year | Atlanta Journal-Constitution obituary reporting age 65 at death on 2010-02-18 |
| John Frank Cardiello | 1959 | year | Jersey Journal obituary reporting age 46 at death on 2006-03-30 |
| Timothy Reed | 1960 | year | Associated Press report published 1998-12-18 reporting his age as 38 |
| Robert Wollheim | 1948-10-10 | day | World Biographical Encyclopedia profile; age corroborated by the Oregon State Bar death notice |
| John Boncore Hill | 1952-01-07 | day | Biographical profile of Splitting the Sky; contemporary reporting corroborates age 61 at death |
| Mujahid Farid | 1949-09-03 | day | New York Times obituary syndicated by WRAL |
| Khatari Gaulden | 1945-12-13 | day | Catalogue description of his contemporary memorial program |
| Pearl C. Ewald | 1893-08-30 | day | Social Security death-index entry, corroborated by Friends Journal and contemporary age reporting |

The initial three year-only entries and fifty entries in the final batch use the most likely year from a dated contemporary age report. The public profiles state the year without an uncertainty note, following the site's existing editorial practice.

William Weiner required special care: a 1954 party memorial printed September 5, 1896, but that date was the Atlantic City birth entry proved forged in his passport prosecution. The Supreme Court opinion records his 1917 draft registration, draft questionnaire, and 1932 reentry application as independently giving September 5, 1893, so the database now uses that date.

## Final 86-profile batch

The final transaction completed the requested total of 100 corrections. It contains:

- **36 authority-record matches.** Exact profile identities were checked against Wikidata descriptions, death dates, political roles, organizations, or distinctive biographical events. Same-name collisions found during screening were excluded. January 1 values that represented year-only authority claims were stored with year precision.
- **50 reported-age calculations.** Each profile already documented the subject's age at a dated event. The stored value is the most likely birth year, following the site's existing editorial rule for the two possible years produced by age reporting. Sentences referring to a different person's age were excluded.

`evidence-ledger-batch-100.json` records the identity evidence or exact age sentence used for every entry. `birthdate-updates-batch-100.json` is the publication manifest.

The authority statements were checked through Wikidata's `timePrecision` metadata after publication. That check confirmed 23 year-level, one month-level, and 12 day-level claims. It also corrected the display precision for Josephine Casey (day) and Martha W. Moore (month) without changing their stored values.

## Publication controls

- Every update required an exact prisoner ID, name, slug, and null live birth date.
- The script created a SQLite `VACUUM INTO` backup before each transaction.
- Only `birthdate`, `date_precision`, `age`, `body`, and `updated_at` could change.
- Every profile received a source link in its body.
- All 100 public profile routes returned HTTP 200 after publication, including all 86 routes in the final transaction.

## Files

- `counts-summary.json`: aggregate live before/after counts and the net change.
- `birthdate-updates.json` and `birthdate-updates-followup.json`: publication ledgers.
- `publish-result.json` and `publish-followup-result.json`: live transaction results and backup paths.
- `http-status.txt`: public route verification.
- `inventory.php` and `publish-birthdates.php`: reproducible inventory and guarded publication scripts.
- `screen-wikidata.py` and `screen-reported-ages.py`: repeatable authority-record and reported-age screening tools.

The complete before/after queue exports, final 86-profile manifest, evidence ledger, field-verification result, and route-verification result remain in the local audit workspace for continued screening. They are excluded from the repository because they contain the detailed profile-level research payload. The repository retains the aggregate counts and screening methodology.
