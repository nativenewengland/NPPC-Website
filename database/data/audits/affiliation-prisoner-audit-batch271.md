# CNVA / Fellowship of Reconciliation affiliation audit — batch 271

Reviewed September 10, 2026. Five missing Everyman defendants, each with one case. The read-only inventory contains 8,786 profiles after deployment of batch 270. All five proposed identities and their recorded aliases are absent. The batch rechecks all profiles, including hidden ones, before creating anything.

## Evidence and additions

| Person | Identity bridge and custody evidence | Vital dates |
|---|---|---|
| Samuel Reynier Tyson / Sam Tyson / Samuel R. Tyson | Full identity in obituary; named in sentencing report; Sam in Chaffee's prison letter | February 3, 1919 – June 17, 2002 |
| Walter Chaffee / Walt Chaffee | Full name in sentencing report; author of dated Santa Rita letter | Unverified |
| Barton Stone / Bart Stone | Full name in sentencing report; Bart in letter; own interview independently confirms prison | Unverified |
| Robert Robbins / Bob Robbins | Full name in sentencing report; Bob in the five-defendant prison letter | Unverified |
| Roger Moss | Full name in sentencing report; Roger in letter, which also names his wife Lynn | Unverified |

The [July 12, 1962 Associated Press report](https://oregonnews.uoregon.edu/lccn/sn85042470/1962-07-12/ed-1/seq-2/ocr/) identifies the three voyagers and two organizers and reports six-month contempt sentences. The [August 22 letter from Santa Rita](https://merton.bellarmine.edu/files/original/41a064aca2e4aa4897d419ceafc953465038282b.pdf), published in the September Catholic Worker, p.7, supplies evidence that the defendants were actually imprisoned. Their repeated activities in custody distinguish this from initial arrest processing. [Friends Journal, October 15, 1962](https://friendsjournal.s3.us-east-1.amazonaws.com/friendsjournal/wp-content/uploads/emember/downloads/1962/HC12-50296.pdf), pp.437 and 443, corroborates the continuing imprisonment and connection with nonviolent action.

Tyson's [Friends Journal obituary](https://friendsjournal.s3.us-east-1.amazonaws.com/friendsjournal/wp-content/uploads/emember/downloads/2003/HC12-51010.pdf), pp.42–43, establishes his full name, both vital dates, six months actually served, CNVA work and Fellowship of Reconciliation connection. The original page images were visually checked. His wartime Civilian Public Service is not added as an imprisonment-for-activism case.

## Conflicts and withheld fields

- [Stone's 1996 interview](https://www.cuke.com/Cucumber%20Project/interviews/bartston.html) recalls a one-year sentence and eight months held, conflicting with the contemporary sentencing report. It independently establishes extended custody, but no numeric actual-duration counter is assigned to him pending further records.
- Tyson's documented six months served is stored as months, without invented calendar endpoints. For the other four, the imposed six months is described as a sentence only. The batch rejects attempts to turn their sentence lengths into actual-duration counters.
- Sentencing has July 1962 precision. No exact arrest, admission, release or sentencing day is inferred. In particular, the August letter date is not a release date.
- Sources sometimes confuse Everyman I and II. The records use Everyman without a numeral; neither crew membership nor a separate case is inferred from the disputed numbering.
- Chaffee's letter and the next letter on the same page are separate. The three-day jaywalking detention belongs to Hugh Maddin, not Chaffee, and is not used here.
- No vital dates from similarly named people are adopted. The evangelist Barton Warren Stone, historian Roger William Moss, and unrelated Walter Chaffee obituaries are not identity matches.
- Markers are approximate San Francisco prosecution-locality coordinates, not prison locations. The historic Santa Rita institution is named in case text; no similarly named modern facility is substituted without verification.
- Existing biographies, cases, photos, populated dates and support websites are preserved. Research URLs stay in the source ledger.

## Remaining research

Franklin Zahn's [March 1997 obituary](https://friendsjournal.s3.us-east-1.amazonaws.com/friendsjournal/wp-content/uploads/emember/downloads/1997/HC12-50933.pdf), p.38, establishes imprisonment after resistance to Civilian Public Service and death on June 3, 1996, but gives no duration. His Everyman II participation alone does not establish qualifying custody; hold the proposed identity until multi-day evidence is found. Monte Steadman and C. George Benello likewise remain held. Do not import prison durations belonging to other people on tax-resistance aggregation pages.

Continue the AFSC and Fellowship of Reconciliation organizational passes, then the next groups in the tracked queue. Prior CNVA and Clamshell leads remain open; these five defendants do not exhaust either organization's membership.

## Validation and deployment

107 assertions passed using production application models with all test writes confined to SQLite memory. Checks include dry-run immutability, aliases and hidden identities, preservation, replay safety, date precision, actual-month counters, cache invalidation and atomic rejection of unsafe payload changes. A live dry-run with SQLite writes disabled confirmed five absent identities. Shell syntax passed. No production data was written.

After merge, apply earlier pending batches first:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-271.sh --dry-run
sudo -u www-data bash database/data/run-batch-271.sh
```
