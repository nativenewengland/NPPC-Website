# 100 additional IWW prisoners: batches 320–325

100 new people, 103 cases, 3 sourced death dates, no portraits. These six idempotent batches preserve every existing prisoner profile, biography, case, photo and support website. Research covers Spokane free-speech imprisonment, wartime and postwar IWW prosecutions, and immigration detention. It is a bounded research pass, not a complete IWW roster.

This package follows PR #2493 (batches 307–319). Merge and deploy that earlier package first, including its partial-date duration fix, then apply these six batches in order. This PR contains only the new numbered batches, their rosters and audits; earlier PR contents are not duplicated.

| Batch | New people | Cases |
|---|---:|---:|
| 320 | 3 | 3 |
| 321 | 15 | 17 |
| 322 | 27 | 27 |
| 323 | 28 | 29 |
| 324 | 6 | 6 |
| 325 | 21 | 21 |
| Total | 100 | 103 |

## Evidence and validation

Each entry includes cited evidence of actual US custody beyond an initial arrest. Exact dates, partial dates and documented durations retain their source limits. Sentence length is not converted into time served, and an unknown release never creates a current incarceration flag. Existing biographies remain unchanged. For batch 325 the dated interval starts with Ellis Island admission, not the earlier original arrest.

Final query-only comparison against 8,957 live identity records found all 100 missing with no cross-batch name/alias collisions. All six runners passed syntax and quoting checks. Isolated-memory batch tests passed 172, 494, 772, 808, 247 and 622 assertions respectively (3,115 total), covering insertion, replay, preservation, rollback, dates, aliases and cache invalidation. No production writes were used.

## Manual deployment after both PRs are merged

Run earlier undeployed batches first. The loop stops immediately if any batch fails.

```bash
cd /var/www/NPPC-Website &&
git pull origin main &&
for batch in {320..325}; do
  sudo -u www-data bash "database/data/run-batch-${batch}.sh" || break
done
```

## New identities

| Batch | Name |
|---|---|
| 320 | Samuel O. Chinn |
| 320 | Henry Bordet |
| 320 | F. J. Ferry |
| 321 | Henry Biddiscomb |
| 321 | Roy Decker |
| 321 | T. Venier |
| 321 | Ed Shanon |
| 321 | Nick Verbeck |
| 321 | Matt Antella |
| 321 | J. Antella |
| 321 | P. Davison |
| 321 | N. McCloud |
| 321 | S. Hourve |
| 321 | A. L. Vecellio |
| 321 | Neal Guiney |
| 321 | E. Pavini |
| 321 | G. Bertini |
| 321 | John Levo |
| 322 | Alex Boggio |
| 322 | Arthur Common |
| 322 | Vincent Corella |
| 322 | Vitan Deloff |
| 322 | Michael Fitzwilliams |
| 322 | John Brodahl |
| 322 | Oliver Dailey |
| 322 | Mike Diska |
| 322 | Amos Enright |
| 322 | Charles Miama |
| 322 | A. Pahjola |
| 322 | Tom Salv |
| 322 | Robert Regan |
| 322 | Charley Butts |
| 322 | Bill Dirk |
| 322 | Nick Wallace |
| 322 | Herbert Beesaw |
| 322 | Jack Curley |
| 322 | H. Radunz |
| 322 | D. S. Dietz |
| 322 | Tom Walden |
| 322 | Adolph Guldahl |
| 322 | Emery Sarrazin |
| 322 | Warner Strang |
| 322 | Walter Strom |
| 322 | H. D. Medis |
| 322 | Paddy Mee |
| 323 | John Collins |
| 323 | Tom Davis |
| 323 | Svan Erickson |
| 323 | Fred Johnson |
| 323 | J. W. Johnson |
| 323 | L. M. Jones |
| 323 | Dennis Kelliher |
| 323 | Victor Lang |
| 323 | Matt Lukla |
| 323 | Ira Young |
| 323 | Weseley Allen |
| 323 | Bill Amy |
| 323 | Bill Clark |
| 323 | William Collins |
| 323 | William Hughes |
| 323 | Carl Kirkby |
| 323 | George MacDonald |
| 323 | Frank Sullivan |
| 323 | E. M. Welton |
| 323 | Golf S. Marhow |
| 323 | Dan Paul |
| 323 | Henry Reed |
| 323 | Thomas Scott |
| 323 | Arthur J. Smith |
| 323 | Eugene Smith |
| 323 | W. K. Stevens |
| 323 | Ed Whitehead |
| 323 | George Williamson |
| 324 | Charles Jacobson |
| 324 | J. Callahan |
| 324 | Ed Lidbery |
| 324 | William Murray |
| 324 | John Hanson |
| 324 | Ralph Bergdorff |
| 325 | John Berg |
| 325 | Alex Kisil |
| 325 | Sol Erlich |
| 325 | M. Slusky |
| 325 | McGregor Ross |
| 325 | John Lund |
| 325 | Hjalmar Holm |
| 325 | William Longfors |
| 325 | Martin De Wal |
| 325 | Edwin Flogus |
| 325 | Axel Hendrickson |
| 325 | Sam Nelson |
| 325 | Aug. Bostrom |
| 325 | Frank Mahalik |
| 325 | James Osborne |
| 325 | Magnus Otterholm |
| 325 | Louis Mische |
| 325 | Gust Lipkin |
| 325 | Fritz Holm |
| 325 | Pete Merta |
| 325 | Gustav Mocha |
