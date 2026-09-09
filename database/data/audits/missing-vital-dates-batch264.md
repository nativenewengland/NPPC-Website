# Missing birth and death dates — batch 264

Research snapshot: 2026-09-09. This batch fills **162 empty date fields for 100 existing public prisoner profiles: 80 dates of birth and 82 dates of death**. There are 62 people receiving both dates, 18 receiving only a birth date, and 20 receiving only a death date.

## Scope and method

A read-only inventory contained 8,614 prisoner records, including 5,581 without a birth date and 7,455 without a death date. The latter includes living people; it is not a count of missing death dates that should be filled. These inventory totals include records outside the public selection.

Candidate identities were checked against the existing profiles using occupations, organizations and case context. The final payload identifies each profile by UUID, slug and exact current name and includes a short research identity note. That note is evidence for reviewers, not a replacement biography.

Most dates are supported by the linked Wikipedia biography revisions; these are secondary sources, pinned to the revisions examined. Selected dates and conflicts were additionally checked against institutional biographies, family notices and historical records, including Stanford's King Institute, PBS, the International Action Center, a Sacramento Bee memorial notice, the LaSalle County Genealogy Guild, the Mississippi United Methodist Conference journal and the SNCC Digital Gateway. The payload records which field each source supports. No generated birth or death dates are used.

Every date in this batch has a documented year, month and day. Year-only, month-only, approximate and disputed values were omitted from this batch rather than expanded into invented full dates. A blank cell below means that field is not being changed; it does not assert the person is living or that the date is unknown everywhere.

## Disagreements and exclusions

- **Rosaura Revueltas:** conflicting birth years; only the death date is included.
- **C. K. Steele:** Stanford lists February 7, whereas the Wikipedia biography lists February 17; only the agreed death date is included.
- **Mugo Gatheru:** the Wikipedia infobox and prose disagree about November/December. The Sacramento Bee anniversary notice confirms death on November 27, 2011; that date is used.
- **John Hossack:** the Wikipedia infobox and prose disagree about November/December. The LaSalle County Genealogy Guild's historical reprint confirms November 8, 1891; that date is used.
- **Eunice Dana Brannan:** conflicting November death days; only the consistent birth date is included.
- **Paul Robeson:** a National Archives page heading gives a conflicting death day. PBS and the Los Angeles Times agree on January 23, 1976; that date is used.
- **Connie Matthews, Harrison George, Francisco Carreón and Gertrude Crocker:** no sufficiently precise death date accepted; birth dates only.
- **Sam Darcy, Peter Van Schaack and Stephen R. Mallory:** no sufficiently precise birth date accepted; death dates only.
- **Adolph Fischer and Mary Moylan:** excluded from this batch because the proposed missing dates were incomplete or conflicted.
- **Franz Bopp, Pat Chambers and Jasmine Richards:** rejected search matches concerned different people. None is included.
- **Lynne Stewart and Bill Epton:** existing populated birth dates differ from the researched biographies. Those populated values are deliberately preserved; only their missing death dates are added. Corrections require a separate review.

## Preservation and validation

The runner fills only raw NULL/empty birthdate or death_date values and updates precision metadata only for fields actually filled. Existing full dates, partial dates and their precision survive unchanged. It does not edit biographies, portraits, support websites, cases or custody/release flags, or create people. Guarded query-level Eloquent updates bypass model saving hooks that could otherwise alter unrelated fields. Normal updated_at timestamps change only on rows receiving dates.

The runner validates the batch identity, counts, date components, source coverage, unique identities, public-review status and birth/death chronology before applying any update. All writes are in one transaction; original date and precision values are guarded against concurrent edits. API and museum caches are invalidated only after a successful write. Dry runs do not change database records or invalidate caches.

Validation completed on 2026-09-09:

- PHP and Bash syntax checks passed; the single-quoted Tinker block contains zero apostrophes.
- A read-only live preview reported exactly 100 rows and 162 missing date fields. Selected raw records were identical before and after the preview.
- The committed `check-batch264.php` test ran against disposable in-memory SQLite with the application's Laravel models and an in-memory cache: all 162 stored dates, date precision, untouched fields, both cache invalidations, and no-op replay including timestamps passed.
- Preservation checks passed for populated full/partial dates and for an existing birth-year precision alongside a newly filled death date.
- Identity mismatch, under-review status, impossible dates, disallowed prose fields, duplicate profiles, missing sources and invalid chronology all failed without changes.
- An injected failure on the last update rolled back earlier updates, demonstrating transaction atomicity.

The test was transmitted in memory to use the existing PHP/Laravel runtime because local PHP/vendor dependencies were unavailable. Test writes used only disposable SQLite and cache stores. Production has not been updated.

## Deploy after merge

Apply earlier pending numbered batches first. Then, on the server:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-264.sh --dry-run
sudo -u www-data bash database/data/run-batch-264.sh
```

The runner uses application-owned PsySH configuration directories to avoid the unwritable `/var/www/.config/psysh` problem. No migration is needed. Replay is safe; dates filled since the snapshot will be preserved and reduce the update count.

To repeat the isolated checks after deployment: `php database/data/audits/check-batch264.php`.

## Proposed additions

| # | Existing profile | Birth date to fill | Death date to fill | Sources |
|---|---|---|---|---|
| 1 | [Lynne Stewart](http://104.238.162.40/prisoner/lynne-stewart) | — | 2017-03-07 | [International Action Center: family death announcement](https://iacenter.org/2017/03/09/stewart/); [Wikipedia: Lynne Stewart](https://en.wikipedia.org/w/index.php?title=Lynne_Stewart&oldid=1371572877) |
| 2 | [Martin Sostre](http://104.238.162.40/prisoner/martin-sostre) | — | 2015-08-12 | [Wikipedia: Martin Sostre](https://en.wikipedia.org/w/index.php?title=Martin_Sostre&oldid=1368565497) |
| 3 | [Julian Heicklen](http://104.238.162.40/prisoner/julian-heicklen) | — | 2022-03-11 | [Wikipedia: Julian Heicklen](https://en.wikipedia.org/w/index.php?title=Julian_Heicklen&oldid=1358040499) |
| 4 | [Sanyika Shakur](http://104.238.162.40/prisoner/sanyika-shakur) | 1963-11-13 | 2021-06-06 | [Wikipedia: Sanyika Shakur](https://en.wikipedia.org/w/index.php?title=Sanyika_Shakur&oldid=1372028051) |
| 5 | [Philip Agee](http://104.238.162.40/prisoner/philip-agee) | — | 2008-01-07 | [Wikipedia: Philip Agee](https://en.wikipedia.org/w/index.php?title=Philip_Agee&oldid=1372825924) |
| 6 | [Edwin P. Wilson](http://104.238.162.40/prisoner/edwin-p-wilson) | — | 2012-09-10 | [Wikipedia: Edwin P. Wilson](https://en.wikipedia.org/w/index.php?title=Edwin_P._Wilson&oldid=1362296603) |
| 7 | [Sal Castro](http://104.238.162.40/prisoner/sal-castro) | — | 2013-04-15 | [Wikipedia: Sal Castro](https://en.wikipedia.org/w/index.php?title=Sal_Castro&oldid=1354457393) |
| 8 | [Ron Porambo](http://104.238.162.40/prisoner/ron-porambo) | — | 2006-10-22 | [Wikipedia: Ron Porambo](https://en.wikipedia.org/w/index.php?title=Ron_Porambo&oldid=1372567229) |
| 9 | [Daniel Berrigan](http://104.238.162.40/prisoner/daniel-berrigan) | — | 2016-04-30 | [Wikipedia: Daniel Berrigan](https://en.wikipedia.org/w/index.php?title=Daniel_Berrigan&oldid=1371904037) |
| 10 | [Philip Berrigan](http://104.238.162.40/prisoner/philip-berrigan) | — | 2002-12-06 | [Wikipedia: Philip Berrigan](https://en.wikipedia.org/w/index.php?title=Philip_Berrigan&oldid=1372768310) |
| 11 | [Bill Epton](http://104.238.162.40/prisoner/bill-epton) | — | 2002-01-23 | [Wikipedia: Bill Epton](https://en.wikipedia.org/w/index.php?title=Bill_Epton&oldid=1321550610) |
| 12 | [Robert F. Williams](http://104.238.162.40/prisoner/robert-f-williams) | — | 1996-10-15 | [Wikipedia: Robert F. Williams](https://en.wikipedia.org/w/index.php?title=Robert_F._Williams&oldid=1366573950) |
| 13 | [William Worthy](http://104.238.162.40/prisoner/william-worthy) | — | 2014-05-04 | [Wikipedia: William Worthy](https://en.wikipedia.org/w/index.php?title=William_Worthy&oldid=1368408076) |
| 14 | [Robert Klonsky](http://104.238.162.40/prisoner/robert-klonsky) | — | 2002-09-07 | [Wikipedia: Robert Klonsky](https://en.wikipedia.org/w/index.php?title=Robert_Klonsky&oldid=1373561565) |
| 15 | [Igal Roodenko](http://104.238.162.40/prisoner/igal-roodenko) | — | 1991-04-28 | [Wikipedia: Igal Roodenko](https://en.wikipedia.org/w/index.php?title=Igal_Roodenko&oldid=1372586011) |
| 16 | [Robert Lowell](http://104.238.162.40/prisoner/robert-lowell) | — | 1977-09-12 | [Wikipedia: Robert Lowell](https://en.wikipedia.org/w/index.php?title=Robert_Lowell&oldid=1371549111) |
| 17 | [Ezra Heywood](http://104.238.162.40/prisoner/ezra-heywood) | 1829-09-29 | 1893-05-22 | [Wikipedia: Ezra Heywood](https://en.wikipedia.org/w/index.php?title=Ezra_Heywood&oldid=1371980422) |
| 18 | [August Spies](http://104.238.162.40/prisoner/august-spies) | 1855-12-10 | — | [Wikipedia: August Spies](https://en.wikipedia.org/w/index.php?title=August_Spies&oldid=1369734304) |
| 19 | [Albert Parsons](http://104.238.162.40/prisoner/albert-parsons) | 1848-06-20 | — | [Wikipedia: Albert Parsons](https://en.wikipedia.org/w/index.php?title=Albert_Parsons&oldid=1370228995) |
| 20 | [George Engel](http://104.238.162.40/prisoner/george-engel) | 1836-04-15 | — | [Wikipedia: George Engel](https://en.wikipedia.org/w/index.php?title=George_Engel&oldid=1369734462) |
| 21 | [Samuel Fielden](http://104.238.162.40/prisoner/samuel-fielden) | 1847-02-25 | 1922-02-07 | [Wikipedia: Samuel Fielden](https://en.wikipedia.org/w/index.php?title=Samuel_Fielden&oldid=1369735335) |
| 22 | [Michael Schwab](http://104.238.162.40/prisoner/michael-schwab) | 1853-08-09 | 1898-06-29 | [Wikipedia: Michael Schwab](https://en.wikipedia.org/w/index.php?title=Michael_Schwab&oldid=1369735288) |
| 23 | [Oscar Neebe](http://104.238.162.40/prisoner/oscar-neebe) | 1850-07-12 | 1916-04-22 | [Wikipedia: Oscar Neebe](https://en.wikipedia.org/w/index.php?title=Oscar_Neebe&oldid=1369786104) |
| 24 | [Dwight Armstrong](http://104.238.162.40/prisoner/dwight-armstrong) | 1951-08-29 | — | [Wikipedia: Dwight Armstrong](https://en.wikipedia.org/w/index.php?title=Dwight_Armstrong&oldid=1363119612) |
| 25 | [Theodore Kaczynski](http://104.238.162.40/prisoner/theodore-kaczynski) | 1942-05-22 | — | [Wikipedia: Ted Kaczynski](https://en.wikipedia.org/w/index.php?title=Ted_Kaczynski&oldid=1372800985) |
| 26 | [Franz von Rintelen](http://104.238.162.40/prisoner/franz-von-rintelen) | 1878-08-19 | — | [Wikipedia: Franz von Rintelen](https://en.wikipedia.org/w/index.php?title=Franz_von_Rintelen&oldid=1353495279) |
| 27 | [Ludwig E. Katterfeld](http://104.238.162.40/prisoner/ludwig-e-katterfeld) | 1881-07-15 | 1974-12-11 | [Wikipedia: L. E. Katterfeld](https://en.wikipedia.org/w/index.php?title=L._E._Katterfeld&oldid=1364856043) |
| 28 | [Israel Amter](http://104.238.162.40/prisoner/israel-amter) | 1881-03-26 | 1954-11-24 | [Wikipedia: Israel Amter](https://en.wikipedia.org/w/index.php?title=Israel_Amter&oldid=1372515364) |
| 29 | [Al Richmond](http://104.238.162.40/prisoner/al-richmond) | 1913-11-17 | 1987-11-09 | [Wikipedia: Al Richmond](https://en.wikipedia.org/w/index.php?title=Al_Richmond&oldid=1371330062) |
| 30 | [Rosaura Revueltas](http://104.238.162.40/prisoner/rosaura-revueltas) | — | 1996-04-30 | [Wikipedia: Rosaura Revueltas](https://en.wikipedia.org/w/index.php?title=Rosaura_Revueltas&oldid=1371826110) |
| 31 | [Emanuel Fried](http://104.238.162.40/prisoner/emanuel-fried) | 1913-03-01 | 2011-02-25 | [Wikipedia: Emanuel Fried](https://en.wikipedia.org/w/index.php?title=Emanuel_Fried&oldid=1370593666) |
| 32 | [Irving Peress](http://104.238.162.40/prisoner/irving-peress) | 1917-07-31 | 2014-11-13 | [Wikipedia: Irving Peress](https://en.wikipedia.org/w/index.php?title=Irving_Peress&oldid=1353409199) |
| 33 | [James E. Jackson](http://104.238.162.40/prisoner/james-e-jackson) | 1914-11-29 | 2007-09-01 | [Wikipedia: James E. Jackson](https://en.wikipedia.org/w/index.php?title=James_E._Jackson&oldid=1372403356) |
| 34 | [Joseph Starobin](http://104.238.162.40/prisoner/joseph-starobin) | 1913-12-19 | 1976-11-06 | [Wikipedia: Joseph Starobin](https://en.wikipedia.org/w/index.php?title=Joseph_Starobin&oldid=1344948616) |
| 35 | [Wendell Furry](http://104.238.162.40/prisoner/wendell-furry) | 1907-02-18 | 1984-12-17 | [Wikipedia: Wendell H. Furry](https://en.wikipedia.org/w/index.php?title=Wendell_H._Furry&oldid=1372341849) |
| 36 | [Leon J. Kamin](http://104.238.162.40/prisoner/leon-j-kamin) | 1927-12-29 | 2017-12-22 | [Wikipedia: Leon Kamin](https://en.wikipedia.org/w/index.php?title=Leon_Kamin&oldid=1370545227) |
| 37 | [Arthur Miller](http://104.238.162.40/prisoner/arthur-miller) | 1915-10-17 | 2005-02-10 | [Wikipedia: Arthur Miller](https://en.wikipedia.org/w/index.php?title=Arthur_Miller&oldid=1372668924) |
| 38 | [Pete Seeger](http://104.238.162.40/prisoner/pete-seeger) | 1919-05-03 | 2014-01-27 | [Wikipedia: Pete Seeger](https://en.wikipedia.org/w/index.php?title=Pete_Seeger&oldid=1371565715) |
| 39 | [George Tyne](http://104.238.162.40/prisoner/george-tyne) | 1917-02-06 | 2008-03-07 | [Wikipedia: George Tyne](https://en.wikipedia.org/w/index.php?title=George_Tyne&oldid=1362606813) |
| 40 | [Paul Robeson](http://104.238.162.40/prisoner/paul-robeson) | 1898-04-09 | 1976-01-23 | [PBS American Masters: Paul Robeson](https://www.pbs.org/wnet/americanmasters/masters/paul-robeson/); [Los Angeles Times: Paul Robeson obituary](https://projects.latimes.com/hollywood/star-walk/paul-robeson/); [Wikipedia: Paul Robeson](https://en.wikipedia.org/w/index.php?title=Paul_Robeson&oldid=1373583277) |
| 41 | [Harold I. Cammer](http://104.238.162.40/prisoner/harold-i-cammer) | 1909-06-18 | 1995-10-21 | [Wikipedia: Harold I. Cammer](https://en.wikipedia.org/w/index.php?title=Harold_I._Cammer&oldid=1361802630) |
| 42 | [E. D. Nixon](http://104.238.162.40/prisoner/e-d-nixon) | 1899-07-12 | 1987-02-25 | [Stanford King Institute: Edgar Daniel Nixon](https://kinginstitute.stanford.edu/nixon-edgar-daniel); [Wikipedia: E. D. Nixon](https://en.wikipedia.org/w/index.php?title=E._D._Nixon&oldid=1368741924) |
| 43 | [C. K. Steele](http://104.238.162.40/prisoner/c-k-steele) | — | 1980-08-19 | [Stanford King Institute: Charles Kenzie Steele](https://kinginstitute.stanford.edu/steele-charles-kenzie); [Wikipedia: Charles Kenzie Steele](https://en.wikipedia.org/w/index.php?title=Charles_Kenzie_Steele&oldid=1357571677) |
| 44 | [Abraham Flaxer](http://104.238.162.40/prisoner/abraham-flaxer) | 1904-09-12 | 1989-01-11 | [Wikipedia: Abram Flaxer](https://en.wikipedia.org/w/index.php?title=Abram_Flaxer&oldid=1356089488) |
| 45 | [Guy Carawan](http://104.238.162.40/prisoner/guy-carawan) | 1927-07-28 | 2015-05-02 | [Wikipedia: Guy Carawan](https://en.wikipedia.org/w/index.php?title=Guy_Carawan&oldid=1345375782) |
| 46 | [A. D. King](http://104.238.162.40/prisoner/a-d-king) | 1930-07-30 | 1969-07-21 | [Stanford King Institute: Alfred Daniel Williams King](https://kinginstitute.stanford.edu/king-alfred-daniel-williams); [Wikipedia: Alfred Daniel King](https://en.wikipedia.org/w/index.php?title=Alfred_Daniel_King&oldid=1342587687) |
| 47 | [Medgar Evers](http://104.238.162.40/prisoner/medgar-evers) | 1925-07-02 | 1963-06-12 | [Wikipedia: Medgar Evers](https://en.wikipedia.org/w/index.php?title=Medgar_Evers&oldid=1373036610) |
| 48 | [James Farmer](http://104.238.162.40/prisoner/james-farmer) | 1920-01-12 | 1999-07-09 | [Stanford King Institute: James Farmer](https://kinginstitute.stanford.edu/farmer-james); [Wikipedia: James Farmer](https://en.wikipedia.org/w/index.php?title=James_Farmer&oldid=1373904367) |
| 49 | [Terry Pettus](http://104.238.162.40/prisoner/terry-pettus) | 1904-08-15 | 1984-10-06 | [Wikipedia: Terry Pettus](https://en.wikipedia.org/w/index.php?title=Terry_Pettus&oldid=1348619618) |
| 50 | [Norman Tallentire](http://104.238.162.40/prisoner/norman-tallentire) | 1886-10-10 | 1953-11-08 | [Wikipedia: Norman Tallentire](https://en.wikipedia.org/w/index.php?title=Norman_Tallentire&oldid=1357424682) |
| 51 | [Mugo Gatheru](http://104.238.162.40/prisoner/mugo-gatheru) | 1925-08-21 | 2011-11-27 | [Sacramento Bee / Legacy: Reuel Mugo-Gatheru memorial](https://www.legacy.com/obituaries/name/reuel-mugo-gatheru-obituary?pid=161273485); [Wikipedia: Mugo Gatheru](https://en.wikipedia.org/w/index.php?title=Mugo_Gatheru&oldid=1372345971) |
| 52 | [Connie Matthews](http://104.238.162.40/prisoner/connie-matthews) | 1943-08-03 | — | [Wikipedia: Connie Matthews](https://en.wikipedia.org/w/index.php?title=Connie_Matthews&oldid=1370674652) |
| 53 | [Thomas A. R. Nelson](http://104.238.162.40/prisoner/thomas-a-r-nelson) | 1812-03-19 | 1873-08-24 | [Wikipedia: Thomas A. R. Nelson](https://en.wikipedia.org/w/index.php?title=Thomas_A._R._Nelson&oldid=1364808176) |
| 54 | [Pablo Manlapit](http://104.238.162.40/prisoner/pablo-manlapit) | 1891-01-17 | 1969-04-15 | [Wikipedia: Pablo Manlapit](https://en.wikipedia.org/w/index.php?title=Pablo_Manlapit&oldid=1371795284) |
| 55 | [Ella Reeve Bloor](http://104.238.162.40/prisoner/ella-reeve-bloor) | 1862-07-08 | 1951-08-10 | [Wikipedia: Ella Reeve Bloor](https://en.wikipedia.org/w/index.php?title=Ella_Reeve_Bloor&oldid=1368421182) |
| 56 | [Vera Buch](http://104.238.162.40/prisoner/vera-buch) | 1895-08-19 | 1987-09-06 | [Wikipedia: Vera Buch](https://en.wikipedia.org/w/index.php?title=Vera_Buch&oldid=1372244472) |
| 57 | [Sam Darcy](http://104.238.162.40/prisoner/sam-darcy) | — | 2005-11-08 | [Wikipedia: Samuel Adams Darcy](https://en.wikipedia.org/w/index.php?title=Samuel_Adams_Darcy&oldid=1355877058) |
| 58 | [Elaine Black](http://104.238.162.40/prisoner/elaine-black) | 1906-09-04 | 1988-05-29 | [Wikipedia: Elaine Black Yoneda](https://en.wikipedia.org/w/index.php?title=Elaine_Black_Yoneda&oldid=1371277221) |
| 59 | [Louis Budenz](http://104.238.162.40/prisoner/louis-budenz) | 1891-07-17 | 1972-04-27 | [Wikipedia: Louis F. Budenz](https://en.wikipedia.org/w/index.php?title=Louis_F._Budenz&oldid=1371752546) |
| 60 | [Bill Gebert](http://104.238.162.40/prisoner/bill-gebert) | 1895-07-22 | 1986-02-13 | [Wikipedia: Bolesław Gebert](https://en.wikipedia.org/w/index.php?title=Boles%C5%82aw_Gebert&oldid=1371890597) |
| 61 | [Josephine Johnson](http://104.238.162.40/prisoner/josephine-johnson) | 1910-06-20 | 1990-02-27 | [Wikipedia: Josephine Johnson](https://en.wikipedia.org/w/index.php?title=Josephine_Johnson&oldid=1371217876) |
| 62 | [Charles Krumbein](http://104.238.162.40/prisoner/charles-krumbein) | 1889-02-10 | 1947-01-20 | [Wikipedia: Charles Krumbein](https://en.wikipedia.org/w/index.php?title=Charles_Krumbein&oldid=1369709664) |
| 63 | [Peter Van Schaack](http://104.238.162.40/prisoner/peter-van-schaack) | — | 1832-09-17 | [Wikipedia: Peter van Schaack](https://en.wikipedia.org/w/index.php?title=Peter_van_Schaack&oldid=1372669569) |
| 64 | [Stephen Bingham](http://104.238.162.40/prisoner/stephen-bingham) | 1942-04-23 | — | [Wikipedia: Stephen Bingham](https://en.wikipedia.org/w/index.php?title=Stephen_Bingham&oldid=1363069108) |
| 65 | [John L. Spivak](http://104.238.162.40/prisoner/john-l-spivak) | 1897-06-13 | 1981-09-30 | [Wikipedia: John L. Spivak](https://en.wikipedia.org/w/index.php?title=John_L._Spivak&oldid=1372723354) |
| 66 | [Hosea Williams](http://104.238.162.40/prisoner/hosea-williams) | 1926-01-05 | 2000-11-16 | [Stanford King Institute: Hosea Williams](https://kinginstitute.stanford.edu/williams-hosea); [Wikipedia: Hosea Williams](https://en.wikipedia.org/w/index.php?title=Hosea_Williams&oldid=1362348330) |
| 67 | [Michael ZinZun](http://104.238.162.40/prisoner/michael-zinzun) | 1949-02-14 | 2006-07-09 | [Wikipedia: Michael Zinzun](https://en.wikipedia.org/w/index.php?title=Michael_Zinzun&oldid=1336675738) |
| 68 | [Rommie Loudd](http://104.238.162.40/prisoner/rommie-loudd) | 1933-06-08 | 1998-05-09 | [Wikipedia: Rommie Loudd](https://en.wikipedia.org/w/index.php?title=Rommie_Loudd&oldid=1369737812) |
| 69 | [John Hossack](http://104.238.162.40/prisoner/john-hossack) | 1806-12-06 | 1891-11-08 | [LaSalle County Genealogy Guild, March-April 2018](https://www.lscgg.org/pdfs/LSCGG_GV_Mar-Apr2018.pdf); [Wikipedia: John Hossack](https://en.wikipedia.org/w/index.php?title=John_Hossack&oldid=1341447298) |
| 70 | [Andrew Humphreys](http://104.238.162.40/prisoner/andrew-humphreys) | 1821-03-30 | 1904-06-14 | [Wikipedia: Andrew Humphreys](https://en.wikipedia.org/w/index.php?title=Andrew_Humphreys&oldid=1373214844) |
| 71 | [John J. Ballam](http://104.238.162.40/prisoner/john-j-ballam) | 1882-06-09 | 1954-09-26 | [Wikipedia: John J. Ballam](https://en.wikipedia.org/w/index.php?title=John_J._Ballam&oldid=1365713890) |
| 72 | [Harrison George](http://104.238.162.40/prisoner/harrison-george) | 1888-06-27 | — | [Wikipedia: Harrison George](https://en.wikipedia.org/w/index.php?title=Harrison_George&oldid=1371667720) |
| 73 | [Clement C. Clay Jr.](http://104.238.162.40/prisoner/clement-c-clay-jr) | 1816-12-13 | 1882-01-03 | [Wikipedia: Clement Claiborne Clay](https://en.wikipedia.org/w/index.php?title=Clement_Claiborne_Clay&oldid=1373181927) |
| 74 | [Stephen R. Mallory](http://104.238.162.40/prisoner/stephen-r-mallory) | — | 1873-11-09 | [Wikipedia: Stephen Mallory](https://en.wikipedia.org/w/index.php?title=Stephen_Mallory&oldid=1348087073) |
| 75 | [Samuel Austin Worcester](http://104.238.162.40/prisoner/samuel-austin-worcester) | 1798-01-19 | 1859-04-20 | [Wikipedia: Samuel Worcester](https://en.wikipedia.org/w/index.php?title=Samuel_Worcester&oldid=1372845939) |
| 76 | [Isabelo de los Reyes](http://104.238.162.40/prisoner/isabelo-de-los-reyes) | 1864-07-07 | 1938-10-10 | [Wikipedia: Isabelo de los Reyes](https://en.wikipedia.org/w/index.php?title=Isabelo_de_los_Reyes&oldid=1373381062) |
| 77 | [Dominador Gómez](http://104.238.162.40/prisoner/dominador-gomez) | 1866-11-04 | 1930-05-14 | [Wikipedia: Dominador Gómez](https://en.wikipedia.org/w/index.php?title=Dominador_G%C3%B3mez&oldid=1370161877) |
| 78 | [Francisco Carreón](http://104.238.162.40/prisoner/francisco-carreon) | 1868-10-05 | — | [Wikipedia: Francisco Carreón](https://en.wikipedia.org/w/index.php?title=Francisco_Carre%C3%B3n&oldid=1366846216) |
| 79 | [Práxedis G. Guerrero](http://104.238.162.40/prisoner/praxedis-g-guerrero) | 1882-08-28 | 1910-12-30 | [Wikipedia: Práxedis Guerrero](https://en.wikipedia.org/w/index.php?title=Pr%C3%A1xedis_Guerrero&oldid=1362028487) |
| 80 | [Leon Czolgosz](http://104.238.162.40/prisoner/leon-czolgosz) | 1873-05-05 | 1901-10-29 | [Wikipedia: Leon Czolgosz](https://en.wikipedia.org/w/index.php?title=Leon_Czolgosz&oldid=1373586977) |
| 81 | [William Monroe Trotter](http://104.238.162.40/prisoner/william-monroe-trotter) | 1872-04-07 | 1934-04-07 | [Wikipedia: William Monroe Trotter](https://en.wikipedia.org/w/index.php?title=William_Monroe_Trotter&oldid=1372660209) |
| 82 | [Rose Winslow](http://104.238.162.40/prisoner/rose-winslow) | 1889-12-15 | 1934-04-16 | [Wikipedia: Ruza Wenclawska](https://en.wikipedia.org/w/index.php?title=Ruza_Wenclawska&oldid=1366377068) |
| 83 | [Eunice Dana Brannan](http://104.238.162.40/prisoner/eunice-dana-brannan) | 1854-08-27 | — | [Wikipedia: Eunice Dana Brannan](https://en.wikipedia.org/w/index.php?title=Eunice_Dana_Brannan&oldid=1355524064) |
| 84 | [Lucy Gwynne Branham](http://104.238.162.40/prisoner/lucy-gwynne-branham) | 1892-04-29 | 1966-07-18 | [Wikipedia: Lucy Gwynne Branham](https://en.wikipedia.org/w/index.php?title=Lucy_Gwynne_Branham&oldid=1372235260) |
| 85 | [Louise Bryant](http://104.238.162.40/prisoner/louise-bryant) | 1885-12-05 | 1936-01-06 | [Wikipedia: Louise Bryant](https://en.wikipedia.org/w/index.php?title=Louise_Bryant&oldid=1373786300) |
| 86 | [Gertrude Crocker](http://104.238.162.40/prisoner/gertrude-crocker) | 1884-01-07 | — | [Wikipedia: Gertrude Crocker](https://en.wikipedia.org/w/index.php?title=Gertrude_Crocker&oldid=1336345604) |
| 87 | [Omali Yeshitela](http://104.238.162.40/prisoner/omali-yeshitela) | 1941-10-09 | — | [Wikipedia: Omali Yeshitela](https://en.wikipedia.org/w/index.php?title=Omali_Yeshitela&oldid=1366218525) |
| 88 | [Tommy Sheridan](http://104.238.162.40/prisoner/tommy-sheridan) | 1964-03-07 | — | [Wikipedia: Tommy Sheridan](https://en.wikipedia.org/w/index.php?title=Tommy_Sheridan&oldid=1373862249) |
| 89 | [Stellan Vinthagen](http://104.238.162.40/prisoner/stellan-vinthagen) | 1964-10-13 | — | [Wikipedia: Stellan Vinthagen](https://en.wikipedia.org/w/index.php?title=Stellan_Vinthagen&oldid=1328270858) |
| 90 | [Joan Little](http://104.238.162.40/prisoner/joan-little) | 1954-05-08 | — | [Wikipedia: Joan Little](https://en.wikipedia.org/w/index.php?title=Joan_Little&oldid=1373147100) |
| 91 | [Louisine Havemeyer](http://104.238.162.40/prisoner/louisine-havemeyer) | 1855-07-28 | 1929-01-06 | [Wikipedia: Louisine Havemeyer](https://en.wikipedia.org/w/index.php?title=Louisine_Havemeyer&oldid=1368131359) |
| 92 | [Elsie Hill](http://104.238.162.40/prisoner/elsie-hill) | 1883-09-23 | 1970-08-06 | [Wikipedia: Elsie Hill](https://en.wikipedia.org/w/index.php?title=Elsie_Hill&oldid=1359513715) |
| 93 | [Hazel Hunkins](http://104.238.162.40/prisoner/hazel-hunkins) | 1890-06-06 | 1982-05-17 | [Wikipedia: Hazel Hunkins Hallinan](https://en.wikipedia.org/w/index.php?title=Hazel_Hunkins_Hallinan&oldid=1372396537) |
| 94 | [Nell Mercer](http://104.238.162.40/prisoner/nell-mercer) | 1893-08-13 | 1979-09-30 | [Wikipedia: Nell Mercer](https://en.wikipedia.org/w/index.php?title=Nell_Mercer&oldid=1328386801) |
| 95 | [Dr. Caroline E. Spencer](http://104.238.162.40/prisoner/dr-caroline-e-spencer) | 1861-10-30 | 1928-09-16 | [Wikipedia: Caroline Spencer (suffragist)](https://en.wikipedia.org/w/index.php?title=Caroline_Spencer_%28suffragist%29&oldid=1350369723) |
| 96 | [Iris Calderhead Walker](http://104.238.162.40/prisoner/iris-calderhead-walker) | 1889-01-03 | 1966-03-06 | [Wikipedia: Iris Calderhead](https://en.wikipedia.org/w/index.php?title=Iris_Calderhead&oldid=1372251424) |
| 97 | [Michael J. Audain](http://104.238.162.40/prisoner/michael-j-audain) | 1937-07-31 | — | [Wikipedia: Michael Audain](https://en.wikipedia.org/w/index.php?title=Michael_Audain&oldid=1372187716) |
| 98 | [Jimmie Travis](http://104.238.162.40/prisoner/jimmie-travis) | 1942-10-08 | 2009-07-28 | [United Methodist Church: Mississippi Conference Journal 2010, page 217](https://www.mississippi-umc.org/files/fileshare/2010msmethodistconferencejournalvol.1.pdf); [Civil Rights Movement Veterans: Jimmie Travis memorial](https://www.crmvet.org/mem/travisj.htm) |
| 99 | [William E. Harbour](http://104.238.162.40/prisoner/william-e-harbour) | 1942-01-09 | 2020-08-27 | [Wikipedia: William E. Harbour](https://en.wikipedia.org/w/index.php?title=William_E._Harbour&oldid=1340027419); [Wikipedia: William Harbour](https://en.wikipedia.org/w/index.php?title=William_Harbour&oldid=1023517111) |
| 100 | [David J. Dennis Sr.](http://104.238.162.40/prisoner/david-j-dennis-sr) | 1940-10-17 | — | [SNCC Legacy Project / Duke University: Dave Dennis](https://snccdigital.org/people/dave-dennis/); [Wikipedia: David Dennis](https://en.wikipedia.org/w/index.php?title=David_Dennis&oldid=747546443) |
