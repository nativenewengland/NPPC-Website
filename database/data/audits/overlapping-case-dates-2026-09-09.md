# Overlapping incarceration dates — September 9, 2026

**56 prisoners have overlapping recorded incarceration ranges (58 case pairs). Two additional prisoners have possible overlaps that depend on incomplete dates.** All 9,059 case rows from the September 9 read-only snapshot were included in the scan. These counts concern cases attached to the same profile; they exclude cases split across duplicate profiles.

This is a data consistency finding, not independent verification of the historical dates. No database records or application code were changed.

## Method

Compared every pair of cases attached to the same prisoner, using incarceration_date as the start and death_in_custody_date, if present, or release_date as the end. An arrest date was not substituted for missing incarceration information, and an absent release date was not assumed to mean continuous imprisonment until today.

Year and month precision were treated as date ranges rather than literal January 1 or month-start dates. Circa years were allowed a one-year margin on either side. An overlap was counted as established in the recorded ranges only if it persisted across all permitted endpoint choices; otherwise it was marked possible. Legacy dates without precision metadata were treated as day precision, matching the application. Historical inaccuracies in those legacy dates remain possible.

Same-day boundaries were excluded (two case pairs). There were 571 within-profile case pairs with at least one missing incarceration/end date, so this is a minimum identifiable count. No evaluated pair had an unambiguously reversed start/end range. Minimum/maximum overlap days are date differences, not inclusive jail-night counts and not proposed corrections to the counter.

## Effect on displayed totals

At the audit snapshot, the repository implementation added each stored imprisoned_for_days value in PrisonerApiController::buildPayload (app/Http/Controllers/Api/PrisonerApiController.php, line 72). It did not merge overlapping incarceration ranges. Consequently, overlapping rows that both contribute a stored duration can count the same time twice. The overlap count alone does not establish the exact amount of inflation: individual stored durations, missing dates and historical custody breaks require reconciliation.

Several pairs describe the same prosecution, while others could represent concurrent proceedings or a longer case encompassing a shorter custody segment. Overlap is not sufficient grounds to delete a case.

## All matches

| Prisoner | Recorded range A | Recorded range B | Finding |
| --- | --- | --- | --- |
| [Megan Rice](http://104.238.162.40/prisoner/megan-rice) | 2014-02-08 to 2015-05-16 | 2012-07-27 to 2015-05-07 | Overlap |
| [Michael Walli](http://104.238.162.40/prisoner/michael-walli) | 2014-02-08 to 2015-05-16 | 2012-07-27 to 2015-05-07 | Overlap |
| [Greg Boertje-Obed](http://104.238.162.40/prisoner/greg-boertje-obed) | 2014-02-08 to 2015-05-16 | 2014-02-17 to 2015-05-15 | Overlap |
| [David Brown](http://104.238.162.40/prisoner/david-brown) | 1799-06-08 to 1801-03-12 | 1799-02-28 to 1801-03-11 | Overlap |
| [Jacob Coxey](http://104.238.162.40/prisoner/jacob-coxey) | 1894-05-08 to 1894-05-28 | 1894-05-21 to 1894-06-10 | Overlap |
| [Edward Schinzing](http://104.238.162.40/prisoner/edward-schinzing) | 2020-07-27 to 2021-10-03 | 2020-07-28 to 2021-10-22 | Overlap |
| [Jared Chase](http://104.238.162.40/prisoner/jared-chase) | 2012-05-16 to 2020-01 | 2014-04-24 to 2020-04-24 | Overlap |
| [Fernando González Llort](http://104.238.162.40/prisoner/fernando-gonzalez-llort) | 1998-09-12 to 2014-02-27 | 1998-09-12 to 2014-02-27 | Overlap |
| [Mary Anne Grady-Flores](http://104.238.162.40/prisoner/mary-anne-grady-flores) | 2014-02-11 to 2016-07-18 | 2016-01-19 to 2016-07-19 | Overlap |
| [Brent Betterly](http://104.238.162.40/prisoner/brent-betterly) | 2012-05-16 to 2015-04 | 2012-05-16 to 2016-05-16 | Overlap |
| [Douglas L. Wright](http://104.238.162.40/prisoner/douglas-l-wright) | 2012-04-30 to 2022-08 | 2012-05-01 to 2022 | Overlap |
| [Matthew DePalma](http://104.238.162.40/prisoner/matthew-depalma) | 2008-08-30 to 2011-09 | 2008-08-30 to 2011-09 | Overlap |
| [Camilo Mejía](http://104.238.162.40/prisoner/camilo-mejia) | 2004-05-20 to 2005-02-14 | 2004-03-14 to 2005-02-14 | Overlap |
| [José R. Rivera Santana](http://104.238.162.40/prisoner/jose-r-rivera-santana) | 2001-05-31 to 2001-07-09 | 2001-05-22 to 2001-08-20 | Overlap |
| [Ardeth Platte](http://104.238.162.40/prisoner/ardeth-platte) | 1999-01-14 to 2005-11-24 | 1998-05-17 to 2005-12-22 | Overlap |
| [Carol Gilbert](http://104.238.162.40/prisoner/carol-gilbert) | 1999-01-14 to 2005-09-24 | 1998-05-17 to 2005-05-23 | Overlap |
| [Daniel Sicken](http://104.238.162.40/prisoner/daniel-sicken) | 1999-01-19 to 2001-10-15 | 1998-08-15 to 2001-10-16 | Overlap |
| [John Patrick Liteky](http://104.238.162.40/prisoner/john-patrick-liteky) | 1998-02-24 to 2000-05-31 | 1998-06-01 to 2000-06-01 | Overlap |
| [Ed Kinane](http://104.238.162.40/prisoner/ed-kinane) | 1997-09-28 to 1999-07-31 | 1998-10-01 to 1999-08-01 | Overlap |
| [Kathleen Rumpf](http://104.238.162.40/prisoner/kathleen-rumpf) | 1997-09-28 to 1999-01-22 | 1998-07-23 to 1999-01-23 | Overlap |
| [Mary Trotochaud](http://104.238.162.40/prisoner/mary-trotochaud) | 1997-09-28 to 1999-05-31 | 1998-10-01 to 1999-06-01 | Overlap |
| [Richard Streb](http://104.238.162.40/prisoner/richard-streb) | 1998-08-31 to 1999-02-28 | 1998-09-01 to 1999-03-01 | Overlap |
| [Sr. Marge Eilerman OSF](http://104.238.162.40/prisoner/sr-marge-eilerman-osf) | 1997-09-28 to 1999-09-30 | 1998-10-01 to 1999-10-01 | Overlap |
| [Susan Crane](http://104.238.162.40/prisoner/susan-crane) | 1997-02-11 to 2012-10-18 | 1997-02-14 to 2012-10-19 | Overlap |
| [Luz María Berríos Berríos](http://104.238.162.40/prisoner/luz-maria-berrios) | 1985-08-29 to 1999-09-09 | 1985-08-29 to 1990-12-05 | Overlap |
| [Thomas Manning](http://104.238.162.40/prisoner/thomas-manning) | 1985-04-23 to 2019-07-29 | 1985-04-24 to 2019-07-30 | Overlap |
| [Jaan Laaman](http://104.238.162.40/prisoner/jaan-laaman) | 1986-06-26 to 2021-06-07 | 1984-11-04 to 2021-06-23 | Overlap |
| [Alberto Rodríguez](http://104.238.162.40/prisoner/alberto-rodriguez) | 1983-06-28 to 1999-09-09 | 1985-01-31 to 1999-09-09 | Overlap |
| [Oscar López Rivera](http://104.238.162.40/prisoner/oscar-lopez-rivera) | 1981-08-10 to 2017-05-16 | 1981-05-29 to 2017-01-17 | Overlap |
| [Carmen Valentín Pérez](http://104.238.162.40/prisoner/carmen-valentin-perez) | 1980-04-03 to 1999-09-09 | 1981-02-17 to 1999-09-09 | Overlap |
| [Dylcia Pagán](http://104.238.162.40/prisoner/dylcia-pagan) | 1981-02-17 to 1999-09-09 | 1980-04-04 to 1999-09-10 | Overlap |
| [Joseph Patrick Doherty](http://104.238.162.40/prisoner/joseph-patrick-doherty) | 1983-06-18 to 1992-02-19 | 1983 to 1992-02-19 | Overlap |
| [Charles Africa](http://104.238.162.40/prisoner/charles-africa) | 1981-03-31 to 2020-02-06 | 1980-08-04 to 2019-01-08 | Overlap |
| [Debbie Africa](http://104.238.162.40/prisoner/debbie-africa) | 1981-03-31 to 2018-06-15 | 1978-08-08 to 2018-06 | Overlap |
| [Eddie Goodman Africa](http://104.238.162.40/prisoner/eddie-goodman-africa) | 1978-08-07 to 2019-06-20 | 1981-03-31 to 2020-01-31 | Overlap |
| [Eddie Goodman Africa](http://104.238.162.40/prisoner/eddie-goodman-africa) | 1978-08-07 to 2019-06-20 | 1978-08-07 to 2019-06-20 | Overlap |
| [Eddie Goodman Africa](http://104.238.162.40/prisoner/eddie-goodman-africa) | 1981-03-31 to 2020-01-31 | 1978-08-07 to 2019-06-20 | Overlap |
| [Ed Mead](http://104.238.162.40/prisoner/ed-mead) | 1976-01-22 to 1993-08-14 | 1976-01-22 to 1993-12-14 | Overlap |
| [William Taylor Harris](http://104.238.162.40/prisoner/william-taylor-harris) | 1975-09-17 to 2011-03-09 | 1975-09-17 to 1983-04-25 | Overlap |
| [Russell Little](http://104.238.162.40/prisoner/russell-little) | 1974-01-09 to 1981-12-14 | 1975-06-09 to 1981-06-04 | Overlap |
| [Veronza Bowers](http://104.238.162.40/prisoner/veronza-bowers) | 1974-04-25 to 2024-05-23 | 1973-08-21 to 2024-05-23 | Overlap |
| [Sundiata Acoli](http://104.238.162.40/prisoner/sundiata-acoli) | 1973-05-01 to 2022-05-09 | 1973-05-01 to 2022-05-25 | Overlap |
| [Dhoruba bin Wahad](http://104.238.162.40/prisoner/dhoruba-bin-wahad) | 1971-06-04 to 1990-03-21 | 1973-10-01 to 1990-03-22 | Overlap |
| [Cleveland Sellers](http://104.238.162.40/prisoner/cleveland-sellers) | 1968-02-08 to 1973-08-30 | 1973-02-01 to 1973-08-31 | Overlap |
| [Ruchell Magee](http://104.238.162.40/prisoner/ruchell-magee) | 1965-09-01 to 2023-07-27 | 1963-01-01 to 2023-07-21 | Overlap |
| [Benjamin J. Davis Jr.](http://104.238.162.40/prisoner/benjamin-j-davis-jr) | 1949-10-20 to 1955-02-28 | 1951-07-02 to 1955-03-01 | Overlap |
| [Gil Green](http://104.238.162.40/prisoner/gil-green) | 1949-10-13 to 1961-07-28 | 1949-10-20 to 1961-07-28 | Overlap |
| [Henry Winston](http://104.238.162.40/prisoner/henry-winston) | 1949-10-20 to 1961-06-29 | 1956-03-01 to 1961-06-30 | Overlap |
| [Jacob Stachel](http://104.238.162.40/prisoner/jacob-stachel) | 1948-05-31 to 1955-02-28 | 1949-10-20 to 1955-02-28 | Overlap |
| [John Williamson](http://104.238.162.40/prisoner/john-williamson) | 1949-10-20 to 1955-02-28 | 1951-07-02 to 1955 | Overlap |
| [Marcus Garvey](http://104.238.162.40/prisoner/marcus-garvey) | 1923-06-20 to 1927-12-01 | 1925-02-08 to 1927-11-18 | Overlap |
| [Joseph Hofer](http://104.238.162.40/prisoner/joseph-hofer) | 1918-05-24 to 1918-11-29 | 1918-05-25 to 1918-11-29 | Overlap |
| [Philip Grosser](http://104.238.162.40/prisoner/philip-grosser) | 1917-08-14 to 1920-12-14 | 1917-12 to 1919-12-02 | Overlap |
| [David Caplan](http://104.238.162.40/prisoner/david-caplan) | 1917-01 to 1923-07-10 | 1915-02-18 to 1917-01 | Possible; incomplete dates |
| [Lindley Macomber](http://104.238.162.40/prisoner/lindley-macomber) | 1863-07 to 1863-11 | 1863-07-13 to 1863-11-07 | Overlap |
| [Moses Harman](http://104.238.162.40/prisoner/moses-harman) | 1906-02-26 to 1906-12-26 | 1906-03-01 to 1906-12-26 | Overlap |
| [Johann Most](http://104.238.162.40/prisoner/johann-most) | 1902-06 to 1903-04 | 1901 to 1902 | Possible; incomplete dates |
| [Fred Shuttlesworth](http://104.238.162.40/prisoner/fred-shuttlesworth) | 1961-05 to 1961-10-28 | 1961-06-02 to 1961-06-03 | Overlap |
| [Tom Tracy](http://104.238.162.40/prisoner/tom-tracy) | 1916-11-05 to 1917-05-05 | 1916-11-05 to 1917-05 | Overlap |
| [Dorothy Day](http://104.238.162.40/prisoner/dorothy-day) | 1917-11-14 to 1917-11-28 | 1917-11-14 to 1917-11-28 | Overlap |

[CSV with case IDs, charges and overlap bounds](overlapping-case-dates-2026-09-09.csv)

[Separate duplicate-profile audit](duplicate-audit-2026-09-09.md)

## Counter fix

[Implementation, validation and before/after totals](custody-counter-fix-2026-09-09.md). This preserves the case records; uncertain and conflicting historical dates still require individual source review.
