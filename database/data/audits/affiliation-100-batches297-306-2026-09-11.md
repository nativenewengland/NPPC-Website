# Combined affiliation research — batches 297–306

Ready for one combined pull request on September 11, 2026: **100 new people, 112 new cases in total, and seven updates to existing cases**. Of the new cases, 107 belong to the new profiles and five add separate episodes to existing profiles. No existing biography, portrait, support website or sentence narrative is replaced. The seven existing-case updates are explicitly reviewed field changes, protected by expected prior values.

## Coverage

| Batch | New people | New cases | Focus |
| --- | ---: | ---: | --- |
| 297 | 8 | 8 | Puerto Rican Nationalist / Cadets-related custody |
| 298 | 6 | 6 | Hayneville civil-rights detainees |
| 299 | 3 | 3 | Catholic Worker archival detainees |
| 300 | 18 | 20 | Catholic Worker and SOA Watch book follow-up |
| 301 | 15 | 19 | Supplied-book individual interviews and chronology |
| 302 | 0 | 5 | Existing-profile cases plus seven case-field updates |
| 303 | 2 | 2 | U.S. colonial custody in the Philippines |
| 304 | 25 | 26 | Stony Brook and related protest custody |
| 305 | 17 | 17 | Maine shoe strike, Bridges hearing and WPA strike |
| 306 | 6 | 6 | Tallahassee jail-in and Albany civil-rights custody |
| Total | 100 | 112 | Seven existing cases also updated |

Each identity has individual evidence of actual custody beyond initial arrest. Affiliation labels distinguish documented membership from broader movement participation. Group sentence reports are not treated as proof everyone served a full term. Unresolved exact dates and unverified portraits remain unset. This is a set of bounded archival passes, not a claim to have exhausted every movement or member roster.

The book follow-up resolves the Five Trident II chronology conflict in favor of the documented 1986 release months, preserving month precision. The accompanying audit identifies the evidence, clears unsupported continuous custody starts, and records Jean Holladay's independently corroborated six months. Four distinct 2000 Plowshares cases and Larry Morlan's earlier Pentagon case are added to existing profiles. Steve Kelly's missing entry date and Scott Schaeffer-Duffy's documented duration are filled. Details and before/after values are in batch302 and the full-books audit.

## Final verification

- Fresh read-only comparison against all 8,853 live identity records, including aliases, component names and hidden profiles: all 100 proposed identities remain missing. No internal cross-batch identity collisions.
- Batch302 read-only preview still matches all seven expected updates and all five separate additions; no live conflicts.
- Every numbered script passes shell syntax and has zero ASCII apostrophes inside its single-quoted PHP body. JSON counts agree with this report.
- The per-batch audits record isolated application-model checks for insertion, replay, preserving existing profiles, rejected ambiguous matches, date precision, unsupported fields and failure rollback. Batch306 passed 250 assertions; no production database write was used for validation.
- Upstream changes through batches294–296 are incorporated; the research queue retains all completed passes. Unrelated portrait work is excluded.

## Manual deployment after merge

These files do not change the live database on their own. Apply any earlier undeployed batches first. From the live application's checkout, pull the merged main branch and run 297 through 306 in order. The loop stops on the first failure; all scripts are idempotent. Run as the application owner so PsySH uses writable application storage. Each batch invalidates its relevant API caches.

```bash
cd /var/www/NPPC-Website &&
git pull origin main &&
for batch in {297..306}; do
  sudo -u www-data bash "database/data/run-batch-${batch}.sh" || break
done
```

No deployment was performed during preparation. Retain the audit notes for any row that is skipped because it now matches an existing identity or stops because a previously reviewed case changed.

## Next research

Continue with Coxey's Army in the affiliation queue, retaining CORE, CIO and supplied-book unresolved leads. Start a fresh 100-new-person accumulation after this PR; do not count these 100 a second time. The existing queue is the continuation record.

## New identities

| Batch | Name |
| --- | --- |
| 297 | Juan Jaca Hernández |
| 297 | Raimundo Díaz Pacheco |
| 297 | Manuel Ávila |
| 297 | Juan Bautista Colón Rivera |
| 297 | Dionisio Vélez Avilés |
| 297 | Santiago Nieves Marzán |
| 297 | Julio Monge Hernández |
| 297 | Juan Álamo Díaz |
| 298 | Richard Morrisroe |
| 298 | Jonathan Myrick Daniels |
| 298 | Ruby Sales |
| 298 | Joyce Bailey |
| 298 | Gloria Larry |
| 298 | Willie Vaughn |
| 299 | Nicole d’Entremont |
| 299 | Dianne Feeley |
| 299 | Patricia Rusk |
| 300 | Nicholas Cardell |
| 300 | Mary Earley |
| 300 | Mary Kay Flanigan |
| 300 | Anne Herman |
| 300 | Paddy Inman |
| 300 | Ken Kennon |
| 300 | Dwight Lawton |
| 300 | Rita Lucey |
| 300 | Bill McNulty |
| 300 | Carol Richardson |
| 300 | Dan Sage |
| 300 | Doris Sage |
| 300 | Randy Serraglio |
| 300 | Rita Steinhagen |
| 300 | Ann Tiffany |
| 300 | Judith Williams |
| 300 | Ruthy Woodring |
| 300 | Johnny Baranski |
| 301 | Robert Wollheim |
| 301 | Steve Woolford |
| 301 | Mike Miles |
| 301 | Hattie Nestel |
| 301 | Joni McCoy |
| 301 | Tom Karlin |
| 301 | Harry Murray |
| 301 | Genevieve Allen |
| 301 | Kim Wahl |
| 301 | Anne S. Hall |
| 301 | Becky Johnson |
| 301 | Renaye Fewless |
| 301 | Marian Mollin |
| 301 | Tina Busch-Nema |
| 301 | Marty Harris |
| 303 | Guillermo Capadocia |
| 303 | Juan Feleo |
| 304 | Scott L. Bassoff |
| 304 | John J. Belford |
| 304 | Larry K. Freeman |
| 304 | Jeanne F. Friedman |
| 304 | David A. Gersh |
| 304 | Saul H. Housman |
| 304 | Christine La Bastille |
| 304 | Eric M. Liskin |
| 304 | Frank D. Lo Presti |
| 304 | Marilyn A. Lo Presti |
| 304 | William B. Martin |
| 304 | Alfred J. Mungo |
| 304 | Steven R. Pressman |
| 304 | Gerard Spiegler |
| 304 | Richard J. Spitz |
| 304 | Alice J. Swartz |
| 304 | Jerry F. Tung |
| 304 | Anthony J. Vanzawaren |
| 304 | Ira M. Wechsler |
| 304 | Howard L. Weiner |
| 304 | Suzanne R. Weiner |
| 304 | Mitch Cohen |
| 304 | Glenn Kissack |
| 304 | Michael Zweig |
| 304 | Jose Calderon |
| 305 | William J. Mackesy |
| 305 | Ernest Henry |
| 305 | Sidney Grant |
| 305 | James D. O'Neil |
| 305 | Leslie Wachter |
| 305 | Charles Grider |
| 305 | William Riley |
| 305 | George Toteno |
| 305 | Milton McLean |
| 305 | Eddie Alberts |
| 305 | Frank Stevens |
| 305 | Floyd Hurley |
| 305 | Myron Philips |
| 305 | Richard Connell |
| 305 | Ralph Core |
| 305 | Charles Connors |
| 305 | Minnie Kohn |
| 306 | John Broxton |
| 306 | William Larkins |
| 306 | Clement Carney |
| 306 | Angela Nance |
| 306 | Henry Marion Steele |
| 306 | Floyd Gardner |
