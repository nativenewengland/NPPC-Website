# ACT UP affiliation research: batch 275

Reviewed September 10, 2026. This is an initial oral-history pass, not an exhaustive ACT UP roster. Inclusion requires actual US activism-related custody lasting multiple days beyond initial processing.

## Proposed addition

**Jamie Bauer — one profile and one case.** The [2004 participant interview](https://www.actuporalhistory.org/s/048-Jamie-Bauer.pdf), printed pp.18–20, establishes personal membership in the Waterloo 54 and several days confined before recognizance release. The [collective statement published in The Witness](https://digitalarchives.episcopalarchives.org/the_witness/pdf/1983_Watermarked/Witness_19831201.pdf), pp.13–14, dates the peace march and arrest to **July 30, 1983**. The sheriff's threatened incitement charge does not establish the charge actually filed.

Bauer's later [NYC Trans Oral History Project interview](https://nyctransoralhistory.org/interview/jamie-bauer/) confirms the display name, they/them pronouns, and involvement in Women's Pentagon Action and ACT UP. The qualifying custody predates ACT UP; this is an affiliated person's earlier peace-activism case, not an ACT UP-organized arrest.

No exact release date, numeric custody total, birthday, sentence, or institution relation is inferred. Map coordinates **42.90, -76.86** are an approximate Waterloo locality marker, not a claimed facility position. Birth year cannot be established from interview ages or an archive's dates-covered field. No portrait or support website is assigned in this batch.

## Identity screening

The September 10 query-only inventory contains **8,786 profiles**, including hidden/under-review records and **11** carrying the ACT UP label. Full name, alias, component-name and slug screening found no Bauer profile. Pending batches **271–274** also contain no match. Historical-name search keys are used only to avoid duplicates, not as the public display name.

Existing ACT UP-labelled profiles preserved: Michael Petrelis, David Pasquarelli, Terrence McGuckin, Kate Sorensen, Paul Davis, Richard Racklin, Jeff Schuerholz, Karl Soehnlein, Luis Salazar, Walter Armstrong and Charles King. Their presence is an inventory observation, not a new assessment of every existing case's eligibility. Petrelis/Pasquarelli detention and Racklin's case must not produce duplicate entries.

## Eight oral histories screened

| Person | Finding and disposition |
| --- | --- |
| Jamie Bauer | Included as above. Other arrests mentioned without adequate duration evidence are not added. |
| [Russell Pritchard](https://www.actuporalhistory.org/s/021-Russell-Pritchard.pdf) | PDF pp.12 and 28–29 describe a six-hour hold and a separate Columbia, South Carolina cite-and-release action. Neither establishes qualifying multiday custody. |
| [Gerri Wells](https://www.actuporalhistory.org/s/075-Gerri-Wells.pdf) | Multiple arrests and strip-search litigation described; no qualifying duration located. Hold for individual custody evidence. |
| [Alexis Danzig](https://www.actuporalhistory.org/s/117-Alexis-Danzig.pdf) | No qualifying personal custody established. Printed p.27 attributes a week of Albany detention to Risa Denenberg, but Denenberg's own account conflicts; see below. |
| [Joe Ferrari](https://www.actuporalhistory.org/s/126-Joe-Ferrari.pdf) | Custody after a demonstration is described, including group activities while detained, but duration remains unresolved. |
| [Esther Kaplan](https://www.actuporalhistory.org/s/146-Esther-Kaplan.pdf) | PDF p.27 recalls at least one night, possibly two, after an office occupation. That uncertainty does not securely establish custody beyond an overnight processing hold. Seek corroboration. |
| [BC Craig](https://www.actuporalhistory.org/s/085-BC-Craig.pdf) | PDF p.13 describes preparing for a three-month sentence that was not imposed then. Preparation and a threatened sentence do not prove imprisonment. |
| [Risa Denenberg](https://www.actuporalhistory.org/s/093-Risa-Denenberg.pdf) | Printed pp.58–60 describe arrest for spray-painting the Albany capitol, overnight processing and release the next day. **Not added:** this contradicts Danzig's week-long recollection. Denenberg's other clinic/protest arrests also lack qualifying duration here. |

The Denenberg discrepancy also concerns location terminology: Danzig calls the property federal, while Denenberg identifies the state capitol. No federal offense, week of custody, or exact date is entered from that account.

## Open research

- Continue the [oral-history subject index](https://www.actuporalhistory.org/interview-index), especially legal issues, civil disobedience and individual detention accounts. Eight transcripts do not exhaust the project's collection.
- Obtain records resolving the Denenberg discrepancy and Kaplan's uncertain duration before reconsidering inclusion.
- Follow Smithsonian interviews for Hunter Reynolds, James Wentzy and Joy Episalla, and named Philadelphia 2000 detention rosters. Check every identity against the whole inventory first.
- Cross-movement lead: Barbara Deming appears absent in the current full inventory. Research individual custody, aliases and pending work before adding her through the peace-movement queue. Do not infer that every Waterloo marcher served an identical interval.
- Continue with **Almighty Latin King & Queen Nation** next. ACT UP remains open for a subsequent research pass.

## Implementation and validation

The JSON payload carries source references and evidence; the numbered script creates only missing identities. Existing matches are preserved in full. Ambiguous matches stop the batch. All entries are validated before creation, and creation is transactional. API, museum and tracker caches are forgotten after application. The script prepares writable PsySH paths and supports a read-only dry run.

Validation: **67 assertions passed** using the application models in disposable SQLite memory, including dry-run behavior, replay, existing-field preservation, alias matches, ambiguity and invalid-data rollback. A query-only live preview found exactly one missing profile. Shell syntax and whitespace checks passed. Production remains unchanged until the user merges and runs the numbered batch on the server, following earlier pending batches in order.
