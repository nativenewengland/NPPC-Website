# Peace-list activists: confirmed multi-day US custody — batch 267

Research and read-only live identity review: September 10, 2026. **Six new profiles, six cases, six assigned portraits, six supported birth dates (three exact and three year-only), and one exact death date.** Prepared for manual deployment; no production rows, public files or caches were changed during research or validation.

## Inclusion rule and coverage

The user requested actual US custody extending beyond initial arrest processing into multiple days. This review applies that rule to politically connected cases among previously unmatched people on the Wikipedia peace-activist list. Arrest, an unserved sentence, foreign custody, or an unrelated criminal conviction alone does not qualify.

The initial name comparison covered 713 distinct listed people. Detailed identity checking corrected Ed Sanders to the existing Edward Sanders profile, leaving 72 existing matches and 641 unmatched before deployment. A targeted US-linked lead screen attempted 163 biographies: 147 retrieved and 16 retrieval errors. Primary sources and reporting were then checked for promising custody leads. This is not an exhaustive detention investigation of every unmatched international biography. Six qualifying missing people are ready in this batch; remaining names are not certified ineligible.

## New records

| Person | Confirmed custody | Source |
| --- | --- | --- |
| Ellen Thomas | 30 days served | [Evidence](https://law.justia.com/cases/federal/appellate-courts/F2/864/188/239801/) |
| William Thomas Hallenback Jr. | 30 days served | [Evidence](https://law.justia.com/cases/federal/appellate-courts/F2/864/188/239801/) |
| George Lakey | One week jailed; case dismissed | [Evidence](https://docuseek.com/bf-citg) |
| Robert Ellsberg | At least 16 days by May 27, 1978 | [Evidence](https://www.nukeresister.org/2023/06/19/where-were-at-robert-ellsbergs-1978-court-statement-daniel-ellsberg-presente/) |
| Lenni Brenner | 39 months served | [Evidence](https://newsarchive.berkeley.edu/news/berkeleyan/2004/10/14_leaders.shtml) |
| Adam Kokesh | Approximately four months in 2013 | [Evidence](https://www.justice.gov/usao-dc/pr/virginia-man-pleads-guilty-weapons-offenses-stemming-july-4th-incident-freedom-plaza) |

## Identity and case decisions

- **Ed Sanders is already Edward Sanders**, with the 1961 submarine protest described. No second record or replacement biography is created. [Existing profile](http://104.238.162.40/prisoner/edward-sanders). Reports give conflicting duration descriptions: [77-day sentence](https://arthurmag.com/2004/07/26/na-144/), [90 days](https://www.fifthestate.org/archive/57-july-4-18-1968/interview-with-ed-sanders/), and [60 days served](https://beyondthc.com/wp-content/uploads/2013/04/Ed-Sanders-RLS.pdf). The existing uncertainty is preserved.
- **William Thomas Hallenback Jr.** is the White House vigiler, distinct from the existing WWI objector St. William Thomas. The batch uses his full legal name and distinguishing aliases.
- **Lenni Brenner / Lenny Glaser / Leonard Glaser** is distinct from Leon Glaser, the Russian-born Seattle deportation prisoner in 1931. Live biographies were checked. His underlying marijuana case is identified; the allegation of politically motivated intervention is attributed.
- **Adam Kokesh:** the qualifying detention concerns his firearm protest and associated proceedings. His antiwar background is not presented as the charge. Approximate custody is not converted to four exact calendar months.
- Court dates and lower-bound custody statements are not used to invent arrest/release dates. Only Brenner’s documented 39 months is entered as an explicit duration counter. Other terms remain visible in the case text.

## Leads held for further evidence

| Person(s) | Reason not added in this batch |
| --- | --- |
| Noam Chomsky; Jane Fonda | Verified episodes establish overnight/initial detention, not the longer period requested. |
| Martin Sheen; Cindy Sheehan; Carl Sagan; Ann Druyan; Howard Zinn; Ann Wright; David Swanson | Arrest evidence or leads found, but the checked material does not establish qualifying multiple-day US custody. This is not a finding that no such case exists. |
| Cindy Sheehan — apparent two-day result | The two-day custody detail referred to Desiree Fairooz, not Sheehan. Not imported. |
| Concepción Picciotto | A supposed 90-day lead referred to William Thomas. Her own qualifying duration remains unverified. |
| Philip Isely | First-person associate’s account mentions WWII federal imprisonment; exact case and actual duration need stronger verification. |
| Robert Baker Aitken; Kurt Vonnegut | Prisoner-of-war detention by Japan/Germany is not US custody. |
| Michael Ferber | A sentence reversed on appeal does not itself establish time actually served. |
| Peter Yarrow | Known jail term was for sexual abuse, not political or protest activity. No political-prisoner case created from that conviction. |

For the earlier arrest sources, see the [full list audit](peace-activists-missing-profiles-2026-09-10.md). These exclusions apply to the evidence reviewed, not to unknown additional cases.

## Data handling and verification

- Existing profiles, biographies, photos and cases are preserved. Hidden records and aliases participate in duplicate checks; ambiguous identities stop the batch.
- Portrait files are linked to the new record’s public-disk photo field. Original images, crop rectangles, checksums and attribution are in [CREDITS-batch267.md](../photos/CREDITS-batch267.md). No AI image generation or retouching was used.
- Research links remain in the JSON source ledger; no research article is assigned as a personal support website.
- Coordinates use the application’s `lat`/`lng` fields and represent approximate documented case cities, not invented institution addresses.
- Batch shell syntax validated. Isolated in-memory Laravel checks passed **114 assertions** covering preview, application, replay, preservation, alias ambiguity, dates, duration handling, photo links/checksums, cache invalidation and failure rollback.
- Read-only live preview with SQLite `PRAGMA query_only = ON`: **would add 6; existing matches 0; B267-OK**. Production writes were disabled.

## Manual deployment after merge

Apply earlier pending batches in numeric order, then:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-267.sh --dry-run
sudo -u www-data bash database/data/run-batch-267.sh
```

The script prepares application-owned PsySH directories and clears the prisoner API, museum and tracker caches after application. Pulling the files alone does not insert the records.

## US-linked lead-screen inventory

“Screened, no qualifying case confirmed” means this pass found no sufficiently supported addition; it is not an assertion of no imprisonment. Wikipedia is a lead source here, not the sole custody evidence for an inserted case.

| Listed person | This pass |
| --- | --- |
| Abraham Joshua Heschel | Screened, no qualifying missing case confirmed |
| Adam Kokesh | Qualifying missing profile prepared |
| Albert Einstein | Screened, no qualifying missing case confirmed |
| Alfred-Maurice de Zayas | Screened, no qualifying missing case confirmed |
| Alice Herz | Screened, no qualifying missing case confirmed |
| Amanda Deyo | Screened, no qualifying missing case confirmed |
| Amy Goodman | Screened, no qualifying missing case confirmed |
| Andrew Carnegie | Screened, no qualifying missing case confirmed |
| Anita Parkhurst Willcox | Biography retrieval failed; unresolved |
| Ann Druyan | Screened, no qualifying missing case confirmed |
| Ann Fagan Ginger | Screened, no qualifying missing case confirmed |
| Ann Wright | Biography retrieval failed; unresolved |
| Annot | Screened, no qualifying missing case confirmed |
| Arik Ascherman | Screened, no qualifying missing case confirmed |
| Arthur Gish | Screened, no qualifying missing case confirmed |
| Ava Helen Pauling | Screened, no qualifying missing case confirmed |
| B. D. Dykstra | Screened, no qualifying missing case confirmed |
| Benjamin Ferencz | Screened, no qualifying missing case confirmed |
| Benjamin Franklin Trueblood | Screened, no qualifying missing case confirmed |
| Bernie Glassman | Screened, no qualifying missing case confirmed |
| Bhikkhu Bodhi | Screened, no qualifying missing case confirmed |
| Bobby Muller | Screened, no qualifying missing case confirmed |
| Carl Sagan | Screened, no qualifying missing case confirmed |
| Cindy Sheehan | Screened, no qualifying missing case confirmed |
| Coleen Rowley | Screened, no qualifying missing case confirmed |
| Colman McCarthy | Screened, no qualifying missing case confirmed |
| Concepción Picciotto | Biography retrieval failed; unresolved |
| Coretta Scott King | Screened, no qualifying missing case confirmed |
| Crystal Eastman | Screened, no qualifying missing case confirmed |
| Dagmar Wilson | Biography retrieval failed; unresolved |
| Danny Glover | Screened, no qualifying missing case confirmed |
| David Adams | Screened, no qualifying missing case confirmed |
| David Cortright | Screened, no qualifying missing case confirmed |
| David Dean Shulman | Screened, no qualifying missing case confirmed |
| David Loy | Screened, no qualifying missing case confirmed |
| David Swanson | Biography retrieval failed; unresolved |
| David Wylie | Biography retrieval failed; unresolved |
| Dennis Kucinich | Screened, no qualifying missing case confirmed |
| Dorothy Detzer | Screened, no qualifying missing case confirmed |
| Dorothy Granada | Screened, no qualifying missing case confirmed |
| Ed Sanders | Already present as Edward Sanders |
| Edip Yüksel | Biography retrieval failed; unresolved |
| Edward Said | Screened, no qualifying missing case confirmed |
| Eleanor Roosevelt | Screened, no qualifying missing case confirmed |
| Elihu Burritt | Screened, no qualifying missing case confirmed |
| Elise M. Boulding | Screened, no qualifying missing case confirmed |
| Ella Baker | Screened, no qualifying missing case confirmed |
| Ellen Thomas | Qualifying missing profile prepared |
| Emmanuel Charles McCarthy | Screened, no qualifying missing case confirmed |
| Eric Garris | Screened, no qualifying missing case confirmed |
| Eugene McCarthy | Screened, no qualifying missing case confirmed |
| Everett Gendler | Screened, no qualifying missing case confirmed |
| Florence Jaffray Harriman | Screened, no qualifying missing case confirmed |
| Frances Benedict Stewart | Screened, no qualifying missing case confirmed |
| Frank Dorrel | Screened, no qualifying missing case confirmed |
| Frida Berrigan | Screened, no qualifying missing case confirmed |
| G. Simon Harak | Screened, no qualifying missing case confirmed |
| Genevieve Fiore | Screened, no qualifying missing case confirmed |
| George Lakey | Qualifying missing profile prepared |
| George McGovern | Screened, no qualifying missing case confirmed |
| George Winne Jr. | Biography retrieval failed; unresolved |
| Georgia Lloyd | Screened, no qualifying missing case confirmed |
| Great Peacemaker | Screened, no qualifying missing case confirmed |
| H. James Shea Jr. | Screened, no qualifying missing case confirmed |
| Hannah Clothier Hull | Screened, no qualifying missing case confirmed |
| Harry Belafonte | Screened, no qualifying missing case confirmed |
| Hedy Epstein | Screened, no qualifying missing case confirmed |
| Helen Keller | Screened, no qualifying missing case confirmed |
| Heloise Brainerd | Screened, no qualifying missing case confirmed |
| Hiawatha | Screened, no qualifying missing case confirmed |
| Howard Zinn | Screened, no qualifying missing case confirmed |
| Ione Biggs | Screened, no qualifying missing case confirmed |
| J. Edward Guinan | Screened, no qualifying missing case confirmed |
| James Colaianni | Screened, no qualifying missing case confirmed |
| Jane Addams | Screened, no qualifying missing case confirmed |
| Jane Fonda | Screened, no qualifying missing case confirmed |
| Jeanmarie Simpson | Screened, no qualifying missing case confirmed |
| Jeannette Rankin | Screened, no qualifying missing case confirmed |
| Jeff Halper | Screened, no qualifying missing case confirmed |
| Jeff Sharlet | Screened, no qualifying missing case confirmed |
| Jessie Wallace Hughan | Screened, no qualifying missing case confirmed |
| Jimmy Carter | Screened, no qualifying missing case confirmed |
| Jodie Evans | Screened, no qualifying missing case confirmed |
| Jody Williams | Biography retrieval failed; unresolved |
| John Lennon | Screened, no qualifying missing case confirmed |
| John McConnell | Screened, no qualifying missing case confirmed |
| John Mott | Screened, no qualifying missing case confirmed |
| John Wallach | Biography retrieval failed; unresolved |
| Jonathan Schell | Screened, no qualifying missing case confirmed |
| Joseph Polowsky | Screened, no qualifying missing case confirmed |
| Josephine Irwin | Screened, no qualifying missing case confirmed |
| José Argüelles | Biography retrieval failed; unresolved |
| Judith Hand | Screened, no qualifying missing case confirmed |
| Judy Collins | Screened, no qualifying missing case confirmed |
| Julia Ward Howe | Screened, no qualifying missing case confirmed |
| Justin Raimondo | Screened, no qualifying missing case confirmed |
| Kurt Vonnegut | Screened, no qualifying missing case confirmed |
| Larry Hebert | Screened, no qualifying missing case confirmed |
| Laurence Overmire | Screened, no qualifying missing case confirmed |
| Lawrence Ferlinghetti | Screened, no qualifying missing case confirmed |
| Lawrence S. Wittner | Biography retrieval failed; unresolved |
| Lee Lorch | Screened, no qualifying missing case confirmed |
| Lenni Brenner | Qualifying missing profile prepared |
| Lillian Wald | Biography retrieval failed; unresolved |
| Linus Pauling | Screened, no qualifying missing case confirmed |
| Lola Maverick Lloyd | Screened, no qualifying missing case confirmed |
| Lorraine Granado | Screened, no qualifying missing case confirmed |
| Marcia Freedman | Screened, no qualifying missing case confirmed |
| Marcus Raskin | Screened, no qualifying missing case confirmed |
| Margaret Isely | Screened, no qualifying missing case confirmed |
| Marii Hasegawa | Screened, no qualifying missing case confirmed |
| Mark Satin | Screened, no qualifying missing case confirmed |
| Martha Root | Screened, no qualifying missing case confirmed |
| Martin Sheen | Screened, no qualifying missing case confirmed |
| Mary Dingman | Screened, no qualifying missing case confirmed |
| Mary Shapard | Screened, no qualifying missing case confirmed |
| Mary Wilhelmine Williams | Biography retrieval failed; unresolved |
| Medea Benjamin | Screened, no qualifying missing case confirmed |
| Mel Duncan | Screened, no qualifying missing case confirmed |
| Michael D. Knox | Screened, no qualifying missing case confirmed |
| Michael Ferber | Screened, no qualifying missing case confirmed |
| Mubarak Awad | Screened, no qualifying missing case confirmed |
| Murray Rothbard | Screened, no qualifying missing case confirmed |
| Noam Chomsky | Screened, no qualifying missing case confirmed |
| Norma Elizabeth Boyd | Screened, no qualifying missing case confirmed |
| Norman Cousins | Screened, no qualifying missing case confirmed |
| Norman Morrison | Screened, no qualifying missing case confirmed |
| Olympia Brown | Screened, no qualifying missing case confirmed |
| Patrick Reinsborough | Screened, no qualifying missing case confirmed |
| Paul Goodman | Screened, no qualifying missing case confirmed |
| Paul Krassner | Screened, no qualifying missing case confirmed |
| Paul Newman | Screened, no qualifying missing case confirmed |
| Peace Pilgrim | Screened, no qualifying missing case confirmed |
| Peter Brock | Screened, no qualifying missing case confirmed |
| Peter Yarrow | Screened, no qualifying missing case confirmed |
| Phil Ochs | Screened, no qualifying missing case confirmed |
| Philip Isely | Screened, no qualifying missing case confirmed |
| Rachel Corrie | Screened, no qualifying missing case confirmed |
| Ramsey Clark | Screened, no qualifying missing case confirmed |
| Randall Forsberg | Screened, no qualifying missing case confirmed |
| Rhoda Hatch | Screened, no qualifying missing case confirmed |
| Robert Baker Aitken | Screened, no qualifying missing case confirmed |
| Robert Ellsberg | Qualifying missing profile prepared |
| Robert L. Holmes | Screened, no qualifying missing case confirmed |
| Roberta Dunbar | Screened, no qualifying missing case confirmed |
| Roger Allen LaPorte | Screened, no qualifying missing case confirmed |
| Ron Paul | Screened, no qualifying missing case confirmed |
| Ronald Podrow | Screened, no qualifying missing case confirmed |
| Samantha Smith | Screened, no qualifying missing case confirmed |
| Sidney Lens | Screened, no qualifying missing case confirmed |
| Sophonisba Breckinridge | Screened, no qualifying missing case confirmed |
| Staughton Lynd | Screened, no qualifying missing case confirmed |
| Thomas | Qualifying missing profile prepared |
| Thomas Merton | Screened, no qualifying missing case confirmed |
| Tom Fox | Screened, no qualifying missing case confirmed |
| Urbain Ledoux | Screened, no qualifying missing case confirmed |
| Ursula Franklin | Screened, no qualifying missing case confirmed |
| Violet Oakley | Screened, no qualifying missing case confirmed |
| W. E. B. Du Bois | Screened, no qualifying missing case confirmed |
| William Grassie | Screened, no qualifying missing case confirmed |
| William Ladd | Screened, no qualifying missing case confirmed |
| Wilson A. Head | Screened, no qualifying missing case confirmed |
| Woody Guthrie | Screened, no qualifying missing case confirmed |
