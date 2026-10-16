# Batch 818: tenth random 100-profile review

Missing fields and supported partial-date refinements only. All biographies and unrelated fields preserved.

## Chris Lamprecht

March1997 Swing interview by J.D.Heiman explicitly places incarceration in January1995 and identifies sentencing judge Sam Sparks. Add month-precision start and judge, preserving existing BOP March3,2000 release. No inferred sentencing day.

Fields: judge, incarceration_date, date_precision

https://www.gbppr.net/mthreat/swing.html
https://www.wired.com/1997/12/twice-removed-locked-up-and-barred-from-net/

## Kate Winston

Erin Spicer biography of Kate Gary Winston explicitly identifies January12 release following five days District Jail in January1919. Sources include Washington Herald January13,p7 and Los Angeles Times January13,p1. Add actual release day and January incarceration month only; do not subtract five days to invent admission day. Saved complete source kate.html.

Fields: incarceration_date, release_date, date_precision

https://documents.alexanderstreet.com/d/1011146184

## Margo Pelletier

Official artist estate chronology explicitly says she served six months at Rikers for the anti-apartheid protest. Add documented actual months, not an imposed sentence. Existing dates and charge descriptions preserved; exact endpoints unresolved. Saved margo.html.

Fields: imprisoned_for_months

https://www.margopelletier.com/life
https://createcouncil.org/wp-content/uploads/2016/03/ArtsAlive-106-WEB-2.pdf

## George Ostensen

Art Laffin chronology explicitly reports first actual release September1989, distinct from October2 rearrest and January30,1990 later release. Add missing first release with month precision, and August20,1987 indictment from explicit account. No original admission inferred. Local same chronology tmp/affiliations300/plowshares.txt line1672 corroborates.

Fields: indicted, release_date, date_precision

https://kingsbayplowshares7.org/plowshares-history/

## Mary Winsor

Library of Congress individual Mary Winsor record cites Doris Stevens, Jailed for Freedom p370: sentenced September 1917 to sixty days at Occoquan. Fill missing sentencing month only. November 3 photograph of released women establishes release by publication but not exact day. Existing portrait retained; later ten-day term separate.

Fields: sentenced_date, date_precision

https://www.loc.gov/resource/mnwp.16003_160035/

## William Eagles

Friends Journal June 1/15, 1972 PDF page34 explicitly identifies William Eagles, Raleigh Meeting, imprisoned at Petersburg, committed November 15, 1971. Three years imposed, parole initially denied. Add actual admission date, preserve already sourced April 16, 1973 release. Original downloaded and text checked.

Fields: incarceration_date, date_precision

https://friendsjournal.s3.us-east-1.amazonaws.com/friendsjournal/wp-content/uploads/emember/downloads/1972/HC12-50520.pdf

## Ura V. Aschliman

Mennonite Life September 1976 p18 reproduces Ura V. Aschliman court-martial record dated September 16, 1918 at Camp Sheridan: pleaded not guilty, convicted, five years imposed. Fill missing sentencing and plea fields. July 18 is disobeyed-order date, not established arrest. Original download hit anti-bot page; full indexed transcription available, parent should review before deployment.

Fields: sentenced_date, plead, date_precision

https://mla.bethelks.edu/ml-archive/pre2000/1976sep.pdf

## Kelsey Donnel Jackson

DOJ July 12, 2021 individual release identifies Judge Richard M. Gergel and prosecutors Emily Limehouse and Nathan Williams, and guilty plea. Sentencing date already present. Third-party inmate mirror offers possible release lead but not authoritative confirmation; no prison admission inferred from sentence.

Fields: judge, prosecutor, plead

https://www.justice.gov/usao-sc/pr/edisto-island-man-who-livestreamed-handgun-violent-charleston-riots-sentenced-24-months

## Maurice L. Snitken

Kohn p133 records Maurice L. Snitken, Indianapolis lawyer, Atlanta prison admission June 23, 1918 and parole August 25, 1920. Add missing admission only. Existing twelve-month documented duration conflicts with longer interval and is flagged, not overwritten.

Fields: incarceration_date, date_precision

https://dokumen.pub/american-political-prisoners-prosecutions-under-the-espionage-and-sedition-acts-0275944158-9780275944155.html

## Victor Agosto

Eyewitness Alice Embree August 7, 2009 report identifies Captain Santos as presiding summary court-martial officer. August 30 release report confirms guilty plea and Saturday release. Fill missing judge/plea/convicted. Courage to Resist has competing August 28 release; preserve existing August 29 pending reconciliation. Existing photograph retained.

Fields: judge, plead, convicted

https://www.theragblog.com/alice-embree-the-court-martial-of-victor-agosto/
https://www.theragblog.com/victor-agosto-released-from-bell-county-jail/
https://couragetoresist.org/afghanistan-resister-victor-agosto-gets-only-30-days-jail/

## Raymond Dennis

Fill the missing judicial and prosecution officials from contemporary trial pamphlet; do not infer service of the sentence.

Fields: judge, prosecutor

https://digicoll.lib.berkeley.edu/nanna/record/72242/files/lb001058.pdf?registerDownload=1&version=1&withMetadata=0&withWatermark=0

## Frank J. Howenstine

Matching government case reports support sentence, conviction and commutation. Exact June sentencing day conflicts, so preserve month precision.

Fields: sentenced_date, date_precision, sentence, convicted

https://www.govinfo.gov/content/pkg/SERIALSET-07987_00_00-063-0159-0000/pdf/SERIALSET-07987_00_00-063-0159-0000.pdf
https://outbound.worldwar1centennial.org/images/official-bulletin/pdf/18-09/2-402-september-03-1918-ww1-official-bulletin.pdf

## Demetrio Perez

Archival contemporary support poster and retrospective named account support missing case details. Fifteen-month imposed term already exists; no actual duration inferred.

Fields: charges, convicted

https://www.collection-politicalgraphics.org/mwebcgi/mweb.exe?request=record%3Bid%3D98432%3Btype%3D101
https://www.scoop.co.nz/stories/HL1306/S00038/for-each-every-warrior-whose-strength-is-not-to-fight.htm

## Jogues Egan

Contemporary AP report January 30 identifies Tuesday commitment for civil contempt and the judge, corroborating existing January 26 custody start.

Fields: judge, sentenced_date, date_precision

https://dailyiowan.lib.uiowa.edu/DI/1971/di1971-01-30.pdf
https://repository.stu.edu/ulma/va/3005/1963/05-31-1963.pdf
https://www.americamagazine.org/politics-society/2023/12/04/kissinger-harrisburg-seven-246639/

## Richard Eells

Museum history explicitly places the arrest in the August 1842 episode and identifies trial judge; exact arrest day and incarceration remain unresolved.

Fields: arrest_date, date_precision, judge

https://www.quincyundergroundrailroad.org/history/

## Barrett Brown

DOJ sentencing announcement and plea announcement establish exact dates and imposed punishment; populated custody and exile fields are not overwritten.

Fields: sentenced_date, date_precision, plead, judge, sentence

https://www.justice.gov/usao-ndtx/pr/dallas-man-associated-anonymous-hacking-group-sentenced-63-months-federal-prison
https://www.justice.gov/usao-ndtx/pr/dallas-man-associated-anonymous-hacking-group-pleads-guilty-federal-charges

## Cheryl Todd

Contemporary newspaper reprint identifies trial judge James B. OConnor and district attorney Phil West. Sentencing was still scheduled, not completed.

Fields: judge, prosecutor

https://courierheraldtoday.com/50-years-ago-february-3-1976-dessie-woods-cheryl-todd-convicted-in-killing-robbery-case/

## Jay Anthony Villarreal

DOJ individually identifies sentencing judge and prosecutors. Official Marine photograph identifies full name, Hondo hometown and Marine service, matching published reporting.

Fields: judge, prosecutor

https://www.justice.gov/usao-wdtx/pr/hondo-man-sentenced-federal-prison-threatening-violently-overthrow-government
https://www.dvidshub.net/image/6638508/pfc-jay-anthony-villarreal
https://www.expressnews.com/news/article/jay-anthony-villarreal-hondo-overthrow-government-22444385.php

## Joseph Patrick Doherty

UPI June12,1981 contemporary report names Joseph Patrick Doherty among seven sentenced to life in absentia that day.

Fields: sentenced_date, date_precision

https://www.upi.com/Archives/1981/06/12/Seven-of-the-eight-IRA-prisoners-who-shot-their/7380361166400/

## Saul Grossman

Congressional Record confirms 1953 three-count indictment and December conviction, subsequently reversed; no served prison term established.

Fields: indicted

https://www.govinfo.gov/content/pkg/GPO-CRECB-1956-pt2/pdf/GPO-CRECB-1956-pt2-12-3.pdf
