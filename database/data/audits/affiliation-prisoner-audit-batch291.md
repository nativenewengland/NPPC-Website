# Affiliation research — batch 291

Prepared September 10, 2026. Two missing people, three custody cases, one identified portrait and two vital years. Local preparation only; not yet published or deployed.

| Person | Evidence and scope | Remaining uncertainty |
| --- | --- | --- |
| Joudon M. Ford | Separate federal and Rikers episodes. [Participant account](https://www.ccny.cuny.edu/sites/default/files/2023-06/Paper_reflection_update_June2023.pdf#page=8), corroborated for the airport arrest by the [contemporary newspaper](https://www.themilitant.com/1969/3306/MIL3306.pdf#page=5). [Alumni obituary](https://s3.us-east-1.amazonaws.com/magazinesccnyalumni.org/magazines/CCNY_Alumnus_Winter_2025_digital.pdf#page=19) supplies birth/death years. | Exact release dates and dispositions not established; political-motive and innocence claims attributed to Ford. |
| Tyronne Smith (also Tyrone Smith) | The same contemporary report individually confirms custody beyond the initial arrest period. | No verified release, verdict, sentence, vital dates or portrait. Ford’s duration is not assigned to Smith. |

## Data decisions

The JSON contains the new biographies, individual case summaries and source references. Ford’s documented months use the existing duration field; no exact endpoints are manufactured. Uncertain incarceration endpoints remain blank so the chart cannot depict continuous imprisonment into later decades. Both people have `in_custody=false`; Ford’s historical release is supported, while Smith uses the existing Other category with explicit uncertainty.

Ford’s vital dates retain year precision. His later death is not a death in custody. The portrait is identified by the publisher and visually checked; attribution is in `CREDITS-batch291.md`. The university PDF photo download was unavailable (HTTP 403); the independently published Bev Grant image was retrieved successfully.

The Rikers case uses the existing generic Rikers Island institution, with UUID and name pinned. No matching West Street institution was found; the later Metropolitan Correctional Center is not substituted. Approximate New York City coordinates identify the case/organizing area. Research links do not populate personal support website fields.

The 1999 New Jersey credential minutes mention a 1973 weapons conviction but do not establish that it was this airport case. The [February 2000 minutes](https://www.nj.gov/education/certification/sbe/minutes/9900/02-24-00.pdf) report no credential action. Neither item is inserted as a disposition of these 1969 cases. Ford is not labeled a Panther 21 defendant.

## Preservation and validation

- Compared normalized names, aliases and identity fields against all 8,786 current profiles, including hidden records, and pending batches 271–290. No matching identity found.
- The runner skips a matched existing identity entirely. Ambiguity aborts; existing biographies, photos, cases, dates and websites are preserved.
- **169 assertions passed** using actual application models with all production connections purged and SQLite `:memory:` as the only connection. File, photo storage and cache operations used test doubles.
- Checks cover three distinct cases, year precision, documented-month totals, no fabricated ongoing custody, first apply/replay, hidden-alias preservation, malformed input, false dates/status/affiliation, photo checksums, storage conflicts and transaction rollback.
- Query-only live preview: **two missing profiles, zero existing matches; B291-OK**. No production records, photos or caches changed.
- Shell syntax and whitespace checks passed. The single-quoted tinker block contains no ASCII apostrophes. No data migration is used.

## Manual deployment after publication and merge

Apply earlier pending batches in order first. A pull alone does not insert the records.

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-291.sh --dry-run
sudo -u www-data bash database/data/run-batch-291.sh
```

The batch links the photo and clears prisoner API, museum and tracker caches. Public GitHub publication remains pending the separate approval required by automatic approval review; this batch has not been pushed.

## Next research

Continue the unresolved chapter leads, starting with the Richmond Five and Des Moines. Keep the other batch266 leads open until individual custody and political connection are established. Same-day release, a suspended sentence, a common-name match or chapter membership alone does not qualify.
