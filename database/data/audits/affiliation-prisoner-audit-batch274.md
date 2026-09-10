# Justice 8 roster audit: batch 274

Reviewed September 10, 2026. The original eight-person roster contains seven existing profiles and one missing person: **Gullit Eder Acevedo**, also known as **Jaguar**. This batch proposes one profile and one case. Existing profiles and all populated fields remain unchanged.

## Roster comparison

| Original roster name | Result |
| --- | --- |
| Edin Alex Enamorado | Existing; preserved |
| Wendy Lujan | Existing; preserved |
| David Chavez | Existing; preserved |
| Stephanie Amesquita | Existing; preserved |
| Gullit Eder Acevedo | Missing; proposed in batch274 |
| Edwin Pena | Existing; preserved |
| Fernando Lopez | Existing; preserved |
| Vanessa Carrasco | Existing; preserved |

The [sheriff's December 14, 2023 release, reproduced by Crime Voice](https://www.crimevoice.com/2023/12/15/multi-agency-operation-results-in-arrest-of-assault-suspects-linked-to-protests-in-san-bernardino-and-los-angeles-counties/), identifies all eight and their booking. Its allegations are not treated as established guilt. Identity matching checked all 8,786 live records, including aliases and hidden records, plus pending batches 271–273. Unrelated people named Acevedo were not merged.

## Custody and outcome evidence

- Arrest: **December 14, 2023**. The sheriff's release names High Desert Detention Center as the booking facility.
- Actual continued custody: [KVCR's December 26 courthouse report](https://www.kvcrnews.org/2023-12-26/street-vendor-activists-facing-felony-charges-await-thursday-bail-hearing) explicitly describes the eight still in custody and names Acevedo. This establishes confinement extending well beyond initial arrest processing.
- [December 28 bail report](https://www.kvcrnews.org/2023-12-28/seven-of-eight-arrested-street-vendor-activists-to-remain-in-jail-without-bail) records conditional bail, not a verified same-day physical release. [January courthouse reporting](https://lataco.com/activists-victorville-no-bail) confirms he was out on bail. Exact release day remains blank.
- [KVCR January 10, 2024](https://www.kvcrnews.org/2024-01-10/judge-rules-testimony-and-evidence-enough-to-hold-street-vendor-activists-for-felony-charges) reports dismissal of most charges with misdemeanor assault remaining. [KVCR December 15, 2024](https://www.kvcrnews.org/2024-12-15/most-of-justice-8-defendants-sentenced-but-edin-enamorado-continues-to-plead-his-case) says all felonies were dismissed and anticipated dismissal of the misdemeanor. Anticipation is not a completed court disposition: no final acquittal/dismissal is invented.
- [The June 2024 plea report](https://www.kvcrnews.org/local-news/2024-06-12/most-of-justice-8-street-vendor-activists-plead-guilty-to-assault-charges) distinguishes six other defendants who accepted agreements. Their pleas and sentences are not assigned to Acevedo.

No birthdate is inferred from a news report giving age 30. No death date, sentence, exact release endpoint, or numerical custody aggregate is guessed. No verified personal support website or cleared portrait was established; these fields stay blank. The stock illustration on the arrest article is not a portrait. Source URLs remain in the research payload rather than the website field.

The live institution lookup found no matching High Desert record, so no institution relation is fabricated. Coordinates **34.54, -117.29** mark approximate Victorville locality, not his home or an asserted jail entrance.

## Validation

67 assertions passed with the application models in disposable SQLite memory: preview nonmutation, first apply, replay, alias preservation, unchanged existing profile/case/photo/website, partial-date handling, unresolved dates, source validation, invalid-field rejection, ambiguous identity rejection, and rollback. All configured database connections were replaced with memory-only connections before test writes. The live preview ran under `PRAGMA query_only = ON` and confirmed one missing person. Shell syntax and staged diff checks passed. No production data was changed.

## Follow-up and deployment

This completes the original eight-person identity roster once deployed; it does not settle every case detail. Follow up on Acevedo's exact physical release date, final misdemeanor disposition, source-supported vital dates and portrait. Broader associated protest arrests, including Victor Alba and Wayne Freeman, remain leads requiring individual multiday custody evidence before inclusion.

Next group **ACT UP** has been started. Michael Petrelis, David Pasquarelli and Richard Racklin already exist and were preserved. Continue beyond these names rather than duplicating their profiles.

After merging, apply earlier pending numbered batches before 274:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-274.sh --dry-run
sudo -u www-data bash database/data/run-batch-274.sh
```
