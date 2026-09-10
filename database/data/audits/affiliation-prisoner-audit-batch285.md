# Affiliation research: batch 285

Reviewed September 10, 2026. First Black Liberation Army pass, including a related New Afrikan organizer encountered in the sources. This is a bounded pass, not a claim that the affiliation is exhausted.

## Proposed additions

| Person | Verified custody | Fields and portrait | Limits |
| --- | --- | --- | --- |
| Ahmed Obafemi / Jesse Dixon | Florida imprisonment, July 1972–October 1976 | Arrest July 1972; release October 1976; Florida State Prison relation; identified 1991 portrait | Ten-year sentence is not ten years served. DOB September 25, 1938 is retained in the new biography and payload research metadata; see display limitation below. Death date unresolved. |
| Anthony “Kimu” White / Kimu Olugbala | Extended pretrial detention at the Tombs before his 1972 escape | Tombs relation; year-only custody exit; January 1973 death month; identified memorial portrait | No conviction inferred. Escape month and death day conflict. Escape is represented by the existing release-date convention, not an acquittal or authorized release. |

Neither identity appears among 8,786 current profiles, including hidden records and aliases, or in pending batches 271–284. The final query-only live preview returned two missing and zero existing. The script also rechecks all identities at application time and preserves an existing match in full.

## Evidence and unresolved dates

**Obafemi:** the dated [New Afrikan Institute brochure](https://freedomarchives.org/Documents/Finder/DOC510_scans/New_Afrikan_Prisoners/510.new.afrikan.institute.ahmed.obafeni.pdf) and a [later speaker biography](https://0l.b5z.net/i/u/6075908/f/Ahmed_Obafemi-at.pdf) agree on actual custody. [Columbia’s conference biography](https://www.columbia.edu/cu/ccbh/acjp/initiatives/acjpconf_panel.html) supplies the gun-charge and DNC context but conflicts on time served; its political framing claim remains attributed. [The New Afrikan, December 1983, p.5](https://freedomarchives.org/Documents/Finder/DOC513_scans/NAPO/513_NAPO_NewAfrikanDec1983.pdf) confirms the July arrest. The [1988 Shakur opinion](https://law.justia.com/cases/federal/district-courts/FSupp/723/925/1630440/) verifies Jesse Dixon as his former name; he was a witness, not a defendant in that case.

The [Re-Build issue index](https://www.rebuildcollective.org/post/respect-our-grind-five-years-of-re-build) establishes that Obafemi was commemorated in Spring 2022, but does not establish his death year or day. Do not substitute a publication date. The application calculates age through today whenever a structured DOB exists without a death date. Consequently the verified DOB stays in prose and research metadata for now, avoiding a false living age. This is a display limitation, not uncertainty about the DOB itself. The similarly named music executive Chaka Zulu is a different person.

**White:** [Shanahan’s documented history](https://illwill.com/escape-from-new-york) describes attempted-murder detention on $250,000 bail and an eleven-day pre-escape period. This is evidence beyond a brief arrest. Its footnote cites October 24, 1972 reporting; that original article was not accessible and is not claimed as independently inspected. The contemporary [Liberated Guardian, February 1973, PDF p.13](https://freedomarchives.org/Documents/Finder/DOC510_scans/Attica/510.liberated.guardian.February1973.pdf) instead gives November for the escape. Only the shared year is entered. [Safiya Bukhari’s memorial, printed pp.20–21](https://black-ink.info/wp-content/uploads/2022/08/bukhari.pdf) gives January 22, 1973 for his death, whereas the Guardian gives January 23. Only the shared month is entered. His death after escape is not entered as death in custody.

Structured incarceration start dates and numerical durations remain empty for both cases. The model otherwise calculates exact day totals from defaulted first-of-month or first-of-year dates. The source-supported intervals remain in case prose. No institution data is changed; approximate action/custody community coordinates are set on the two new prisoner records. Neither source collection is placed in a personal support website field.

## Leads carried forward

| Lead | Status / next step |
| --- | --- |
| Tarik Sonnebeyatta / Sonebeyatta / Sune Beyatta | Obafemi co-defendant. Columbia and [Prisons: Fortresses of Repression (1984)](https://www.freedomarchives.org/Documents/Finder/DOC501_scans/PFOC/501.pfoc.control.84.pdf) describe prosecution and confinement. Resolve legal identity, individual term, and later status before addition. Original Afro-American July 22, 1972 coverage is a further lead. |
| Rauf Robert Vickers / Rashad Abdur-Rahman | Missing under Vickers. [UCLA Kochiyama collection defense publication](https://www.aasc.ucla.edu/da/kochiyama/nps1/locker/aasc-yk-1337_B.pdf) is a lead for the 1971 incident and 1972 arrest; individual detention chronology needs verification. A much later narcotics prosecution must not be substituted for activism custody. |
| Melvin Kearney / Rema Olugbala / Kerney | Missing under Kearney. Shanahan describes 1974 detention and a fatal May 1975 escape attempt. Verify original prosecution, aliases and death against contemporary reporting before addition; a roster’s 1976 death claim conflicts. |
| Pedro Monges / Chango Monges | Missing under Monges. Same history documents custody in 1974–1975. Verify aliases, political case and subsequent disposition. |
| Roderick Pearson | Alleged BLA connection in the same history; independently verify identity and activism-related detention. |
| Woodie / Woody / Changa Green / Greene | Memorials establish death and mention earlier jail, but do not yet establish an eligible individual multi-day activism case. |

All eight San Francisco 8 identities were located across affiliation labels; do not duplicate them. Albert Nuh Washington, Ashanti Alston, Kuwasi Balagoon and Tariq James Haskins also exist under broader identity checks. Donald Weems must not be added separately from Kuwasi Balagoon. Existing Cecilio Ferguson / Chui Ferguson and Cecilia Ferguson / Chui Ferguson entries warrant a separate duplicate audit; neither is modified here.

Continue these open leads immediately, then proceed to Black Liberation Front and Black Liberation Movement. Pending merges and manual deployment do not pause research.

## Validation and deployment

156 assertions using actual application models in a separate SQLite `:memory:` connection, with all production connections purged before test writes and fake file storage/cache. Covers unchanged existing rows, hidden aliases, ambiguous matches, idempotent replay, date precision, absence of false counters/living ages, portrait assignment and checksums, cache invalidation, malformed payload rejection, and storage-failure rollback. Production preview was enforced read-only with `PRAGMA query_only = ON`. Shell syntax and the apostrophe-free tinker block checked. No production changes made.

After merge, pull main and run any earlier undeployed batches in order, then:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-285.sh --dry-run
sudo -u www-data bash database/data/run-batch-285.sh
```
