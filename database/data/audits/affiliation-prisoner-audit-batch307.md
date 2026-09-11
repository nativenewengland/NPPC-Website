# Batch 307 — Montana Coxeyite prisoners

Reviewed September 11, 2026. Three new people and three cases prepared locally for the next 100-person combined PR. Not deployed. PR 2489 remains unchanged and its 100 identities are excluded from this new accumulation.

| New identity | Evidence of sustained custody | Precision retained |
| --- | --- | --- |
| William Hogan | Fort Missoula museum reports three months served; contemporary dispatch confirms a six-month sentence. | Arrest April 1894; incarceration year 1894; sentencing May 14, 1894; no inferred release or duration counter. |
| John Helme, also printed John Helm | Named prisoner interviewed at the Helena fairgrounds; account describes four days during confinement when donated bread was withheld. | Incarceration year 1894; no individual sentence or exact endpoints. |
| P. McMahon | Named alongside Helme as a prisoner in the same interview and account of sustained confinement. | Initial retained; incarceration year 1894; no individual sentence or exact endpoints. |

Sources and episode-level custody evidence are in [batch307.json](../fixes/batch307.json). The museum exhibit is [Organized labor and the railroads](https://scalar.usc.edu/works/weve-been-working-on-the-railroad/organized-labor-and-the-railroads-1). The [May 17 Chase County Courant](https://newspapers.swco.ttu.edu/bitstream/handle/20.500.12255/20739/Chase_County_Courant_1894_05_17.pdf?isAllowed=y&sequence=3), PDF page 8, has a Helena May 15 dispatch saying sentencing occurred yesterday; its article image was visually reviewed. The [May 9 Anaconda Standard](https://tile.loc.gov/storage-services/service/ndnp/mthi/batch_mthi_darter_ver01/data/sn84036012/00294550355/1894050901/1048.pdf), page 5, was available as archival extracted text; its scan could not be retrieved for visual review. Its May 8 dateline describes the previous afternoon visit, so May 7 is an interview date, not an arrest or prison-entry date.

Existing Coxey, Browne and Christopher Columbus Jones records are preserved. Existing John Sanders needs scoped identity comparison before deciding whether other John Sherman Sanders information belongs to him. The identity inventory contains John Hogan and Daniel Hogan, neither matching William. No Helme/Helm or McMahon identity matched this batch in the read-only deployment preview.

## Held research leads

- Train crew: contemporary names differ between engineer Harmon and engineer Cleveland. Fireman Brady and conductor Willy lack a securely connected individual custody outcome. Do not assign the unnamed crew's sentence by assumption.
- George versus Jack Primrose: April 8–9 reports suggest custody across two days but conflict on the given name. Hold for identity resolution.
- General Carter and two aides in Utah: first names and individual sustained custody remain unresolved.
- Dick Williams of Montpelier, Idaho: arrest and transfer are documented, but no individual duration has yet been tied to him. Group sentences are insufficient.
- Barney Cassidy: mentioned in the contingent's journey, not individually confirmed as a sustained-custody prisoner by the reviewed account.
- Unnamed Anaconda correspondent, unnamed Montana captains, Kansas prisoners and Maryland prisoners: totals are not a named roster.
- Phair and the Kaslo detainees: Canadian custody does not meet this project's US-custody scope.
- [History Colorado Heritage, March/April 2024](https://spl.cde.state.co.us/artemis/hedserials/hed615internet/hed61520240304internet.pdf) describes John Sherman Sanders and the Colorado contingent. Its engineer identification is explicitly uncertain; no alias merger has been made.
- [Idaho Legal History Society, Summer 2014](https://idb.uscourts.gov/Content_Fetcher/index.cfml/ILHS_Newsletter_Summer_2014_1955.pdf?Content_ID=1955) supports further Idaho custody research but does not establish a named roster for all its group sentences.

## Validation

- Shell syntax passed; the single-quoted Tinker body contains zero ASCII apostrophes.
- Read-only application preview: three would add, zero existing matches, B307-OK. Production database writes disabled.
- Application integration exercise: **174 assertions passed** in SQLite memory, with production connections removed before test writes and cache/storage replaced by fakes. Covers replay, preservation of existing and hidden-alias records, ambiguous-match rejection, date precision, missing source rejection, no invented current custody, no calculated release, and cache invalidation.
- No existing biographies, cases, portraits, populated fields or support websites changed. No vital dates, photographs or coordinates inferred.

The numbered batch is retained locally until the next accumulation reaches 100 new identities. Deployment remains manual after the eventual combined PR is merged.
