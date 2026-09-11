# Four historical underground-press and antiwar detainees

Prepared September 11, 2026 following investigation and the request for a prisoner-addition pull request.

## Scope and evidence

The [import payload](../underground-press-prisoners.json) contains four profiles and one case per person, with source URLs and individual research notes:

- **Tom Forcade:** Underground Press Syndicate organizer. Albert Goldman's retrospective describes actual jail custody; Forcade's interview dates his explosives acquittal to 1973. The entry attributes those accounts and leaves custody endpoints and duration unknown.
- **Carlos Calderon:** El Barrio/Street Journal editor and Brown Beret. The Reader records release on bail. Its account of complete clearance conflicts with Arthur J. Miller's description of pleas by two unnamed defendants. No individual final disposition is assigned.
- **Jerome “Jerry” Friedman:** Columbus Community Union organizer and antiwar activist. The court establishes pretrial confinement under excessive bail. The Jerry/Jerome identity match is supported by the same OSU presidency and search-committee role in contemporary reporting.
- **Colin Neiburger:** Columbus antiwar participant whose excessive-bail detention is established by the same court opinion. No newspaper job or organizational membership is inferred. Alternate spellings are used conservatively for duplicate screening; the separate Detroit grand-jury lead is not imported.

These are historical records marked released, not currently imprisoned. The documented dates of court action and later accounts remain in prose. No arrest, incarceration, release, sentencing, birth, or death date is supplied as a structured field. Time-served counters, specific institutions, and unsupported demographic fields remain empty. Research URLs are kept in source notes, not personal-website fields.

The unrelated and unresolved leads from the research are outside this four-person import.

## Duplicate check

A read-only live check on September 11 found **8,953 profiles**, including hidden records. Searches across names and aliases found only the unrelated Jeanne F. Friedman and Jose Calderon. The four proposed people were absent. The command repeats identity screening against the database where it runs; it includes aliases, accents, reversed name order, and hidden profiles. An ambiguous match aborts before insertion, and an existing match is preserved with all its cases.

## Apply after merge

From the application directory, as the application owner:

```sh
php artisan prisoners:add-underground-press --dry-run
php artisan prisoners:add-underground-press
```

The importer calls the existing `prisoner:add` command inside a transaction. New records receive positions after the existing sequence so previous curated positions remain intact. Reruns preserve existing profiles and cases. A successful insertion clears database, museum, and tracker payload caches. No schema or data migration is introduced.

## Verification

Run the focused integration checks with:

```sh
php tests/Integration/UndergroundPressImport.php
```

The harness uses only a new in-memory SQLite database and the existing domain schema migrations. It checks the dry run, four profiles/four cases, preservation of existing content and positions, unknown-field handling, cache invalidation, repeat execution, hidden aliases, accent/reversed-name matching, ambiguity, and rollback after a failed case insertion.

The pull request supplies an executable data change. Applying it to production is a separate post-merge step; preparing and testing this change does not insert production records.

Validation completed: **83 integration assertions passed** using the existing PHP 8.4 runtime and an isolated in-memory SQLite database. The existing importer and prisoner/case model files matched the PR base byte-for-byte. A live read-only dry run then reported exactly four additions. No production prisoner records were written.
