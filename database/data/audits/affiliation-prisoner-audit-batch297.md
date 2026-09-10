# Batch 297: Cadets of the Republic and related Nationalist prisoners

Reviewed September 10, 2026. **Local, validated contribution of eight new people and eight cases toward the next 100-person pull request. Not deployed or submitted as a separate PR.**

The user changed the publication threshold: accumulate 100 new verified identities across groups before each new PR. Existing PRs through batch 296 do not count toward this total. Continue on the current accumulation branch; do not start subsequent batches from main and discard unpublished work.

| New identity | Individual custody evidence | Assigned historical institution |
| --- | --- | --- |
| Juan Jaca Hernández | Federal habeas decision expressly records continuing life imprisonment in 1967; personal archival account corroborates prison letters and eventual release | La Princesa and Oso Blanco described in narrative; no single institution relation imposed |
| Raimundo Díaz Pacheco | Contemporary named roster, corroborated by attributed first-person testimony of months in prison | United States Penitentiary, Leavenworth |
| Manuel Ávila | Named contemporary penitentiary roster | United States Penitentiary, Lewisburg |
| Juan Bautista Colón Rivera | Named contemporary penitentiary roster | United States Penitentiary, Lewisburg |
| Dionisio Vélez Avilés | Named contemporary penitentiary roster | United States Penitentiary, Lewisburg |
| Santiago Nieves Marzán / Malsán | Named contemporary penitentiary roster; spelling reconciled against the docket list and thesis | United States Penitentiary, Lewisburg |
| Julio Monge Hernández | Named contemporary penitentiary roster; Mange variant included defensively | United States Penitentiary, Lewisburg |
| Juan Álamo Díaz | Named contemporary penitentiary roster; Juan Alamo in the federal docket | United States Penitentiary, Leavenworth |

## Evidence and limits

Source URLs and precise locators are in `database/data/fixes/batch297.json`. The contemporary source is the **Cuban** solidarity committee's December 1939 publication, preserved by Chile's congressional library; it is not a Chilean contemporary report. Printed pages 17–18 (PDF pages 15–16) were visually checked. The federal order independently establishes defendant identities but is not treated as evidence of imprisonment.

The published prison roster provides actual, individual penitentiary placement. No five-year sentence is converted to five years served. Its facility assignments take precedence over a generalized Atlanta statement in the later thesis. The six shorter records retain unknown judgment details, release outcomes, vital dates, and custody endpoints. Their Nationalist association does not establish individual membership in the Cadets.

Jaca's 1951 prosecution and subsequent Law 53 proceeding belong to one continuing custody episode. His co-defendants Carlos Feliciano, Carlos M. Castro and Leonides Díaz were acquitted of the murder/assault charges on appeal; the opinion is not a blanket affirmance for every defendant. No new records for those co-defendants are included here without separate custody verification.

Jaca's birthday has conflicting months, so only 1909 is stored. His documented visit in summer 1994 and the cited November 1994 memorial support death-year precision, not an exact death date. His pardon is not converted into an exact physical-release day. Raimundo's sentence-expiration date is likewise left out of the release-date field. His later death in the Fortaleza attack is not recorded as a death in custody.

## Photos

No image is assigned. The `centrales-web.jpg` header on the Jaca article was downloaded and inspected: it portrays **José Celso Barbosa**, not Jaca. The Raimundo Claridad image is a crossword grid. A Wikimedia image-title lead did not provide a verified usable original. Do not reuse these rejected images or unrelated genealogy portraits.

## Existing records and unresolved leads

The eight 1936 appellate defendants, Julio Pinto Gandía, Rafael Ortiz Pacheco, Tomás López de Victoria, Heriberto Marín, Olga Viscal Garriga, Blanca Canales, Carlos Feliciano, Elifaz Escobar, Casimiro Berenguer and Santiago González already have matching records. Preserve them completely.

Continue researching the 1938 roster: Prudencio Segarra, Juan Pietri and Domingo Saltari Crespo. The 1939 publication records imprisonment, but further identity, case and outcome review is pending. Further 1950 leads include Ramón Pedrosa, Estanislao Lugo, Ricardo Díaz Díaz (father and son must remain distinct), Ismael Díaz Matos, Gregorio Hernández Rivera, Rafael Molina Centeno, Bernardo Díaz Díaz, Manuel Méndez Gandía, Justo Guzmán Serrano, Saúl Cuevas Rodríguez and Ángel Ramón Díaz. Formal conviction alone does not complete custody verification. Hiram Rosado and Elías Beauchamp's same-day 1936 arrest/deaths do not satisfy the standing multi-day criterion without evidence of another episode.

Original-source follow-up: Miñi Seijo Bruno's Claridad interviews (October 31–November 6, 1980), Raimundo's prison manuscript excerpt, and the November 18–24, 1994 Jaca memorial; contemporary 1938 judgments and 1950–1951 commitment records. The thesis supplies page references. This is a first pass, not an exhaustive prisoner roster.

## Preservation and validation

- Screened 8,846 live identities, including hidden records, names and aliases, plus all pending/published batches 269–296. Read-only preflight: eight missing, zero matched existing.
- Verified institution UUID/name pairs against the live inventory. Civilian Leavenworth is not the military disciplinary barracks. No institution edits.
- Existing identity matches are skipped entirely; ambiguous matches abort. Profile, dates, case text and matching keys are pinned to the reviewed payload.
- No migrations or production writes. Real-model validation used SQLite `:memory:` with all production connections removed and fake files, storage and caches: **299 assertions passed**.
- Checked insertion, UUIDs, replay, preservation, hidden aliases, ambiguity, invalid evidence/fields, partial dates, absent portraits, historical status, institution links and cache invalidation. No fabricated ongoing incarceration or exact duration.
- Shell syntax and whitespace checks passed. PsySH directories use application storage. Deployment remains manual after the future combined PR is merged.

Next affiliation: Camden 28. Keep the above leads open while progressing through the main queue.
