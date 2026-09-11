# Combined affiliation research: batches 307–319

100 new people, 107 cases and 6 sourced portraits. All existing profiles are preserved wholesale, including biographies, cases, photos and support websites. No production changes during preparation.

The coverage includes Coxey’s Army, Chicago garment-strike prisoners, New York political detainees, animal-rights campaigns, Earth First!, RAMPS/Mountain Justice, environmental prosecutions, Elaine legal-support detention, anti-conscription organizing, Puerto Rican solidarity, Bay Area civil-rights/FSM custody and Greenpeace-associated protest cases. These are bounded research passes, not exhaustive affiliation rosters.

| Batch | New people | Cases | Portraits |
|---|---:|---:|---:|
| 307 | 3 | 3 | 0 |
| 308 | 28 | 28 | 3 |
| 309 | 9 | 9 | 0 |
| 310 | 4 | 6 | 1 |
| 311 | 9 | 11 | 0 |
| 312 | 12 | 12 | 0 |
| 313 | 5 | 8 | 0 |
| 314 | 2 | 2 | 0 |
| 315 | 1 | 1 | 1 |
| 316 | 2 | 2 | 0 |
| 317 | 5 | 5 | 1 |
| 318 | 9 | 9 | 0 |
| 319 | 11 | 11 | 0 |
| Total | 100 | 107 | 6 |

## Evidence and safeguards

Every proposed person has evidence of actual US custody extending beyond initial arrest. Contemporary reports, participant accounts, court material and archival publications are linked in the per-batch audits and JSON payloads. Precise dates are populated only at their supported precision. Sentence lengths, estimated ages, scheduled releases, bail authorization and travel restrictions do not supply missing prison endpoints.

Unresolved Rushmore chronology and the Richard Harris/George Harvey identity conflict are documented; the disputed fifth identity is excluded. The later memoir’s incorrect jail geography does not create an institution link. Journalists in the Vandenberg case are distinguished from Greenpeace members. Vital-date and portrait fields remain empty where identity or sources were insufficient.

The accompanying seven-line PrisonerCase change prevents year/month/circa date placeholders from producing an apparently exact day total. Explicit documented months and legacy exact dates keep their existing behavior. Four focused feature tests cover these cases; the batch checks exercise the application model in isolated memory.

## Validation

Final query-only identity comparison on September 11, 2026 covered 8,957 live identity records, including aliases, component names and hidden profiles. All 100 remain missing, with zero cross-batch collisions. All thirteen JSON/script counts reconcile; shell syntax and tinker quoting pass. Individual batch audits record isolated-memory insertion, replay, preservation, partial-date, alias, rollback and photo checks. Batch318 passed 322 assertions and batch319 passed 372. No production write used for testing.

## Manual deployment after merge

Apply earlier undeployed batches first. Run 307 through 319 in order as the application owner. Scripts are idempotent and clear relevant API caches. The loop stops on failure.

```bash
cd /var/www/NPPC-Website &&
git pull origin main &&
for batch in {307..319}; do
  sudo -u www-data bash "database/data/run-batch-${batch}.sh" || break
done
```

## New identities

| Batch | Name |
|---|---|
| 307 | William Hogan |
| 307 | John Helme |
| 307 | P. McMahon |
| 308 | Jennie Miller |
| 308 | Eva Jacobs |
| 308 | Jennie Chanin |
| 308 | Sara Schneider |
| 308 | Esther Richman |
| 308 | Rose Silver |
| 308 | Kate Koppa |
| 308 | Bessie Gettman |
| 308 | Rose Goodman |
| 308 | Anna Berenbaum |
| 308 | Caroline Wiglowski |
| 308 | Mae Boncinsky |
| 308 | Marion Brostick |
| 308 | Freda Reicher |
| 308 | Evelyn Dornfield |
| 308 | Florence Corn |
| 308 | Oscar Simons |
| 308 | Yetta Hornstein |
| 308 | Lena Movich |
| 308 | Minnie Seidel |
| 308 | Theresa Rhode |
| 308 | Lillian Greenberg |
| 308 | Ida Dubnow |
| 308 | Fannie Goldberg |
| 308 | Bertha Plantt |
| 308 | Eleanor Sadlowski |
| 308 | Victoria Cieslakiewicz |
| 308 | Vanda Kaleto |
| 309 | Lionel Jean-Baptiste |
| 309 | Michelle Thomas |
| 309 | Olive Armstrong |
| 309 | Milton Parish |
| 309 | Jacqueline Bernard |
| 309 | Jean Ford |
| 309 | Dorie Clay |
| 309 | Wanda Wareham |
| 309 | Arthur Majid Barnes |
| 310 | Alexandra Paul |
| 310 | Amber Canavan |
| 310 | Melany P. Brieno |
| 310 | Adam Durand |
| 311 | Ilse Asplund |
| 311 | Erik Bowers Ryberg |
| 311 | Jennifer Prichard |
| 311 | Peggy Sue McRae |
| 311 | Panagioti Tsolkas |
| 311 | Lynne Purvis |
| 311 | Willow Cordes-Eklund |
| 311 | Erik Gillard |
| 311 | Stevie Lynn Lowe |
| 312 | Sophia Morgan |
| 312 | Dorian Williams |
| 312 | Clark Santee |
| 312 | George Vest |
| 312 | Van Pham |
| 312 | Matthew K. Smith |
| 312 | Kevin Kuenster |
| 312 | Dustin Steele |
| 312 | Junior Walk |
| 312 | Jocelyn Sawyer |
| 312 | Emily Gillespie |
| 312 | Nathan Walker Joseph |
| 313 | Glen Collins |
| 313 | Matthew Almonte |
| 313 | Isabel Indigo Brooks |
| 313 | Camilo Pereira |
| 313 | Catherine Ann MacDougal |
| 314 | Zachary Jenson |
| 314 | Lauren Weiner |
| 315 | Ocier S. Bratton |
| 316 | Thomas Aloysius Hickey |
| 316 | Raymond Soto Dávila |
| 317 | David Lance Goines |
| 317 | Kipp Dawson |
| 317 | Michael Dale Rossman |
| 317 | Patricia Iiyama |
| 317 | Michael Henry Marcus |
| 318 | Virginia Lee Hunter |
| 318 | Lynn Dyan Stone |
| 318 | John Allen Watterberg |
| 318 | Jessica Joyce Miller |
| 318 | Renee Claire Blanchard |
| 318 | Mike Roselle |
| 318 | Steve Loper |
| 318 | Ken Hollis |
| 318 | Phillip Templeton |
| 319 | Nic Clyde |
| 319 | Stuart Lennox |
| 319 | Bill Nandris |
| 319 | John Wills |
| 319 | Tom Knappe |
| 319 | Mathias Pendzialek |
| 319 | Samir Nazareth |
| 319 | Patrik Eriksson |
| 319 | Guy Levecher |
| 319 | Stephen Morgan |
| 319 | Jorge Torres |
