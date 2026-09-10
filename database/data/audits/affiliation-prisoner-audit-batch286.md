# Batch 286 — Black Liberation Army, second custody pass

Reviewed September 10, 2026. Two proposed missing people, two historical custody episodes and one identified photographic portrait. This is a bounded pass, not a claim that the affiliation roster is complete.

The read-only inventory contains 8,786 profiles, including hidden records and aliases. Both identities were checked against that inventory and pending batches 271–285. The batch skips any identity already present at deployment, preserving the entire existing profile, biography, cases and images. An ambiguous match stops the batch. No production data has been changed.

| Proposed person | Established custody and case | Structured dates | Outstanding information |
| --- | --- | --- | --- |
| Melvin “Rema” Kearney | Contemporary account describes attempted-murder pretrial detention; government chronology places him in Brooklyn custody in August 1974 and May 1975. | Death and death in custody: May 25, 1975. | Arrest date, birth date and final trial disposition. No sentence or exact duration inferred. |
| Pedro Mario Monges / Chango Caribe | Eighteen-year federal bank-robbery sentence individually confirmed in an appellate opinion; separate records establish actual custody across 1973–1975. | Sentenced: 1973, year only, from university archival catalog. | Exact arrest, sentencing and release dates; later status, vital dates and an identified portrait. |

## Evidence and attribution

### Melvin Kearney

- [Black Community News Letter, July 1975, vol.13, printed p.15 / PDF p.16](https://www.freedomarchives.org/Documents/Finder/DOC32_scans/32.BlackComNewsletter.AwakeningofaDragon.pdf): captioned photographic portrait, Rema alias, May 25 fatal fall during attempted escape, and Pedro Chango Monges’s recapture. Cover and page were visually inspected. This is a contemporary movement memorial; its political rhetoric is not reproduced as a judicial finding.
- [Osawatomie, Summer 1975 no.2, printed p.28](https://s3.us-west-1.wasabisys.com/luminist/PR/OSA_1975_2.pdf): pending attempted-murder trial concerning a 1973 incident involving police. The publication’s claimed retaliatory motive is explicitly attributed.
- [New York State Policy Study Group on Terrorism, November 1985, Appendix F, printed p.101](https://www.ojp.gov/pdffiles1/Digitization/101960NCJRS.pdf): custody incidents August 15, 1974 and May 25, 1975. The latter date agrees with the contemporary memorial and newspaper. The report contains errors elsewhere, so this corroborated chronology is used narrowly.
- [Breakthrough, Fall 1978, PDF p.53 and printed p.57 / PDF p.59](https://www.freedomarchives.org/Documents/Finder/DOC501_scans/Break/501.break.5.fall.78.pdf): further historical corroboration and a labeled drawing. The earlier photographic portrait was selected instead.

The death year is **1975**, supported by contemporary sources, despite a later 1976 claim encountered in research. No birth year is calculated from the reported age of 22. A cemetery entry for a Melvin K. Kearney, born December 2, 1951 and deceased May 25, 1975, has not been independently linked to this person. The small handwritten memorial birth-year mark is also insufficiently clear and conflicts with the age evidence. DOB and the cemetery middle initial remain unassigned. Search-only spelling/alias leads remain in conservative duplicate matching without being asserted as public biographical facts.

### Pedro Monges

- [United States v. Estremera, 531 F.2d 1103 (2d Cir. February 2, 1976), paragraph 2 and footnote 2](https://app.midpage.ai/document/united-states-v-raul-estremera-333977): Pedro **Mario** Monges; guilty plea to bank robbery, Count 1; **eighteen years**. The underlying Bronx bank robbery occurred February 9, 1973. That action date is not entered as an arrest date. Estremera’s separate seventeen-year sentence is not Monges’s sentence.
- [Jackson State University, Margaret Walker Center, HCAC.JSU.0660](https://nmaahc.chnm.org/s/hcacdigitalarchive/item/7592): catalog identifies Pedro Monges as Chango Caribe, dates imprisonment/sentencing to 1973 and connects it with a BLA-related robbery and weapons prosecution. The item is a letter dated February 22, 1974. The catalog was read; the handwritten original was not transcribed or reproduced.
- [New York City Board of Correction, October 2, 1973 minutes, printed p.8](https://www.nyc.gov/assets/boc/downloads/pdf/1973-oct-02.pdf): September 24 custody memorandum names Monges among the Brooklyn detainees. This independently establishes imprisonment by that date.
- [Wilson v. Beame, 380 F. Supp. 1232 (E.D.N.Y. June 7, 1974), pp.1234–1235](https://law.justia.com/cases/federal/district-courts/FSupp/380/1232/1457995/): Monges was a plaintiff challenging administrative-segregation conditions while escape charges were pending. The injunction addressed access to religious services, legal assistance and education, not release from the underlying custody. This litigation is contextualized within his case rather than counted as another period of imprisonment.
- The state chronology and July 1975 newsletter above document later confinement and recapture. Their agreement and the 1973–1974 records establish custody far beyond an initial arrest.

His sentence is not entered as an eighteen-year time-served total. No subsequent release or death has been verified. Both current-custody and release flags remain false, the existing site’s **Other** category, and the biography states that later status is unresolved. This does not establish either ongoing imprisonment or release. The existing dashboard includes Other in its general active/other total, although this profile is not flagged currently imprisoned; no dashboard code is changed in this data batch.

## Data and image handling

- No existing fields or biographies are edited. No source URL is put in a personal support website field.
- No structured incarceration start or numeric duration is supplied, preventing an uncertain historical interval from becoming a precise or ongoing counter.
- The case model automatically mirrors a death-in-custody date into its custody-ending `release_date`. Kearney’s profile remains `released=false`, and the narrative explicitly states death, not discharge. This behavior is covered by the model checks.
- Brooklyn House of Detention for Men is named in prose. None of the inspected institution records was an established match. Federal MDC Brooklyn and the jail in Brooklyn, Connecticut are different institutions and were not substituted. Approximate Brooklyn coordinates are 40.69, −73.99; they are community coordinates, not exact premises.
- Kearney’s portrait is a lossless rectangular crop of a native raster from the captioned July 1975 memorial. See [credits](../photos/CREDITS-batch286.md). No image is assigned to Monges without an identified source.

## Validation and deployment

The shell syntax check and the no-apostrophes tinker-block check pass. Actual application-model checks run only in SQLite `:memory:` after all production connections are purged, with file reads, storage and cache replaced by test doubles. They exercise idempotency, preservation of hidden aliases and existing records, ambiguous identity rejection, date precision and death-endpoint behavior, photo hashes, storage conflict/failure handling, and rejection of unreviewed fields. A separate live preview enforces `PRAGMA query_only = ON`.

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-286.sh --dry-run
sudo -u www-data bash database/data/run-batch-286.sh
```

## Continue without waiting

Next: Black Liberation Front, then Black Liberation Movement. Carry forward Robert/Rauf Vickers (Rashad Abdur Rahman), Tarik Sonnebeyatta and other unresolved BLA leads. Vickers’s unrelated later drug prosecution must not be substituted for evidence about his earlier political case. Other open work: Monges’s later status, both men’s vital dates, and the possible existing Cecilio/Cecilia Ferguson alias overlap, which this batch does not merge or edit.
