# American Indian Movement: first custody pass (batch 277)

Reviewed September 10, 2026. One proposed new profile and case: **Nilak Butler**. This is a first pass with open leads, not a complete roster of people detained in connection with AIM.

## Proposed record

The payload contains the case account, custody evidence and field-level source references. [Butler's recorded testimony](https://archive.mpr.org/stories/1977/03/02/highlights-of-the-minnesota-citizens-review-commission-on-the-fbi-hearings-part-2-norman) establishes qualifying custody and reports acquittal. The exact release date, trial date and numeric duration remain unset. Her [memorial by Winona LaDuke](https://www.freepeltier.org/nilak_in_memory.htm) explicitly supplies September 3, 1953 and December 26, 2002, identifies the historical name Kelly Jean McCormick, and supports the affiliations.

The portrait is identified and credited within that memorial; see `../photos/CREDITS-batch277.md`. It is a small archival image, not a generated reconstruction.

The [county jail report, section 1.10](https://file.lacounty.gov/SDSInter/bos/bc/197361_LosAngelesCountyJailPlanIndependentReviewandComprehensiveReport.pdf) resolves the transcript's phonetic rendering of Sybil Brand Institute. A query-only lookup found no existing matching institution, so the name is in the case narrative without a fabricated relation. Coordinates approximate Los Angeles; they do not identify the airport or jail entrance. Source websites are not assigned as personal support websites.

## Existing people and held leads

All 8,786 profiles, including hidden records and aliases, and pending batches 271–276 were checked for Butler's identity. Fifty live profiles carry the AIM label. Existing biographies, cases, affiliations, dates, photos and support links are preserved in full.

| Identity or group | Finding / next step |
|---|---|
| Norman Brown | His MPR testimony explicitly denies being jailed and describes roughly six hours of questioning. That incident does not qualify. Investigate any later, separately documented custody. |
| Jean Bordeaux / Bourdeau | MPR records material-witness detention, but not a verified multiday duration. Held. |
| Gregorio Jaramillo | Missing identity; the [1974 district court opinion](https://law.justia.com/cases/federal/district-courts/FSupp/380/1375/1457930/) establishes a March 9, 1973 arrest and acquittal. Actual detention duration still needs evidence. |
| Michael Eugene Sturdevant | Already present as **Mike Sturdevant**, under Menominee Warrior Society; not a new profile. |
| Kenneth Loud Hawk, Russell Redner, Anna Mae Aquash, Ka-Mook Banks, Dennis Banks | Existing profiles cover the named defendants in the [Loud Hawk litigation](https://supreme.justia.com/cases/federal/us/474/302/). Years of litigation must not become years of incarceration. |
| Pat Bellanger, Lorelei DeCora, Bill Means | Further custody research needed; organizational prominence alone is insufficient. |
| Wounded Knee airlift participants | Identify individual arrested participants and actual custody. Do not assume organizer Bill Zimmerman was the arrested pilot. |
| Wounded Knee defendants generally | Continue named rosters and individual custody records. The occupation's 71 days are not a jail term. |

The [MPR retrospective on the Wounded Knee trial](https://archive.mpr.org/stories/1974/09/21/wounded-knee-epilogue) documents acquittals/dismissals and brief contempt detentions, but does not qualify every participant for inclusion.

## Validation and deployment

- 87 assertions passed using actual application models with a disposable SQLite in-memory database, array cache and in-memory image storage. Production queries were explicitly read-only.
- Checks cover preview, first application, replay, hidden alias preservation, exact vital dates, unset duration/release, photo bytes and attachment, cache invalidation, invalid input rejection, different existing image refusal, and image-write failure rollback.
- Query-only live preview: one missing profile, zero existing matches; no writes.
- Shell syntax and `git diff --check` passed.
- Public storage receives the reviewed PNG only for a newly created profile. Existing different image bytes are never overwritten. A later database failure could leave an unused identical image file; replay safely reuses it.
- A ready PR changes nothing on the live site until the user merges and deploys. Apply earlier pending batches in order, then preview and run batch 277 as the application owner.

Next affiliation: **American Labor Party**. Open AIM leads remain recorded for a later pass.
