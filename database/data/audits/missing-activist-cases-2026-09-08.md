# Missing activist case fields — batch 255

Read-only production audit on September 8, 2026 of the eight people whose missing
photos were filled by batch 254. Each currently has one case. This batch proposes
17 empty case fields in six rows. It creates no prisoners or cases and makes no
biography, photo, charge, support-website, institution, or stored-duration changes.
All populated values, including conflicting values, are preserved.

| Person | Missing fields proposed | Evidence and limits |
| --- | --- | --- |
| Kent Ford | Arrest and incarceration: June 1969, month precision | [Oregon Encyclopedia](https://www.oregonencyclopedia.org/articles/black_panthers_in_portland/) and [Reed Magazine](https://www.reed.edu/reed-magazine/articles/2009/black-panthers-clinics.html). The [July 4, 1969 Militant](https://www.themilitant.com/1969/3327/MIL3327.pdf) distinguishes initial arrest and rearrest in June; this single existing case does not receive an unsupported exact day. |
| Larry Gossett | Sentenced July 1, 1968; judge James J. Dore; prosecutor Neal L. Shulman | [University of Washington history project](https://depts.washington.edu/civilr/BSU_FranklinHS.shtml), hearing, trial and sentencing sections, with contemporary newspaper citations. |
| Larry Little | Arrested January 19, 1971; indicted; no-contest plea and misdemeanor disposition; sentenced March 19, 1973; judge J. William Copeland; six months suspended for two years | [Friedman chapter in Comrades](https://dokumen.pub/comrades-a-local-history-of-the-black-panther-party-0253349281-9780253349286.html), printed pp. 69, 73; [FBI Vault Part 34](https://vault.fbi.gov/Black%20Panther%20Party%20/Black%20Panther%20Party%20Part%2034%20%28Final%29), PDF pp. 47 and 60; [State v. Cornell](https://law.justia.com/cases/north-carolina/supreme-court/1972/9-1-5.html). The seven additions describe the original arrest and later reduced disposition without replacing the existing felony charge text. |
| Scott Camil | Indicted; judge Winston Arnow; prosecutor Jack Carrouth | [Texas Observer, September 21, 1973, p. 5](https://issues.texasobserver.org/pdf/ustxtxb_obs_1973_09_21_issue.pdf). Existing acquittal and court institution remain unchanged. |
| Connie Matthews | Exile began February 1971, month precision | [Contemporary TIME report](https://time.com/archive/6638872/radicals-the-divided-panthers/) of her disappearance; [Historical Journal account](https://www.cambridge.org/core/journals/historical-journal/article/black-panther-partys-publishing-strategies-and-the-financial-underpinnings-of-activism-19681975/441702BF3381BFADB7EEEC0CB355D6E6) establishes subsequent arrival in Algeria. [Secondary chronology](https://en.wikipedia.org/wiki/Connie_Matthews) corroborates the month, also already described in her unchanged biography. |
| Mabel Williams | Exile ended September 1969, month precision | [Facing South remembrance](https://www.facingsouth.org/2014/04/remembering-southern-black-freedom-fighter-mabel-w.html) distinguishes her return from Robert's subsequent return and arrest. |
| Carl Hampton | None supported for the existing fatal police-shooting event | Related arrests and prosecutions concern other people; no personal arrest, plea, sentencing or release is inferred. |
| Janet McCloud | None safely supported without resolving conflicting existing data | Contemporary docket identifies a different charge description; no reliable new sentencing date or judicial personnel established. |

## Preserved conflicts and open questions

- **Gossett:** the existing arrest date is March 29, 1968, the protest date. UW
  places arrest on April 4. That field and the biography are untouched. April and
  July custody episodes are distinct; no continuous detention interval or actual
  six-month time-served figure is invented. The historic jail was in the King
  County Courthouse; an institution lookup found modern Seattle jail entries, so
  no unverified historical institution match was assigned.
- **McCloud:** existing arrest and incarceration dates are December 31, 1969,
  while release is recorded as October 1965 at month precision. Existing charges
  say illegal fishing; [Civil Liberties Docket](https://digicoll.lib.berkeley.edu/record/222226/files/meik-12_1.pdf),
  PDF p. 234 / printed p. 83, entry 604.10a, describes October 13, 1965 charges of
  obstructing a public officer and a still-pending case. Defense lawyer Michael
  Rosen is not a prosecutor and has no corresponding field in this schema.
  These discrepancies require separate review; no existing value is changed.
- **Hampton:** the case already records July 26, 1970 as both release and death in
  custody, although its narrative describes a police shooting. Do not infer a
  personal incarceration episode from those fields. The [March 27, 1971 Black
  Panther, p. 8](https://washingtonareaspark.com/wp-content/uploads/2020/05/1971-03-27-bpp.pdf)
  reports related prosecutions of Johnny Coward and Bartee Haile, not Hampton.
  Reports of an arrested Carl Hampton in Cairo concern a different person and
  are excluded.
- **Little:** six months suspended is not six months served. His earlier 1970
  disorderly-conduct case is not merged into this January 1971 meat-truck case.
  Fuller's pretrial days and sentence in the same FBI report are not Little's.
  Accounts give differing monetary amounts, so no fine or restitution is added.
- **Ford:** sources identify Rocky Butte Jail, but no matching institution was
  found in the lookup. An unrelated Portland institution is not substituted.
  No exact bail-release date is established.
- **Camil:** acquittal does not establish the date physical detention ended.
- **Matthews and Williams:** exile is not treated as custody, and their husbands'
  criminal proceedings are not assigned to them.

The FBI, Observer and Civil Liberties Docket pages were rendered and visually
checked against extracted text. In particular, Little's sentencing date is
**March 19**, not the incomplete OCR reading “March 9.” Field-level source URLs,
locators and limits are recorded in `fixes/batch255.json`; none becomes a personal
support-website link.

## Preservation and validation

The runner validates payload identity, allowed fields, dates and source coverage;
preflights every prisoner/case ID, ownership and existing charge text; and updates
only null or empty-string fields. Existing precision metadata is retained. An
incompatible precision value on an empty date causes that date to be skipped.
Partial dates are displayed at their documented precision, despite the internal
first-day storage convention.

Guarded Eloquent query updates avoid model saving hooks that could recompute
durations or derive unrelated dates. Original field and precision values are
included in each update condition, with all case writes in one transaction.
Only normal update timestamps change alongside the proposed fields. API cache
is invalidated after a successful nonempty batch; no-op replay leaves it alone.

Validation completed:

- Shell and PHP syntax checks passed.
- Read-only live preview matched all six identities and proposed 17 fields.
- `check-batch255.php` passed using PHP/Laravel with in-memory models: field
  boundaries, partial-date display, preservation of populated values and unrelated
  metadata, idempotent planning, newly populated fields, and conflicting precision.
  It evaluates only the validation/planner portion and does not execute database
  persistence or invalidate cache.
- Production updates were not executed. Actual write-path integration was not
  tested against a disposable database; deployment remains manual.

## Manual deployment

After merging the PR and applying any earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-255.sh --dry-run
sudo -u www-data bash database/data/run-batch-255.sh
```

Expected on the audited state: six case rows updated, ending with `B255-OK`.
Fewer updates are normal if someone filled fields before deployment. Identity
mismatches stop the batch before any case write. The runner uses application-owned
PsySH directories to avoid the earlier `/var/www/.config/psysh` permission error.
