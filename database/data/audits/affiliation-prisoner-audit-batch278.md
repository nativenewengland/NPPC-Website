# Affiliation research — batch 278

Reviewed September 10, 2026. Four proposed profiles and cases; existing records remain untouched. This is an initial American Labor Party pass, a completed check of the named Angola 3 roster, and a substantive Progressive Party/labor case found in the contemporary newspaper archive. It is not a completed review of every member of these movements.

## Proposed additions

| Person | Role in the New Kensington case | Verified actual custody | Later disposition | Portrait |
|---|---|---|---|---|
| Harry W. Truitt Jr. | Progressive Party organizer; supported pickets | 10 months | Suspended sentence at retrial | Labeled newspaper photograph |
| John F. Allen | Truitt's laboratory assistant | 10 months | Acquitted at retrial | Not verified |
| Lester Peay | Picket | 10 months | Suspended sentence at retrial | Not verified |
| Robert T. Smith | Picket captain | 10 months | Suspended sentence at retrial | Not verified |

The [Pennsylvania Supreme Court opinion](https://law.justia.com/cases/pennsylvania/supreme-court/1951/369-pa-72-0.html) identifies these four appellants and reverses their convictions on December 19, 1951. It distinguishes the charges and discusses political and racial prejudice. The payload retains those distinctions rather than presenting a blanket acquittal or treating allegations as proven facts.

[National Guardian, February 27, 1952, p.4](https://www.marxists.org/history/usa/pubs/national-guardian/1952-02-27-4-19-nat-guardian.pdf) supplies the biographical context and labeled portrait. [March 5, p.8](https://www.marxists.org/history/usa/pubs/national-guardian/1952-03-05-4-20-nat-guardian.pdf) explicitly reports actual custody for all four. [March 26, p.4](https://www.marxists.org/history/usa/pubs/national-guardian/1952-03-26-4-23-nat-guardian.pdf) resolves the retrial outcomes. The latter report was located by searching the archive's available March–June issues after ordinary search results missed it.

These are contemporaneous reports by an advocacy newspaper. Its characterization of a frame-up is not entered as an adjudicated fact. The suspended sentences are not counted as additional imprisonment.

## Precision and preservation decisions

- All four arrest dates: March 18, 1950. Exact incarceration starts and physical release dates remain unset. The report's description of defendants being freed after retrial does not establish uninterrupted custody until that trial.
- Use `imprisoned_for_months: 10`, independently documented as time already served. The application internally converts this to 306 days using its date anchor; **306 is an implementation conversion, not a sourced exact day count**. Public display verified as `10 Months`.
- No DOB or DOD inferred from ages or unrelated namesakes. No portraits for the other three verified.
- The newspaper names a Westmoreland County workhouse; the court specifies Allegheny County Workhouse as the sentencing destination. No institution relation is assigned until that discrepancy is resolved.
- Map coordinates `40.57, -79.77` approximate New Kensington, Pennsylvania. They do not identify a jail entrance or a private address.
- Only Truitt receives the verified Progressive Party affiliation. The other three are not automatically enrolled in the party or Local 65; the court distinguishes paid pickets from union members. No American Labor Party label is inferred from the research trail.
- All 8,786 inventory identities, aliases and pending batches 271–277 were checked. Existing **Robert Smith** is a Beale Air Force Base anti-drone protester, confirmed by a query-only profile/case read. His record remains unchanged; the proposed historical picket captain uses his documented middle initial. Existing **Edwart Truitt** is a different IWW prisoner.
- Charles B. Tarpley and John Kuchek remain research leads. Their initial arrests do not prove the same ten-month custody. The court deferred Tarpley's sentence because of illness. Do not substitute either into the four-person appeal roster.
- No existing bios, cases, photos, support websites or filled fields are updated. Citation URLs stay in the payload and audit, never in the personal `website` field.

## American Labor Party: open leads

The two labeled live profiles are Olava Skottedal and Martin Robbins. Howard Fast, Leon Josephson, Harry Winitsky, Benjamin J. Davis Jr. and Abraham Unger were also found already present under other labels. No new ALP member was added solely for party membership.

[National Guardian, January 24, 1951](https://www.marxists.org/history/usa/pubs/national-guardian/1951-01-24-3-14-nat-guardian.pdf) describes the Skottedal/Robbins proceeding as originating in 1950, including a period free without bail. Existing case summaries use 1951. That discrepancy and their individual physical custody periods require further evidence; this batch preserves their records. Bail and maximum exposure alone do not meet the multi-day-custody rule.

Sidney Hillman, Louis Waldman, Eugene P. Connolly, Leo Isacson and Peter Cacchione remain unqualified leads until an individual qualifying detention is established. Defense work by Vito Marcantonio is not itself imprisonment.

## Angola 3: named roster already present

The [official support summary](https://angola3.org/media/A3_Final_Summary_MASTER_20131.pdf) names Robert King, Albert Woodfox and Herman Wallace. All three already exist, including King under **Robert Hillary King** (also Robert King Wilkerson). No duplicate is proposed. The dated support document is used for identity, not present-day custody status. Wider Angola organizing is a separate research question.

## Validation and deployment

- Shell syntax and the zero-apostrophe tinker constraint checked.
- **132 assertions passed** using the application models, an exclusively in-memory SQLite database, and a fake image store. Tests cover preview, replay, existing profile/case/timestamp preservation, namesake separation, documented-month display, unknown dates, invalid payloads, ambiguous identities, portrait checksum/write failures and cache invalidation.
- Live preview is query-only. Production writes are not part of this research workflow.
- Only a newly created Truitt profile receives the image. An existing match is preserved. A different file at the proposed storage path is not overwritten. If a later database operation fails after a successful image write, an unused identical image file may remain; a replay safely reuses it.
- After earlier batches are deployed in order and this PR is merged, run `sudo -u www-data bash database/data/run-batch-278.sh --dry-run`, then the same command without `--dry-run`, from `/var/www/NPPC-Website` after pulling main.

Next: continue Animal Liberation Front and Animal Liberation Movement custody research immediately, without waiting for this batch to be merged or deployed.
