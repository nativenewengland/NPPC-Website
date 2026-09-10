# Affiliation prisoner research: batch 272

Reviewed September 10, 2026. Four new profiles and four cases proposed; no existing records changed. Refreshed read-only inventory: 8,786 profiles. All canonical names and shorter aliases were checked against the full inventory and pending batch 271. Deployment remains manual.

## Included

| Person | Eligibility and fields | Limits |
|---|---|---|
| Alfred Hassler | FOR editor; diary documents sustained federal custody in 1944–1945. Birth year1910; death June5,1991; arrest year1944; parole March1945; existing Lewisburg penitentiary relation. | Exact initial admission and sentence length unresolved. |
| Lawrence Scott | Former AFSC peace-education director; institutional archive explicitly records thirty days served for a White House vigil. Birth year1908; death August7,1986. | No exact date attached to this episode. |
| Florence Y. Carpenter | Peace Action Center worker; twenty days actually served following the1962 nuclear-test vigil. Arrest/sentencing year1962. | Vitals and exact custody endpoints unverified. |
| Pearl C. Ewald | Same documented twenty-day imprisonment; death January1988 from an editorial notice accompanying her letter. Arrest/sentencing year1962. | Birth date and exact release date unverified. |

### Sources and review

- Hassler: [his prison diary excerpts, pp.322–335](https://web.english.upenn.edu/~cavitch/pdf-library/Brock_These_Strange_Criminals.pdf), [Stanford's King Papers identity note](https://kinginstitute.stanford.edu/king-papers/documents/glenn-e-smiley-0), and [Boston University's Box88 biographical note](https://www.bu.edu/library/files/2022/05/King-Martin-Luther-Jr-inventory-1965-2013.pdf). Indexed PDF text was readable, although direct retrieval encountered an expired certificate/404. June custody preceded the July6 transfer from New York to Lewisburg; the latter is not used as the beginning of the entire confinement.
- Scott: [Swarthmore College Peace Collection DG090](https://findingaids.library.upenn.edu/records/Peace_SCPC.DG.090). The archive's thirty-day term is undated within1961–1963. A separate contemporary account has him bailed during a twenty-day sentence. These are not collapsed into an invented dated interval or counted as multiple verified cases.
- Carpenter/Ewald: Mildred B. Young, [Vigil at the White House, July1,1962, p.281](https://friendsjournal.s3.us-east-1.amazonaws.com/friendsjournal/wp-content/uploads/emember/downloads/1962/HC12-50290.pdf). Original page visually inspected. It distinguishes completed sentences from bail release. [The full appeal opinion](https://law.justia.com/cases/district-of-columbia/court-of-appeals/1962/3097-2.html) supplies middle initials and the disorderly-conduct provision; judgments affirmed October26,1962.
- Ewald's [June1988 letter and death notice, p.4](https://friendsjournal.s3.us-east-1.amazonaws.com/friendsjournal/wp-content/uploads/emember/downloads/1988/HC12-50828.pdf) was visually checked: death reported in the preceding January. The unrelated widow's tax discussion in the next column is not attributed to Ewald.
- Carpenter's Ohio Quaker identity is corroborated by the [Lake Erie Association Bulletin, December1962, p.6](https://leym.org/wp-content/uploads/2014/09/2-2-dec-1962.pdf). Contemporary and modern same-name obituary results were not treated as identity matches.

## Preservation and modeling decisions

- Only Scott receives AFSC affiliation and only Hassler receives FOR affiliation. The two women receive their documented Peace Action Center and Religious Society of Friends affiliations.
- The existing model overwrites `imprisoned_for_days` on save and supports only whole documented months independently of dates. Twenty/thirty-day durations remain in the sentence text. They are not rounded into months, or supplied with fabricated endpoints.
- Hassler's release uses month precision. His initial incarceration date stays blank because pairing partial endpoints would manufacture a precise counter in the existing model. The diary still documents his imprisonment in the case text.
- No photos or personal support websites were verified for these four; those fields remain blank. Sources are not assigned as support websites.
- Washington markers `(38.90, -77.04)` represent approximate protest locality. Hassler's marker reuses the existing penitentiary coordinates `(40.964421, -76.884795)` and UUID `df689c4b-2900-4bb4-bb53-55fd5fd1abd7`. Other similarly named institution rows are not changed.
- Batch validates all identities, allowed fields, dates, affiliations, sources and institution before transactionally creating missing records. Hidden or alias-matched profiles are preserved, ambiguities abort, and successful writes invalidate the established caches.

## Reviewed existing people and held leads

- Journey of Reconciliation: Rustin, Joseph/Joe Felmet, Roodenko, Peck, Houser, William Worthy, Wallace Floyd Nelson and Ernest Bromley already exist. [Yale's original report](https://documents.law.yale.edu/journey-reconciliation) supplies the roster. Andrew Johnson remains held: [the county's exoneration account](https://orangecountync.gov/2937/Journey-of-Reconciliation) establishes conviction but not his individual multiday service. Its apparent James/Joseph Felmet discrepancy is not a new identity.
- The1947 Chicago musician Dennis Banks must not be equated with the much younger AIM founder. Conrad Lynn, Eugene Stanley, Nathan Wright, Louis Adams, Homer Jack and Worth Randle remain leads requiring individual custody evidence.
- Glenn E. Smiley: [Stanford](https://kinginstitute.stanford.edu/smiley-glenn-e) establishes1945 imprisonment and exact vitals; retain as a strong lead pending a clear multiday interval or prison account establishing duration. No unrelated Rustin booking-slip fields are attributed to him.
- Allan Brick: [his obituary](https://www.friendsjournal.org/allan-brick/) describes alternative service; that alone is not imprisonment. Michael Jendrzejczyk: prison advocacy is not proof of personal custody. Caleb Foote, Tom/Thomas Cornell, Don/Donald Benedict and Wilmer Young already exist.
- John Suter's [personal account](https://www.crmvet.org/vet/suterj.htm) describes six hours of custody for the episode reviewed; below the user's threshold.
- Next organizational pass: Alabama Christian Movement for Human Rights. FOR/AFSC wider membership remains open; this is not a claim of exhaustive coverage.

## Validation and deployment

Passed101 assertions using production application models with test writes isolated to disposable SQLite memory: dry-run immutability, partial-date preservation, no invented counter, existing biography/photo/site/case preservation, hidden alias handling, repeat idempotency, cache invalidation and atomic rejection of malformed/ambiguous data. Live preview under `PRAGMA query_only = ON` found all four absent. Shell syntax passed. No production writes.

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-272.sh --dry-run
sudo -u www-data bash database/data/run-batch-272.sh
```
