# Omali Yeshitela mural case — batch 251

Researched September 7, 2026. Existing profile: `omali-yeshitela`, formerly Joseph Waller Jr. The live read-only preview found the historical case missing. Add one case for the December 29, 1966 St. Petersburg City Hall mural incident; preserve the existing 2023 case and every prisoner field.

## Custody evidence and limits

- The [Supreme Court oral argument transcript](https://www.supremecourt.gov/pdfs/transcripts/1969/69-24_11-13-1969.pdf), November 13, 1969, printed pp. 16–17 (PDF pp. 18–19), records counsel reporting **16 months in jail** and about **18 months out on bond**. The scanned page was visually verified. This establishes actual confinement and an interim duration, not a reconciled final total.
- [Yeshitela's June 2016 press conference, reported by The Weekly Challenger](https://theweeklychallenger.com/yeshitela-the-original-art-critic/), gives his later recollection of **two and a half years in prison** for the mural. Its [2017 anniversary report](https://theweeklychallenger.com/yeshitela-vs-the-mural/) repeats that duration and describes the arrest following the December 29 protest.
- [Waller v. Florida, 397 U.S. 387 (1970)](https://www.law.cornell.edu/supremecourt/text/397/387) supplies the municipal and state sentencing history and the April 6, 1970 decision vacating the state conviction. It does not give an exact release date or settle final time served.

The case's sentence text attributes the duration accounts and dated events described below. Numeric duration and the consolidated incarceration/release fields remain unfilled. In this application `imprisoned_for_months` means a final duration; choosing one of the differing accounts would erase a material limitation in the evidence. A continuous arrest-to-decision interval would misrepresent the interrupted custody. No biography, support website, coordinates, institutional attribution or modern custody status is changed.

## Validation

### Further release-date research

A publicly linked [full copy of Cutting's thesis](https://drive.google.com/file/d/1zio7H0Tcp_YlZOm6OsbS26Xh2CFKaK8A/view), located through RBG Communiversity, became accessible after the university download failed. Its title and approval pages identify the author and 2000 USF thesis. Relevant scanned pages were visually checked:

| Date | What the thesis establishes | Printed page |
| --- | --- | --- |
| March 29, 1968 | Initial release on $2,500 bond | 52 |
| May 6, 1968 | Release after a separate Gainesville arrest, followed within hours by bond revocation | 53 |
| May 29, 1968 | Release from Alachua County jail after raising both bonds | 53 |
| October 29, 1973 | Judge David Seth Walker reduced the mural sentence to eighteen months already served | 59–60 |

Footnote 20 cites *St. Petersburg Times*, May 30, 1968, B2, “Waller Free after Posting 2 Bail Bonds.” Footnote 31 cites Dean Boyer, “Judge Lifts Waller's Remaining Prison Term,” October 30, 1973, B1, and the author's November 27, 2000 interview with Judge Walker. These newspaper pages were not independently retrieved; the dates are attributed to the thesis. Footnote 19 has apparent date inconsistencies, so its original articles remain a useful corroboration target for the March release.

The [APSP's history](https://apspuhuru.org/about/apsp-history/) reports another imprisonment on the mural charge in May 1973 and release within two months. The [June 18, 1973 Supreme Court journal entry](https://www.supremecourt.gov/pdfs/journals/scannedjournals/1972_journal.pdf), p. 663, records a stay of enforcement, but does not establish the physical release day. The [October 15, 1973 order and dissent](https://www.govinfo.gov/content/pkg/USREPORTS-414/pdf/USREPORTS-414-BackMatter-4.pdf), 414 U.S. 945, confirm the intervening state reconviction, 1972 affirmance, and denial of further Supreme Court review.

The updated payload records this later outcome and the attributed bond-release dates in case text. It does not label October 29 as a physical release or treat 1966–1973 as uninterrupted custody. The exact departure day for the 1973 imprisonment remains unverified. The 18 months recognized at sentence reduction is added with attribution; it is not silently substituted for the other duration accounts.

### Checks

- Bash syntax, PHP syntax and JSON validation passed. No apostrophes occur within the single-quoted tinker block.
- The actual script wrapper ran successfully as `www-data` against the live application in forced dry-run mode with the payload supplied inline. No production data was written. The application-owned PsySH directories avoid the earlier unwritable-home failure.
- Mutation tests used the server's Laravel models and SQLite schema, with all production database connections removed from configuration and replaced by an isolated `:memory:` database. Tests confirmed one added case, unchanged prisoner and existing case attributes, accurate arrest precision, unset uncertain endpoints/duration, cache invalidation and idempotent replay.
- An independently entered mural case and a mismatched prisoner identity each prevented writes. A historical case with no verified incarceration interval did not start counting custody through today, even with modern custody flags enabled in the isolated test.

## Deploy after merge

Run any earlier undeployed batches first, including 250. From the application directory:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-251.sh --dry-run
sudo -u www-data bash database/data/run-batch-251.sh
```

The batch stops without edits if it finds a different existing historical case. Successful application prints `Added cases: 1` and `B251-OK`; an unchanged replay prints `Added cases: 0`. The API cache is cleared after insertion.
