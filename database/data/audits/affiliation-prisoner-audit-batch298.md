# Affiliation research: local batch 298

Reviewed September 10, 2026. Six new people and six cases; no existing records changed. This batch stays local with batch 297 until the combined PR contains 100 new identities. Accumulated validated count: **14/100**.

## Verified additions

| Person | Custody | Vital dates entered |
|---|---|---|
| Richard Morrisroe | Hayneville, August 14–20, 1965 | None |
| Jonathan Myrick Daniels | Hayneville, August 14–20, 1965 | March 20, 1939–August 20, 1965 |
| Ruby Sales | Hayneville, August 14–20, 1965 | July 8, 1948 |
| Joyce Bailey | Hayneville, August 14–20, 1965 | None |
| Gloria Larry | Hayneville, August 14–20, 1965 | None |
| Willie Vaughn | Hayneville, August 14–20, 1965 | None |

The decisive source is the [contemporary SNCC report](https://www.crmvet.org/docs/650820_sncc_daniels.pdf), PDF page 5, visually checked against its scan. It explicitly identifies the six released detainees. The first reports contain errors about the shooting; the detailed report and later sources distinguish survivors from Daniels, who died **after release**, not in custody. No speculative birth years derived from the inconsistent contemporary ages.

[Morrisroe's own account](https://www.crmvet.org/vet/morrisro.htm) verifies his CIC membership and actual detention. [Episcopal Archives](https://exhibits.episcopalarchives.org/s/church-awakens/page/jonathan-daniels) independently confirms Daniels's six days and ESCRU membership. His [vital dates](https://encyclopediaofalabama.org/article/jonathan-myrick-daniels/) and [Sales's birthday](https://snccdigital.org/people/ruby-sales/) have separate citations in the payload. Only Morrisroe is assigned CIC membership. Broad civil-rights affiliation for Bailey, Larry and Vaughn does not imply formal CIC membership.

The [McMeans decision](https://law.justia.com/cases/federal/district-courts/FSupp/247/606/1956495/) establishes the constitutional and legal context. Its published text does not print the full petitioner roster, so no individualized September 30 dismissal or sentencing date is assigned to these six. In particular, that later decision is not their physical release date.

No matching historical Hayneville/Lowndes institution was returned by the live institution query. Records use an explicitly approximate Hayneville municipal marker, 32.18222,-86.57861. No institution is created or linked to a different jail.

## Identity and preservation checks

All 8,847 live identities, including aliases and hidden records, and batches 269–297 were screened. Query-only batch preview: **six missing, zero existing**. W. J. Vaughn is a separately existing 1918 Espionage Act defendant; his record remains untouched. Guarded runner preserves any identity that becomes present before deployment and aborts ambiguous matches. It never fills fields on an existing identity.

Unknown photos, support websites and additional vital dates remain blank. Morrisroe's reported 1938 birth year is a lead requiring review of the full interview; unrelated Morrisroe obituaries were rejected. Identified portrait leads: [Daniels/VMI credit](https://encyclopediaofalabama.org/media/jonathan-myrick-daniels-2/), Episcopal Archives Daniels exhibit, and Ruby Sales's Library of Congress oral history linked from SNCC Digital Gateway. Assets have not yet been downloaded, inspected and credited for this batch.

## Queue groups 36–38

**Camden 28:** 28 profiles already carry the affiliation. Named participants in the [PBS documentary description](https://archive.pov.org/camden28/film-description/) and [participant discussion](https://www.americamagazine.org/issue/625/100/debating-camden-28) are represented. No new eligible identity established. Original camden28.org unavailable; full primary indictment roster remains an open check. Neither the film donor credits nor FBI informant Bob Hardy is a prisoner roster. Do not claim all 28 were acquitted at the same trial or that arrest-to-trial time was continuous custody.

**Camp Pendleton 14:** Nine existing profiles preserved: Eddie Page, Curtis Jones Jr., Bobby Bishop, Clarence Copens, Donald Hunter, Glen White, Gregory Coffey, Herman Fletcher and Ricky McGivery. [VVAW's interview](https://www.vvaw.org/veteran/article/?id=1668) identifies Lance Corporal Anthony Mathews as another defendant. Its [release report](https://www.vvaw.org/veteran/article/?id=1649) confirms eight defendants had spent three months in the brig but does not name those eight. Mathews therefore remains held pending individual custody evidence. Do not assume every one of the 14 served a sentence. Workers World September 30,1977 p5 was visually reviewed: campaign context, no new named defendant custody proof. BPP 1977 downloads were incomplete; do not use truncated bpp1701.pdf as evidence. Further roster research remains open.

**Catholic Interracial Council:** Existing James T. Carey and Molly Rush preserved. Morrisroe research led to the six additions above. Mathew Ahmann and Eugene Boyle remain leads, not eligible solely from civic leadership or arrests. Additional Hayneville leads: Rev. John L. McMeans and Christopher Wylie; the court confirms arrest and jail transfer but their individual multiday/release evidence still requires completion. Other juveniles released earlier must not inherit the six-person dates.

## Validation

- Shell syntax and whitespace checks passed.
- **246 assertions passed** with real Laravel models against SQLite `:memory:` only; production connections purged, File/Storage/Cache faked. Preserved existing bios/photos/support sites/cases; replay adds nothing; ambiguous identities and altered dates rejected; UUIDs and caches checked.
- Verified exact custody dates produce a six-day counter; no invented whole-month value. Daniels's post-release death remains separate from case death-in-custody fields.
- Production preview ran with `PRAGMA query_only = ON` and dry-run enabled. No production writes or deployments occurred.

Next queued group: **Catholic Left (39)**. Retain the open leads above and in prior audits. No claim of exhaustive group coverage.
