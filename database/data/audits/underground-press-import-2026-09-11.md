# Eight historical underground-press and antiwar detainees

Prepared September 11, 2026. The first four profiles were submitted in [PR #2490](https://github.com/pucakisses-star/NPPC-Website/pull/2490), which has merged. This follow-up adds Frank Gormlie, Ron Ridenour, J. D. Arnold, and Steven Abbott.

## Scope and evidence

The [import payload](../underground-press-prisoners.json) now contains eight profiles and one case per person, with source URLs and individual research notes:

- **Tom Forcade:** Underground Press Syndicate organizer. Goldman's retrospective describes jail custody; Forcade's interview dates his explosives acquittal to 1973.
- **Carlos Calderon:** El Barrio/Street Journal editor and Brown Beret. The Reader records release on bail, but its account of complete clearance conflicts with Miller's description of pleas by two unnamed defendants. No individual final disposition is assigned.
- **Jerome “Jerry” Friedman:** Columbus Community Union organizer and antiwar activist. The court establishes pretrial confinement under excessive bail.
- **Colin Neiburger:** Columbus antiwar participant whose excessive-bail detention is established by the same opinion. No newspaper job or second Detroit grand-jury case is inferred.
- **Frank Gormlie:** Ocean Beach People's Rag founder, convicted after the Collier Park demonstration. OB Rag gives custody as May–December 1972 and reports reversal of newspaper-related probation restrictions. The convictions and defense account remain distinct.
- **Ron Ridenour:** Los Angeles Free Press journalist convicted after garment-strike picketing. Contemporary reporting documents a six-month sentence being served on nights and weekends in 1973. The numeric time-served fields remain empty because this was intermittent custody. His separate photography prosecution is not conflated with this case.
- **J. D. Arnold:** Dallas Iconoclast news editor, also identified as John D. Arnold. His 1972 article establishes a weekend in jail; the contempt charges were dismissed in 1974. The six-month sentence imposed is not recorded as time served.
- **Steven Abbott:** Columbus Free Press writer jailed under bail later reduced from $20,000 to $3,000. Custody duration and final disposition remain unknown. The conditional bail order is not a release record.

Historical custody periods and court-action dates remain in prose; no invented day-level endpoints, calculated durations, or specific institutions are supplied. Research URLs are kept in source notes, not personal-website fields. The other unresolved research leads remain outside this import.

## Duplicate screening and deployment state

The initial read-only check for PR #2490 covered 8,953 profiles, including hidden records, and found none of its four candidates.

After that PR merged, the expanded command's live read-only preview reported:

- Preserved existing: Tom Forcade, Carlos Calderon, Jerome Friedman, Colin Neiburger.
- Would add: Frank Gormlie, Ron Ridenour, J. D. Arnold, Steven Abbott.
- Total new profiles: **4**.

The importer checks names and aliases, including accents, reversed name order, and hidden profiles. An ambiguous match aborts before insertion. Existing profiles and their cases remain unchanged.

## Apply after merge

From the application directory, as the application owner:

```sh
php artisan prisoners:add-underground-press --dry-run
php artisan prisoners:add-underground-press
```

The importer calls the existing `prisoner:add` command inside a transaction. New records are appended after the existing sequence. Reruns preserve existing profiles and cases. Successful insertion clears the database, museum, and tracker payload caches. No migration is introduced.

## Verification

```sh
php tests/Integration/UndergroundPressImport.php
```

**150 integration assertions passed** using the existing PHP 8.4 runtime and an isolated in-memory SQLite database. Checks cover eight profiles/eight cases from an empty candidate set, four additions when the first four are already imported, preservation of original profiles and cases, unknown-field handling, the intermittent and weekend custody descriptions, cache invalidation, reruns, hidden aliases, ambiguity, and rollback after a case-insertion failure.

The production command already exists from PR #2490, so the temporary test harness registered the candidate implementation under a separate class name with the same command signature. No production files were replaced. The live preview used SQLite read-only mode. Preparing and testing this follow-up did not insert production records; the four new additions await the post-merge import.
