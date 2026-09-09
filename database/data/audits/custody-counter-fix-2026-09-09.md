# Count overlapping imprisonment once — validation and impact

The counter now merges overlapping dated custody durations within each prisoner. It preserves all case rows, biographies, dates, photographs, source notes and other prisoner data. This is an application change, not a data batch. It does not consolidate duplicate prisoner profiles.

## Read-only preview

The preview evaluated all 9,059 live case rows with SQLite query-only mode enabled. Stored case data had the same hash before and after. **57 prisoner totals decreased; none increased.** The API was checked for all 57 affected public profiles plus Robert Swann. Four representative profiles were also evaluated through the actual counter block from the Blade template; their phrases agreed with the API.

| Profile | Before, days | After, days | Duplicate contribution removed |
| --- | ---: | ---: | ---: |
| [Megan Rice](http://104.238.162.40/prisoner/megan-rice) | 1,476 | 1,023 | 453 |
| [Michael Walli](http://104.238.162.40/prisoner/michael-walli) | 1,476 | 1,023 | 453 |
| [Greg Boertje-Obed](http://104.238.162.40/prisoner/greg-boertje-obed) | 1,096 | 644 | 452 |
| [David Brown](http://104.238.162.40/prisoner/david-brown) | 1,383 | 742 | 641 |
| [Jacob Coxey](http://104.238.162.40/prisoner/jacob-coxey) | 40 | 33 | 7 |
| [Meagan Morris](http://104.238.162.40/prisoner/meagan-morris) | 866 | 461 | 405 |
| [Edward Schinzing](http://104.238.162.40/prisoner/edward-schinzing) | 884 | 452 | 432 |
| [Jared Chase](http://104.238.162.40/prisoner/jared-chase) | 4,978 | 2,900 | 2,078 |
| [Fernando González Llort](http://104.238.162.40/prisoner/fernando-gonzalez-llort) | 11,294 | 5,647 | 5,647 |
| [Mary Anne Grady-Flores](http://104.238.162.40/prisoner/mary-anne-grady-flores) | 1,070 | 889 | 181 |
| [Brent Betterly](http://104.238.162.40/prisoner/brent-betterly) | 2,511 | 1,461 | 1,050 |
| [Douglas L. Wright](http://104.238.162.40/prisoner/douglas-l-wright) | 7,277 | 3,745 | 3,532 |
| [Matthew DePalma](http://104.238.162.40/prisoner/matthew-depalma) | 2,194 | 1,097 | 1,097 |
| [Camilo Mejía](http://104.238.162.40/prisoner/camilo-mejia) | 607 | 337 | 270 |
| [José R. Rivera Santana](http://104.238.162.40/prisoner/jose-r-rivera-santana) | 129 | 90 | 39 |
| [Ardeth Platte](http://104.238.162.40/prisoner/ardeth-platte) | 5,282 | 2,776 | 2,506 |
| [Carol Gilbert](http://104.238.162.40/prisoner/carol-gilbert) | 5,008 | 2,687 | 2,321 |
| [Daniel Sicken](http://104.238.162.40/prisoner/daniel-sicken) | 2,158 | 1,158 | 1,000 |
| [John Patrick Liteky](http://104.238.162.40/prisoner/john-patrick-liteky) | 1,558 | 828 | 730 |
| [Ed Kinane](http://104.238.162.40/prisoner/ed-kinane) | 975 | 672 | 303 |
| [Kathleen Rumpf](http://104.238.162.40/prisoner/kathleen-rumpf) | 665 | 482 | 183 |
| [Mary Trotochaud](http://104.238.162.40/prisoner/mary-trotochaud) | 853 | 611 | 242 |
| [Richard Streb](http://104.238.162.40/prisoner/richard-streb) | 362 | 182 | 180 |
| [Sr. Marge Eilerman OSF](http://104.238.162.40/prisoner/sr-marge-eilerman-osf) | 1,097 | 733 | 364 |
| [Susan Crane](http://104.238.162.40/prisoner/susan-crane) | 11,454 | 5,729 | 5,725 |
| [Luz María Berríos Berríos](http://104.238.162.40/prisoner/luz-maria-berrios) | 7,048 | 5,124 | 1,924 |
| [Thomas Manning](http://104.238.162.40/prisoner/thomas-manning) | 25,030 | 12,516 | 12,514 |
| [Jaan Laaman](http://104.238.162.40/prisoner/jaan-laaman) | 26,145 | 13,380 | 12,765 |
| [Alberto Rodríguez](http://104.238.162.40/prisoner/alberto-rodriguez) | 11,251 | 5,917 | 5,334 |
| [Oscar López Rivera](http://104.238.162.40/prisoner/oscar-lopez-rivera) | 26,080 | 13,136 | 12,944 |
| [Carmen Valentín Pérez](http://104.238.162.40/prisoner/carmen-valentin-perez) | 13,876 | 7,098 | 6,778 |
| [Dylcia Pagán](http://104.238.162.40/prisoner/dylcia-pagan) | 13,876 | 7,098 | 6,778 |
| [Joseph Patrick Doherty](http://104.238.162.40/prisoner/joseph-patrick-doherty) | 6,504 | 3,532 | 2,972 |
| [Charles Africa](http://104.238.162.40/prisoner/charles-africa) | 28,227 | 14,430 | 13,797 |
| [Debbie Africa](http://104.238.162.40/prisoner/debbie-africa) | 28,132 | 14,556 | 13,576 |
| [Eddie Goodman Africa](http://104.238.162.40/prisoner/eddie-goodman-africa) | 44,039 | 15,152 | 28,887 |
| [Ed Mead](http://104.238.162.40/prisoner/ed-mead) | 12,950 | 6,536 | 6,414 |
| [William Taylor Harris](http://104.238.162.40/prisoner/william-taylor-harris) | 15,734 | 12,957 | 2,777 |
| [Russell Little](http://104.238.162.40/prisoner/russell-little) | 5,083 | 2,896 | 2,187 |
| [Veronza Bowers](http://104.238.162.40/prisoner/veronza-bowers) | 36,829 | 18,538 | 18,291 |
| [Sundiata Acoli](http://104.238.162.40/prisoner/sundiata-acoli) | 35,826 | 17,921 | 17,905 |
| [Dhoruba bin Wahad](http://104.238.162.40/prisoner/dhoruba-bin-wahad) | 12,881 | 6,866 | 6,015 |
| [Cleveland Sellers](http://104.238.162.40/prisoner/cleveland-sellers) | 2,241 | 2,031 | 210 |
| [Ruchell Magee](http://104.238.162.40/prisoner/ruchell-magee) | 43,264 | 22,122 | 21,142 |
| [Benjamin J. Davis Jr.](http://104.238.162.40/prisoner/benjamin-j-davis-jr) | 3,295 | 1,958 | 1,337 |
| [Gil Green](http://104.238.162.40/prisoner/gil-green) | 8,605 | 4,306 | 4,299 |
| [Henry Winston](http://104.238.162.40/prisoner/henry-winston) | 6,217 | 4,271 | 1,946 |
| [Jacob Stachel](http://104.238.162.40/prisoner/jacob-stachel) | 4,421 | 2,464 | 1,957 |
| [John Williamson](http://104.238.162.40/prisoner/john-williamson) | 3,236 | 1,957 | 1,279 |
| [Marcus Garvey](http://104.238.162.40/prisoner/marcus-garvey) | 2,638 | 1,625 | 1,013 |
| [Joseph Hofer](http://104.238.162.40/prisoner/joseph-hofer) | 377 | 189 | 188 |
| [Philip Grosser](http://104.238.162.40/prisoner/philip-grosser) | 1,949 | 1,248 | 701 |
| [Lindley Macomber](http://104.238.162.40/prisoner/lindley-macomber) | 240 | 147 | 93 |
| [Moses Harman](http://104.238.162.40/prisoner/moses-harman) | 1,273 | 973 | 300 |
| [Fred Shuttlesworth](http://104.238.162.40/prisoner/fred-shuttlesworth) | 666 | 665 | 1 |
| [Tom Tracy](http://104.238.162.40/prisoner/tom-tracy) | 358 | 181 | 177 |
| [Dorothy Day](http://104.238.162.40/prisoner/dorothy-day) | 1,327 | 1,313 | 14 |

These counts differ from the date-overlap audit because the counter uses stored credited durations, including cases whose physical release dates are missing. It preserves the existing rule that a documented duration outranks approximate case endpoints. Thus the date-range audit and the counter impact measure different things.

## Calculation rules

- Merge exact anchored spans as a union, keeping gaps between separate stays. Count each prisoner independently in global totals.
- Only positive stored durations contribute. Missing duration does not become ongoing imprisonment. An ordinary arrest date alone does not locate a custody span.
- Preserve undated duration values, including Robert Swann’s 24 documented months displayed as two years. Documented month durations retain the model’s existing arrest-anchor fallback.
- For month/year/circa incarceration starts, deduct only overlap shared by every permitted placement of the stored duration. Do not merge short separate stays simply because their dates use the same January 1 placeholder.
- Preserve month units when the deduplicated result is a whole-month span. Mixed units or an exact dated union containing additional days use the existing day breakdown.
- Do not repair conflicting dates, infer missing releases, delete cases, reconcile state/federal prosecutions, or merge duplicate profiles automatically. These remain source-research tasks.

## Surfaces and deployment

Updated profile counters, prisoner API/database-list counters, the About page total, the admin dashboard imprisonment total, and the tracker’s total imprisonment days. API and tracker cache keys were versioned so new deployments do not reuse the previous server-side totals. Existing client caches can still last up to ten minutes.

The tracker’s monetary estimates remain its separate, case-based cost model. This change does not assign overlapping custody to a particular institution or rewrite investigation, prosecution or incarceration cost estimates.

After merging, deploy code and clear compiled views (no data batch or migration):

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data php artisan view:clear
```

## Validation

- PHP syntax checks passed for all changed PHP files and new tests/fixtures.
- 248 runtime assertions passed across 21 fixtures, reversed input orders, preservation checks, a per-prisoner partition check, and 100 independent day-set overlap trials.
- Read-only preview checked all case rows and the affected API/profile outputs; production was not modified.
- PHPUnit regression tests were added for the fixture matrix, cross-prisoner totals and API/case preservation. PHPUnit itself was unavailable in the installed runtime; the same fixtures were executed directly against the actual application classes. Full CI results should be checked on the PR.
- Git whitespace validation passed.

[CSV impact list](custody-counter-impact-2026-09-09.csv)
