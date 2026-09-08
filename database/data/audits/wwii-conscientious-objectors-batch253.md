# Five American WWII conscientious objectors — batch 253

Prepared September 8, 2026. Adds Howard Schoenfeld, James Ellery Bristol,
Lee Donald Stern, Robert Swann, and Donald Earl Wetzel only when no existing
identity or alias matches. All five were absent in the read-only live preview.
Includes under-review records when checking duplicates. Oscar Schoenfeld is a
different person and must not prevent Howard's addition.

## Verified fields and limitations

| Person | Birth | Death | WWII custody | Photo |
| --- | --- | --- | --- | --- |
| Howard Schoenfeld | 1915 (year only) | 2004 (year only) | Danbury; refusing draft registration under the 1940 Act | Group photograph located and identified; usable standalone portrait still missing |
| James Ellery Bristol | February 12, 1912 | October 26, 1992 | Convicted October 31, 1941, New Jersey; released 1943 (year only) | AFSC, two-person photograph; Bristol at left |
| Lee Donald Stern | 1915 (year only) | October 9, 1992 | Convicted December 7, 1942, Northern Ohio; Milan, December 1942–January 1946 | PBI Canada portrait |
| Robert Swann | March 26, 1918 | January 13, 2003 | Ashland, September 1942–September 1944; 24 months served per memoir | Schumacher Center portrait |
| Donald Earl Wetzel | June 8, 1921 | June 21, 2007 | CPS walkout followed by federal imprisonment; dates unverified | Arizona Daily Star portrait |

Field-level citations and qualifications accompany each entry in
`../fixes/batch253.json`. Image provenance is in
`../photos/CREDITS-batch253.md`.

The published 1947 pardon list was visually checked: Bristol is entry 124 on
printed page 1444; Stern is entry 1277 on page 1460. Conviction dates go in
`convicted` text because there is no dedicated conviction-date column. A
conviction day does not establish a sentencing or prison admission day, and
the December 23, 1947 pardon is not a release date.

Stanford reports Bristol served 18 months, while AFSC reports 19. Leave numeric
duration unset and preserve both accounts. Swann's own memoir supplies five years
sentenced and two served; an obituary's conflicting two-and-a-half-year sentence
is disclosed. Do not combine those numbers. Wetzel's memoir supplies the prison
sequence; the later obituary's contradictory location for his Lepke encounter
is not adopted. Civilian Public Service camp assignment is not counted as jail.

Howard's January 2, 1915 and September 26, 2004 dates are secondary bibliographic
leads; source pages could not be independently read. Stern's January 21, 1915
birth day is a genealogy-index lead. Exact days are not promoted to verified
fields. Known years and months use the existing partial-date mechanism, so the
API emits `1915`, `1943`, `1942-12`, etc., not invented January 1 or month-start
dates. Existing application duration arithmetic for Stern uses these partial
endpoints; it is not evidence of an exact number of days served. Swann's
documented 24 months is stored explicitly.

No existing biographies are edited. No source link is inserted as a support
website. Only new profiles and their cases are created; existing matches are
preserved, and ambiguous matches abort the whole batch. Existing mapped
institutions FCI Danbury, FCI Milan, and FCI Ashland are reused with city/state
and coordinate-presence checks. Their coordinates are not changed. Bristol's
prison remains unverified and receives no invented institution or map location.

## Validation

- Shell syntax and PHP lint passed; the single-quoted tinker block contains no
  apostrophes.
- Read-only production preview found all five missing and validated each linked
  institution plus all four bundled image signatures/checksums.
- Actual Laravel models and `prisoner:add` were exercised against an in-memory
  SQLite database, with every live connection removed from configuration and
  photo writes redirected to a temporary directory removed afterward.
- Confirmed five profiles/five cases, exact and partial date output, four photo
  copies, existing biography/case preservation, correct custody flags, no source
  URLs in website fields, cache invalidation, and idempotent replay.
- Under-review alias matching prevented duplication; ambiguous identities
  aborted before any inserts. Oscar's profile remained distinct from Howard.
- The batch itself has not been applied to the production database.

## Manual deployment

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-253.sh --dry-run
sudo -u www-data bash database/data/run-batch-253.sh
```

The wrapper supplies writable PsySH directories under application storage.
Photo installation needs the application owner's public-storage permissions.
API cache invalidation occurs after additions. If a later database error rolls
back, a newly copied, checksum-matching photo may remain as an unreferenced file;
replay safely reuses it. No deploy is implied by a commit or PR merge.
