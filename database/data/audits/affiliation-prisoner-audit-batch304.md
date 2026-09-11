# Batch 304 — Stony Brook and CWP-related custody

Reviewed September 11, 2026. **25 missing identities, 26 cases**, validated locally. Accumulation: **77 of 100 new people** across unpublished batches 297–304. No PR or deployment. Existing biographies, cases, images and populated fields are preserved.

## Evidence

The March 21, 1969 *Statesman*, printed/PDF page 1, names all 21 defendants sentenced for the Stony Brook library protest. The March 27 issue, printed/PDF page 1, confirms they were still serving their county-jail terms, quoting jail officials and describing visits the preceding weekend. This establishes actual multi-day custody separately from the fifteen-day sentences. Each name appears in the accompanying CSV and JSON.

The February 23, 1970 *Statesman* editorial, **printed page 4, PDF page 1**, confirms Mitch Cohen and Glenn Kissack spent the weekend in county jail following sentencing. Cohen received concurrent four- and three-month terms; Kissack ten days and a $100 fine. Full service of those sentences is not inferred. The editorial concerns campus exclusion and intervention in Cohen's arrest, not the later May 1969 cases.

The March 21, 1969 issue separately reports economics professor Michael Zweig still held following Tuesday's contempt sentence and Wednesday faculty support. His refusal concerned grand-jury questions about students in a drug investigation. He is not assigned the library protest's charges.

The November 10, 1970 *Statesman*, printed/PDF page 1, confirms Ira Wechsler remained jailed since October 26 and received fourteen months plus a $1,200 fine on November 9. This later prosecution arising from May 1969 protests is a second case, separate from his March 1969 term. The adjacent portrait is attorney Robert Reiter, not Wechsler.

*Workers Viewpoint*, September 30–October 13, 1981, page 7, reports Jose Calderon began serving his thirty-day term September 21. The National Governors Association's 1980 proceedings, printed page 63/PDF page 78, independently document the egg-throwing protest, assault-on-a-congressman conviction and January 1981 sentence. Party allegations about other people's criminal responsibility are not adopted as factual findings.

The relevant *Statesman* and *Workers Viewpoint* page images were visually reviewed. NGA proceedings were checked in the full extracted primary text; the web screenshot request failed, so no visual review is claimed for that page. SUNY PDFs sometimes store all OCR on the first PDF page and arrange page images out of printed order. Locators above refer to verified images, not OCR match positions.

Source URLs and evidence for each case are retained in [batch304.json](../fixes/batch304.json). The author biography for Jerry Tung supports his later CWP leadership; the PLP obituary supports Wechsler's SDS/PLP participation. The other library defendants are assigned antiwar movement participation, not unverified CWP membership.

## Dates and identities deliberately left unresolved

- The library roster's March 21 report says sentencing occurred Tuesday (March 18), while March 27 gives March 19. Sentencing is month-only. March 13 arrest is explicit; uninterrupted custody beginning on that day is not established, so incarceration is month-only.
- March 25 and 27 reports predict March 28 early release conditional on good behavior. They do not confirm release. No release endpoint or actual fifteen-day total is entered, and none of the unnamed five honor-farm transfers is assigned to an individual.
- William B. Martin was an eighteen-year-old student in the 1969 roster. Scoped live case reads identify existing William E. Martin and William M. Martin as separate World War I prisoners. His middle initial is retained in matching; the ambiguous generic William Martin alias is omitted.
- Michael Cohen is not treated as a verified alias for Mitch/Mitchel Cohen. No modern namesake information is imported.
- Ira Wechsler's January 2, 2025 obituary says December 9 and age 75 without an explicit death year in its body. No exact birth or death date is inferred. No birth dates are calculated from newspaper ages.
- Jerry Tung's subsequent longer sentence and Kissack's later May 1969 proceeding remain separate research leads; no duplicate or speculative case is added.
- No photos, coordinates, support websites, or institution IDs are introduced without verification.

## Validation

- September 11 query-only live preview: 25 would be added, zero existing profiles selected. The snapshot and preceding unpublished payloads were also screened.
- 736 assertions passed with all writes confined to isolated SQLite memory. Production connections are removed before test mutations. Checks include replay, existing hidden aliases, preservation, payload validation, date precision, separate cases and rollback.
- Shell syntax passed; the single-quoted tinker body contains zero ASCII apostrophes. JSON holds prose. Application cache invalidation is included for eventual manual deployment.

## Held CPUSA and CWP leads; next affiliation

CPUSA's broader membership remains open. The August 31, 1934 *Daily Worker*, page 1, calls a released defendant **Fred Biedell**, while Kelley's account calls the apparent counterpart **John Beidel**. Identity and multi-day continuity need resolution. Fred Keith, Donald Burke and A. Landy were arrested in the September 21, 1932 ILD raid, but the September 23 report gives only 27 hours before release; no qualified entry is counted. Addie/Adie Adkins and Mrs. Scheinert's September 1934 charge dismissals do not prove days in custody. Nat/Ned Goodwin and the Braziers remain held as explained in batch303.

Discovery references: [August 31, 1934](https://www.marxists.org/history/usa/pubs/dailyworker/1934/v11-n209-aug-31-1934-DW-LOC.pdf), [September 22, 1932](https://www.marxists.org/history/usa/pubs/dailyworker/1932/v09-n227-NY-sep-22-1932-DW-LOC.pdf), [September 23, 1932](https://www.marxists.org/history/usa/pubs/dailyworker/1932/v09-n228-NY-sep-23-1932-DW-LOC.pdf), [September 25, 1934](https://www.marxists.org/history/usa/pubs/dailyworker/1934/v11-n230-sep-25-1934-DW-LOC.pdf). These are held OCR/text leads, not visually verified import evidence.

CWP's NASSCO Three—Mark Loo, Rodney Johnson and David Boyd—and Nelson Johnson already match existing identities. Page 6 of the September 30 *Workers Viewpoint* names John Spearman, Mike Young, Aaron Estis and Vera Michaelson in Albany arrests, but this review does not establish individual multi-day custody. Bojie Jordon and unnamed related detainees also remain held. The CIA archive copy of *CovertAction* is a hosted magazine, not a CIA factual finding.

Continue queue 49, Congress of Industrial Organizations, retaining these open leads. No claim that either party's complete membership has been exhausted.
