# Batch 327: IWO political immigration detainees

3 new profiles, 4 detention segments and 1 identified historical photo. Together with batch326 this brings the next PR accumulation to13/100 new people. Local preparation only; no production writes or new PR.

| Person | Custody represented | Date limits |
|---|---|---|
| Andrew Dmytryshyn | Ellis Island,1952; parole November1952 | Recommitment day conflicts between contemporary reports; year only |
| Sam Milgrom | Ellis Island and guarded hospital confinement,1952 | Subsequent freedom confirmed; exact release unknown |
| Ignatz Mezei | Initial1950–1952 exclusion detention and separate1953–1954 recommitment | First start year-only because removal voyages interrupt continuous US-soil presence; final release month-only |

## Research decisions

- Original October27,1952 Naujienos front page names Dmytryshyn and Milgrom among eight people transferred after bail cancellation. Jewish Life December1952,p.10 instead gives November17. No exact recommitment date is selected. January1953,p.16 reports Andrew's November parole and Milgrom still under hospital guard. The legal six-month detention limit is not assumed to be Andrew's served term.
- Milgrom's1950 arrest, intervening bail and1952 recommitment are not merged. Zecker,p.241 confirms eventual freedom after litigation; no three-year jail term is inferred. Hospital transfer is not release. Birthdate/photo/website of the modern gallerist named Sam Milgrom are excluded.
- Mezei's Supreme Court opinion establishes prolonged confinement after arrival February9,1950 and failed attempts to remove him. His initial interval is year-precision to avoid an invented uninterrupted day count. Weisselberg's archival reconstruction, note203, identifies May10,1952 release on bond and April22,1953 recommitment. His year at home on bond is excluded. Final parole is recorded at August1954 month precision. Political security allegations are not described as criminal convictions. The unrelated1935 fine and disputed1927 arrest are not new activism cases.
- IWO affiliations use the existing canonical label International Workers Order. A newspaper identifies Andrew as a Ukrainian American Fraternal Union officer; that additional affiliation is supported. No vital dates, prison foreign keys, coordinates or personal support websites are inferred.

## Preservation and checks

All3 missing in the query-only live preview; aliases also screened against the8,957-row snapshot and all pending/submitted batches297–326. 188 assertions passed in isolated SQLite memory, with production connections removed and filesystem/storage/cache mocked: insertion, replay, whole-profile preservation, alias collisions, date precision, no invented ongoing custody, invalid-input rejection, portrait integrity, rollback on storage failure and cache invalidation. Model observers may invalidate the API cache during a transaction that later fails; no success-path museum/tracker invalidation runs on failure.

The batch only creates missing identities. Existing biographies, cases, populated fields, photographs and support URLs remain unchanged. No migration or direct production write. Deploy manually after earlier batches and the partial-date counter fix from PR2493.

## Sources

- [National Guardian, July 26, 1950, p.4, Elmer Bendiner, Full-scale attack opens on foreign-born in U.S.; identifies Andrew Dmytryshyn, his IWO section leadership and photograph.](https://www.marxists.org/history/usa/pubs/national-guardian/1950-07-26-2-35-nat-guardian.pdf)
- [Naujienos, October 27, 1952, p.1, 8 komunistai Ellis Islande; names the eight detainees and reports their transfer after bail revocation. Original scan reviewed.](https://www.spauda.org/naujienos/archive/1952/1952-10-27-NAUJIENOS.pdf)
- [Jewish Life, December 1952, p.10, Ellis Island as Concentration Camp; names eight detained people but supplies November 17, conflicting with the October report.](https://www.marxists.org/history/usa/pubs/jewish-life/vol-7/v07n02-dec-1952-JL.pdf)
- [Theodore Jacobs, Jewish Life, January 1953, p.16, Will the Racist Law Remain?; Dmytryshin November parole and Milgrom guarded hospitalization.](https://www.marxists.org/history/usa/pubs/jewish-life/vol-7/v07n03-jan-1953-JL.pdf)
- [Robert M. Zecker, A Road to Peace and Freedom (Temple University Press, 2018), p.241 and notes 85-87; Milgrom hospitalization and eventual freedom. Litigation length is not continuous jail time.](https://tile.loc.gov/storage-services/master/gdc/gdcebookspublic/20/19/66/78/51/2019667851/2019667851.pdf)
- [Cornell Kheel Center, International Workers Order records, collection 5276; leadership records include Sam Milgram spelling.](https://rmc.library.cornell.edu/EAD/htmldocs/KCL05276.html)
- [Shaughnessy v. United States ex rel. Mezei, 345 U.S.206 (1953), pp.208-209; arrival, exclusion, failed removals, long Ellis Island detention and intervening bond release.](https://supreme.justia.com/cases/federal/us/345/206/)
- [Charles D. Weisselberg, The Exclusion and Detention of Aliens, 143 University of Pennsylvania Law Review 933 (1995), pp.971 note 203, 974, 976, 982-983; archival reconstruction of IWO role and separate custody periods.](https://lawcat.berkeley.edu/record/1115033/files/fulltext.pdf)
- [Julie Chinitz, Shiftiness: The Border in Eight Cases, ZYZZYVA, March 16, 2017; recounts Mezei parole and return to Buffalo in 1954.](https://www.zyzzyva.org/2017/03/16/shiftiness-the-border-in-eight-cases/)

## Open leads retained

Max Bedacht's1920 case does not establish actual imprisonment: the court record says he did not appear for sentencing. Do not treat every defendant's collective conviction as proof that he served. Louise Thompson Patterson's Birmingham arrest lacks an individually verified multi-day interval. Joseph Simonoff/Simoniff/Siminoff and Harry Yaris/Varis are named detainees, but their individual affiliation/case details need review. Dr.Krishna Chandra appears in deportation material; actual sustained US detention remains unverified.

Already represented names include Frank Borich, Michael Nukk, Jack Schneider, Paul Yuditch, Katherine Hyndman, Peter Harisiades, Stanley Nowak, Benjamin Saltzman, William Weiner and Carl Paivio. No duplicates added.

ILGWU held leads remain in audit326; additional library/periodical searches did not resolve their individual service. This is a bounded pass, not a claim that either affiliation is exhausted. Next: Iraq Veterans Against the War and related war-resister cases, queue86.
