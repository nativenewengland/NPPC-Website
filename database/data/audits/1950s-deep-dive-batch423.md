# 1950s missing-field research: batch 423

Prepared October 12, 2026 after repeating live verification of batch 422. All 23 earlier target rows retained their values; all 16 public pages displayed the additions; ten public image checksums matched. The repeat report is `batch422-public-recheck-2026-10-12.txt`.

| Profile | Verified missing additions |
| --- | --- |
| Alfred Marder | Born January 18, 1922; died December 19, 2023. Community obituary explicitly gives both dates and identifies the New Haven activist and Smith Act defendant. Existing photo preserved. |
| A. Leon Lowry | Born June 12, 1913, per Contemporary Black Biography; died August 20, 2005, confirmed by the Wilson Funeral Home notice identifying the Beulah Baptist minister. |
| Albert Lima | Died June 3, 1989, per contemporary obituary. Existing photo preserved. |
| Andrew Wade | Born February 12, 1922; died September 26, 2005; captioned Keith Williams portrait from the February 15, 1988 Courier-Journal, reproduced in Kentucky's National Register district nomination. Family funeral notice independently confirms death. |
| Asbury Howard | Sentenced January 24, 1959; entered the post-bond imprisonment episode February 23, 1959. |
| Asbury Howard Jr. | Sentenced and jailed March 7, 1959, per National Guardian, March 16, page 3. |

Six people, four profile rows and two case rows. Twelve additions: one portrait, three birth dates, four death dates and four case dates. Every date has day precision; existing precision keys remain unchanged. Sources and identity evidence for each field are in `fixes/batch423.json`. Portrait crop coordinates, credit and checksum are in `photos/CREDITS-batch423.md`.

## Custody interpretation and unresolved findings

The [1959 USCCR report](https://www.usccr.gov/files/historical/1959/59-002.pdf), printed pages 74–75, establishes Howard's January 24 trial and sentence. Its January 29 arrest date precedes neither that trial nor the beating; the report is internally inconsistent. The existing January arrest with month precision is preserved. The [March 16 National Guardian](https://www.marxists.org/history/usa/pubs/national-guardian/1959-03-16-11-22-nat-guardian.pdf), page 3, independently places conviction on January 24, then distinguishes freedom on bond from a later remand on Washington's birthday. The [August 14 ILWU Dispatcher](https://archive.ilwu.org/wp-content/uploads/2015/03/19590814.pdf), page 7, explicitly supplies February 23 as the imprisonment start. It reports release after five months but does not give a day; no release date is calculated from that duration.

Howard Jr.'s March 7 incarceration records the later sentence-related episode. The government report describes his earlier arrest followed by release on bond. Neither case receives an assumed continuous jail interval, release date, or duration total. The historical newspaper's description of the father's poster is not evidence that its drawing depicts Howard himself; that drawing is not used as a portrait.

Other unresolved points:

- Wade's nomination contains inconsistent obituary bibliographic dates. The family notice explicitly identifies the electrician, wife Charlotte and September 26 death; the nomination narrative agrees. No jail release day established.
- Lowry's existing case describes a legislative committee's recommendation to jail him. No actual incarceration or release date is supplied from that recommendation. A primary oral-history transcript remains a further research lead.
- Marder's acquittal is not assumed to be his release date.
- Lima's exact birthday is not established by the obituary. Genealogical age/date leads conflict. Two existing Smith Act case rows appear to overlap and contain conflicting dates; no existing dates or assumed custody endpoints are copied or replaced.
- Howard Jr.'s potential 2005 obituary requires further identity verification; no birth/death date is assigned from a name match alone.
- Abraham Unger's Swedish namesake portrait is incorrect for the American attorney. A Canadian newspaper photo lead was blocked by source access; no image assigned.
- Abdel Mohsen Hamouda, Arthur McPhaul, Albert E. Perry Jr. and Dan B. Speed retain unresolved vital/custody/photo gaps after initial searches. They are not marked complete.

## Audit and deployment

The persistent candidate tracker contains 722 profiles and explicitly separates pending and partial research from verified applied fields. It does not imply that all 851 associated cases date from the 1950s or that all candidates have been individually researched. A blank death date does not establish that a person has died.

The numbered script and payload are committed before application. No pull request is created. The script checks identity, missing-field expectations, existing precision, custody guards and the portrait checksum before writing; it runs a transaction and clears the API cache only after actual changes. Direct query updates avoid unrelated model hooks. Photos already present are preserved. Biographies, case prose, custody flags, duration totals and all existing dates remain unchanged. Live before/after comparison, replay and public checks are recorded in `batch423-live-verification.txt` after deployment.
