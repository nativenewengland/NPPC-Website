# Batch 324: six further IWW prisoners

Six new identities and six custody episodes. Accumulation now 79 people and 82 cases in batches 320-324; 21 more new people before the next PR. Nothing deployed. Existing biographies, cases, populated fields, photos and support websites remain untouched.

## Evidence and limits

| Person | Evidence retained |
|---|---|
| Charles Jacobson | Virginia, Minnesota union secretary; January 4, 1917 arrest and actual sentence service confirmed by the historical study. The $100-or-ninety-days disposition is not converted into ninety days actually served. Release unknown |
| J. Callahan; Ed Lidbery; William Murray | Prosser criminal-syndicalism arrests dated November 16, 1919; the April 1920 defense-committee roster expressly identifies the three as serving six-month-to-one-year sentences. Exact prison admission, sentencing and release dates are unknown; no full term served inferred |
| John Hanson | Ritzville criminal-syndicalism conviction; April 1920 register expressly says serving six-month-to-one-year sentence. No custody year assigned from publication date |
| Ralph Bergdorff | Six actual months in Spokane County Jail after a 1918 rearrest. Earlier Bellingham sentence/escape and January 1919 return are context only; no continuous interval or additional served term invented |

All six have unknown final release status and are explicitly not flagged as presently imprisoned. Bergdorff's documented six months is stored as a duration; the application's internal derived day value does not establish a release date. No vital dates or portraits were independently identified.

## Identity distinctions

A targeted read-only review examined existing Charles Jacobs, H. R. Hanson and J. W. Murray. The August 30, 1918 Victoria Daily Times p.16 lists **Charles Jacobson of Duluth and Charles Jacobs of Denver separately** in the same sentencing report; both columns were visually reviewed. The union organizer is therefore added as a distinct identity, without modifying Jacobs. His later federal prosecution is a further research lead; federal admission/release dates are not inferred here.

The existing H. R. Hanson record concerns a Los Angeles/San Quentin case; no evidence links that initialed name to John Hanson. Existing J. W. Murray concerns a distinct Atlanta federal case. Initials are not silently expanded except the conventional Wm.=William in the new Prosser record; the printed abbreviation is also retained as an alias. Existing and pending names/aliases were screened, and live forced preview found all six missing.

## Sources

- **march-p7:** [One Big Union Monthly, March 1920, printed p.7: Ralph Bergdorff individual custody entry; scan image reviewed.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **april-p13:** [John Engdahl / Northwest District Defense Committee, One Big Union Monthly, April 1920, printed p.13: named Ritzville and Prosser defendants explicitly serving sentences; full page image reviewed.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n04-apr-1920_One%20Big%20Union.pdf)
- **jacobson-study:** [John E. Haynes, Revolt of the Timber Beasts, Minnesota History 42 (Spring 1971), pp.163,173: Local490 leadership, January4 arrest, sentence and actual imprisonment while a successor took over; original historical-society PDF text reviewed.](https://collections.mnhs.org/mnhistorymagazine/articles/42/v42i05p162-174.pdf)
- **jacobson-distinction:** [Victoria Daily Times, August 30, 1918, p.16, IWWs Sentenced at Chicago Today: Charles Jacobson of Duluth and Charles Jacobs of Denver are separately listed in the same sentencing report. Both columns checked in original page image.](https://upload.wikimedia.org/wikipedia/commons/1/11/Victoria_Daily_Times_%281918-08-30%29_%28IA_victoriadailytimes19180830%29.pdf)

## Validation

247 assertions passed in isolated SQLite memory after production connections were removed; File/Storage/Cache operations mocked. Fresh live forced dry-run used PRAGMA query_only=ON and found six missing profiles. Checks cover preservation, hidden aliases, all cases, replay, rollback, partial dates, no invented release/current custody, documented months and cache invalidation. Pending batches 297-323 screened. Shell syntax, no-apostrophe tinker block and diff checks passed.

## Next research

- Next batch 325. Complete remaining March/April leads before moving onward in the affiliation queue.
- George Bennett, Joseph Ryan and S. Beauchamp: Logan October 22, 1918 arrests, held at Bozeman. Find a second dated source to bound actual detention rather than assume the entire gap to the March 1920 publication.
- Gus Henricson: Sault Ste. Marie October 10, 1919; resolve precise custody span.
- E. J. McCocham: check McCochan/McCormick spelling variants and prisoner files before another identity.
- Richard Mattson and Jack Beaton: Minnesota/Wisconsin strike-related arrests are documented but still need individual multi-day custody confirmation. Charles Jacobson's duration must not be assigned to them.
- The April 12-14 roster includes injunction-only names, released people, bail cases and unnamed dismissed defendants. Its 318 cases are not 318 qualifying prisoners.
- Continue held Ellis Island co-release identities, Guiney year conflict and other alias ambiguities from earlier audits. Nothing in this batch resolves those gaps.
