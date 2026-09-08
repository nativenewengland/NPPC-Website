# Omali Yeshitela mural case — batch 251

Researched September 7, 2026. Existing profile: `omali-yeshitela`, formerly Joseph Waller Jr. The live read-only preview found the historical case missing. Add one case for the December 29, 1966 St. Petersburg City Hall mural incident; preserve the existing 2023 case and every prisoner field.

## Custody evidence and limits

- The [Supreme Court oral argument transcript](https://www.supremecourt.gov/pdfs/transcripts/1969/69-24_11-13-1969.pdf), November 13, 1969, printed pp. 16–17 (PDF pp. 18–19), records counsel reporting **16 months in jail** and about **18 months out on bond**. The scanned page was visually verified. This establishes actual confinement and an interim duration, not a reconciled final total.
- [Yeshitela's June 2016 press conference, reported by The Weekly Challenger](https://theweeklychallenger.com/yeshitela-the-original-art-critic/), gives his later recollection of **two and a half years in prison** for the mural. Its [2017 anniversary report](https://theweeklychallenger.com/yeshitela-vs-the-mural/) repeats that duration and describes the arrest following the December 29 protest.
- [Waller v. Florida, 397 U.S. 387 (1970)](https://www.law.cornell.edu/supremecourt/text/397/387) supplies the municipal and state sentencing history and the April 6, 1970 decision vacating the state conviction. It does not give an exact release date or settle final time served.

The case's sentence text attributes both duration accounts. Numeric duration, incarceration and release dates remain unfilled. In this application `imprisoned_for_months` means a final duration; setting it to either figure would erase a material limitation in the evidence. An inferred release date or a continuous arrest-to-decision interval would also misrepresent custody, given the period on bond. No biography, support website, coordinates, institutional attribution or modern custody status is changed.

## Validation

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
