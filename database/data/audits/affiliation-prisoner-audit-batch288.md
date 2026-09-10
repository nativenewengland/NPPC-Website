# Affiliation prisoner audit — batch 288

One proposed missing person, **Kijana Tashiri Askari (Marcus L. Harrison)**, with one administrative-custody case. The refreshed read-only inventory contains 8,786 identities including hidden profiles. Name, spelling and alias checks also cover pending batches 271–287. No existing record is changed.

## Evidence and limits

The [2010 federal order](https://www.govinfo.gov/content/pkg/USCOURTS-cand-3_07-cv-03824/pdf/USCOURTS-cand-3_07-cv-03824-6.pdf) documents his SHU confinement and political-mail dispute. Its denial of defendants’ summary-judgment motion is not a final judgment invalidating confinement. Officials’ gang rationale and Askari’s political explanation remain distinguished.

His [CCR account](https://ccrjustice.org/home/blog/2017/09/22/peaks-and-valleys-being-released-general-population-after-21-years-solitary) supplies segregation endpoints. They remain attributed prose: ending solitary did not mean leaving prison. Structured incarceration/release and duration fields are intentionally empty to avoid a false release or ongoing counter. [Solitary Watch’s 2012 report](https://solitarywatch.org/2012/05/01/inmates-in-solitary-confinement-in-california-respond-to-prison-policy-reforms/) independently corroborates prolonged SHU custody and his legal name and prisoner number.

The [2011 first-person manual](https://prisonerhungerstrikesolidarity.wordpress.com/voices-from-inside/a-survivors-manual-for-solitary-confinement-self-destruction-to-the-reconstruction-of-self/) separates earlier offenses from political development. Earlier crimes are not relabeled as activism cases. The broad movement affiliation describes his organizing; formal BGF membership is not inferred from prison classification.

A [2021 supporter fundraiser](https://www.gofundme.com/f/support-brotha-kijana-tashiri-askari) does not establish present status. Both non-null status flags remain false, the existing **Other** category, with that uncertainty explained. No invented vital dates. The historical prisoner number is H54077. Coordinates approximate Crescent City; the institution relation points to the verified Pelican Bay State Prison UUID. The episode also mentions later transfers without pretending all confinement occurred there.

CCR identifies a photograph with a visitor, but retrieval failed; it is a follow-up lead, not an attached portrait. A reported self-portrait is not silently substituted for a photograph. Personal support website remains empty; news sources and the old fundraiser are not inserted there.

## Other leads screened

| Name | Finding / remaining work |
| --- | --- |
| Khatari Gaulden / Jeffrey Gaulden | Already present; preserve the existing entry. |
| W.L. Nolen | Organizing and death documented; seek individual punitive-confinement evidence and original petition. The O.C. Nolen procedural appeal does not establish W.L.’s case. |
| Cleveland Edwards, Alvin Miller, Howard Tole | Association alone does not establish qualifying activism custody. |
| Shaka At-Thinnin | [2004 first-person interview](https://www.indybay.org/olduploads/issue3.pdf) reports nearly ten years and release in 1977; verify identity and individual political punishment before adding. |
| Moja Kutendo Askari / Larry Woodward | [October 2015 joint prison letter](https://www.prisonlegalnews.org/news/publications/rock-newsletter-4-10-volume-4-2015/) names him at Tehachapi SHU; investigate individual custody basis. Distinct from a New York prison employee of the same name. |
| Albert Ford | Futch’s co-plaintiff; individual political organizing and confinement basis still needed. |
| Al Musadig Yusef | Williamsburg Four alias unresolved; establish identity before duplication checks. |

Queue proceeds to Black Lives Matter Movement while retaining these leads. Earlier groups’ open leads are not represented as exhausted.

## Validation and deployment

Validation uses actual application models only in isolated SQLite memory, with fake files, storage and cache. The separate live preview is query-only. Checks cover preservation, aliases, replay, invalid evidence/dates, institution identity, documented number, no false custody or release, and cache clearing. Results are recorded after execution below.

After merge and earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-288.sh --dry-run
sudo -u www-data bash database/data/run-batch-288.sh
```

Production was not modified during preparation.

Validated: **118 assertions passed** in isolated memory. Query-only live preview: **one missing profile, zero existing matches**, B288-OK. Shell syntax and whitespace checks passed.
