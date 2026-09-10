# Affiliation research — batch 292

Reviewed September 10, 2026. One missing identity, one custody case and one identified portrait. Continues the Black Panther Party pass begun in batches266/291, including previously unresolved chapter leads.

## Proposed entry

**Herbert Hawkins (Philadelphia).** The [Philadelphia Inquirer’s participant interview](https://www.inquirer.com/news/herbert-hawkins-philadelphia-black-panther-frank-rizzo-raid-20200701.html) supports the individual case and named photo in the payload. Reported age is not converted to a guessed DOB. Exact release and dismissal dates, specific individual charge counts and the jail institution remain unresolved. General raid charges are not assigned to him. The duration stays in prose: the current model recomputes the day field and supports documented months, so a two-week account cannot safely become an exact numeric counter without a separate code change.

All 8,786 existing identities, including hidden profiles and aliases, and pending batches269–291 were screened. No match for Herbert Hawkins or Herb Hawkins. The unrelated existing Hawkins records are preserved. Approximate Philadelphia map coordinates are used, not a home or jail address. No support website was verified.

## Further research and held leads

| Lead | Finding and remaining requirement |
| --- | --- |
| Jacob Bethea; Albert Douglas Moore, Richmond | Original [July 19, 1971 Panther paper](https://www.marxists.org/history/usa/pubs/black-panther/06%20no%2025%201-20%20jul%2019%201971.pdf), printed pages5/15, identifies and pictures them and reports convictions. [October 8, 1971 Militant](https://www.marxists.org/history/etol/newspape/themilitant/1971/v35n36-oct-08-1971-mil.pdf), page2, reports September10 sentencing. Neither establishes each man’s actual multi-day confinement. Keep pending; do not equate prison sentences with service. |
| Richmond Five, earlier material | [June19, 1971 paper at University of Nebraska](https://rozsixties.unl.edu/items/show/633.html), statement on the Five, adds defense context but no individual detention span. [Supreme Court volume409](https://tile.loc.gov/storage-services/service/ll/usrep/usrep409/usrep409801/usrep409801.pdf), pages802/882, records Brunson’s petition dismissal and Jacob Bethea’s certiorari denial; these do not prove detention. Howard Moore, Charles Brunson and Junius Underwood already exist. |
| Richmond identity/date conflicts | [FBI file](https://www.archives.gov/files/research/jfk/releases/2022/docid-32989569.pdf) supplies Albert’s middle name and allegations about November1970 travel, not judicial findings. Do not conflate the November1970 traffic stop with April1971 warrants. Follow the Washington Evening Star, July7,1971, B3, “Three Convicted in Panther Weapon Case,” cited in [the UNCW thesis](https://libres.uncg.edu/ir/uncw/f/preusserj2006-1.pdf), to resolve conflicting trial dates. No verified vital dates. |
| Richard/Ricky Gossett; Trolice Flavors, Seattle | [UW’s original historical research](https://depts.washington.edu/civilr/BSU_Franklin.htm) reports April4 arrests, April5 release of Flavors and three others, and charges dropped against Flavors and Richard. This does not establish custody beyond the initial overnight period for these two. Do not assign Larry Gossett, Aaron Dixon and Carl Miller’s six-month sentences to them. The latter three were bailed out the morning after sentencing, according to this account. |
| Charles Oliver; Clifton Wyatt; Larry Tukes, Seattle | Named in [Larry Gossett’s retrospective roster](https://www.franklinalumni.net/quaker-times-1/2025/4/22/the-gang-of-four). Individual multi-day juvenile detention remains unverified; no new entries. Richard’s appearance in the [July5,1968 Militant](https://themilitant.com/1968/3227/MIL3227.pdf) is not duration proof. |
| Willie Crawford, Buffalo | [September26,1970 Panther paper](https://s3.us-west-1.wasabisys.com/luminist/PR/BP_1970_09_26.pdf), page6, reports September3 arrest and alleged mistreatment, but a qualifying detention span and individual activism link remain unresolved. A [congressional hearing](https://blackfreedom.proquest.com/wp-content/uploads/2020/09/blackpanther21.pdf), page4562, discusses a Philadelphia leader of this name; do not merge these identities. |
| Felix Welch; Gene Epps; Richard Smithe; Raymond Owens; Leslie Mays; Allen Crawford; Dakin/Darkin Gentry; James Johnson, Milwaukee | The [July5,1969 Panther paper](https://www.marxists.org/history/usa/pubs/black-panther/03n11-jul%205%201969.pdf), page10, contains arrests/tickets, not each person’s jail duration. The [January24,1970 paper](https://washingtonareaspark.com/wp-content/uploads/2020/06/1970-01-24-black-panther-vol-4-no-8.pdf), page14, distinguishes Welch’s non-political forgery case and describes chapter conflict. Neither membership nor an unrelated conviction suffices. |
| Milwaukee Three | [July4,1970 Panther paper](https://s3.us-west-1.wasabisys.com/luminist/PR/BP_1970_07_04.pdf), page16, and [September1970 Industrial Worker](https://files.libcom.org/files/2025-05/Industrial%20Worker%20%28September%201970%29-compressed.pdf) establish custody. Booker Collins, Earl Leverette and Jesse White already exist; no duplicate entries. [September23,1970 Daily Cardinal](https://asset.library.wisc.edu/1711.dl/VZ24TMCZVO36F84/E/file-8048c.pdf) distinguishes Leverette’s bail status from the other two. Nate Bellamy also already exists. |
| Tommie Carr; Danny Solomon, Cleveland | Arrests described in local-history research still need individual multi-day custody evidence. Do not equate all chapter arrests with incarceration. Original research PDF access was denied; no bypass attempted. |
| Des Moines Charles Edward Smith; Peter John Williams | [Congressional hearing clipping](https://blackfreedom.proquest.com/wp-content/uploads/2020/09/blackpanther22.pdf), page4840, indicates detention but the alleged robbery’s political nexus is unresolved. Membership alone does not establish an activism case. |

Other unresolved batch266 leads remain open. No claim that all chapter rosters are exhausted. Move the first organizational pass to **Black Panther Party support campaigns**, retaining these leads for targeted follow-up.

## Preservation and validation

The runner validates reviewed identity fields, case/date values, source references and the photo checksum before mutation. Existing name/alias matches are skipped entirely; ambiguous matches abort. Existing bios, fields, photos, cases and personal support websites are preserved. The payload adds no migration. The runner prepares writable PsySH paths under application storage and clears the established prisoner API, museum and tracker caches after application.

**133 assertions passed** using actual application models in isolated SQLite memory, after purging all production connections and replacing photo storage and caches with memory doubles. Coverage included initial application, identical replay, hidden alias preservation, invalid evidence/date/identity rejection, unknown-duration handling, portrait attachment and rollback on simulated storage failure. The query-only live preview reported one missing identity and no existing matches. Shell syntax, JSON parsing, crop pixel equality and diff whitespace checks passed. No production data writes or deployment were performed.

## Manual deployment

After merging this PR and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-292.sh
```

A git pull alone does not add the entry or attach its portrait. Replaying the batch preserves existing data.
