# 1950s missing-fields review: batch 425

Prepared October 12, 2026. Seven existing rows receive ten missing information fields: four portraits, two vital years, one arrest date, two post-verdict custody starts, and one criminal-contempt sentencing date. Payload `fixes/batch425.json` records source URLs, identity and empty-value guards, precision, and image hashes. `photos/CREDITS-batch425.md` records the image provenance and exact crop rectangles.

| Profile | Missing fields filled | Verification |
| --- | --- | --- |
| Barbara Hartle | Portrait | University of Washington caption explicitly identifies the woman on the left speaking to HUAC in 1954. |
| Barrows Dunham | Portrait | Photographer Bud Schultz identifies Dunham in his own collection. Exposure date unspecified; 2012 Flickr metadata is not used as a historical exposure date. |
| Henry P. Huff | Portrait; post-verdict incarceration October 10, 1953 | University headshot explicitly identified as Huff. ILWU Dispatcher October 16, 1953, printed page 7 names him among the five convicted and jailed on October 10. |
| Paul Bowen | Portrait; birth year 1922; death year 2008; arrest September 17, 1952; post-verdict incarceration October 10, 1953 | Jet October 2, 1952 page 10 identifies the pictured Paul Miller Bowen, arrested in Chicago. University oral history explicitly gives both vital years. Same-day United Press arrest report in Medford Mail Tribune and the ILWU remand report establish the two case dates. |
| Terry Pettus | Criminal-contempt sentencing October 16, 1953 | November 27, 1953 United States Attorneys Bulletin page 8 and Daschbach v. United States, 254 F.2d 687, paragraphs 18-19 explicitly date the later contempt sentencing. |

Only missing fields are filled. All existing biographies, dates, prose, case outcomes, durations, custody flags, sort order, and portraits remain unchanged. Missing dates use exact day precision where verified; Bowen's vital dates use year precision, with January 1 solely as the existing storage convention. No release date or served five-year term is inferred. The two new incarceration dates describe documented post-verdict remand, not the earlier arrest or an assumed start of a full prison sentence. Pettus's existing case describes earlier civil confinement and the later criminal sentence; its civil-custody endpoints and 52-day duration remain unchanged.

## Unresolved conflicts and further research

- The Seattle federal trial ended October 10, 1953 according to the appellate court. ILWU contemporaneous reporting agrees, while National Guardian October 19 reports October 9. Existing profiles and Hartle's populated sentencing date contain October 9. They are preserved within this missing-only scope. October 9 was the civil-contempt discharge and criminal-contempt adjudication day; the later contempt sentencing was October 16.
- Bowen's biography contains contradictory acquittal and conviction claims, his middle initial and race conflict with identified primary sources, and two existing cases appear to describe the same Smith Act prosecution with conflicting outcomes. Dates are filled only on the existing conviction case `55c6b3fe-2990-4cea-940d-7833d87726aa`. The other case and all populated profile fields remain unchanged. This conflict is recorded for a separate correction; no case is deleted or silently rewritten.
- Neither Bowen nor Huff has a verified appeal-bail release day. The criminal-contempt court's authorization to post bail is not proof of actual release. No counter total is manufactured.
- Hartle's exact prison-entry day, vital dates, and potentially matching Washington burial identity remain unresolved. Twenty and one-half months is not converted into a calculated admission date.
- A group photograph caption names six Seattle defendants but does not explicitly establish left-to-right identification. No new John Daschbach portrait is assigned from an assumed ordering. Independent labeled headshots are used for Huff and Bowen.
- Terry Pettus's separate Smith Act sentencing day remains blank pending an explicit source for that particular sentence. The October 16 contempt sentence is supported directly.
- Barrows Dunham's acquittal and existing statement that he was not jailed remain intact. A missing portrait does not establish custody or justify adding custody dates.
- A. Robert Kaufman's University of Baltimore collection remains an archival photograph lead. The similarly named Beat poet is excluded. Calvert Institute's textual reference to a photo does not supply an accessible picture.
- Benjamin Lowell Careathers's exact birth day remains uncorroborated. A genealogical October 20, 1888 date is not filled; a historical account reports that he did not know the exact date. His 1953 defense pamphlet and Masses & Mainstream interview remain source leads.
- Bernadette Doyle: unrelated Canadian and nun namesakes are excluded; existing portrait preserved.
- Andrew Peter Yiannopoulos is not conflated with legal scholar Athanasios N. Yiannopoulos. Deportation proceedings alone do not supply custody endpoints.
- Andrew Fortuna and Arlie Pate: modern namesake obituaries and offender records are excluded. Identified Korean War-era photographs and vital records remain unresolved.

The full 1950s audit remains in progress. Deployment and preservation checks are recorded separately after application; this document does not claim every candidate has been researched.
