# Affiliation research: first prisoner-roster pass (batch 269)

Date: September 10, 2026. This starts the requested group-by-group audit; it does not claim that every affiliation has been researched.

## Inventory and sequence

- Live read-only snapshot: **8,700 profiles**, including **2 under review**. All identities were considered for duplicate detection; no private account or contact tables were queried.
- **203 public affiliation labels**, grouped into **199 research queues** by four obvious spelling/abbreviation pairs. This is an audit-only grouping; no existing affiliation is renamed.
- `affiliation-research-queue-2026-09-10.csv` retains every original label, its counts, research order, status and next step. Membership totals overlap because a profile can have several affiliations.
- First substantial roster: Heart Mountain draft resistance, reached through the listed Heart Mountain Fair Play Committee. Catonsville Nine received an initial identity screen. The prior CNVA batch is linked in the queue rather than represented as newly researched.

## Eligibility and preservation

Add people missing from the database only when the sources connect activism or resistance with actual US custody beyond initial arrest processing. A charge, an imposed sentence, a suspended sentence, generic group membership, or foreign detention alone is insufficient. Check the named individual rather than applying a collective sentence to every participant.

Batch 269 adds **81 profiles and 81 attached cases**, with **80 birthdates**, **76 death dates**, and **199 case-date fields** at the precision supported by their sources. Seventy-three cases link to existing prison records. Seven existing Fair Play Committee leaders are preserved, including Min Tamesa / Minola Minoru Tamesa. Every existing profile and case remains unchanged.

New profiles use **Heart Mountain draft resistance**. Formal Fair Play Committee membership is not inferred from resistance or trial participation. Some resisters did not attend its meetings. The biographies describe the broader movement and each prisoner’s case; they do not turn all resisters into committee leaders or universal conscientious objectors.

No photo, personal/support website, unknown birth/death date, actual time-served total, or exact release endpoint is invented. Research URLs stay in source notes. Biographies and other populated fields on existing records are untouched.

## Sources and date decisions

- The [Heart Mountain museum roster](https://www.heartmountain.org/collections-archives/draft-resisters/) links individual archival biographies and WRA files. Each proposed record cites its own biography in the payload and roster ledger; the roster’s total is not itself proof that every named person qualifies.
- [National Archives McNeil Island records](https://catalog.archives.gov/id/2675080) provide an independent check. The [December 1946 release-planning lists](https://catalog.archives.gov/id/342713854), images 1–6, identify prisoners still awaiting release. These support sustained custody alongside the prosecution chronology, but their future dates are not entered as completed releases.
- [James Satoru Sako](https://catalog.archives.gov/id/342713926) and [Torao Uyemura](https://catalog.archives.gov/id/342714112) have museum placeholders. Their original prison admission summaries, dated August 7, 1944, record July 10 commitment, three-year sentences, dates of birth and the citizenship-rights explanation for refusal. Their release dates remain blank.
- [Fred Homi Iriye’s prison file](https://catalog.archives.gov/id/342714054) and [museum biography](https://heartmountain.org/draft-resisters-hub/iriye-fred-homi/) establish prison work and death in custody in 1946. Death is entered at year precision. No exact death day is derived from an anticipated release; the incarceration start is retained in research evidence rather than used to produce an exact total from a year-only death endpoint.
- Sako’s arrest day is April 4 in the prison interview versus April 7 in his brother’s museum biography. Iriye’s is April 6 versus April 7. Both use **April 1944**, not a guessed day.
- Mutsuo Higuchi’s individual biography gives a three-year term while the second-trial cohort is described as receiving two years. His imprisonment is corroborated by the December 1946 prison list, but the numeric sentence is left unresolved.
- Fred Toru Okuma’s museum chronology (1944 trial / July 1946 release) conflicts with the December 1946 list for Toru Fred Okuma. The batch adds his verified imprisonment while leaving the disputed arrest, sentencing and release chronology blank.
- Roy Masao Uyeda’s biography flags competing birth years, 1923 and 1924. The birthdate remains blank. No death date is assumed for people whose sources do not supply one.
- [Shigeru Fujii’s appeal](https://law.justia.com/cases/federal/appellate-courts/F2/148/298/1503825/) distinguishes Selective Service refusal from the leaders’ separate conspiracy prosecution. The batch does not add a sedition charge to the resisters.
- Profile map coordinates **44.67, -108.95** identify the Heart Mountain protest/camp area, using [Yale’s camp exhibit](https://outofthedesert.yale.edu/gallery/heart-mountain/). They are approximate historic coordinates, not current residences. Prison links use existing McNeil Island and civilian USP Leavenworth records, not the military disciplinary barracks.

## Held leads

| Name | Why not added yet |
|---|---|
| Kawakami, Frank Masao | Custody confirmed by December 1946 McNeil list, but biography describes a draft-registration transfer mix-up rather than deliberate resistance; activism connection unresolved. |
| Mori, George Hajime | Museum explicitly says draft charge/conviction is unclear; camp confinement alone does not meet this activism-custody screen. |
| Shimane, Chester Toru | Museum file pending. Brother’s biography identifies arrest and imprisonment, but individual dates and identity need further documentation. |
| Tanaka, Tad Tadaichi | Museum file pending; individual qualifying custody not verified. |
| Uyeda, Frank Hiroshi | Arrest and transfer to Cheyenne are documented, then 4-F exemption; number of days actually jailed is not established. |

## Catonsville Nine identity screen

The [federal appellate decision](https://law.justia.com/cases/federal/appellate-courts/F2/417/1002/190492/) names all nine defendants. Existing profiles were found for Daniel Berrigan, Philip Berrigan, George Mische, John Hogan, Mary Moylan, Marjorie Melville, Tom Melville and Tom Lewis. Only three currently carry the Catonsville 9 affiliation; that label count is not a count of all represented participants.

David / James Darst is absent, but his imposed two-year sentence does not establish service: he died before beginning it. [George Mische’s firsthand account](https://www.ncronline.org/news/justice/inattention-accuracy-about-catonsville-nine-distorts-history) supplies the participant roster. Pretrial multi-day custody needs further verification before an entry can be added under this screen. No existing Catonsville biography or affiliation is changed.

## Validation and deployment

Passed **1,018 assertions** against a disposable SQLite database using the installed application models and table definitions. Checks cover dry-run immutability, 81 profile/case relationships, preservation of existing biographies/photos/cases, aliases including hidden profiles, idempotent replay with timestamps unchanged, partial-date precision, no invented time-served counters, cache invalidation, and atomic rejection of invalid sources, dates, institutions, affiliations and ambiguous identities. Shell syntax and content/source checks passed. A separate live preview enforced SQLite `PRAGMA query_only = ON` and confirmed all 81 identities absent and both institution links valid. Final text/precision refinements did not change the identities or institutional targets.

After merge, deploy manually and run earlier pending batches in order, then:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-269.sh --dry-run
sudo -u www-data bash database/data/run-batch-269.sh
```

The script is idempotent, skips existing identities, preflights all payloads and institution identities, supports dry-run, and clears the prisoner API, museum and tracker caches after an applied batch. No production database writes were performed during research or validation.
