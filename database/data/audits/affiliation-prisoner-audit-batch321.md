# Batch 321: IWW custody rosters

Fifteen missing prisoners and seventeen distinct custody episodes. No production data changed. With batch320, the next accumulation now contains 18 people and 20 cases; 82 more new identities are needed before opening a PR. Existing biographies, populated fields, cases, photos and support websites are never modified. Both batches use the established Industrial Workers of the World (IWW) affiliation label.

| People | Evidence / retained uncertainty |
|---|---|
| Henry Biddiscomb; Roy Decker | Omaha convention arrest November 13, 1917; named release December 6, 1918. Federal conspiracy indictment September 21, 1918. March register gives $10,000 bail; later participant roster gives $1,000 release bond. No conviction inferred |
| T. Venier; Ed Shanon | July 20, 1917 arrests; successive Idaho confinement and November 29 release |
| Matt Antella; J. Antella; P. Davison; N. McCloud; S. Hourve | July 23 arrests; August 2 Moscow transfer, September 17 St. Maries transfer; November 29, 1917 release |
| Nick Verbeck | July 23 arrest; explicitly excluded from group release. May correction reports continued federal detention and July 1918 six-year military sentence. No eventual release or full term served inferred |
| A. L. Vecellio | Idaho July 23–November 29, 1917 and separate Van Voorhis June 29–July 3, 1919. Eureka entry names L. Vecellio, so that episode is held pending identity confirmation |
| Neal / Neil Guiney | Idaho July 18, 1917 arrest and months of custody. March register says release in 1918; May roster says November 29, 1917. No release date entered. Separate Portland confinement after early-1919 arrest is explicitly ongoing in March1920 register; no later outcome inferred |
| E. Pavini | Eureka initial vagrancy arrest January 26, 1918 followed by release and immediate federal rearrest January 27; qualifying custody ends May 2. Initial one-night detention not counted separately |
| G. Bertini | Fort Bragg arrest February 7, 1918 and forty-one days confined. Exact release day not calculated from duration; statutory charge unspecified |
| John Levo | Seattle arrest January 16, 1918; confinement in Washington and Ellis Island; release March 17, 1919. Release is not labeled deportation |

The shared Idaho transfers and release are an explicit narrative following the named roster, not an assumption that everyone arrested in Idaho had the same dates. The source singles out Verbeck as the exception. Initials are not expanded without corroboration; Henry Biddiscomb full first name appears in the March register. No birthdays or photos attached from namesake searches. Later biographies and final dispositions remain research gaps.

## Sources

- **omaha-roster:** [E. W. Latchem, Omaha List of 1917, letter April 12, 1920, One Big Union Monthly May 1920, printed p.51 / scan pp.55 and 59: November 13, 1917 convention arrests and individual bail-release dates; page image reviewed.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n05-may-1920_One%20Big%20Union.pdf)
- **march-register:** [One Big Union Monthly March 1920, printed pp.7,9,11,20: Henry Biddiscomb and Roy Decker conspiracy indictments; Neal Guiney later Portland custody and earlier conflicting release year; Nick Verbeck earlier entry corrected in May. Pages 7 and 9 images reviewed; remaining entries extracted and reviewed.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n03-mar-1920_One%20Big%20Union.pdf)
- **vecellio-roster:** [A. L. Vecellio, letter April 11, 1920 and named custody roster, One Big Union Monthly May 1920, printed p.52 / scan p.60: individual arrests, shared transfers, November 29 release with explicit Verbeck exception, California/Pennsylvania episodes and John Levo custody; full page image reviewed.](https://www.marxists.org/history/usa/pubs/one-big-union-monthly/v02n05-may-1920_One%20Big%20Union.pdf)

## Validation

494 assertions passed with writes confined to SQLite memory after all production connections were purged; file/storage/cache writes mocked. The checks cover all seventeen episodes, precision, unknown endpoints, no present-day custody, alias preservation, replay and validation rollback. Live forced dry-run over a query-only connection found all fifteen missing. Normalized names/aliases checked against pending batches297–320. Canonical affiliation refinement retested in both batches (172 + 494 assertions). Shell syntax and zero-apostrophe tinker block checked.

## Next research

- Complete Guiney release conflict using Idaho court or contemporary daily press; do not overwrite uncertainty with the group date.
- Check J. Jarvis against existing Charles Jervis / Charles Jarvis before any addition.
- Check the thirteen other Ellis Island release names against identities and independently date their Red Special detention. Do not assign Levo January 1918 arrest to the group.
- Ulm Makarus and Ittone Alterio require individual custody duration; being out on bail alone is insufficient.
- March1920 roster has 679 cases; April adds 318, May adds 206. These are source counts, not missing database counts, and include short arrests, unapprehended defendants and duplicates. Continue screening qualifying names page by page.
- Existing Carl Larson profiles cover the name of the May page53 letter author; investigate aliases before adding anything.
- Review held Carl Swanson and Pietro Pierre individually; preserve known Shurin/Shuren, Blaine/Blame and Winsky/Winski spelling matches.
