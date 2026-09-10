# Affiliation research: Panther support campaigns and Boston Weathermen

September 10, 2026. Batch 293 proposes **three missing profiles, four cases, one birth year and one identified portrait**. The research follows George Jackson supporter Eric Mann into his earlier Boston proceedings. This does not classify the three people as Black Panther Party members or claim to exhaust Panther support campaigns.

| Proposed profile | Verified qualifying custody | Other supplied information |
| --- | --- | --- |
| Eric M. Mann | Boston English pretrial detention; separate Harvard CFIA prison term | Birth year 1942; USC portrait; CFIA sentence, entry date and release year |
| Henry A. Olson | Boston English pretrial detention | Appealed misdemeanor conviction and sentence |
| Philip C. Nies | Boston English pretrial detention | Appealed misdemeanor conviction and sentence |

## Evidence and chronology

- [The Crimson, October 30, 1969](https://www.thecrimson.com/article/1969/10/30/mann-weathermen-released-after-arrests-for/), names all three and describes a four-day Charles Street Jail stay. The detailed timeline distinguishes Friday's arrest and late-night release from Saturday's recommitment. The dates derived from the dated article are October 24 arrest, October 25 incarceration and October 29 release; the weekday calculation was checked. The intervening release is not erased.
- [The Crimson, November 8, 1969](https://www.thecrimson.com/article/1969/11/8/judge-convicts-mann-on-charge-of/), reports the previous day's misdemeanor assault-and-battery convictions and release on appeal bonds. The three-month sentences are **not recorded as time served**. The dangerous-weapon allegation did not proceed. The final appeal outcome is unresolved.
- [Kirkpatrick Sale, SDS, PDF page 422](https://www.sds-1960s.org/books/sds.pdf#page=422), situates Boston English within the Weatherman school confrontation/recruitment campaign before the Chicago National Action. Used as historical context, alongside contemporary individual custody reporting. These were violent political confrontations, not described here as peaceful civil disobedience.
- [The Crimson, January 26, 1970](https://www.thecrimson.com/article/1970/1/26/mann-gets-another-year-for-role/), establishes Mann's immediate prison entry on Friday, January 23, after a two-year sentence following the de novo trial. This is distinct from the earlier Boston English case. [March 26 reporting](https://www.thecrimson.com/article/1970/3/26/police-make-a-new-arrest-in/) confirms continued imprisonment and the CFIA raid's anti-imperialist context.
- [Mann v. Commonwealth, 359 Mass. 661](https://law.justia.com/cases/massachusetts/supreme-court/volumes/359/359mass661.html), decided June 15, 1971, supplies the final sentencing structure and appellate outcome. The additional assault sentences were suspended, with probation after the custodial term. The court upheld the increased sentence; the batch does not claim a judicial finding of retaliation. The unrelated November 1969 police-station shooting allegations are not used as Mann's conviction or prison sentence.
- [Mann's Comrade George (1972), printed pages 1 and 5](https://99books.freedomarchives.org/wp-content/uploads/2021/08/513.GeorgeJackson.ComradeGeorgeAnInvestigatonOfficialStory.pdf), is a first-person source for a year and a half actually served and the final nine months at Concord. His opening scene places him free in Berkeley after Jackson's death in August 1971. The batch retains 18 months as a source-stated recollection, not a day-exact measurement. The release field is **1971 only**; a secondary July claim remains unverified. The nine Concord months form part of this term, not an additional case or additional time.
- [Mann's 2014 autobiographical essay](https://www.counterpunch.org/2014/09/03/palestine-will-win/) supplies birth year **1942**, without month or day. Birth and release dates use the application's year precision. No exact birthday, death date or later death in custody is invented. The portrait and later organizing background come from the [USC interview report, printed page 78](https://dornsife.usc.edu/eri/wp-content/uploads/sites/41/2023/01/2012_LArising_full_report.pdf#page=79).

## Identity, geography and preservation

- Read-only inventory: **8,842 profiles**, including hidden records and aliases. All candidates were also screened against batches 269–292. No matches found. The runner repeats the complete identity check before applying and aborts ambiguous matches.
- The existing **Charles Street Jail** UUID is used for the three documented pretrial stays. The similarly named **Middlesex County jail** in the database is in **Virginia**, so it is not linked to Mann's Massachusetts term. Neither Concord District Court nor California's Concord Naval Weapons Station is his prison. The Massachusetts institutions remain in the sourced case narrative pending a verified institution record.
- Approximate Boston marker **42.36, -71.06**, supported by [U.S. DOT's city entry](https://www.itskrs.its.dot.gov/node/193165), denotes the historical case city, not a current residence or exact arrest site.
- Every existing matching identity is skipped entirely. Existing biographies, cases, photos, dates, affiliations and support websites are unchanged. Source URLs remain research citations, not personal-support websites.
- The runner validates reviewed identities, cases, dates, institutions and photo checksum; uses Eloquent in a transaction; and follows the existing API, museum and tracker cache-forget convention. No migration or production data write was used to prepare this batch.

## Held leads and next group

- **William Gordon** already exists from batch 266. His Los Angeles support-protest sentence is not new evidence of actual service. Preserve his existing profile.
- **G. Flint Taylor and Jeffrey Haas:** [Hampton v. Hanrahan](https://caselaw.findlaw.com/court/us-7th-circuit/118123832.html) and [The Militant, January 14, 1977](https://themilitant.com/1977/4101/MIL4101.pdf) establish only short contempt custody in the reviewed episodes: Taylor's reported five hours and Haas's overnight confinement. Do not substitute the trial's duration for time in jail.
- **James Reeves:** the November 8 report describes arrest in court and same-day release on November 7. A sentence alone does not meet the multi-day custody rule. Hold for stronger individual evidence.
- **Jill Wattenburg, Susan Hagedorn and Mark Doran:** CFIA fines or short arrest reports do not establish a served multi-day term. Do not assign Mann's imprisonment to them. Other November police-station defendants need individual detention and disposition research.
- Olson/Nies vital dates and portraits, the Boston English appeals and Mann's exact release remain open. The UMass archive and Strategy Center pages returned human-verification screens; those restrictions were not bypassed. Cornell's public PDF download failed; the batch instead uses Mann's accessible original account for release evidence.
- Continue with **Black Power movement**, then the remaining queue. No claim of exhaustive chapter or supporter coverage.

## Validation and deployment

**196 assertions passed** using the application's actual models and SQLite `:memory:` after purging all production connections, with memory file/storage/cache doubles. Checks cover distinct episodes, year-only output, the four-day stays, source-stated 18 months, existing/hidden identity preservation, repeat-run idempotence, ambiguous aliases, wrong institutions, unreviewed dates and portraits, and rollback on storage failures. Shell syntax and JSON checks passed. Final query-only live preview confirmed **three missing identities and zero existing matches**; deployment remains manual.

After merge and earlier pending batches:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-293.sh
```
