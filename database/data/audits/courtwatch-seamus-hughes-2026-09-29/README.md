# Court Watch / Seamus Hughes archive audit

Audit date: 2026-09-29

Source: <https://www.courtwatch.news/authors/seamus-hughes>

## Scope and method

The author archive exposes 145 posts across six API pages. Every post page was downloaded and converted to searchable text. The audit screened both standalone reports and the short items embedded in weekly docket roundups. It also extracted 100 linked Justice Department pages whose URLs indicated terrorism, political violence, protest, election, immigration, espionage, classified-information, or foreign-agent subject matter.

Candidate names were checked against the live prisoner database, including profiles hidden by normal publication scopes. The exact comparison output is preserved in `live-name-check.csv`. The full source inventory is in `all-145-posts.csv`, and the filtered DOJ link set is in `relevant-doj-links.txt`.

The review applied the site's standing custody rule: a person was not treated as a database candidate if the available evidence showed less than 24 hours of detention. Allegations are treated as allegations, and an entry's political nexus is recorded separately from whether the alleged conduct was violent.

## Results

- 145 of 145 archive posts reviewed.
- 100 potentially relevant DOJ links extracted from the body of those posts.
- 72 name variants checked against the live database, representing about 67 people after aliases and duplicate spellings are collapsed.
- Eleven live matches found in this comparison set: Shawn Monper, LaMonica McIver, Christopher Solomon Proctor, Mohammad Yousef Hasna, Muhammad Shahzeb Khan, Kyle Wagner, Maricela Rueda, Cameron Arnold, Savanna Batten, Zachary Evetts, and Elizabeth Soto.
- 36 high-confidence missing people had both a political or ideological nexus and evidence of more than 24 hours of custody. They are listed in `high-confidence-missing.csv` and were imported on September 29, 2026; see `RESULTS.md`.
- A second pass rechecked unresolved defendants, the linked DOJ pages, and custody dispositions that were not established in the newsletter text. It resolved eight additional qualifying profiles: Terrell Bailey-Corsey, Abdullah Haji Zada, Ammaad Akhtar, Dale Britt Bendler, Rex Crofton, Mansuri Manuchekhri, Mark Tucci, and Carter Miles Ledoux. Their records are in `round-two-profiles.json` and the findings are summarized in `ROUND-TWO.md`.

The strongest newly discovered omission is Ahmadzai Abdul Ghafoor. He was held in immigration custody from May 30, 2025, until a federal judge ordered his immediate release on May 13, 2026. Court Watch reported that he said he had protected the U.S.-backed Afghan president and feared Taliban retaliation, while the government would not tell the court whether its suspected-terrorist label or investigation remained active.

The archive also surfaced several completed sentences that are straightforward to date and count, including Douglas Thrams, Tyler Miles Leveque, Alexander Justin White, Gunner Joseph Fisher, Brad Kenneth Spafford, Tina Peters, Nasir Ahmad Tawhedi, Ahmad Khalil Elshazly, Sinmyah Amera Ceasar, Dallas Humber, Awais Chudhary, Mohammed Azharuddin Chhipa, Aws Mohammed Naser, Clift Seferlis, Benjamin Hanil Song, Bradford Morris, and Daniel Rolando Sanchez-Estrada.

## Exclusions and unresolved cases

The following were not placed on the high-confidence list:

- Sam O'Hara was handcuffed for roughly 15 to 20 minutes and released without charges, so the case fails the 24-hour rule.
- Kyle Reynolds was arrested on January 23, 2025, and released the following day. The available reporting does not establish more than 24 hours in custody.
- Protest arrests mentioned only as a group, including several ICE-facility and pro-Palestine cases, were not counted without names and individual custody durations.
- Bingkun Feng's airbase case has an implied national-security angle, but the available article did not establish a political motive or a qualifying custody duration.
- Terrell Bailey-Corsey's unresolved custody span was established during the second pass: he was held from July 18 through August 20, 2025. Mohammad Rafi Mohammadi remains unresolved because the sources reviewed do not establish that his initial detention exceeded 24 hours.
- Several foreign defendants named in sanctions, procurement, and Russian intelligence cases were not counted where the sources did not establish U.S. custody.
- 764 child-exploitation defendants were excluded where the charged conduct was sexual exploitation rather than political activity, even when DOJ used an extremist label.

## Primary verification links for the leading omissions

- Ahmadzai Abdul Ghafoor: <https://www.courtwatch.news/p/judge-orders-known-suspected-terrorist-released>
- Douglas Thrams: <https://www.justice.gov/usao-ndin/pr/milford-man-sentenced-time-served>
- Tyler Miles Leveque: <https://www.justice.gov/usao-nm/pr/albuquerque-man-sentenced-threats-against-president>
- Alexander Justin White: <https://www.justice.gov/usao-ednc/pr/durham-man-sentenced-8-years-federal-prison-conspiring-provide-support-isis-terrorists>
- Forrest Kendall Pemberton: <https://www.justice.gov/opa/pr/florida-man-indicted-attempted-mass-shooting-targeting-jewish-victims>
- Brad Kenneth Spafford: <https://www.justice.gov/usao-edva/pr/smithfield-man-sentenced-eight-years-prison-possessing-over-150-improvised-explosive>
- Brian J. Cole Jr.: <https://www.justice.gov/usao-dc/pr/brian-cole-jr-charged-indictment-planting-explosive-devices-outside-rnc-and-dnc-jan-5>
- Reda Mazen Rida Sabassi: <https://www.justice.gov/opa/pr/san-diego-resident-charged-conspiring-provide-material-support-hamas>
- Mahmoud Amin Ya'qub Al-Muhtadi: <https://www.justice.gov/opa/pr/gaza-man-arrested-alleged-involvement-october-7-2023-terrorist-attacks>
- Catherine Beth Washburn: <https://www.justice.gov/opa/pr/upstate-new-york-woman-arrested-charged-attempting-provide-material-support-palestine>

## Data limitations

Court Watch often links directly to a complaint or docket entry without naming the defendant in its own prose. Those links were retained for follow-up even when a name could not be safely recovered from the HTML. The 36-person list is therefore a conservative floor, not a claim that every potentially relevant linked docket has been fully resolved.

