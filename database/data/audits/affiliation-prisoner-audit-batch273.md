# ACMHR and related Birmingham campaign: batch 273

Reviewed September 10, 2026. This first pass proposes five missing people and five cases, each supported by actual US confinement lasting several days. It does not claim to exhaust the Birmingham campaign or ACMHR membership.

## Verified additions

| Person | Actual custody evidence | Dates entered |
| --- | --- | --- |
| Abraham Lincoln Woods Jr. | The HistoryMakers interview biography records five days jailed following his first department-store sit-in; a congressional tribute places the action in spring 1963. | Birth October 7, 1928; death November 7, 2008; arrest year 1963. |
| Charles Avery Jr. | His published interview identifies the May 7 student march and five days in Birmingham Jail. | Arrest May 7, 1963. |
| Janice Wesley Kelsey | NPR interview reporting records four days detained. Her recorded oral history independently describes the arrest and first night in the county jail. | Arrest May 2, 1963. |
| Freeman A. Hrabowski III | His Civil Rights History Project interview explicitly records five days in juvenile detention; his own 2013 article dates the episode to May 1963. | Birth August 13, 1950; arrest May 1963. |
| Mamie King-Chalmers | Her interview biography records five days in jail during the Birmingham campaign. | Birth June 19, 1941; death November 29, 2022; arrest year 1963. |

Woods has verified ACMHR and SCLC affiliations. The other four receive the movement label **Birmingham civil rights campaign**, not inferred formal ACMHR/SCLC membership. Coordinates 33.52, -86.80 are an approximate Birmingham locality marker, not a claimed jail address. No institution relation is guessed.

The payload contains descriptive source labels and direct URLs for each entry. Principal sources:

- [Woods interview biography](https://www.thehistorymakers.org/biography/reverend-abraham-woods-jr) and [Congressional Record tribute](https://www.congress.gov/113/crec/2014/02/28/CREC-2014-02-28-pt1-PgE276.pdf).
- [Avery interview](https://www.birminghamtimes.com/2019/10/civil-rights-icon-charles-avery-jr-continues-his-fight-for-what-is-right/).
- [Kelsey recorded interview transcript, pp.11–14](https://www.lifestories.org/attachment/en/65946ce4179a0efae9038462/TextOneColumnWithFile/6644cc393d483c8847009890) and [NPR report](https://www.delawarepublic.org/npr-headlines/2023-06-02/60-years-since-the-childrens-crusade-changed-birmingham-and-the-nation).
- [Hrabowski interview, p.25](https://www.crmvet.org/nars/2011_hrabowski.pdf), [birthdate biography](https://www.thehistorymakers.org/biography/freeman-hrabowski-39), and [his 2013 article](https://wasb.org/wp-content/uploads/2025/05/WASB_2013-10oct.pdf).
- [King-Chalmers interview biography](https://kidsinbirmingham1963.org/category/mamie-king-chalmers/) and [AP death report quoting her daughter](https://www.clickondetroit.com/news/michigan/2022/12/03/mamie-king-chalmers-woman-in-civil-rights-photo-dies-at-81/).

## Precision and identity cautions

No exact release dates are calculated from narrative day counts. Actual days are preserved in sentence text; the existing model overwrites source-only `imprisoned_for_days`, so this batch does not invent endpoint pairs or round days into months. No unverified convictions or sentence lengths are added. Kelsey's charge text attributes the ordinance explanation to her account of the arresting officer.

Kelsey said her birthday had passed in April and that she deliberately told police she was fifteen to stay with friends. Neither her stated age nor the booking age is converted into a birthdate. Avery's age in a later interview is likewise not converted into a birth year. King-Chalmers's death date is the calendar resolution of the daughter's statement that she died Tuesday, in AP's Friday December 2, 2022 report. Her May 3 firehose photograph is not treated as an arrest record. Hrabowski's interview explicitly rejects the interviewer's fairgrounds suggestion; no fairgrounds institution is assigned.

All five were checked against 8,786 live identities (including aliases and records hidden from public listings), and against pending batches 271 and 272. Existing J. S. Phifer, Fred Shuttlesworth, Calvin Woods, Frank Dukes, Charles Billups, and Audrey Faye Hendricks are preserved. Abraham Woods is distinct from his brother Calvin. Kelsey is distinct from Cynthia Wesley; Hrabowski III is distinct from his father and grandfather.

## Open leads

- Edward Gardner and William Eugene Shortridge: leadership established in the [Stanford King Papers letter](https://kinginstitute.stanford.edu/king-papers/documents/alabama-christian-movement-human-rights), but individual multiday confinement not verified. Gardner's appearance at a 1967 release celebration does not mean he was among those released. Shortridge's work raising bail is not evidence of his own imprisonment; see [Montevallo Legacy Project](https://themontevallolegacyproject.com/untoldstories/civil-rights-movement-leader-william-shortridge-birmingham).
- Gwendolyn Sanders: campaign participation is a lead; individual duration still unresolved.
- James W. Stewart: resolve identity against existing James Stewart/James Steward before proposing anything.
- Continue named participant accounts from Kids in Birmingham 1963 and archival children's detention rosters. Each storyteller still requires individual custody verification; appearance in a campaign archive is insufficient.
- Portraits and any personal support websites remain blank in this batch; source URLs are not placed in the website field. Potential archive images require a separate identity/rights check before use.

## Validation and deployment

111 assertions passed using the application models in disposable SQLite memory. Checks covered dry-run nonmutation, first apply, replay, hidden aliases, preservation of existing profiles/cases/photos/websites, date precision, empty unresolved dates, invalid-source rejection, ambiguous identities, invalid fields and rollback. Every database connection was replaced with memory-only configuration before test writes. Production was read-only, including `PRAGMA query_only = ON` for the live preview, which found five missing people. Shell syntax passed.

Merge the PR, pull main on the server, and apply earlier pending batches in order before 273:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-273.sh --dry-run
sudo -u www-data bash database/data/run-batch-273.sh
```

Only the ACMHR queue row changes here to avoid conflicts with earlier pending batches. Next queued group: **#Justice8**, followed by **ACT UP**. After deployment, add the new Birmingham campaign label to the ongoing queue without treating this five-person pass as exhaustive.
