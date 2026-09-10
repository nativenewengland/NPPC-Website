# Affiliation prisoner audit — batch 270

Reviewed September 10, 2026. Five proposed new people and five cases. The refreshed read-only inventory contains 8,781 profiles, including batch 269. All five names and recorded aliases are absent; every existing identity is preserved by the batch at runtime, including hidden profiles.

## Verified additions

| Person | Listed affiliation reviewed | Qualifying custody | Sources |
|---|---|---|---|
| David Darst / James Darst | Catonsville 9 | May 17–24, 1968, Baltimore County Jail | [Contemporary account](https://www.newyorker.com/magazine/1970/03/14/acts-of-witness-priests-and-vietnam-war-protesters), [appeal](https://law.justia.com/cases/federal/appellate-courts/F2/417/1002/190492/) |
| Arnie Alpert | Clamshell Alliance; later AFSC | May 1–13, 1977; Concord armory | [First-person recollection](https://peaceworks.afsc.org/arnie-alpert/story/441), [AFSC interview](https://afsc.org/news/winning-justice-nonviolent-campaigns) |
| Harold Marcuse | Clamshell Alliance | Multi-day detention in 1977; accounts vary between about 10 and 12 days | [Personal account](https://www.marcuse.org/harold/pages/seabrook.htm) |
| Thea Paneth | Clamshell Alliance | Twelve days detained in 1977 | [Testimony biography](https://www.iraqtribunal.org/thea_paneth) |
| Court Dorsey | Clamshell Alliance | Approximately two weeks in Somersworth armory, 1977 | [First-person account](https://clamshellalliance.com/2023/06/14/clam-magic/), [appeal](https://law.justia.com/cases/new-hampshire/supreme-court/1978/78-154-0.html) |

Darst completes the nine-person Catonsville roster at the identity level: the other eight already exist, although not all carry the same affiliation label. His unserved two-year term is explicitly distinguished from pretrial custody. The [memorial homily](https://darstcenter.org/remembering-brother-david-darst/) supports birth on December 6, 1941 and death on October 30, 1969; its inconsistent stated age is not copied. [Marcuse's own biography](https://www.marcuse.org/harold/) supports birth-year precision of 1957. Other vital dates remain blank.

The Clamshell pass is preliminary, not an exhaustive roster of the mass arrests. Current AFSC label coverage is likewise not a membership census; Alpert is a cross-affiliation addition, not completion of the AFSC audit.

## Precision and preservation

- Only Darst and Alpert have verified paired custody endpoints. The model should calculate seven and twelve days respectively. No sentence is entered as actual months served.
- Marcuse's opening date/day wording is inconsistent with his timeline. The timeline and accounts of the action support the May 1 arrest; no individual release date or exact duration is inferred. His armory naming is uncertain, so no institution link is guessed.
- Paneth's source establishes the year and duration, not an exact arrest, sentencing or release day. Dorsey's case uses May precision. Their actual detention is described in case text without manufacturing endpoint dates.
- New profile markers use approximate protest locations: Catonsville locality and Seabrook Station. They are not prison coordinates. No historical jail is linked to a similarly named modern institution without verification.
- Existing biographies, cases, pictures, vital dates and support websites are untouched. No research-source URL is inserted in the public support-website field.

## Open leads and next pass

| Lead | Current disposition / next step |
|---|---|
| Cheryl Fox | Dorsey names her in the affinity group, but this pass did not isolate an explicit individual custody duration. Find her own account or a named detention roster. |
| Robin Thompson | A 2017 Boston Globe interview reports eleven days held. Obtain fuller identity and case evidence before preparing a record. |
| Elizabeth Boardman | Training and organizing are documented; verify her own actual detention, rather than inferring it from the group. |
| Richard Asinof; Peter Blood; Kristina Johnson; Katherine Ashton; Cathy Wolff | Candidate organizer names only; identities and qualifying custody still require individual research. |
| Paul Gunter; Anna Gyorgy; Guy Chichester; Renny Cushing; Harvey Wasserman; Sukie Rice; Murray Bookchin; Howie Hawkins; Howard Morland | Existing Clamshell identities; preserved. Broader duplicate screening also found Frances Crowe, Randy Kehler and Betsy Corner already present under other affiliations. |
| CNVA unresolved leads | Continue the individual evidence gaps recorded in the batch 268 audit; no change to their disposition in this pass. |
| AFSC; Fellowship of Reconciliation | Continue the organizational pass, checking against the full database rather than only matching labels. |

## Validation and deployment

Validation passed: 105 assertions using production application models with all test writes confined to a disposable SQLite memory database. Checks covered dry-run immutability, precise/partial dates, actual custody counters, preservation of existing records and hidden aliases, idempotent replay, cache invalidation, and atomic rejection of invalid data or ambiguous identities. A live preview under SQLite `PRAGMA query_only = ON` confirmed five missing identities. Shell syntax passed. Production has not been written by this work.

After merge, apply earlier pending batches first:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-270.sh --dry-run
sudo -u www-data bash database/data/run-batch-270.sh
```
