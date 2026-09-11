# Batch 300 — Catholic Worker book leads and SOA protest prisoners

Reviewed September 10, 2026. **18 new people, 20 cases; local only.** No PR, push, deployment, or production database writes. Continues batches 297–299 toward the user’s 100-new-person PR threshold.

## Verified additions

The seventeen SOA protesters below were arrested at Fort Benning on November 16, 1997. Each has an individual completed-custody profile in the [November 30, 1998 clemency submission preserved by the Clinton Presidential Library](https://nara-media.s3.amazonaws.com/presidential-libraries/clinton/foia/2006/2006-1704-F-Seg-1-PDF/2006-1704-F-Pardons-PDF/Box_63/42-t-7422575-20061704F-063-006-2016.pdf). This is the petitioners’ contemporary evidence, not a finding that clemency was granted. The [SOA Watch roster](https://soaw-austin.org/POC1983_2012.php) corroborates participation.

| New person | Documented prison entry | Documented release | Individual archive page (PDF page) |
| --- | --- | --- | --- |
| Nicholas Cardell | March 23, 1998 | September 18, 1998 | 45 |
| Mary Earley | March 23, 1998 | September 18, 1998 | 46 |
| Mary Kay Flanigan | March 23, 1998 | September 1998; around the 18th | 47 |
| Anne Herman | January 30, 1998 | July 27, 1998 | 48 |
| Paddy Inman | March 23, 1998 | September 1998; around the 18th | 49 |
| Ken Kennon | March 23, 1998 | September 18, 1998 | 50 |
| Dwight Lawton | March 23, 1998 | September 18, 1998 | 51 |
| Rita Lucey | March 23, 1998 | September 18, 1998 | 52 |
| Bill McNulty | March 23, 1998 | September 18, 1998 | 52 |
| Carol Richardson | January 21, 1998 | July 19, 1998 | 54 |
| Dan Sage | March 23, 1998 | September 18, 1998 | 55 |
| Doris Sage | March 23, 1998 | September 18, 1998 | 56 |
| Randy Serraglio | March 23, 1998 | September 18, 1998 | 56 |
| Rita Steinhagen | March 23, 1998 | September 18, 1998 | 56–57 |
| Ann Tiffany | March 23, 1998 | September 18, 1998 | 58 |
| Judith Williams | March 23, 1998 | September 18, 1998 | 59 |
| Ruthy Woodring | March 23, 1998 | September 18, 1998 | 61 |

**Johnny Baranski** has three separately evidenced prison experiences: the 1974 government-property prosecution and McNeil Island imprisonment; imprisonment at Lompoc after the 1979 Bangor Trident protest; and the 1987 Snohomish County railway-blockade case. [Paul Miller’s biographical sketch and Haiku Northwest memorial](https://www.haikunorthwest.org/poems-by-haiku-northwest-members/johnny-baranski-1948-2018) connect the cases to his prison writing. Sentence lengths are recorded as sentences, not assumed actual durations. No release dates or unverified incarceration start dates are manufactured. Only the explicitly dated 1987 confinement receives an incarceration year.

## Precision and identity decisions

- Flanigan and Inman: the submission explicitly qualifies release as approximate. Store month precision and six documented months; retain the approximate day in explanatory prose. The remaining SOA dates use the stated endpoints, not six-month arithmetic.
- Original PDF pages 45, 47–49 and 59 inspected visually. Christopher Jones’s approximate release is September **18**, not the OCR’s September 13. He is **held**, because the broad name matcher also finds existing Christopher Columbus Jones; review that existing identity before introducing a disambiguation exception. No existing Jones record changed.
- Carol Richardson’s November 19, 1997 sentencing is separately stated. January 21/22 trial-date disagreement for the larger group is not propagated into their sentencing fields.
- Williams: the 1998 dates come from the contemporary submission. A later article’s 1991 reference is not treated as a separate imprisonment. Her [interview with Laura DeNooyer](https://lauradenooyer.com/meet-my-neighbor-judith-imagining-a-better-world-part-3/) identifies the federal prison at Pekin.
- Baranski: full family-obituary name is Johnny Wayne Baranski. John/John Wayne variants are duplicate-prevention keys. No details from the reversed *Four of Us* case are merged without a stronger identity bridge.
- All new identities checked against 8,853 live profiles including aliases and hidden records, and payloads 269–299. The live preview reports 18 missing, zero existing. Existing profiles are skipped as a whole at execution, even if newly added after research.

## Vital dates, photos and map

- Baranski: May 1, 1948–January 24, 2018, [family obituary](https://www.legacy.com/us/obituaries/oregon/name/johnny-baranski-obituary?id=17093027).
- Flanigan: May 7, 1932–July 31, 2025, [congregational/funeral obituary published in the Post Bulletin](https://www.postbulletin.com/obituaries/obits/sister-mary-kay-flanigan-optqvlp2hwdv3e1nafm7).
- Cardell: June 23, 1925–October 7, 2002, [UU ministers’ memorial, reproduced page 4](https://www.yumpu.com/en/document/view/24350484/in-memoriam-unitarian-universalist-ministers-2002-a-2003/4). Not the namesake Nicholas Cardell who died in 2025.
- Steinhagen: November 21, 2006 death, [funeral notice](https://obituaries.startribune.com/obituary/sister-rita-anthony-steinhagen-1090547471/). Ignore the migrated page’s erroneous 2025 heading. Birth-date lead remains open; no age subtraction.
- No portraits added in this batch. Identified image leads on the Baranski memorial, Flanigan obituary and Williams interview remain for attribution and image review. No personal-support websites verified; news/source URLs never populate that field.
- SOA markers use the approximate [Columbus municipality point](https://en.wikipedia.org/wiki/Columbus,_Georgia), explicitly representing the protest/court region. They do not falsely assign seventeen people to a single prison. Only Williams has the supported Pekin relation.
- Baranski’s marker reuses the existing McNeil Island point and named McNeil/Snohomish relations. Do not assign a 1979 Lompoc penitentiary account to the present FCI without verifying institutional continuity.

## Validation

- Shell syntax checked; no ASCII apostrophes in the single-quoted tinker block.
- **589 assertions passed** with real application models in isolated SQLite memory. Production schema read under `PRAGMA query_only = ON`, then every production connection purged and replaced by the single memory connection before test writes.
- Covers partial dates, exact durations, documented months, vitals, separate cases, UUIDs, existing bios/cases/photos/support URLs, hidden-alias preservation, ambiguous identities, rollback, replay idempotency, cache invalidation and rejection of unreviewed data.
- Live preview was read-only. Production deployment remains manual after the eventual combined PR and earlier batches in order.

Continue the book review in `catholic-worker-books-review-2026-09-10.md`; this batch does not claim the four full books have been read.
