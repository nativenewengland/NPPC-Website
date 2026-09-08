# Batch 246: dashboard event import reconciliation

This batch records 27 events inserted into the live database on September 8,
2026 UTC before they went through the numbered-batch and PR workflow. Ten
events were inserted first, followed by 17 historical events. The live mapped
total increased from 1,059 to 1,086. This is a reconciliation of those writes,
not a new claim that another 27 events have been added.

The payload preserves the original titles, source URLs, categories, event dates,
coordinates and location labels, plus research notes. `published_at` represents
the event or court-development date used by the dashboard timeline. Separate
source-date fields are provenance only. Approximate and representative pins
are identified in the location labels and coordinate notes.

## Data path

`resources/views/pages/dashboard.blade.php` loads `DashboardLink::onMap()` for
event markers and `DashboardLink::published()` for the newswire. These records
use `lat` and `lng`. Prisoner and institution coordinates belong to a different
data path; this batch does not create or alter prisoner profiles. The batch
also calls `Cache::forget(PrisonerApiController::cacheKey())` on a normal run
to follow the repository cache-invalidation convention.

## Replay and deployment

Merge the PR, pull `main` on the server, and apply earlier pending batches in
their established order before running:

```sh
bash database/data/run-batch-246.sh --dry-run
bash database/data/run-batch-246.sh
```

The importer matches URL plus event date. Two Indianapolis demonstrations
share a WFYI article but occurred on different days; some court outcomes also
use an article already linked to an earlier arrest. Matching on URL alone
would collapse distinct dated developments.

All rows are validated and existing matches checked before any insertion.
Missing records are inserted in a transaction. Existing matches are verified
and left unchanged; duplicate matches or changed fields stop the batch for
review. A replay against the original 27 live additions should verify all 27
and insert zero. The dry run writes no records and does not clear caches.
The shell wrapper requires both a successful process exit and the final
`B246-OK` sentinel, because Tinker may otherwise hide execution failures.

## Validation

The Bash script and embedded PHP passed syntax checks, and the Tinker block
contains zero apostrophes. The payload contains 27 unique URL/date pairs.
A forced read-only execution of the same PHP against the live application,
with the payload supplied in memory, verified all 27 records exactly and
reported zero missing records and 1,086 mapped events. It did not insert
records, clear caches, or install files on the server.
Local wrapper checks also confirmed dry-run selection and nonzero failure
statuses for an unknown option, a Tinker exception that exits zero, and a
nonzero process exit even when the success sentinel appears.

## Original production audit artifacts

The original insert-only Artisan commands and their JSON payloads were left
untracked in the server checkout. They are superseded by batch 246:

- `app/Console/Commands/AddSep2026DashboardLinks.php`
- `app/Console/Commands/AddHistorical2026DashboardLinks.php`
- `database/data/dashboard-additions-2026-09-08.json`
- `database/data/dashboard-historical-additions-2026-09-08.json`

The original imports backed up only the public `dashboard_links` table. These
backups and their `.receipt.json` companions, which contain inserted UUIDs,
remain under `/var/www/NPPC-Website/storage/app/dashboard-imports/`:

- `september-2026-20260908-001817-396645.json`
- `historical-2026-09-08-20260908-003025-379664.json`

They are retained as operational evidence, rather than committed as database
dumps. No rollback or deletion of the live additions is part of this batch.
