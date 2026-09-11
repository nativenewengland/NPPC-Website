# Full-book additions and Trident II correction — batches 301–302

Prepared September 10, 2026. Local, tested data batches; **not applied to production** and no PR opened. Fifteen new people bring the unpublished total in batches 297–301 to **50**, toward the user-requested 100-person PR threshold.

## What the batches do

- **301:** add 15 absent identities and 19 distinct cases, with original-book evidence locators. Includes Robert Wollheim’s verified September 21, 2019 death date. Unknown dates, institutions and coordinates remain blank. No portraits are included in these custody batches; the separate caption-identified photo leads remain available for follow-up.
- **302:** correct five Trident II cases; fill Steve Kelly’s missing December 19, 1999 incarceration date and Scott Schaeffer-Duffy’s documented three months served; add five separate cases to existing profiles.
- No existing prisoner biographies, support websites, photographs or other profile fields change. Existing charges and sentence prose remain unchanged. Newly added cases receive their own sourced charge/outcome descriptions.

## Resolving the Trident II conflict

The five current rows have arrest and incarceration September 30, 1984 and release September 30, 1985. The release predates the actual sentencing. **The supported release year is 1986.**

| Person | Correct release | Precision |
|---|---|---|
| Jean Holladay | April 1986 | Month; parole after six months served |
| William Boston | August 1986 | Month |
| Leo Schiff | August 1986 | Month |
| John Pendleton | August 1986 | Month |
| Frank Panopoulos | October 1986 | Month; additional contempt term |

For all five, the action/arrest was **October 1, 1984**, and sentencing was **October 18, 1985**. Laffin and Montgomery, *Swords into Plowshares*, **1987 first edition**, printed pp.38–39 / PDF pp.66–67, explicitly gives these dates and the individual release months. The supplied page was visually inspected. Laffin’s 2003 *Plowshares Disarmament Chronology*, p.22 / PDF p.34, corroborates the action and sentencing, but is not an independent author. The [later support-site chronology](https://kingsbayplowshares7.org/plowshares-history/) is another derivative, not a third independent witness.

The [contemporary Washington Post report](https://www.washingtonpost.com/archive/lifestyle/1986/08/05/conscience-and-the-criminal/7510f097-230e-40d4-8f1c-0b70d869251e/) independently confirms Jean’s April 1986 parole after six months.

No reliable exact release day was found. The Berkeley peace-law index identifies litigation but its PDF retrieval returned no readable document; it is **not used as evidence for these dates**. Prisoner lists can be stale and do not establish release days.

The unsupported continuous custody start is cleared on all five cases. Retaining the old 1984 start while correcting the end would falsely imply uninterrupted imprisonment until 1986. Neither the arrest date nor the sentencing date alone proves when an uninterrupted term began. Jean’s explicitly documented six months are stored in `imprisoned_for_months`; the other four have no invented actual duration. Existing sentence text is preserved. The full before/after field values and record IDs are in batch302.json.

## New prisoner entries

| Person | Cases | Book evidence |
|---|---:|---|
| Robert Wollheim | 1 | pp.18-24; 10_1_Precur.html, Robert (Bob) Wollheim |
| Steve Woolford | 1 | pp.152-158, especially p.153; 13_4_Cathol.html |
| Mike Miles | 2 | pp.280-284; 15_6_Resist.html |
| Hattie Nestel | 1 | pp.289-293, especially p.290; 15_6_Resist.html |
| Joni McCoy | 2 | pp.293-296; 15_6_Resist.html |
| Tom Karlin | 1 | pp.298-302; 15_6_Resist.html |
| Harry Murray | 2 | pp.302-308; 15_6_Resist.html |
| Genevieve Allen | 1 | pp.230-233, especially p.231; 14_5_Resist.html |
| Kim Wahl | 1 | pp.255-263, especially p.261; 15_6_Resist.html |
| Anne S. Hall | 1 | pp.270-275; January 1989 photo caption in 15_6_Resist.html |
| Becky Johnson | 1 | pp.316-320, especially p.318; 16_7_After.html |
| Renaye Fewless | 1 | chapter 7, Renaye Fewless; chap7.html |
| Marian Mollin | 2 | chapter 8, Marian Mollin; chap8.html |
| Tina Busch-Nema | 1 | chapter 6, Tina Busch-Nema; chap6.html |
| Marty Harris | 1 | chapter 3, Marty Harris; chap3.html |

Stated sentence lengths are not automatically treated as time served. Actual months are entered only where the interview expressly describes them: six each for Steve Woolford and Mike Miles, two separate three-month terms for Joni McCoy, and nineteen months for Marty Harris. Other stated day counts are preserved in the case narrative; the existing model recalculates its numeric days field from dates and does not safely support an independent day-only count.

Renaye Fewless’s completion in 2000 and Tina Busch-Nema’s completion in 2007 remain in the case narratives. Their year-only release estimates are not inserted as January 1 endpoints, because the current duration model calculates directly from stored placeholders. This avoids publishing a false zero-day interval or an end before the known March entry. The profiles are historically released; no current detention is asserted. Unknown exact DOBs are not inferred from ages. No modern institution is substituted for an unverified historical facility.

## New cases on existing profiles

| Profiles | Distinct case | Supported custody |
|---|---|---|
| Anne Montgomery, Carol Gilbert, Ardeth Platte, Jackie Hudson | Sacred Earth and Space Plowshares **2000**, Peterson Air Force Base | September 9–16, 2000, El Paso County Jail; charges dropped |
| Larry Morlan | Holy Innocents Pentagon action, 1983 | Sentenced February 17, 1984 to six months; actual DC/Virginia custody, exact release unresolved |

The four 2000 cases are supported by the supplied *Chronology*, pp.76–77 / PDF pp.88–89, including the visually inspected jail/release passage. They are different from the 1998 Gods of Metal and 2002 Sacred Earth and Space II actions. Morlan’s earlier case is supported by his named interview in *Crossing the Line*, chapter 8. Transfers are not separate cases.

**Held for further identification:** Elizabeth Walters already has an undated generic Plowshares row that could overlap the 2000 action, so batch302 preserves it. Existing Carol/Ardeth 1998–2005 intervals, Sprong’s pretrial-versus-sentenced custody, and other Berrigan/Crane/Kelly case conflicts remain listed in the full-books review; this batch does not silently resolve those separate problems. Caption photo leads remain research leads, not attached photos.

## Safety and validation

- Both shell scripts pass syntax checking, and the single-quoted tinker blocks contain no ASCII apostrophes.
- Batch301: **514 assertions** against the real application in an isolated SQLite `:memory:` database. Checks actual-month versus sentence handling, partial dates, 19 separate cases, current-custody flags, preservation of existing identities/aliases/bios, replay, ambiguous identities, invalid fields, and cache invalidation.
- Batch302: **242 assertions** in isolated memory. Checks precise versus month-only dates, unsupported-start removal, seven-day 2000 custody, preservation of every existing profile, preservation of unrelated case fields, replay, newly conflicting dates, newly added/changed episodes, changed ownership, exile/death flags, edited payloads and all-or-nothing failure.
- Read-only live previews confirm **15 missing people**, **7 case updates**, and **5 missing cases**. Production uses SQLite query-only mode for previews. Test harnesses discard every production connection before test writes; caches/files are faked. No production rows or media changed.
- Batch302 pins the reviewed payload hash and expected prior values. It preserves newly edited data by stopping on conflicts. Existing case lists and episode details are checked to prevent duplicates; normal Eloquent-generated UUIDs are used. Successful replays make no changes.
- Both batches follow the repository’s manual batch deployment workflow and invalidate the API cache after changes. No migrations and no direct production edits.

After the eventual 100-person PR is merged and preceding batches have been applied in order, run the new scripts as the application owner from `/var/www/NPPC-Website`. These batches are not yet a deployed or merged change.

See `full-books-source-manifest-2026-09-10.json` for the original full-book hashes, and the JSON payloads for per-person/per-case source locators.
