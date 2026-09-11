# Batch 322: IWW detention register

27 missing people and 27 qualifying custody episodes. This raises the next PR accumulation to 45 people and 47 cases across batches 320-322. Nothing deployed; another 55 new identities are required before opening a PR. Existing biographies, dates, photographs, support sites and cases are preserved.

## Evidence

The March 1920 One Big Union Monthly printed prisoner register is a contemporary union source, not an independent court finding. Every included entry was checked against its scanned page, including neighboring columns. Individual charge descriptions stay within what each entry supplies. Later vital dates and photographs remain unverified.

| People | Verified custody / limits |
|---|---|
| Alex Boggio | Seattle March 14-December 2, 1918; precise charge absent |
| Arthur Common; Vincent Corella; Vitan Deloff | Seattle arrests February 15, March 25 and February 5 respectively; each released November 27, 1918; habeas filings recorded without inferring the precise ruling |
| Michael Fitzwilliams | Seattle April 17-November 27, 1918; precise charge absent |
| John Brodahl; Oliver Dailey | Spokane April 5-May 11, 1918; April 10 sentencing to thirty days and costs |
| Mike Diska | Same custody dates, but individual disposition is a $100 fine and costs; no thirty-day sentence copied from neighbors |
| Amos Enright | Spokane April 5-10, 1918; $100 fine and costs |
| Charles Miama; A. Pahjola | Spokane April 5-June 9, 1918; individual fine / sentence retained distinctly |
| Tom Salv | Spokane April 5-May 11, 1918; thirty days and costs |
| Robert Regan | St. Maries, Idaho April 5-May 11, 1918; thirty days and costs. Printed Idaho location preserved |
| Charley Butts; Bill Dirk; Nick Wallace | Sandpoint organizing arrests in 1918; several months held without trial before release |
| Herbert Beesaw | Kalispell in 1917, several days held for hearing before release. Later Whitefish thirty-day sentence is contextual prose, not a claimed additional verified custody interval; latest outcome unresolved |
| Jack Curley | Copalis July 1917, five days held on vagrancy charge before dismissal. Duration checked visually against adjacent Curry entry |
| H. Radunz; D. S. Dietz | Seattle June 1918, twenty days held for federal investigation before release. Unnamed companions not added |
| Tom Walden | Copalis July 1917, twenty days held before release |
| Adolph Guldahl | Spokane August 1, 1918 arrest; fifteen months actually held pending deportation decision, then release. Documented months stored; no release day calculated |
| Emery Sarrazin; Warner Strang | St. Maries March 17, 1918 arrests in deportation proceedings; releases December 9 and November 27 respectively |
| Walter Strom | Eureka early 1918; two months actually held before army induction. Ordinary military service not counted as prison time; exact release date absent |
| H. D. Medis | Yakima federal custody for several months, then grand-jury release. No arrest year or decade assigned |
| Paddy Mee | August 10-September 10, 1918 sedition detention; released for insufficient evidence. No state/city copied from neighboring entries |

Exact release days are never calculated from nominal sentences or reported durations. For Guldahl and Strom, documented-month fields let the existing counter use the source duration while endpoint fields remain empty. The model derives internal day values from months; those values are not claims of independently established release dates.

## Sources

- **march-p7:** [One Big Union Monthly, March 1920, printed p.7, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p8:** [One Big Union Monthly, March 1920, printed p.8, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p9:** [One Big Union Monthly, March 1920, printed p.9, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p10:** [One Big Union Monthly, March 1920, printed p.10, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p11:** [One Big Union Monthly, March 1920, printed p.11, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p15:** [One Big Union Monthly, March 1920, printed p.15, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p17:** [One Big Union Monthly, March 1920, printed p.17, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p18:** [One Big Union Monthly, March 1920, printed p.18, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **march-p20:** [One Big Union Monthly, March 1920, printed p.20, named prisoner register. Individual entry and neighboring columns checked against the scanned page image.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)

## Identity review and held leads

Compared against the 8,957-identity read-only inventory, name/alias substrings and likely spelling variants, pending batches 297-321, and a fresh live query-only preview. All 27 are missing in that preview. Names stay as printed rather than being silently modernized or expanded.

Held: Roy Sample versus existing A. H. / Alonzo H. Sampley; A. Spanberg versus A. Svartberg; E. McNicoll versus J. McNichols; Ralph Bergdorff requires individual episode separation; John Westphal requires separating repeated state arrests from the later federal case. Known Shurin/Shuren, Blaine/Blame, Winsky/Winski and other spelling duplicates remain excluded.

The 123-name scratch candidate scan is a lead list, not 123 verified missing prisoners. It also misses candidates whose surnames occur in unrelated existing names (Bill Dirk and Nick Wallace were recovered by checking the printed pages).

## Validation

772 assertions passed with database writes confined to SQLite memory after all production connections were removed; file, storage and cache mutations mocked. Checks cover missing-only creation, preservation of an existing hidden alias, existing photos/bios/cases, replay, date precision, unknown endpoints, documented months, release status, validation rollback and API cache invalidation. Fresh live forced dry-run used PRAGMA query_only=ON and found 27 missing identities. Shell syntax, zero-apostrophe tinker block and diff checks passed.

The first test run exposed a test expectation that did not account for the existing documented-month behavior. Explicit expected values were added for the two documented durations; no production or application code was changed.

## Next research

Continue the remaining March roster candidates, especially pages 12-16 and 21; check their page images and aliases. Revisit held identity ambiguities, the Ellis Island group and the Guiney release-year conflict. Keep research accumulating locally until 100 new people qualify.
