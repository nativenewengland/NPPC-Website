# Global exile expansion — China, Sweden, North Korea, France and Algeria

Published September 26, 2026. This pass expanded the archive beyond the Soviet Union and post-Soviet Russia. It created 26 profiles and improved three existing profiles. Every new profile has a dated exile case, source links, an individual biographical summary and a recorded outcome. All 29 public pages returned HTTP 200 after publication.

## China: all 21 Korean War non-repatriates

The pass adds the complete group of 21 American Korean War prisoners who declined repatriation and crossed into China on February 24, 1954:

- Clarence Adams
- Howard Gayle Adams
- Albert Constant Belhomme
- Otho Grayson Bell
- Richard Gordon
- William Cowart
- Rufus Douglas
- John Roedel Dunn
- Andrew Fortuna
- Lewis Wayne Griggs
- Samuel David Hawkins
- Arlie Pate
- Scott Rush
- Lowell Skinner
- LaRance Sullivan
- Richard Tenneson
- James Veneris
- Harold Webb
- William White
- Morris Wills
- Aaron Wilson

Their shared chronology is supported by the U.S. Army’s Korean War chronology, the National Film Board of Canada documentary *They Chose China*, and Adam J. Zweiback’s peer-reviewed study of the non-repatriates. The profiles distinguish refusal of repatriation from a criminal conviction. The Army dishonorably discharged the men, and no one is described as convicted merely for choosing China. Individual return, onward migration and death information is recorded only to the precision supported by the source record.

The group was not politically uniform. Clarence Adams explicitly cited U.S. racism and later made anti-war broadcasts. Some others embraced communist politics, while several later described youth, captivity, family circumstances or fear of punishment as more important. The profiles avoid assigning a single motive to the group.

## Sweden

**Terry Marvell Whitmore** was added with his March 6, 1947 birth date, July 11, 2007 death date and a May 27, 1968 exile start. Whitmore was a wounded and decorated Black Marine who refused orders returning him to Vietnam, traveled from Japan to Sweden through an anti-war assistance network and publicly linked his desertion to the war, atrocities he witnessed and racism. His permanent return to Memphis in 2001 ends the recorded exile span at year precision.

The database already contained the four sailors commonly known as the Intrepid Four. Other names associated with Sweden were screened but not included in this publication without stronger individual evidence separating anti-war desertion, asylum, return and identity from common-name false matches.

## North Korea

Four post-armistice U.S. Army deserters were added:

- **Larry Allen Abshier** — crossed May 28, 1962; died in Pyongyang July 11, 1983.
- **James Joseph Dresnok** — crossed August 15, 1962 while facing military discipline; died in Pyongyang in November 2016.
- **Jerry Wayne Parrish** — crossed December 6, 1963; died in Pyongyang August 25, 1998.
- **Charles Robert Jenkins** — crossed January 5, 1965, left North Korea in July 2004, pleaded guilty to desertion and aiding the enemy, and served 25 days of a 30-day sentence.

These profiles do not presume that the crossings began as political asylum claims. The record instead describes each known motive and the later coercive conditions reported in North Korea, including compulsory ideological study, propaganda work and the failed 1966 attempt to obtain Soviet asylum. Jenkins’s later court-martial is recorded as custody; the other three were never tried in the United States.

## France and Algeria

A preflight identity check found that Donald Lee Cox already existed as **Don Cox**, so the live record was updated instead of duplicated. Sources were added and the existing exile and case chronology was retained. The pass also added sources to **Eldridge Cleaver** and **Kathleen Cleaver** and supplied Kathleen Cleaver’s previously missing 1969–1975 exile span at year precision.

The archive already contained Melvin McNair, Jean McNair, George Brown and Joyce Tillerson from the 1972 Algeria-to-France group, as well as Eldridge and Kathleen Cleaver. Pete and Charlotte O’Neal were also already present for Tanzania.

## Cuba cross-check

The principal named U.S. political exiles screened for Cuba were already present: Assata Shakur, Charlie Hill, Nehanda Abiodun and William Morales. No duplicate profiles were created.

## Files

- `inspect.php` performs exact-name and alias screening against the live database.
- `inspect-existing.php` records the existing Cox and Cleaver data used to plan safe updates.
- `publish-initial.php` contains guarded preflight and publication paths, creates a SQLite backup before writing, and performs post-write checks.
- `verify.php` produces a compact live-data verification record.
- `live-verification.json` records the final stored flags, exile dates and partial-date precision.
