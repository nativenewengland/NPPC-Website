# Verified case dates — batch 265

Research and read-only database snapshot: September 9, 2026.

This batch reconciles 27 existing case rows belonging to 14 existing prisoner profiles. It corrects or refines 66 populated date fields and fills 21 missing date fields (87 total) and corrects eight date-related phrases in case sentence text. It changes no prisoner rows or biographies, photos, websites, charges, institutions, custody flags, case relationships, or documented months. Duplicate cases are retained. The separate overlapping-period counter continues to count shared custody once.

Every proposed date has field-level source references in `fixes/batch265.json`. That file also contains each complete original case row, allowing reviewers to inspect the original facts and the deployment script to reject stale research. This is a researched subset of the earlier overlap audit, not a claim that all conflicting dates in the database have been resolved.

## Reconciled chronology

- **MOVE 9:** arrest and custody began August 8, 1978. Conviction was May 8, 1980; sentencing was August 4, 1981. The latter is confirmed by a court opinion and a contemporary report naming all nine defendants. Sentencing does not mark the beginning of their continuous detention. Thirteen existing rows are reconciled, including missing sentencing fields.
- **Eddie Goodman Africa:** all three case endpoints become June 21, 2019. Existing rows alternately say June 20, 2019 and January 31, 2020. MOVE's direct release announcement resolves the disagreement. The conflicting conviction year in one case's sentence text is also corrected.
- **Other MOVE endpoints:** Charles/Chuck Sims Africa, February 7, 2020; Debbie Africa, June 16, 2018; Delbert Africa, January 18, 2020; Janet Holloway Africa and Janine Phillips Africa, May 25, 2019; Michael Davis Africa, October 23, 2018. Debbie's month-only entry is upgraded to the verified day. Charles's incorrect January 2019 date in case sentence text is corrected too.
- **Deaths in custody:** Merle Africa, March 13, 1998; William/Phil Africa, January 10, 2015; Thomas Manning, July 30, 2019. The schema uses `release_date` as a custody endpoint even for deaths; these rows therefore also explicitly record the matching `death_in_custody_date`. These are not described as releases from prison.
- **Y-12 / Transform Now Plowshares:** Megan Rice, Michael Walli and Greg Boertje-Obed were arrested July 28, 2012; the long post-conviction custody period began May 8, 2013; sentencing occurred February 18, 2014; physical release occurred May 16, 2015. Eight existing Y-12 rows are reconciled. The May 8, 2015 appeal decision and May 15 release order are not physical release dates. Five case-text corrections remove the May 9 conviction error and the conflation of appeal and release. Greg's separate Andrews Air Force Base case is untouched.
- **Thomas Manning:** April 24, 1985 arrest is confirmed in the court record. Three conflicting arrest fields and one matching detention-start error are corrected. July 30, 2019 death is independently confirmed by the New Jersey State Police colonel's report of receiving BOP notification. All five associated rows now agree on that custody endpoint; one false date in case sentence text is corrected.
- **Fernando González Llort:** the sparse February 26, 2014 endpoint is corrected to February 27, matching the two other rows and the contemporary support committee account. This is departure from federal prison, followed by immigration detention until his return to Cuba February 28; the two events must not be conflated.

## Preserved questions requiring further work

- Manning's rows mix state and federal proceedings and carry different sentencing dates, sentence lengths and institutions. This batch does not choose one sentencing date for different proceedings, fill his missing custody starts, or change the existing federal sentence-start field without docket-specific evidence.
- The Y-12 long custody period is May 2013–May 2015. Brief detention immediately after the 2012 arrest is a separate episode; its release endpoints have not been established here. These corrected long-period counters should not be represented as a complete lifetime detention total. No fictitious continuous July 2012–May 2015 imprisonment is retained.
- Existing biographies are intentionally untouched even if they contain a conflicting date. Other unrelated inaccuracies in case narrative or institutional assignment require their own sourced correction.
- Other profiles from the earlier overlap audit remain outside this batch. Overlap alone does not establish a wrong date; concurrent sentences and separate prosecutions can legitimately overlap.

## Deployment and safeguards

After merging, pull main and apply any earlier pending data batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-265.sh --dry-run
sudo -u www-data bash database/data/run-batch-265.sh
```

Only run the second command for the batch after the dry run succeeds. PsySH uses writable application storage, avoiding the earlier `/var/www/.config/psysh` error.

The runner validates case UUID, prisoner UUID/name/slug, review status, field allowlists, calendar dates, source references and expected counts. Every changed row must match its complete researched snapshot. All plans are checked before any write, and guarded updates execute in one transaction. A newer edit aborts the whole batch; it is never silently replaced. Already-correct rows are preserved, including their timestamps. Only exact, expected date phrases in case sentence text can be replaced.

Corrected custody endpoints recalculate `imprisoned_for_days` using the application method. Model saving hooks are bypassed to prevent inferred exile or profile mutations. Successful application or replay invalidates the API, museum and current-year tracker caches. Dry run does not write data or invalidate caches. No data migration or direct production write was performed during preparation.

## Validation

- Shell and PHP syntax checks passed.
- `check-batch265.php` passed **630 assertions** in disposable in-memory SQLite with an in-memory cache using the installed Laravel runtime. Checks cover every changed date, independent duration arithmetic, preservation of unrelated fields and profiles, replay, dry-run behavior, cache invalidation and atomic rejection of 12 failure scenarios, including a failure on the last write.
- A fresh live preview ran with SQLite `PRAGMA query_only = ON`: **27 cases, 87 date fields, eight case date phrases**. Complete before/after snapshots of all 14 profiles and their cases were identical.
- No live batch was applied. The website changes only after manual deployment above.

## Field-level changes and evidence

The following table is generated from the reviewed payload. A missing value is shown as “missing”; explicitly partial dates include their precision. Source numbers link to the evidence registry below. The JSON retains full original prose and exact guarded replacements.

| Person | Case UUID | Field | Before | Verified date | Sources |
| --- | --- | --- | --- | --- | --- |
| Charles Africa | 3aa76515-7485-42f8-82fe-1e6281d3076b | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Charles Africa | 3aa76515-7485-42f8-82fe-1e6281d3076b | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| Charles Africa | 3aa76515-7485-42f8-82fe-1e6281d3076b | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Charles Africa | 3aa76515-7485-42f8-82fe-1e6281d3076b | `release_date` | 2020-02-06 | 2020-02-07 | [5](#source-5) |
| Charles Africa | 0f49f477-cd70-4e63-9c98-c2444deb3193 | `incarceration_date` | 1980-08-04 | 1978-08-08 | [1](#source-1) |
| Charles Africa | 0f49f477-cd70-4e63-9c98-c2444deb3193 | `sentenced_date` | 1980-08-04 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Charles Africa | 0f49f477-cd70-4e63-9c98-c2444deb3193 | `release_date` | 2019-01-08 | 2020-02-07 | [5](#source-5) |
| Debbie Africa | 173dcccf-defd-4dda-970b-e2be6dd6a644 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Debbie Africa | 173dcccf-defd-4dda-970b-e2be6dd6a644 | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| Debbie Africa | 173dcccf-defd-4dda-970b-e2be6dd6a644 | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Debbie Africa | 173dcccf-defd-4dda-970b-e2be6dd6a644 | `release_date` | 2018-06-15 | 2018-06-16 | [6](#source-6) |
| Debbie Africa | 30123096-f0fa-437a-b70f-276816790543 | `sentenced_date` | missing | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Debbie Africa | 30123096-f0fa-437a-b70f-276816790543 | `release_date` | 2018-06-01 (month) | 2018-06-16 | [6](#source-6) |
| Delbert Africa | dbe6ad28-10bc-4b0c-a61a-281324be3da2 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Delbert Africa | dbe6ad28-10bc-4b0c-a61a-281324be3da2 | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| Delbert Africa | dbe6ad28-10bc-4b0c-a61a-281324be3da2 | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Delbert Africa | dbe6ad28-10bc-4b0c-a61a-281324be3da2 | `release_date` | 2020-01-17 | 2020-01-18 | [7](#source-7) |
| Eddie Goodman Africa | 3244d2de-aef3-4825-8f1f-2dc42342a609 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Eddie Goodman Africa | 3244d2de-aef3-4825-8f1f-2dc42342a609 | `incarceration_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Eddie Goodman Africa | 3244d2de-aef3-4825-8f1f-2dc42342a609 | `sentenced_date` | missing | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Eddie Goodman Africa | 3244d2de-aef3-4825-8f1f-2dc42342a609 | `release_date` | 2019-06-20 | 2019-06-21 | [4](#source-4) |
| Eddie Goodman Africa | ff8115cc-a00d-48c0-9c01-e24e89c7788a | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Eddie Goodman Africa | ff8115cc-a00d-48c0-9c01-e24e89c7788a | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| Eddie Goodman Africa | ff8115cc-a00d-48c0-9c01-e24e89c7788a | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Eddie Goodman Africa | ff8115cc-a00d-48c0-9c01-e24e89c7788a | `release_date` | 2020-01-31 | 2019-06-21 | [4](#source-4) |
| Eddie Goodman Africa | 77d2fc91-2312-43b7-a33e-af1e837d4f06 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Eddie Goodman Africa | 77d2fc91-2312-43b7-a33e-af1e837d4f06 | `incarceration_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Eddie Goodman Africa | 77d2fc91-2312-43b7-a33e-af1e837d4f06 | `sentenced_date` | missing | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Eddie Goodman Africa | 77d2fc91-2312-43b7-a33e-af1e837d4f06 | `release_date` | 2019-06-20 | 2019-06-21 | [4](#source-4) |
| Fernando González Llort | 713f6539-3fa0-4196-9a52-10f2171c409c | `release_date` | 2014-02-26 | 2014-02-27 | [17](#source-17) |
| Greg Boertje-Obed | 0534d1b9-efd7-4870-b228-6062ea5364ed | `incarceration_date` | 2014-02-08 | 2013-05-08 | [11](#source-11) |
| Greg Boertje-Obed | 0534d1b9-efd7-4870-b228-6062ea5364ed | `sentenced_date` | missing | 2014-02-18 | [12](#source-12) |
| Greg Boertje-Obed | 3f89413e-5598-47cf-b018-8650a623fa47 | `arrest_date` | 2012-07-27 | 2012-07-28 | [11](#source-11) |
| Greg Boertje-Obed | 3f89413e-5598-47cf-b018-8650a623fa47 | `incarceration_date` | 2014-02-17 | 2013-05-08 | [11](#source-11) |
| Greg Boertje-Obed | 3f89413e-5598-47cf-b018-8650a623fa47 | `sentenced_date` | 2014-02-17 | 2014-02-18 | [12](#source-12) |
| Greg Boertje-Obed | 3f89413e-5598-47cf-b018-8650a623fa47 | `release_date` | 2015-05-15 | 2015-05-16 | [13](#source-13) |
| Greg Boertje-Obed | 171ed183-f101-4359-8f29-0b6ec0fd27d8 | `incarceration_date` | missing | 2013-05-08 | [11](#source-11) |
| Greg Boertje-Obed | 171ed183-f101-4359-8f29-0b6ec0fd27d8 | `sentenced_date` | missing | 2014-02-18 | [12](#source-12) |
| Greg Boertje-Obed | 171ed183-f101-4359-8f29-0b6ec0fd27d8 | `release_date` | missing | 2015-05-16 | [13](#source-13) |
| Janet Holloway Africa | 83e095a8-9dc1-4cc2-a592-898f84d759a9 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Janet Holloway Africa | 83e095a8-9dc1-4cc2-a592-898f84d759a9 | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| Janet Holloway Africa | 83e095a8-9dc1-4cc2-a592-898f84d759a9 | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Janet Holloway Africa | 83e095a8-9dc1-4cc2-a592-898f84d759a9 | `release_date` | 2019-05-24 | 2019-05-25 | [8](#source-8) |
| Janine Phillips Africa | 09ae4df9-7c7a-4fd2-8da6-68fa421f9ad3 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Janine Phillips Africa | 09ae4df9-7c7a-4fd2-8da6-68fa421f9ad3 | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| Janine Phillips Africa | 09ae4df9-7c7a-4fd2-8da6-68fa421f9ad3 | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Janine Phillips Africa | 09ae4df9-7c7a-4fd2-8da6-68fa421f9ad3 | `release_date` | 2019-05-24 | 2019-05-25 | [8](#source-8) |
| Megan Rice | 47488521-b54f-46ff-91b9-93916b4130a7 | `incarceration_date` | 2014-02-08 | 2013-05-08 | [11](#source-11) |
| Megan Rice | 47488521-b54f-46ff-91b9-93916b4130a7 | `sentenced_date` | missing | 2014-02-18 | [12](#source-12) |
| Megan Rice | 67d3bad3-f99d-4db2-95a8-6c2513115f0f | `arrest_date` | 2012-07-27 | 2012-07-28 | [11](#source-11) |
| Megan Rice | 67d3bad3-f99d-4db2-95a8-6c2513115f0f | `incarceration_date` | 2012-07-27 | 2013-05-08 | [11](#source-11) |
| Megan Rice | 67d3bad3-f99d-4db2-95a8-6c2513115f0f | `sentenced_date` | missing | 2014-02-18 | [12](#source-12) |
| Megan Rice | 67d3bad3-f99d-4db2-95a8-6c2513115f0f | `release_date` | 2015-05-07 | 2015-05-16 | [13](#source-13) |
| Merle Africa | efbb6c47-f5c6-44f9-bd42-7d69d98e97df | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Merle Africa | efbb6c47-f5c6-44f9-bd42-7d69d98e97df | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| Merle Africa | efbb6c47-f5c6-44f9-bd42-7d69d98e97df | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Merle Africa | efbb6c47-f5c6-44f9-bd42-7d69d98e97df | `release_date` | 1998-03-12 | 1998-03-13 | [9](#source-9) |
| Merle Africa | efbb6c47-f5c6-44f9-bd42-7d69d98e97df | `death_in_custody_date` | missing | 1998-03-13 | [9](#source-9) |
| Michael Davis Africa | aafdf71a-fbc2-482d-98ff-88cbf4fff242 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Michael Davis Africa | aafdf71a-fbc2-482d-98ff-88cbf4fff242 | `incarceration_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| Michael Davis Africa | aafdf71a-fbc2-482d-98ff-88cbf4fff242 | `sentenced_date` | missing | 1981-08-04 | [2](#source-2), [3](#source-3) |
| Michael Davis Africa | aafdf71a-fbc2-482d-98ff-88cbf4fff242 | `release_date` | 2018-10-22 | 2018-10-23 | [1](#source-1) |
| Michael Walli | 2c503526-b99b-4d79-a4ed-6e38f3a1f19e | `incarceration_date` | 2014-02-08 | 2013-05-08 | [11](#source-11) |
| Michael Walli | 2c503526-b99b-4d79-a4ed-6e38f3a1f19e | `sentenced_date` | missing | 2014-02-18 | [12](#source-12) |
| Michael Walli | 66bc96a0-a695-4e84-98d7-6b3f8f9574ed | `arrest_date` | 2012-07-27 | 2012-07-28 | [11](#source-11) |
| Michael Walli | 66bc96a0-a695-4e84-98d7-6b3f8f9574ed | `incarceration_date` | 2012-07-27 | 2013-05-08 | [11](#source-11) |
| Michael Walli | 66bc96a0-a695-4e84-98d7-6b3f8f9574ed | `sentenced_date` | missing | 2014-02-18 | [12](#source-12) |
| Michael Walli | 66bc96a0-a695-4e84-98d7-6b3f8f9574ed | `release_date` | 2015-05-07 | 2015-05-16 | [13](#source-13) |
| Michael Walli | 0dff6aa5-e1d7-470f-aa43-ac633cde7c05 | `incarceration_date` | missing | 2013-05-08 | [11](#source-11) |
| Michael Walli | 0dff6aa5-e1d7-470f-aa43-ac633cde7c05 | `sentenced_date` | missing | 2014-02-18 | [12](#source-12) |
| Michael Walli | 0dff6aa5-e1d7-470f-aa43-ac633cde7c05 | `release_date` | missing | 2015-05-16 | [13](#source-13) |
| Thomas Manning | be200589-3c89-4bb8-a829-2163af615e9e | `arrest_date` | 1985-04-23 | 1985-04-24 | [15](#source-15) |
| Thomas Manning | be200589-3c89-4bb8-a829-2163af615e9e | `release_date` | missing | 2019-07-30 | [16](#source-16) |
| Thomas Manning | be200589-3c89-4bb8-a829-2163af615e9e | `death_in_custody_date` | missing | 2019-07-30 | [16](#source-16) |
| Thomas Manning | 8e8a1a16-0989-4aef-9ed7-0ce43ba6ad58 | `arrest_date` | 1985-04-23 | 1985-04-24 | [15](#source-15) |
| Thomas Manning | 8e8a1a16-0989-4aef-9ed7-0ce43ba6ad58 | `incarceration_date` | 1985-04-23 | 1985-04-24 | [15](#source-15) |
| Thomas Manning | 8e8a1a16-0989-4aef-9ed7-0ce43ba6ad58 | `release_date` | 2019-07-29 | 2019-07-30 | [16](#source-16) |
| Thomas Manning | 8e8a1a16-0989-4aef-9ed7-0ce43ba6ad58 | `death_in_custody_date` | missing | 2019-07-30 | [16](#source-16) |
| Thomas Manning | be03e3ee-045a-4a07-b396-2b9aa6db4ca9 | `arrest_date` | 1985-04-25 | 1985-04-24 | [15](#source-15) |
| Thomas Manning | 8798f656-3f0c-4e55-aabd-80e32f6ab103 | `death_in_custody_date` | missing | 2019-07-30 | [16](#source-16) |
| Thomas Manning | 05604135-daeb-4902-b7ec-39152bb03f17 | `release_date` | 2019-07-29 | 2019-07-30 | [16](#source-16) |
| Thomas Manning | 05604135-daeb-4902-b7ec-39152bb03f17 | `death_in_custody_date` | 2019-07-29 | 2019-07-30 | [16](#source-16) |
| William Phillips Africa | cd1096c7-6d3f-4c61-b0c7-8f68e5cfa8c1 | `arrest_date` | 1978-08-07 | 1978-08-08 | [1](#source-1) |
| William Phillips Africa | cd1096c7-6d3f-4c61-b0c7-8f68e5cfa8c1 | `incarceration_date` | 1981-03-31 | 1978-08-08 | [1](#source-1) |
| William Phillips Africa | cd1096c7-6d3f-4c61-b0c7-8f68e5cfa8c1 | `sentenced_date` | 1981-03-31 | 1981-08-04 | [2](#source-2), [3](#source-3) |
| William Phillips Africa | cd1096c7-6d3f-4c61-b0c7-8f68e5cfa8c1 | `release_date` | 2015-01-09 | 2015-01-10 | [10](#source-10) |
| William Phillips Africa | cd1096c7-6d3f-4c61-b0c7-8f68e5cfa8c1 | `death_in_custody_date` | missing | 2015-01-10 | [10](#source-10) |

## Evidence registry

### Source 1

[MOVE counsel: Mike Africa released, October 23, 2018](https://abolitionistlawcenter.org/2018/10/23/media-release-move-9-member-mike-africa-released-on-parole-after-40-years-in-prison/). Identifies the MOVE 9 prosecution and imprisonment beginning August 8, 1978; confirms Mike left prison October 23, 2018.

### Source 2

[Revolutionary Worker, August 14, 1981, page 8](https://www.bannedthought.net/USA/RCP/RW/1981/RW117-English-OCR-sm.pdf). Contemporary report names all nine defendants and dates their sentencing August 4, 1981.

### Source 3

[Commonwealth v. Davis, 810 EDA 2013, pages 1-2](https://law.justia.com/cases/pennsylvania/superior-court/2013/810-eda-2013.html). Court records May 8, 1980 conviction and August 4, 1981 sentencing in the joint MOVE prosecution.

### Source 4

[MOVE: Eddie Africa Freed on Parole](https://onamove.com/eddie-africa-paroled-fri-june-21st/). Direct release announcement confirms June 21, 2019.

### Source 5

[Chuck Sims Africa freed, February 7, 2020](https://www.theguardian.com/us-news/2020/feb/07/chuck-sims-africa-move-9-freed-philadelphia). Release: February 7, 2020.

### Source 6

[MOVE: Debbie Africa](https://onamove.com/move-9/debbie-africa/). Direct announcement confirms release June 16, 2018.

### Source 7

[Delbert Orr Africa freed, January 18, 2020](https://www.theguardian.com/us-news/2020/jan/18/move-9-delbert-orr-africa-released-prison). Release: January 18, 2020.

### Source 8

[MOVE counsel: Janet and Janine Africa released, May 25, 2019](https://abolitionistlawcenter.org/2019/05/25/media-release-janet-and-janine-africa-are-paroled-after-forty-years-of-incarceration/). Counsel confirms both left SCI Cambridge Springs that morning, May 25. Parole approval May 14 was an earlier event.

### Source 9

[MOVE: Merle Africa](https://onamove.com/move-9/merle-africa/). Death in custody March 13, 1998. Used for the date only; competing institution descriptions are outside this correction.

### Source 10

[MOVE: Phil Africa](https://onamove.com/move-9/phil-africa/). Self-written account places imprisonment at August 8, 1978. Editorial notice dates death in custody January 10, 2015.

### Source 11

[The Nuclear Resister 170, June 5, 2013, page 1](https://www.nukeresister.org/wp-content/uploads/2013/06/NR170web.pdf). Trial reports identify all three, July 28, 2012 arrest and May 8, 2013 conviction/remand. The long custody period starts at remand, not arrest or sentencing.

### Source 12

[U.S. Attorney, Eastern District of Tennessee: Y-12 sentencing](https://www.justice.gov/usao-edtn/pr/three-individuals-convicted-sabotage-y-12-national-security-complex-sentenced). February 19, 2014 announcement explicitly dates sentencing February 18, 2014 for all three.

### Source 13

[The Nuclear Resister: Freedom for the Transform Now Plowshares three](https://www.nukeresister.org/2015/05/16/freedom-for-sr-megan-rice-michael-walli-greg-boertje-obed/). Contemporaneous announcement confirms all three physically left prison May 16, 2015.

### Source 14

[United States v. Walli, Sixth Circuit, May 8, 2015](https://cases.justia.com/federal/appellate-courts/ca6/14-5220/14-5220-2015-05-08.pdf?ts=1431100856). Sabotage conviction reversal May 8, 2015 is distinct from physical release May 16.

### Source 15

[United States v. Levasseur, 699 F. Supp. 995, section C](https://law.justia.com/cases/federal/district-courts/FSupp/699/995/1419153/). Court describes the arrest of Thomas and Carol Manning on April 24, 1985 in Norfolk, Virginia.

### Source 16

[Association of Former New Jersey State Troopers, September 2019, Colonel’s Comments, page 3](https://www.ftanjsp.org/newsletter/FTA_Newsletter_September_2019.pdf). Reports receiving BOP notification on July 31 that Thomas Manning died in prison July 30, 2019.

### Source 17

[International Committee for the Freedom of the Cuban 5: Fernando Gonzalez Llort](https://www.thecuban5.org/who-are-the-cuban-5/fernando-gonzalez-llort/). Federal prison sentence ended February 27, 2014, followed by immigration detention and return to Cuba February 28. The case release field refers to the federal prison endpoint.
