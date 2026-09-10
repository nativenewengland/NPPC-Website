# Affiliation prisoner audit — batch 289

Three missing Black Lives Matter Movement protesters; three cases and two identified photographs. Checked the read-only inventory of 8,786 identities, including hidden profiles and aliases, and all pending batches 271–288. Existing identities are skipped in full; no existing biography or field changes.

| Proposed person | Verified custody and outcome | Remaining gaps |
| --- | --- | --- |
| Denzel Draughn | Actual pretrial jail custody following August 28, 2020 arrest; still confined at September 9 hearing. Jury acquittal December 9, 2021. | Exact jail-release date; vital dates. |
| Taylor Breann Enterline | September 14–17, 2020 pretrial custody. January 2023 jury verdict, April 2023 probation, May 2024 appeal decision. | Vital dates. |
| Kathryn Patterson | September 14–17, 2020 pretrial custody. March 2023 misdemeanor plea and probation. | Exact sentencing day, vital dates, verified photograph. |

[KPBS bail reporting](https://www.kpbs.org/news/public-safety/2020/09/09/judge-lowers-high-bail-san-diego-protestor), [later reporting](https://www.kpbs.org/news/local/2021/12/10/prosecutors-throw-book-san-diego-protester-activists-say-message) and the [December 9 broadcast](https://www.pbs.org/video/thursday-december-9-2021-xjnub3/) distinguish Draughn’s detention from acquittal. Defense and police accounts are attributed; changing charge totals are not conflated. No acquittal date is substituted for release.

[Contemporary Lancaster release reporting](https://oneunitedlancaster.com/coronavirus-awareness-lancaster/activists-seek-fair-trial-justice-for-protesters/) establishes the two exact jail intervals. Enterline’s [appellate memorandum](https://law.justia.com/cases/pennsylvania/superior-court/2024/715-mda-2023.html) supplies the final reviewed judgment; its discussion of sufficiency includes waiver and alternative merits analysis. Patterson’s [AP report](https://www.fox29.com/news/woman-gets-probation-in-charges-related-to-lancaster-protest-disorder.amp) supplies her plea and noncustodial sentence. Probation is never counted as incarceration.

All three have verified historical release and are not marked currently imprisoned. Approximate coordinates locate San Diego and Lancaster. Only the verified San Diego County Jail institution is linked; the Lancaster courthouse is not substituted for a jail. No unverified personal support website, prisoner number or vital date. Patterson’s race remains unknown. Movement affiliation describes these protests, not inferred formal membership.

Photographs and provenance are documented in [CREDITS-batch289.md](../photos/CREDITS-batch289.md). No synthetic images or retouching.

## Continuation

Jessica Lopez and Jamal Newman Jr. already have entries. Preserve them. Continue the missing Lancaster identities: Alexa Wise (also reported as Lee Wise), Dylan Davis, Barry Jones III, Talia Gessner, Yoshua Montague, T-Jay Fry, Matthew Modderman and Frank Gaston. The [prosecutor’s outcome list](https://crimewatch.net/us/pa/lancaster/da/11617/post/last-defendant-2020-lancaster-city-riots-pleads-guilty) is a lead for individual verification, not proof that every arrestee spent multiple days jailed. Check Christopher Vazquez separately. Retain Phoenix and Indianapolis leads, then continue the affiliation queue to Black Panther Party.

## Validation and deployment

**181 actual-model assertions passed** in isolated SQLite memory after purging production connections. File, storage and cache operations are simulated. Checks include exact partial dates, three-day counters, no false ongoing custody, preservation, hidden aliases, replay, evidence validation, photo checksums, storage failures and collisions. Query-only live preview: **three missing profiles, zero existing matches**, B289-OK. Shell syntax and whitespace checks passed. Production was not modified.

After merging and deploying earlier batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-289.sh --dry-run
sudo -u www-data bash database/data/run-batch-289.sh
```
