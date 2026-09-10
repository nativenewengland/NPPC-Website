# Affiliation prisoner audit — batch 290

Four proposed missing people, four custody cases and one identified photograph. Refreshed read-only inventory: **8,786 profiles**, including hidden entries. Alias checks cover the entire inventory and pending batches 271–289. Existing biographies, identities, cases and populated fields are preserved in full.

## Cases and evidence

| Person | Final outcome recorded | Date limits |
| --- | --- | --- |
| Alexa Wise | January 2023 jury conviction on five counts; conspiracy acquittal; April 2023 probation. | Three-day physical jail interval only. |
| Dylan Davis | November 2022 bench conviction; February 2023 probation. | Three-day physical jail interval only. |
| Barry Jones III | July 2022 failure-to-disperse conviction; time-served-to-twelve-month sentence. | A maximum sentence is not time actually served. |
| T-Jay Fry | September 2022 misdemeanor plea, fines and costs. | Multi-day detention established; release endpoint unknown. |

The [contemporary release report](https://oneunitedlancaster.com/coronavirus-awareness-lancaster/activists-seek-fair-trial-justice-for-protesters/) and [official arrest chronology](https://lancaster.crimewatchpa.com/lbop/19659/post/arrests-made-related-arsonriot-lancaster-bureau-police-station-9142020) establish actual custody. [Individual dispositions](https://crimewatch.net/us/pa/lancaster/da/11617/post/last-defendant-2020-lancaster-city-riots-pleads-guilty), [Wise’s verdict](https://crimewatch.net/us/pa/lancaster/da/11617/post/two-convicted-rioting-outside-city-police-station-2020) and [sentencing report](https://crimewatch.net/us/pa/lancaster/da/11617/post/defendant-2020-lancaster-city-riots-sentenced-13-30-months-prison-two-additional) distinguish final outcomes from initial accusations. All case-level evidence is referenced in the payload.

Subsequent house arrest and probation are excluded from physical imprisonment counters. The three closed intervals yield three elapsed days each and only 2020 in the incarceration chart. Fry has no asserted release or ongoing incarceration: both status flags are false, the existing Other category, with uncertainty explicit in his new biography. No inference about current custody from old allegations or sentencing ranges.

Alexa’s name and female identity follow later local and official reporting. Earlier legal-name forms are used for duplicate matching. No race is inferred from photographs. No vital dates are calculated from ages. No prisoner numbers, personal support websites or private addresses. Map coordinates approximate Lancaster. The existing Lancaster courthouse is not linked as the jail.

The [photo credit](../photos/CREDITS-batch290.md) documents Wise’s unchanged publisher image. Three other linked police image downloads returned HTTP 406; those assets are not attached. No synthetic substitutes.

## Leads retained; next affiliation

- Talia Gessner and Yoshua Montague: prosecution outcomes found; obtain individual custody/release documentation before adding exact intervals or asserting sentence served.
- Matthew Modderman: initial confinement and later minor disposition reported; duration needs further confirmation. Do not confuse him with the attorney of the same name.
- Frank Gaston: omitted from the later official roster; resolve identity, custody and outcome before adding.
- Christopher Vazquez: later prison sentence found; investigate actual confinement and individual activism context.
- Jessica Lopez and Jamal Newman Jr. already have profiles and are preserved.

Proceed to **Black Panther Party** while retaining these leads and the Phoenix/Indianapolis leads. This is a bounded first research pass, not a claim that every group has been exhausted.

## Validation and manual deployment

**202 assertions passed** using actual application models solely in isolated SQLite memory, after purging production connections. Files, storage and cache were simulated. Checks cover preservation, hidden aliases, replay, partial dates, three-day counters, unresolved status, evidence and photo validation, storage failure/collision, and cache invalidation. Query-only live preview: **four missing identities, zero existing matches**, B290-OK. Shell syntax and whitespace checks passed. No production writes.

Deployment is manual. After merging this batch and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-290.sh --dry-run
sudo -u www-data bash database/data/run-batch-290.sh
```
