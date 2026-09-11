# Batch 305 — labor prisoners

Reviewed September 11, 2026. **17 new identities and 17 cases**, validated locally. This brings the unpublished accumulation to **94 of 100**. No PR or deployment. Existing records are preserved.

## Maine shoe strike

William J. Mackesy (also reported as Macksey), Ernest Henry and attorney Sidney Grant are missing. Powers Hapgood already exists. The Maine Supreme Judicial Court's *Charles Cushman Co. v. Mackesy*, 135 Me. 490–500, identifies the contempt proceeding and six-month sentences. The July 31, 1937 *Socialist Call*, page 2, confirms the organizers and Grant were released on bail after about two months. Norman Thomas's June 26 report, page 12, independently describes Henry's three weeks in jail and the remaining organizers' confinement.

The contempt proceedings were dismissed June 30, 1938 because the petitions lacked the verification needed to confer jurisdiction. This is not entered as their release date. A separate conspiracy/riot prosecution appears in the same court volume; its dispositions are not collapsed into the contempt case. Prison entry and sentencing are May 1937 at month precision. No exact release or exact two-month counter is inferred from approximate reporting. Grant is associated with the labor movement, without an unsupported claim of union membership.

## James D. O'Neil

The April 30, 1941 AP dispatch in the *Medford Mail Tribune*, page 12, reports a sixty-day contempt sentence for nonappearance as a summoned witness in the Harry Bridges deportation hearing. It records O'Neil's illness explanation, the court's contrary view, and an order that custody continue despite a stay of execution. *Voice of the Federation*, May 3, page 8, confirms he was serving the term. His former CIO publicity role and the hearing context are verified; union assertions about a frame-up are not adopted as an adjudicated fact. The actual release date and full time served remain unresolved.

## Minneapolis WPA defendants

Twelve men in the February 8, 1940 *Northwest Organizer* sentence roster are missing: Leslie Wachter, Charles Grider, William Riley, George Toteno/Totino, Milton McLean, Eddie Alberts, Frank Stevens, Floyd Hurley, Myron Philips/Phillips, Richard Connell, Ralph Core and Charles Connors. Edward Palmquist and Max Geldman already exist.

The same page confirms actual county-jail confinement through the milk-delivery report. February 15, page 3, explicitly dates sentence service to February 3 and describes the transfer to Sandstone, with Core and Connors remaining for local custody. The group transfer counts do not agree across the reports, so no unnamed additional prisoner or individual institution assignment is inferred. In particular, the wording does not clearly assign Core versus Connors to the workhouse versus county jail. Individual charging-count details remain unresolved rather than applying one generic count to all.

The February 8 page image clearly gives **four months** for Milton McLean; OCR incorrectly renders one month. It gives **eight months plus eighteen months probation** for Leslie Wachter; the later Dobbs history gives one year and one day. The payload uses the attributed contemporary sentence and leaves actual completed duration unfilled. Palmquist and Geldman's analogous sentence conflict remains an existing-record research lead.

Minnie Kohn is the thirteenth missing WPA defendant added. The February 15 report, pages 1 and 3, records her February 10 sentencing and immediate workhouse entry for forty-five days. Max Geldman's recollection reproduced in Farrell Dobbs's *Militant* excerpt, May 9, 1975, page 15, explicitly says she served her time and came out. This establishes actual service; no release date is calculated from the sentence.

The other thirteen women received probation or suspended sentences. Ben Palmer, Oscar Schoenfeld, Carl Pemble and the five nolo-contendere defendants are not added on the basis of noncustodial dispositions. Victor Nicholas's individual sentence and actual entry remain a follow-up; the February 8 report still schedules his sentencing for later.

## Sources and verification

URLs and individual case evidence are in [batch305.json](../fixes/batch305.json); the names are also in the accompanying CSV. Relevant newspaper pages and court page 491 were visually reviewed; the full court opinion and AP dispatch were read in text. No full-volume reading is claimed. The ILWU-hosted 1941 newspaper's printed title is *Voice of the Federation*, not *The Dispatcher*.

- Live query-only preview: 17 additions, zero matched existing profiles. Identity and alias checks also cover the 8,853-record snapshot and prior pending payloads.
- 525 assertions passed with all writes confined to isolated SQLite memory, including replay, preservation, ambiguous aliases, date precision, rollback, and no invented release endpoints.
- Shell syntax passed; the single-quoted tinker body has zero ASCII apostrophes. Cache invalidation is included for eventual manual deployment.
- No verified vital dates or portraits were established in this pass. Personal support websites remain blank; citation links are not put in that field.

Continue queue 50, CORE, while retaining the wider CIO rosters and the held cases above. This was a bounded named-custody pass, not an exhaustive review of all CIO affiliates.
