# Jailed participants in the 1966 St. Petersburg mural protest

Researched September 7, 2026. Batch 252 adds five missing prisoner profiles and one historical case for each. The live read-only preview found all five absent, including name variants and under-review records. Omali Yeshitela already exists; his mural case is separately prepared in batch 251 / PR #2433.

| Person | Verified custody | State grand-larceny outcome |
| --- | --- | --- |
| Joseph "Jody" Wall | Arrested and detained December 29, 1966; prolonged detention because he could not afford bail | Convicted May 1967; three years of probation; reversed October 2, 1968 and remanded for a separate trial |
| John Wesley Bryant | Arrested and detained December 29, 1966 | Acquitted May 1967 |
| Lemuel Green | Arrested and detained December 29, 1966 | Acquitted May 1967 |
| Tommy Williams | Arrested and detained December 29, 1966 | Acquitted May 1967 |
| Crawford Louis Jones | Arrested and detained December 29, 1966 | Acquitted May 1967 |

## Evidence and qualifications

The principal historical source is Anita Richway Cutting, *From Joe Waller to Omali Yeshitela: How a Controversial Mural Changed a Man* (2000), [USF repository](https://digitalcommons.usf.edu/honorstheses/76/), consulted through the [full mirrored thesis](https://drive.google.com/file/d/1zio7H0Tcp_YlZOm6OsbS26Xh2CFKaK8A/view). Printed pages 36–39 identify the six arrests, booking and inability to meet bail. Page 42 describes their transfer to the county jail in downtown Clearwater on December 30. Pages 43–46 discuss bail and municipal proceedings. Pages 48–50 describe the state trial, four acquittals and Wall's probation.

These are entries for people who were actually detained; a subsequent acquittal does not erase their detention. Keep the state and municipal results distinct. Bryant's municipal public-property charge was dropped, but he was convicted of disorderly conduct, with sentencing initially deferred. Cutting describes suspended thirty-day municipal sentences and probation for the four defendants tried after Bryant and Waller. Suspended sentences are not recorded as time served.

[Wall v. State, 214 So. 2d 384](https://www.casemine.com/judgement/us/5914997aadd7b04934614e83/amp) independently establishes Wall's reversal and Green's acquittal. The remand is not an acquittal or proof of Wall's release day. No post-remand outcome has been established.

Do not convert January 3, 1967 (bond reduction), May 1967 (verdicts), or October 2, 1968 (Wall's appellate ruling) into release dates. The newspaper headline about Wall's 98 days is cited in the thesis and describes an interim custody report, not a final total. Williams's later recollection of six months is expressly questioned by Cutting; it remains attributed narrative, not a numeric duration. Original newspaper issues and police reports cited in the thesis have not been independently retrieved.

The [March 2016 commemoration announcement](https://theweeklychallenger.com/black-community-celebrates-heroes-50-years-since-racist-mural-was-torn-down-from-city-hall/) supports SNCC affiliation and Green's posthumous recognition. Its January incident date is erroneous and is not used. [January 2017 coverage](https://theweeklychallenger.com/yeshitela-vs-the-mural/) corroborates the December 29 date and the named group. No inferred birth dates from reported ages, death dates, photos or personal/support websites are entered. Thomas Williams is only a conservative duplicate-search variant, not an asserted legal name.

All five profiles are marked as former custody, with exact arrest and initial incarceration dates and unknown release dates. No present-day prison coordinates or institution are assigned to the historical downtown jail. Existing biographies and cases are preserved. Normal `prisoner:add` placement may shift sort-order values.

## Validation and deployment

Passed Bash/PHP syntax checks and the zero-apostrophe check inside the single-quoted tinker block. The service-account wrapper successfully previewed all five additions without database writes.

Mutation checks used the application's actual models and command against an isolated SQLite in-memory database, after removing all configured production database connections. They confirmed five profiles/five cases, sourced dates, null unverified fields and durations, preservation of existing biography/case content, API-cache invalidation, unchanged replay, alias/under-review duplicate detection and atomic rejection of ambiguous identities.

Merge the PR and earlier pending batches first. On the server, use the established application account and apply numbered batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
# Apply any earlier pending batches, including 251, before 252.
sudo -u www-data bash database/data/run-batch-252.sh --dry-run
sudo -u www-data bash database/data/run-batch-252.sh
```

The batch uses application-owned PsySH directories to avoid the unwritable `/var/www/.config/psysh` error. The live database changes only when the reviewed batch is applied. Research and verification did not insert production rows.
