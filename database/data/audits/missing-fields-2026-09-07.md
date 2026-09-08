# Missing-field audit: September 7, 2026

First pass across the 8,602 public prisoner profiles and their 9,047 case rows,
read through the live Laravel models. Under-review profiles were excluded by
the public scope. The accompanying CSV records blank-field indicators and case
counts for every public profile; it contains no biographies. This snapshot was
taken before deployment of batch 249 and before the proposed batch 250.

| Field | Blank rows |
| --- | ---: |
| Prisoner photo | 6,390 |
| Birth date | 5,587 |
| Death date | 7,463 |
| Case arrest date | 4,385 |
| Case incarceration date | 4,757 |
| Case release date | 5,012 |
| Case sentencing date | 8,233 |

These are **research leads, not error totals**. A living person's death date, a
current prisoner's release date, or an unconvicted person's sentencing date can
properly be blank. Partial dates count as populated and are preserved. A separate
read-only check of all 2,212 populated photo paths found zero missing files on
the public storage disk. This verifies file existence, not identity, image
quality, licensing, or HTTP delivery for every existing image.

## First verified batch

Batch 250 fills 36 date fields and one portrait across 19 existing profiles:

- John Brown, Aaron Stevens, John E. Cook, John Anthony Copeland Jr., Edwin
  Coppoc, Shields Green and Albert Hazlett: missing birth/death fields. Green's
  birth remains approximate (`c. 1836`). Brown also receives his sentencing date.
- Eight Haymarket defendants: missing judicial sentencing dates. Spies,
  Parsons, Fischer and Engel also receive their documented death dates.
- Thomas Wilson Dorr: missing birth, death and sentencing dates.
- Dick Gregory: missing birth and death dates.
- Iva Toguri D'Aquino: missing birth, death, U.S. arrest and sentencing dates.
- C. T. Vivian: missing portrait, with the existing birth/death dates preserved.

Every added value is tied to sources in `../fixes/batch250.json`. The batch uses
an explicit field allowlist, fills blanks only, and cannot update biographies,
Website fields, case prose or custody flags. Existing partial dates are not
upgraded. No new custody endpoint or inferred time-served value is inserted.

## Leads held for separate review

Existing data discrepancies were observed but are outside this fill-only batch:

- John Brown's arrest/incarceration fields say October 17, 1859; the
  [NPS biography](https://home.nps.gov/people/john-brown.htm) dates capture to
  October 18. Establish the meaning of both fields before any correction.
- Albert Hazlett's arrest field says October 21, 1859; the
  [NPS raiders account](https://www.nps.gov/articles/john-browns-raiders.htm)
  says capture on October 22.
- The eight Haymarket cases share May 4 arrest/incarceration dates, although
  individual arrests and Parsons's later surrender require separate treatment.
  Three release fields say June 25, 1893, while the
  [museum chronology](https://www.chicagohistoryresources.org/hadc/chronology.html)
  dates the pardon to June 26. Pardon and actual release must be distinguished.
- Dorr's incarceration field says June 25, 1844; the
  [Rhode Island Historical Society account, printed p. 53](https://www.rihs.org/assetts/files/publications/2010_SumFall.pdf)
  distinguishes sentencing on June 25 from entry to the state prison June 27.
- Iva Toguri's populated year-only release field could eventually be refined to
  January 28, 1956 using the FBI and Densho sources; it is left unchanged here.
- Six blank photo fields match bundled filenames, but names alone are
  insufficient identification. In particular, `john-martin.jpg` is linked in
  the Zimmer import to `john-martin-2`, and the Figueroa Cordero records also
  require duplicate-profile review. No automatic basename matching was applied.

Next research groups: Freedom Riders with unfilled dates; historical labor and
sedition cases; verified support-organization portraits. Audit each person's
individual custody episode and inspect caption/rights evidence for each photo.

## Validation and deployment

JSON, Bash and PHP syntax checks passed, including the prohibition on apostrophes
inside the single-quoted Tinker block. The live dry run identifies 26 existing
rows to update across the 19 profiles and preserves all selected fields.

Tests with the actual Laravel models, a sole in-memory SQLite connection, and
an in-memory photo disk verified all 36 dates, circa display, the photo, cache
invalidation, repeat-run idempotency, populated-field preservation, and refusal
of missing cases, conflicting image files, and biography fields in date data.
Every biography and every field outside the explicit update allowlist remained
byte-for-byte unchanged. No production rows or photo files were written.

After merge, deploy the numbered script on the server after all earlier pending
batches. Preview with `bash database/data/run-batch-250.sh --dry-run`, then run
`bash database/data/run-batch-250.sh` as the application owner. The audit inventory
is a dated snapshot and is not imported into the live database.
