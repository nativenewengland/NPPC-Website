# 100 additional prisoners: batches 326–338

**100 new people, 114 custody periods, 14 portraits, five birth dates and seven death dates.** The package covers garment labor organizing, International Workers Order detention, military conscientious resistance, Irish republican US custody, and Philippine resistance under US colonial authority. It is a bounded research pass, not a claim that every participant in these movements has been identified.

All additions require reliable evidence of actual multi-day custody by US authorities. The scripts preserve existing profiles, biographies, cases, photos and personal support URLs. Source citations remain research citations. No migrations or direct production writes were used.

Merge and deploy PR #2493 (batches 307–319, including the partial-date counter fix) and PR #2494 (320–325) before this package. The new PR contains only batches 326–338 and their research files; earlier data changes are not duplicated.

| Batch | New people | Cases | Photos |
|---|---:|---:|---:|
| 326 | 10 | 20 | 8 |
| 327 | 3 | 4 | 1 |
| 328 | 5 | 5 | 0 |
| 329 | 4 | 4 | 1 |
| 330 | 2 | 2 | 0 |
| 331 | 2 | 3 | 0 |
| 332 | 5 | 6 | 0 |
| 333 | 5 | 6 | 1 |
| 334 | 4 | 4 | 1 |
| 335 | 2 | 2 | 2 |
| 336 | 12 | 12 | 0 |
| 337 | 23 | 23 | 0 |
| 338 | 23 | 23 | 0 |
| **Total** | **100** | **114** | **14** |

## Evidence and verification

The final read-only inventory contained 8,957 profiles. Names, aliases, components, spelling variants and prior pending payloads 297–325 were checked. Two token matches are demonstrably different people: Buffalo detainee James Kelly versus Everett prisoner James Whiteford, and Balayan detainee Francisco Martinez versus Colorado attorney Francisco Kiko Martinez. Narrow UUID-and-identity guards preserve the existing profiles and stop if their reviewed identity fields change. There are no unresolved identity collisions.

All 13 runners passed syntax and quoting checks. Isolated in-memory tests passed **3,951 assertions** across the package, covering insertion, replay, preservation, alias matching, partial dates, rollback, photo hashes and cache invalidation where applicable. Production connections were purged before mutations and file/storage/cache writes mocked. Separate live previews were query-only and found all 100 missing. Production deployment has not occurred.

Sentences are not treated as proven time served. Bail orders are not assumed to be physical release. Transfer dates are recorded in narrative without becoming release dates. Month/year precision preserves unresolved conflicting or unreadable days. Philippine military allegations are attributed to the record, and historical-war context is distinguished from proven organizational membership. The Balayan records establish custody under US authority abroad; they are not mainland imprisonments. No birthdays are reconstructed from ages. Each batch audit explains its own sources and held leads.

## Manual deployment after the preceding PRs and this PR are merged

Run all earlier undeployed batches first. This loop stops at the first failure.

```bash
cd /var/www/NPPC-Website &&
git pull origin main &&
for batch in {326..338}; do
  sudo -u www-data bash "database/data/run-batch-${batch}.sh" || break
done
```

## New identities

| Batch | Name |
|---|---|
| 326 | Bertha Elkins |
| 326 | Josephine Casey |
| 326 | Morris Sigman |
| 326 | Morris Stupnicker |
| 326 | Solomon Metz |
| 326 | Julius Woolf |
| 326 | Isidore Ashpitz |
| 326 | Abraham Weidinger |
| 326 | Max D. Singer |
| 326 | Louis Holzer |
| 327 | Andrew Dmytryshyn |
| 327 | Sam Milgrom |
| 327 | Ignatz Mezei |
| 328 | Victor Agosto |
| 328 | Marc A. Hall |
| 328 | Ricky Clousing |
| 328 | Mark Wilkerson |
| 328 | Agustin Aguayo |
| 329 | Joel Klemkewicz |
| 329 | Katherine Jashinski |
| 329 | Ivan Brobeck |
| 329 | Ryan Jackson |
| 330 | Ryan Johnson |
| 330 | Patrick Brendan Hart |
| 331 | Michael O'Rourke |
| 331 | Gerry McGeough |
| 332 | Michael Martin |
| 332 | Denis Leyne |
| 332 | Thomas Oliver Maguire |
| 332 | Gerard Anthony Brannigan |
| 332 | Patrick Moley |
| 333 | Olive McKeon |
| 333 | Bernard J. McKeon |
| 333 | Desmond Ellis |
| 333 | Owen Carron |
| 333 | Danny Morrison |
| 334 | Edward Howell |
| 334 | William Gilroy |
| 334 | William O’Neill |
| 334 | James Kelly |
| 335 | Dionisio Magbuelas |
| 335 | Felipe Salvador |
| 336 | Ramon Mortel |
| 336 | Gregorio Mortel |
| 336 | Domingo Macuha |
| 336 | Rufino Macuha |
| 336 | Brigido Miña |
| 336 | Francisco Cadacio |
| 336 | Juan Suarez |
| 336 | Sinforoso de Leon |
| 336 | Andres Mendoza |
| 336 | Gregorio Hernandez |
| 336 | Francisco Endozo |
| 336 | Paulino Evangelista |
| 337 | Raymundo Manalo |
| 337 | Sueto Manalo |
| 337 | Diego Rol |
| 337 | Ildefonso Javier |
| 337 | Apolonio Garcia |
| 337 | Crispulo Rinosa |
| 337 | Francisco Medrano |
| 337 | Domingo Destreza |
| 337 | Guillermo Gonzales |
| 337 | Modesto de Ocampo |
| 337 | Antonio Olaye |
| 337 | Mariano Ropo |
| 337 | Bonifacio Limboc |
| 337 | Teodoro Mala |
| 337 | Isidoro Gallardo |
| 337 | Andres Bayaborda |
| 337 | Gregorio Mendoza |
| 337 | Andres Austria |
| 337 | Francisco Sanchez |
| 337 | Miguel Cudiamat |
| 337 | Antonio Asuncion |
| 337 | Sotero Basques |
| 337 | Braulio Dinamaria |
| 338 | Andres Valencia |
| 338 | Baltazar Afable |
| 338 | Mariano Lindnog |
| 338 | Rosendo Olayi |
| 338 | Gregorio Riel |
| 338 | Gregorio Olayi |
| 338 | Eduardo Rol |
| 338 | Felipe Samonteza |
| 338 | Juan de la Cuesta |
| 338 | Rufino Billon |
| 338 | Antonio Biadoy |
| 338 | Crisanto Biadoy |
| 338 | Cirilo Garcia |
| 338 | Cornelio Alaras |
| 338 | Mariano Reyes |
| 338 | Francisco Martinez |
| 338 | Maximo Castillo |
| 338 | Marcos Batangan |
| 338 | Pio Condicion |
| 338 | Remigio Pedroza |
| 338 | Crisanto de Ocampo |
| 338 | Seferino Calzado |
| 338 | Cornelio de Jesus |
