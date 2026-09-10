# Latin King & Queen Nation affiliation audit — batch 276

Reviewed September 10, 2026. Initial pass; organizational membership alone does not establish activism-related imprisonment. Existing records are preserved even when their histories require a separate audit.

## Proposed: Antonio Fernandez (King Tone)

One missing profile and one case, grounded in [United States v. Fernandez, 943 F. Supp. 295 (S.D.N.Y. 1996)](https://law.justia.com/cases/federal/district-courts/FSupp/943/295/2376078/). The opinion documents **eight months actually jailed**, the **May 12, 1995 arrest**, and an unlawful stop associated with protected peaceful assembly. It supports inclusion beyond a bare allegation of political targeting. It does not establish that a gun was definitively planted or that the court ruled on police political motivation.

The release month, **March 1996**, comes from Ed Morales's [King of New York](https://edmorales.net/wp-content/uploads/2022/04/morales-king-of-new-york.pdf), *Village Voice*, December 10, 1996, p.42. The original page was visually checked because extracted columns interleave. Lynda Edwards's [Once and Future Kings](https://www.miaminewtimes.com/news/once-and-future-kings-6360574/), August 28, 1997, supplies additional reporting on Fernandez's organizing and his lawyer's account.

### Data choices

- Use the court's eight-month duration, rather than the eight-and-a-half-month figure in later reporting.
- Keep the exact incarceration start blank: the arrest was followed by bail before the parole detention. Do not equate the full arrest-to-release interval with continuous imprisonment.
- Preserve release precision as month only. March 1 is not asserted as the release day.
- Use the existing **Rikers Island** institution, UUID `1b84c078-2ee3-4856-b40f-1a2049f0f067`, verified by query-only lookup. Do not substitute the historical New York City Penitentiary or Rose M. Singer Center records.
- Coordinates **40.85, -73.90** approximate the Bronx arrest locality; they are not a claimed facility entrance.
- No vital date, portrait, personal support website, or custodial sentence is guessed. Separate later drug and domestic-abuse proceedings are not added as activism cases.

The application stores an internal 245-day conversion of the documented months using its existing arrest-anchor rule. The public counter is verified to read **8 Months**. That internal conversion is not an archival claim about exact custody days or an inferred release date. No synthetic incarceration start is stored.

## Identity and roster checks

The refreshed, query-only inventory contains **8,786 profiles** and **204 affiliation labels**. Full names, aliases, name components and slugs were screened, including hidden records. Pending batches **271–275** contain no Fernandez/King Tone match.

The North Carolina roster is reconstructed from the [December 6, 2011 DOJ indictment announcement](https://www.justice.gov/archive/usao/ncm/news/2011/12_06_11.html) and [October 3, 2012 superseding-case update](https://www.justice.gov/archive/usao/ncm/news/2012/10_03_12.html). These distinguish the original thirteen defendants from the later fourteen-person case.

| Roster member | Database result |
| --- | --- |
| Jorge Peter Cornell / King Jay | Existing as Jorge P. Cornell, without the affiliation label; preserve. |
| Russell Lloyd Kilfoil / King Peaceful | Existing as Russell Kilfoil; preserve. |
| Randolph Leif Kilfoil / King Paul | Existing as Randolph Kilfoil; preserve. |
| Jason Paul Yates / King Squirrel | Existing as Jason Yates; preserve. |
| Wesley Anderson Williams / King Bam | Existing as Wesley Williams; preserve. |
| Samuel Isaac Velasquez / King Hype | Existing as Samuel Velasquez; preserve. |
| Irvin Vasquez / King Dice | Existing; preserve. |
| Carlos Coleman / King Spanky | Existing; preserve. |
| Ernesto Wilson / Yayo | Existing; preserve. |
| Luis Alberto Rosa / King Speechless | Missing identity. Existing Luis Rosa is the FALN defendant, a different person. Hold. |
| Steaphan Acencio-Vasquez / King Leo | Missing. Hold for individual activism/custody nexus. |
| Marcelo Ysrael Perez / King Lyrix | Missing. Hold for individual activism/custody nexus. |
| Charles Lawrence Moore / King Toasty | Missing identity. Existing Charles Moore is a WWI conscientious objector, a different person. Hold. |
| Richard Lee Robinson / King Focus | Missing. Hold for individual activism/custody nexus. |

Nine existing identities plus five unresolved candidates account for all fourteen; this is not a claim that the five should automatically be added. The government's announced guilty pleas establish prosecution outcomes, not an activism-related reason for custody. Conversely, a guilty plea alone is not a reason to dismiss a documented political-prisoner claim.

The [support campaign's historical prisoner list](https://alkqnsupport.wordpress.com/current-address-for-friends/) includes Steaphan Acencio-Vasquez and confirms actual imprisonment rather than merely an imposed sentence. Its political interpretation is a campaign position. Further individual evidence is needed for this pass's inclusion standard. Old listed institutions are not assumed to be current.

## Other open leads

- **Cara Martha Williams, Allan Jordan and Robert Vasquez:** [Jordan Green's contemporary reporting, reprinted by SCSJ](https://southerncoalition.org/jorge-cornell-called-for-gang-peace-so-why-does-he-look-like-a-marked-man/), describes disputed 2008 arrests. Williams is presented as a member's mother and supporter, not a proven formal member. The article's chronology does not clearly establish her total detention length. No birthdate is inferred from age, and no automatic addition is made from proximity to Cornell.
- **New York 1998 arrests:** the [Revolutionary Worker report](https://revcom.us/a/v19/950-59/959/lks.htm) is a useful advocacy lead, but does not supply verified individual custody histories for the entire group. Do not add every arrestee from an aggregate raid count.
- **Luis Felipe / King Blood:** distinguish from existing Luis Felipe Moreno Godoy. Membership and imprisonment alone are insufficient to select a qualifying activism case.
- Missing affiliation labels on already-existing profiles, including Cornell, are a separate preservation-safe research task; this batch does not rewrite them.

Next affiliation: **American Indian Movement**. These unresolved leads remain available for subsequent passes.

## Validation and deployment

**69 assertions passed** using actual application models in disposable SQLite memory. Checks cover creation, exact and partial dates, institution identity, documented-month rendering, replay, existing biographies/cases/photos/sites, hidden alias matches, ambiguous identities, and rollback on invalid input. A query-only live preview confirms exactly one missing profile. Shell syntax and whitespace checks passed.

The batch validates all data before creation, uses a transaction, preserves any existing match, supports dry-run mode, prepares writable PsySH storage, and clears the established API/museum/tracker caches after application. Production was not modified.

After merge, apply earlier pending batches 271–275 first, then:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-276.sh --dry-run
sudo -u www-data bash database/data/run-batch-276.sh
```
