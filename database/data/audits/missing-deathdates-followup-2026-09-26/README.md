# Missing death dates follow-up — 2026-09-26

This pass screened profiles without death dates against external authority records, then checked identity and date precision before publication. It added **27 corroborated death dates** to the live database. The missing-death-date count fell from **8,198** to **8,171**.

## Results

- **19 full dates** were stored with day precision.
- **8 year-only dates** were stored with year precision, so the interface does not present January 1 as a known date.
- Exact biographical matches were required. Same-name authority records whose occupation, geography, organization, or chronology did not match the NPPC profile were excluded.
- Coarse authority years were refined when a stronger obituary, contemporary newspaper, archival biography, or historical study supplied a full date.
- One candidate, Lowell Wakefield, had already been renamed and supplied with a death year in the live database. The guarded publisher detected the mismatch before writing and the profile was excluded from this transaction.

## Publication controls

- Every change required an exact prisoner ID, current name, current slug, and a null live death date.
- A SQLite `VACUUM INTO` backup was created before the transaction.
- Only `death_date`, `date_precision`, `age`, `body`, and `updated_at` could change.
- Existing citations were reused instead of duplicated; profiles without the selected source received a source credit.
- All 27 stored dates, precision values, and source links passed a post-publication database check.
- All 27 public profile routes returned HTTP 200 after publication.

`counts-summary.json` retains the aggregate result. The complete candidate queue, publication manifest, evidence notes, and profile-level verification output remain in the local audit workspace for continued research and are not included in the repository.
