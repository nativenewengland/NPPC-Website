# Batch 329: additional military conscientious resisters

Four new profiles, four custody cases and one identified portrait. Batches 326–329 accumulate 22/100 new identities, 33 custody segments, 10 photos, one birth year and two death years. No new PR or deployment.

| Person | Verified custody and date precision |
|---|---|
| Joel Klemkewicz | Church publication confirms four months actually imprisoned; December 2004 sentencing from resistance archive; custody endpoints withheld. |
| Katherine Jashinski | May 2006 military-prison entry; May 23 sentencing and July 9, 2006 release. Earlier restriction not turned into a guessed prison interval. |
| Ivan Brobeck | 2006 imprisonment and February 2007 release; December 5, 2006 trial. Exact served days withheld. |
| Ryan Jackson | Charleston Naval Brig pretrial custody from April 14, 2008; May 30 trial, release in 2008 at year precision. |

## Evidence and uncertainty

- Klemkewicz: his church describes Sabbath-work refusal and four months served; the resistance archive describes refusal to carry a gun, willingness to deploy unarmed, a seven-month sentence and later discharge upgrade. These are not necessarily contradictory but the charging document has not been located. No armed-duty statute, precise confinement dates, or prison location is inferred. Documented four months populates the existing months field; the model uses its established average-month value only internally. No claim of an exact 122-day historical term is made. Heading Klemkewicz and photo-caption Klenkewicz are screened as aliases. No birth/death dates verified.
- Jashinski: direct brig and post-release interviews establish Afghanistan, despite the newsletter mistakenly naming Iraq. The court acquitted missing movement and convicted disobedience. Pretrial credits and the remaining Miramar period belong to the same sentence. The precise day of prison arrival is unverified, so May 23 is only the sentencing date. Conflicting reported ages do not establish a birthday. Her named article photograph has a broken original URL; nothing is assigned from it.
- Brobeck: Ann Wright places his surrender in November 2006, court-martial December 5, and release in February 2007. The roster has an impossible November 2007 return before February 2007 release. Eight- versus ten-month sentences and different discharge descriptions appear in summaries; the entry attributes the eight-month report and withholds an exact served total. February 6 and 63 days remain unverified derivative claims. Exact November commitment is also unresolved; imprisonment is stored at year precision.
- Jackson: April 4 surrender in Oklahoma and April 14 return/confinement in Georgia are separate events. Charleston imprisonment is documented before the trial. Contemporary reports identify unauthorized absence, while derivative tables say desertion. His 100-day sentence included pretrial credit. Jamail's interview-based retrospective confirms release, but a precise release day is not calculated from its remaining-days statement. The contemporary late-June estimate alone would not establish actual release. His own quoted affiliation statement supports IVAW participation. Athlete namesakes are excluded.

US-authority military custody qualifies under the user's scope. Existing biographies, cases, photos and populated fields are preserved wholesale. No unsupported institutional links, coordinates or support websites are assigned.

## Duplicate identities caught before publication

Two initially researched names already exist and are excluded from this batch and its new-person count:

- **Tony Anderson = Anthony Michael Anderson**, profile ID `11c2dc95-89f5-4122-9d8f-730623b48f99`, slug `anthony-michael-anderson`. Lazare's trial report confirms November 17, 2008 sentencing/removal under guard. September 2009 notice gives the expanded name and Fort Sill custody. A Courage to Resist announcement reproduced October 22 already reports freedom, superseding the prospective November release. Precise release day and earlier pretrial custody remain unresolved. Keep these findings for a guarded missing-field review, not a second prisoner.
- **Clifford Cornell = Cliff Cornell**, profile ID `d37c4701-5b22-480e-be0a-5203eeeee2d0`, slug `cliff-cornell`. WRI and contemporary reporting establish April 28, 2009 sentencing; April 29 is one article's publication date. Gerry Condon's legal-support newsletter confirms actual Camp Lejeune custody. The release interview establishes January 16, 2010 only; do not infer eleven months actually served from sentence reduction. Existing fields need inspection before any proposed append or correction.

The new records were screened against the 8,957-profile snapshot, canonical/alias token variants and pending/submitted batches 297–328. The fresh query-only preview independently confirms all four are absent.

## Validation

202 assertions passed using the application's PHP runtime and models with production connections removed, an isolated SQLite memory database, and mocked filesystem/storage/cache writes. Checks cover insertion, replay, preservation of existing profiles/cases/photos, hidden aliases and ambiguity, date precision, documented months, no invented current custody, invalid-input rollback, corrupt portrait bytes, storage failure, and cache invalidation. The live preview forced dry-run mode and SQLite query-only mode. Shell syntax and whitespace checks pass. Production has not changed.

## Sources

- [Northern Asia-Pacific Division News & Views, May–June 2010, p.16, Pastor Joel Klemkewicz in Okinawa International Church; original PDF page and identified photograph reviewed.](https://documents.adventistarchives.org/Periodicals/NSDNV/NSD-NV-2010-05-06.pdf)
- [Courage to Resist, Profiles in Resistance, Klemkewicz and Brobeck entries. Roster used cautiously: identified date conflicts are not copied.](https://couragetoresist.org/profiles-in-resistance/)
- [Chris Lombardi, Women’s eNews, August 7, 2006, Conscientious Objector Re-Engineers Her Life; interview in Miramar brig and after release.](https://womensenews.org/2006/08/conscientious-objector-re-engineers-her-life/)
- [Nuclear Resister 142, July 24, 2006, p.1; sentencing May 23 and actual release July 9. The article incorrectly names Iraq; the direct interview establishes Afghanistan.](https://www.nukeresister.org/wp-content/uploads/resister/nr142.pdf)
- [Sarah Lazare, ZNetwork, November 22, 2008, Tony Anderson; trial statements and removal under guard following November 17 court-martial.](https://znetwork.org/znetarticle/tony-anderson-by-sarah-lazare/)
- [Courage to Resist prison-support notice reproduced September 10, 2009 by Adopt Resistance; Anthony Michael Anderson/Tony Anderson in Fort Sill custody. November release was an estimate.](https://adoptresistance.blogspot.com/2009/09/)
- [Courage to Resist release announcement quoted October 22, 2009 by The Common Ills; public original HTML retrieved. Freedom confirmed, exact release day not stated.](https://thecommonills.blogspot.com/2009/10/those-amazing-and-wonderful-iraqi.html?m=0)
- [Ann Wright, Another US War Resister From Canada Court-Martialed, August 24, 2008; November 2006 surrender, December 5 trial, eight-month sentence and February 2007 actual release.](https://www.commondreams.org/views/2008/08/24/another-us-war-resister-canada-court-martialed)
- [Travis Lupick, Georgia Straight, April 29, 2009; court-martial occurred Tuesday April 28, quotes War Resisters Support Campaign and attorney James Branum.](https://www.straight.com/article-216523/cliff-cornell-sentenced)
- [War Resisters’ International April 30, 2009 release, German translation by Rudi Friedrich, Connection e.V.; explicitly dates sentencing April 28.](https://de.connection-ev.org/article-746)
- [Gerry Condon, On Watch, National Lawyers Guild Military Law Task Force, Summer 2009, p.2; confirms Cornell serving at Camp Lejeune after desertion conviction.](https://nlgmltf.org/downloads/onwatch/Onwatch_xx-4.pdf)
- [Canwest News Service, Global News, January 17, 2010; interviewed Cornell several hours after his Saturday release. Used solely to establish January 16, 2010 release.](https://globalnews.ca/news/83163/u-s-army-deserter-who-fled-to-canada-freed-from-prison/)
- [Courage to Resist, Free Ryan Jackson now, May 28, 2008 update; Charleston Naval Brig pretrial custody since April 14.](https://couragetoresist.org/free-ryan-jackson/)
- [Nuclear Resister 149, July 3, 2008, p.2 and prison letter; May 30 unauthorized-absence conviction and 100-day sentence. June release was prospective.](https://www.nukeresister.org/wp-content/uploads/resister/nr149.pdf)
- [Dahr Jamail, The Will to Resist (Haymarket, 2009), pp.95–96; interview-based account and retrospective confirmation of release after credited pretrial confinement.](https://dokumen.pub/the-will-to-resist-soldiers-who-refuse-to-fight-in-iraq-and-afghanistan-9781608460755-9781931859882.html)
- [Workers World, May 29, 2008, p.2, GIs, vet resisters take lead; quotes Jackson identifying his participation in IVAW and Courage to Resist.](https://www.workers.org/pdf/2008/21ww29May2008.pdf)

## Next leads, queue 86

Continue Eric Jasinski and Daniel Sandate before leaving this movement. Their actual custody is documented, but establish the antiwar/conscientious connection carefully rather than relying on illness-related absence alone. For Jasinski, the April 20, 2010 jail letter and Alice Embree report establish continued confinement; April 24 is still prospective in that report. For Sandate, follow his own March 22, 2009 statement on Iraq and Kimberly Rivera, attorney James Branum's JMBzine archive, the January 2009 release coverage and the July 16, 2008 CBP arrest release. Preserve Sandante as an alias lead. Leo Church remains held: family-care absence alone does not demonstrate activism.

Also screen Abdullah William Webster and later US-custody developments for Ryan Johnson, Kimberly Rivera and other Canada-based refusers; do not equate Canadian exile or threatened prosecution with served US jail time. Already-known existing identities and batches 328–329 must be excluded. Then move to Irish Republican Army (queue 87), preserving unresolved leads in this audit.

The Nuclear Resister PDFs were available as indexed text through the web tool, but direct downloads returned 404 and web screenshots failed; no original-page visual review is claimed for those two issues. The church and NLG PDFs were retrieved successfully. Portrait credit details are in `database/data/photos/CREDITS-batch329.md`.
