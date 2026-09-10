# Affiliation research — batch 284

Reviewed September 10, 2026. Follow-up to the Black Guerrilla Family research group; the actual additions are prison organizers, without converting disputed gang classifications into membership labels.

## Proposed additions

| New profile | Distinct qualifying custody | Birth date | Portrait |
|---|---|---|---|
| Michael Zaharibu Dorrough / Michael Reed Dorrough | Continued SHU confinement after a political-book-based 2006 review, described in his petition and subsequent correspondence | January 27, 1954 | Identified October 2018 support-site photograph |
| Todd Lewis Ashker | Administrative segregation from 2017 to at least the January 2023 judicial retaliation finding | July 5, 1963 | Identified 2015 CCR interview still |
| Mutope Duguma / James Daren Crawford | More than a decade of segregation under a disputed classification connected, in his account, to political beliefs | August 26, 1966 | Unresolved |
| Joka Heshima Jinsai / Shannon Lemar Denham | Years of segregation linked, in his account, to political expression and association evidence | Withheld: sources conflict | Unresolved |

The [2012 petition to the UN Special Rapporteur](https://s3.amazonaws.com/s3.documentcloud.org/documents/452653/final-public-un-petition-to-special-rapporteur.pdf) supplies the three birth dates and named accounts of confinement: Ashker pp.11–12, Duguma pp.15–16, Denham pp.18–19, Dorrough pp.26–27. It is a submission by prisoners and supporting organizations, **not a UN determination**. Its text retains drafting errors, so its fields were checked individually.

## Independent and subsequent evidence

- **Dorrough:** [firsthand correspondence on his support site](https://zaharibu.wordpress.com/tag/csp-corcoran-shu/) documents Corcoran SHU custody in 2013–2014 and hunger-strike participation. The [support-site home page](https://zaharibu.wordpress.com/) describes subsequent departure from long-term isolation. His original murder/narcotics conviction is contextual, not classified here as an activism prosecution.
- **Ashker:** the [California Assembly's March 3, 2025 agenda, p.13](https://abgt.assembly.ca.gov/media/7760#page=13) expressly reports the January 5, 2023 judicial finding about retaliatory housing since 2017. This establishes actual prolonged confinement. The [CCR case chronology](https://ccrjustice.org/home/what-we-do/our-cases/ashker-v-brown) distinguishes later appellate proceedings over general settlement monitoring. No claim is made that the monitoring extensions remain effective or that the historical housing episode continues today.
- **Duguma:** [Solitary Watch's 2012 original correspondence](https://solitarywatch.org/2012/08/07/political-or-gang-activity-new-afrikan-inmates-in-the-shu/) corroborates prolonged SHU confinement and records his denial of gang membership. [In re Crawford](https://law.justia.com/cases/california/court-of-appeal/2012/a131276.html) concerns unconstitutional mail censorship; it is not a prison or segregation release order. His [2019 essay](https://prisonerhungerstrikesolidarity.wordpress.com/2019/12/04/lost-in-time-lift-up-our-brother-sitawa-and-strike-down-indefinite-incarceration/) provides further organizing context.
- **Jinsai/Denham:** the [2023 clemency campaign](https://www.change.org/p/free-social-justice-activist-joka-heshima-jinsai-shannon-denham) ties his organizing identity to J38283 and reports extended solitary confinement connected to advocacy. Its estimate is not converted into exact case dates or an uninterrupted term.

## Current custody and identity checks

The public CDCR CIRIS lookup was checked on September 10, 2026. These are informational listings, not certified records. Historical numbers and legal aliases match:

| Lookup | Listed legal name | Listed institution |
|---|---|---|
| [D83611](https://ciris.mt.cdcr.ca.gov/api/ciris/v1/incarceratedpersons/D83611) | DORROUGH, MICHAEL REED | Substance Abuse Treatment Facility and State Prison, Corcoran |
| [C58191](https://ciris.mt.cdcr.ca.gov/api/ciris/v1/incarceratedpersons/C58191) | ASHKER, TODD LEWIS | California Men's Colony |
| [D05996](https://ciris.mt.cdcr.ca.gov/api/ciris/v1/incarceratedpersons/D05996) | CRAWFORD, JAMES DAREN | California State Prison, Los Angeles County |
| [J38283](https://ciris.mt.cdcr.ca.gov/api/ciris/v1/incarceratedpersons/J38283) | DENHAM, SHANNON LEMAR | Kern Valley State Prison |

All four therefore retain current-custody status. CDCR's admission dates concern their underlying imprisonment and are not entered as segregation starts. The three supplied DOBs agree with current listed ages. Denham's listed age **54** conflicts with the petition's **January 17, 1960** birth date and other older age claims. His DOB remains completely blank; neither a replacement year nor partial January 17 date is inferred.

## Dates, affiliations, location and preservation

- All four cases have **empty date fields and no numeric duration**. The models would extend an incarceration date without an endpoint to today for a currently detained person. That would misrepresent historical segregation as continuously ongoing. Available periods remain in case prose; a SHU exit is not prison release.
- No death date is verified for these currently listed prisoners.
- Broad prison-rights movement affiliations reflect their documented organizing. Ashker additionally has Short Corridor Collective; Jinsai has Autonomous Infrastructure Mission. BGF and Aryan Brotherhood membership are not inferred from contested classification labels.
- Coordinates are approximate communities of current listed custody: Corcoran, San Luis Obispo, Lancaster and Delano. They are not exact prison-cell locations.
- Historical Corcoran SHU links to **California State Prison, Corcoran**, UUID `a470fa1a-4cfc-4b15-b5cc-b40560c7a8c7`. It is not the separate SATF institution where Dorrough is currently listed. Duguma's historical case links to Pelican Bay, UUID `4d17ee62-74b8-4c04-9e72-a9d58580d5a8`. Ashker's case has no guessed institution relation.
- Only verified personal support sites populate `website`: Dorrough and Ashker. Article citations are kept in the research payload. Duguma and Jinsai remain blank there.
- All **8,786** existing records, including hidden records and aliases, and pending batches **271–283** were screened. All four identities are missing. The deploy script checks again and skips any identity that appears before deployment, preserving its complete bio, fields, photo and cases.

## Portrait provenance

See [CREDITS-batch284.md](../photos/CREDITS-batch284.md). Both selected files are downloaded unchanged from explicitly identified support-site images. No AI, crop, color change or resampling was applied. A different Ashker image with publisher-altered background was not selected. Duguma's identified group-photo lead could not be obtained and individually resolved from an accessible original; no substitution was made.

## Validation and deployment

The script has a dry run, immutable reviewed identities/cases/dates/portraits, checksums, ambiguity checks, transaction rollback, replay protection, and the established API/museum/tracker cache invalidation. The single-quoted Tinker body contains zero apostrophes. No migrations or production writes were used.

- Shell syntax valid.
- Application-model tests passed in a separate SQLite in-memory database after purging all production connections, with fake photo storage and cache: **198 assertions**.
- Tests cover unchanged existing/hidden identities, repeat execution, no invented custody counters or chart years, current-custody flags, the withheld Denham DOB, inmate numbers, source/institution guards, portrait checksums and storage failure rollback.
- Production preview enforced SQLite query-only mode: **4 would add, 0 existing, `B284-OK`**; no database, photo or cache changes.

Merge the PR, pull main and manually run earlier pending batches in order before batch 284:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-284.sh --dry-run
sudo -u www-data bash database/data/run-batch-284.sh
```

## Continuing research

The next first-pass group is **Black Liberation Army** (45 labeled profiles in the starting inventory). This bounded follow-up does not exhaust prison-organizer research. Retain the batch 283 leads: Edward Terran Furnace / proposed Asr Tauf Shakanasa I, Virgil Wilkins, Arturo Castellanos, Antonio Guillen, James Baridi Williamson, W. L. Nolen, Johnny Owen Vick, Hozel Alanzo Blanchard and Christian Gomez. Each still needs an individual eligibility/status determination; a name in a group statement alone is insufficient.

Further UN-petition names remain leads, not automatic additions: Walter J. Coto, Christopher Flores, Alfred Sandoval, Javier A. Zubiate, Scott D. Stoner, Victor Cantero, Synrico J. Marcus Rodgers, Roberto Campa Lopez, Phil Fortman, Robbie Riva, Michael E. Spencer, Donald Lee Moran Jr., Carlos Roberto Robledo, Derek Carbajal and Richard Satterfield. Do not try to identify anonymous petitioners from initials. Keep original criminal custody separate from punishment for subsequent organizing.
