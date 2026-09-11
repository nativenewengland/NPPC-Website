# Full-book review: peace activism and missing custody records

Reviewed September 10, 2026. **15 proposed missing prisoners, 21 case findings involving 20 existing profiles, and 12 caption-identified photograph leads.** These are research findings, not deployed additions. Existing biographies, cases, photos and personal support links have not been changed.

This review replaces the access limitations of the earlier preview-based review. It does **not** claim that every Catholic Worker action or every passage in these books has now been exhausted. All four supplied files were indexed in full; the analysis below comes from targeted reading of custody narratives, chronology entries, notes, captions and existing case summaries. Further person-by-person and case-by-case verification continues.

## Sources and comparison

| Supplied book | Edition and material | Work performed |
| --- | --- | --- |
| Arthur J. Laffin and Anne Montgomery, *Swords into Plowshares: Nonviolent Direct Action for Disarmament* | **1987 first edition**, Perennial Library/Harper & Row; ISBN 0-06-064911-9; 276 PDF pages | Indexed entire scan; read contents, early chronology and selected prison narratives. Visually checked the Trident II sentencing/release page. This is not the later Volume Two preview. |
| Arthur J. Laffin, ed., *The Plowshares Disarmament Chronology, 1980-2003* | 2003 Rose Hill Books; 114 PDF pages | Indexed entire scan; reviewed later chronology entries, especially 1999-2003, and checked selected source pages visually. The physical page number is 12 below the PDF page number in the later chronology: printed p.74 = PDF p.86. |
| Rosalie G. Riegle, *Doing Time for Peace: Resistance, Family, and Community* | Vanderbilt University Press, copyright 2012; supplied EPUB, 24 spine sections | Indexed entire EPUB with embedded printed-page markers; read targeted interviews, context and photograph captions. A section includes both the narrator and editorial transitions, so paragraph attribution was checked individually. |
| Rosalie G. Riegle, *Crossing the Line* | Supplied EPUB, 23 spine sections | Indexed entire EPUB; reviewed targeted US custody accounts, family testimony, foreign proceedings, endnotes and captions. Cited by chapter and named interview because this EPUB does not provide reliable printed-page markers. |

No *Voices from the Catholic Worker* file was supplied in this message. Its earlier archive leads remain separate. The full books and extracted text remain local working material; they are not included in the repository changes. File hashes and extraction coverage are recorded in `full-books-source-manifest-2026-09-10.json`.

Duplicate screening used the existing 8,853-person name/alias inventory and pending batches 269-300. Read-only, scoped production queries supplied **178 public case summaries for 144 book-identified people**. A name inventory is not a complete content audit: case proposals must be rechecked against the current database and pending case-fix batches before import. No full production export or production write was performed.

## Missing people with explicit multi-day US custody

The [person review CSV](full-books-missing-prisoners-2026-09-10.csv) records aliases, evidence, page/chapter references and unresolved fields for every person below. None matched the live identity inventory or the reviewed pending person batches.

| Person | Custody supported by the book | Principal remaining detail |
| --- | --- | --- |
| Robert / Bob Wollheim | Less than five months following Vietnam draft refusal; imprisonment described in 1969 | Exact endpoints; reconcile conflicting chronology in later memorial |
| Steve Woolford | Actually served six months after Pentagon blood-pouring; 2003 custody | Exact dates and facility sequence |
| Mike Miles | Six months in DC jail after Holy Week 1981 White House protest; later weeks for Project ELF | Dates of later term |
| Hattie Nestel | Actual detention during a 45-day sentence connected with Trident resistance | Identify action year, facility and whether all 45 days were served |
| Joni / Joan McCoy | Three months at Lexington and a separate three months in Bay County Jail | Years and individual action dates |
| Tom Karlin | Roughly four months after Bangor protest, including Boron and transit jails | Year and precise total; estimates do not add cleanly |
| Harry Murray | 90-day community-confinement sentence in Rochester; separate 15-day Albany jail term | Exact years and endpoints; retain work-release distinction |
| Genevieve “Mickey” Allen | Five days after refusing a promise to appear; time-served disposition | Specific Trident action and year |
| Kim Wahl | 25 days in King County Jail for Boeing-sidewalk protest | Exact dates; this is not Bill Wahl’s term |
| Anne S. Hall | Six days in Camden County Jail after January 1989 Kings Bay protest | Exact arrest/release days |
| Becky Johnson | Actual county and Alderson custody after a six-month sentence in 2003 | Individual SOA roster and precise endpoints |
| Renaye Fewless | One week after the 2000 Rampart Police Station protest; charges dismissed | Exact days |
| Marian Mollin | 48 hours after Honeywell prosecution; separate 30-day Massachusetts term | Identity/year of the Massachusetts manufacturer action |
| Tina Busch-Nema | Actual Carswell custody after 2006 SOA action; sentence January 29, 2007; entered prison late March | Exact entry/release days; do not equate sentence automatically with completed days |
| Marty / Martin Harris | Explicitly served 19 months of a 42-month draft-refusal sentence; entered March 1969 | Institution and exact release |

For Wollheim, the [Reed College memorial](https://www.reed.edu/reed-magazine/in-memoriam/obituaries/2020/robert-douglas-wollheim-70.html) independently supports Safford imprisonment and death on September 21, 2019. Its introductory 1967 imprisonment date conflicts with the book's later sequence and should not be copied. The books support custody even when an exact date remains unknown; unknown fields should remain blank rather than turning an estimate into a date.

## Existing cases: additions and conflicts

The [case findings CSV](full-books-case-findings-2026-09-10.csv) contains **21 findings, not 21 newly proven missing cases**. It includes corroboration, missing fields, missing proceedings and conflicts. Each row identifies the existing profile and case IDs for a guarded follow-up.

- **Trident II defendants:** Jean Holladay, Frank Panopoulos, William Boston, Leo Schiff and John Pendleton lack the October 18, 1985 sentencing date. The book supplies different **1986 release months**, while the live cases all end September 30, 1985, before that sentencing. These require explicit corrections, not empty-field updates. Month-only releases must retain month precision.
- **Sacred Earth and Space Plowshares, 2000:** Anne Montgomery, Carol Gilbert, Ardeth Platte and Jackie Hudson have no distinctly identified September 9-16, 2000 case in the inspected summaries. The chronology documents their week at El Paso County Jail and dropped charges. Existing generic/overlapping cases must first be reconciled. Elizabeth Walters is also named in the book, but her individual case comparison is still required.
- **Steve Kelly:** the 1999 case has a blank incarceration date; the chronology supports December 19, 1999. It also distinguishes the later 2001 federal revocation proceeding. His August transfer between authorities is not a release to freedom.
- **Michael Sprong:** the May 4, 2001 sentencing date is missing. The book distinguishes June 24-27, 2000 detention from the later sentence, conflicting with the current continuous interval beginning June 14.
- **Philip Berrigan and Susan Crane:** the chronology distinguishes the 1999 Maryland prosecution from February 2-December 14, 2001 revocation custody. Their current records have serious unrelated-date/duplicate problems, so adding overlapping cases without reconciling those rows would worsen the counter.
- **Daniel Berrigan:** a current 1980 Plowshares case has a 1972 release date. This is an impossible interval, not an unknown date.
- **Kathy Kelly:** one current row explicitly combines several distinct prosecutions and spans 1988-2014. The books provide a way to research the earlier terms individually; they cannot verify a later 2014 proceeding published after the books.
- **Larry Morlan:** the 1983 Holy Innocents Pentagon case and February 17, 1984 sentencing appear missing. His later generic Plowshares record requires identification before any dates are filled.
- **Martha Hennessy:** a three-month Seabrook imprisonment is a separate earlier-case lead. It must not be pasted into her sparse case ending in 2021.
- **Lisa Hughes and Scott Schaeffer-Duffy:** the interviews help distinguish prison-reporting dates and actual term lengths from initial arrests. Lisa's Greenville institution relationship is missing.
- **Barbara Katt:** both the 1984 suspended sentence and June 24-27, 2000 detention are already present. No additional case is needed.
- **Anne Montgomery's 1982 custody:** Judith Beaumont's account distinguishes roughly seven weeks of pretrial custody from later imprisonment. A single uninterrupted term would lose that distinction.

## Identity, source and precision cautions

The [held-leads CSV](full-books-held-leads-2026-09-10.csv) records 16 rows, some covering several related names. These are not 16 additional import-ready people.

Family members' interviews cannot be treated as their partners' prison records. Examples include Nettie Cullen, Kim Williams, Claire Schaeffer-Duffy and the Berrigan children. Chris Allen-Doucot describes his own weekends in jail, but the particular cases remain unresolved. Bill Wahl describes jail experience but also an explicitly noncustodial disposition; Kim's 25 days must not be assigned to him.

Maggie Geddes explicitly says she was not arrested for Women Against Daddy Warbucks and that no one served time for that action. John Hagedorn's three years were **probation**. Scott Albrecht's two-day imprisonment in Britain does not establish US custody. Naming a US aircraft or US military installation in a foreign action also does not automatically establish US imprisonment.

The books themselves contain discrepancies. The 1987 chronology dates Trident Nein July 4, while Beaumont's essay says July 5. *Doing Time* misidentifies the state court on which Wollheim later served and appears to reverse the methods used in the Baltimore Four and Catonsville Nine actions. Such conflicts should be checked against primary records, not silently propagated. Oral-history approximations must remain approximate.

## Photographs and next work

The [photo leads CSV](full-books-photo-leads-2026-09-10.csv) records **12 caption-identified images** of missing candidates, with EPUB image references and printed credits. These include Wollheim, Woolford, Mickey Allen, Kim Wahl, Hall, Miles, Nestel, McCoy, Karlin, Murray, Harris and Fewless. Some are family/group scenes and need visual identification and exact rectangular crops. No image has been extracted for publication or attached to a record in this review; captions alone do not establish reuse terms or crop boundaries.

Next work is to verify the unresolved dates and aliases, prepare source-backed idempotent additions, and reconcile conflicting existing cases separately. Recheck every import against current names/cases and pending fixes; fill only genuinely blank fields and preserve all biographies. Research findings are **not** counted as validated additions toward the PR threshold: batches 297-300 still contain **35 prepared people**. No new PR until **100 new people** are validated in total.

## Implementation follow-up

The verified findings are now prepared in local batches **301–302**: 15 new people with 19 cases, seven existing-case updates including the five Trident II date corrections, and five new cases on existing profiles. Not deployed. See [the implementation and conflict-resolution report](full-books-batches301-302-2026-09-10.md). Other held findings and photo leads remain open.
