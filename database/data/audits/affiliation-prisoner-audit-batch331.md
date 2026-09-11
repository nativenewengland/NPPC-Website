# Batch 331: Irish republican US custody

Two new profiles, three separate custody episodes, no photographs or vital dates. The unpublished accumulation is **26/100 new people**, 38 custody periods, 10 photographs, one birth year and two death years across batches326-331. No new PR or production deployment.

| Profile | Stored US custody |
|---|---|
| Michael O'Rourke, alias Patrick Mannion | October30,1979 to June1984, ending in deportation into Irish custody. |
| Gerry McGeough, formally Terence Gerard McGeough | May28,1992 to an unresolved day in1992, before bail; separate prison term April1994 to1996. |

## Research decisions

O'Rourke's initial arrest is dated by his sworn account and Senator Specter's correspondence. The habeas opinion also uses a loose early-November description of the INS arrest; preserve the specific corroborated October30 date. This was civil detention for a visa overstay. It was not a US conviction or sentence for the Irish offenses. June1984 is the end of US custody, explicitly a transfer into Irish imprisonment rather than freedom. The historical released flag denotes the completed US custody episode; do not infer the end of the Irish sentence from it. UPI describes his Dublin arrival on June20, but the precise US departure/handover time is not established, so no exact release day is entered.

McGeough's interview establishes ten days in Manhattan before bail and places him at Schuylkill on Easter Monday1994. Store the two episodes separately; no continuous 1992-1996 incarceration. April1994 is supported for the later term without converting a holiday recollection into an exact commencement day. The imposed sentence is described as three years in contemporary sources and two years in the later Northern Irish appellate history. The sentencing date also conflicts: June1993 in Wilson versus April1994 in the diplomatic summary. Neither a structured sentencing date nor a documented-month total is invented. A revised scheduled February23 release in the diplomatic file differs from the later court's March1996 release/return description. Keep year precision pending US records distinguishing prison release, immigration detention and deportation. German credit is not US time actually served.

McGeough's full legal name and shorter variants were screened. Birth1958/1959 is unresolved, and the exact September2 birthday was not independently verified. Neither person's DOB, DOD or a reusable portrait is added. No news source is placed in the personal-support website field.

## Validation

158 assertions passed using isolated SQLite memory with production connections removed and file/storage/cache mocked. Coverage includes insertion, repeat runs, existing-record preservation, aliases, ambiguous identities, partial dates, counter behavior, invalid-input rollback and cache invalidation. The forced query-only live preview finds both profiles absent. No alias collision against the8,957-profile snapshot or pending/submitted batches297-330. Shell syntax and whitespace checks pass. No existing biography or populated field is changed.

## Queue87 continuation

The live affiliation query returns29 profiles; the original queue counted28. Searches must cover all affiliations, because many relevant prisoners are filed elsewhere. This is a partial pass, not an exhaustive review. Continue queue87 at batch332 before moving to queue88.

Already present and excluded from the new-person count:

- Joe Doherty is Joseph Patrick Doherty; William/Liam Quinn is William Quinn.
- Desmond H. Mackin appears in the component name fields of the profile displayed as Charles H. MacKinnon (1104528e-eaca-4a1a-8379-cc221571b424). Flag for a separate identity audit; do not create another Mackin or silently rename the existing profile.
- James Barr is Jim Barr (059e7ce7-bed1-40eb-bd2a-a45623ee8944).
- Seamus Maley in one archival transcription is Seamus Moley (f0d63253-b9d3-4495-be9b-82487baf9c71).
- Martin Quigley, Richard Johnson, Kevin McKinley, Brian Fleming and Chuck Malone already exist.
- Kevin Barry Artt may be the existing Kevin Art (df868f83-2f33-47b2-9dbf-331c8b32250f). Read the full existing case before deciding; do not create a second profile.

Leads for the next pass:

- Michael/Mixey/Mixie Martin: diplomatic file describes a mistaken release and September1995 recommittal, transfer to Irish prison January31,1996, then Irish release February18. Separate the US segments and exclude the later foreign custody. No candidate with these name strings found in the name/alias snapshot; continue full identity verification.
- Noel Gaynor, Paul Campbell, Robert McErlean, Charles Caulfield, Brian Pearson and Matt Morrison: official asylum/deportation chronology alone does not establish each person's multi-day US custody. Find individual custody and bail evidence first.
- George Harrison, Michael Flannery, Thomas Falvey and Patrick Mullin: check actual pretrial days rather than assuming an arrest or acquittal establishes multi-day imprisonment.
- Malachy McAllister: the June2020 overnight removal report does not by itself meet the sustained-custody criterion. Research earlier US detention separately; Northern Irish imprisonment is not US custody.
- Denis Leyne, Thomas Maguire, Gerard Brannigan and Patrick Moley: review individual 1992 arms-case custody and identity variants. Aggregate indictment or sentence reports do not establish every individual's actual jail period.

## Sources

- [O'Rourke v. Warden, 539 F.Supp.1131 (SDNY May24,1982), full judicial opinion: prolonged civil immigration detention, October30,1979 arrest in sworn account, visa-overstay determination and denied bail.](https://law.justia.com/cases/federal/district-courts/FSupp/539/1131/2151540/)
- [O'Rourke v. US Department of Justice, 684 F.Supp.716 (DDC April25,1988), full judicial opinion: nearly four years already detained in July1983 and subsequent June1984 deportation.](https://law.justia.com/cases/federal/district-courts/FSupp/684/716/1896713/)
- [Congressional Record, February28,1984, printed pp3594-3595: Senator Specter correspondence independently gives October30,1979 Philadelphia arrest and continued New York detention.](https://www.congress.gov/98/crecb/1984/02/28/GPO-CRECB-1984-pt3-4-2.pdf)
- [UPI, June20,1984, Dublin arrival report: O'Rourke deported from US custody and arrested in Ireland. Distinguish transfer from freedom.](https://www.upi.com/Archives/1984/06/20/An-IRA-bomb-expert-who-was-deported-from-the/3817456552000/)
- [NYU Frank Durkan Papers, SeriesIV Legal Cases: archival index identifies Michael O'Rourke as Patrick Mannion. Finding aid reviewed; underlying legal files not inspected.](https://findingaids.library.nyu.edu/tamwag/aia_008/contents/aspace_ref12/)
- [Queen v. Terence Gerard McGeough, [2013] NICA22, May7,2013, paragraphs12-13: US weapons plea, May28,1992 extradition and March1996 return. Two-year sentence wording conflicts with contemporary three-year accounts.](https://www.judiciaryni.uk/files/judiciaryni/decisions/Queen%20v%20Terence%20Gerard%20McGeough.pdf)
- [Irish Department of Foreign Affairs, September3,1996, NAI/DFA/2021/50/303, scan pp8-9: sentence/transfer chronology; scheduled February23 release and March deportation are not conflated. Birth1959 conflicts with other accounts.](https://cain.ulster.ac.uk/nai/1996/nai_DFA-2021-50-303_1996-09-03.pdf)
- [Andrew J.Wilson, The Congressional Friends of Ireland and the Anglo-Irish Agreement,1981-1985, footnote15: June1993 sentence and actual spring1994 prison entry, separating the bail interval.](https://www.cain.ulst.ac.uk/events/aia/wilson95.htm)
- [An Phoblacht, A century of struggle, December16,1999: interview describing McGeough serving the US term and prison transfers.](https://www.anphoblacht.com/contents/5708)

Additional leads: [An Phoblacht, Artt/Brennan/Kirby bail release, October22,1998](https://www.anphoblacht.com/contents/4121); [Irish Times, prisoner transfer report, January30,1996](https://www.irishtimes.com/news/us-agrees-to-transfer-of-irish-prisoner-to-dublin-to-serve-rest-of-sentence-1.26458). PDF text was reviewed; failed browser screenshot requests are not claimed as visual verification.
