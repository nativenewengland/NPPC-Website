# Batch 335: Philippine anticolonial prisoners

Two missing people, two custody periods, two portraits, one birth date and two death dates. These are US colonial-authority imprisonments in the Philippines, not mainland imprisonment. Local preparation only; no production writes. Accumulation: **42/100 new people**, 56 periods, 14 portraits, five birth dates and six death dates.

| Person | Verified additions |
|---|---|
| Dionisio Magbuelas / Papa Isio | August 6, 1907 surrender and prison entry; 1907 sentence and commutation; death in Bilibid in 1911, year precision; identified 1907 prison portrait. |
| Felipe Salvador / Apo Ipe | July 24, 1910 capture; April 15, 1912 execution in Bilibid; May 26, 1870 birth; captioned historical portrait. |

Both profiles have `released=false` and explicit death-in-custody dates. The existing model mirrors that endpoint into its release-date column for the duration calculation; this is not a finding that either person left prison alive. Magbuelas has no exact death date or exact duration. Salvador's exact counter can use the sourced endpoints. February 27, 1912 was Supreme Court review affirming an earlier sentence, so it is not entered as the original sentencing day despite the abbreviated wording on the marker. His separate earlier sedition imprisonment and escape in 1902 or 1903 remain a lead; they are not merged into the later case.

The charges and armed-resistance context are retained. The court's findings about killings are attributed to the colonial court rather than erased or presented as a conviction solely for peaceful speech. Movement affiliation is Santa Iglesia or Babaylanes and Philippine independence; disputed formal Katipunan membership is not inferred. Magbuelas's online birthday lacks reliable corroboration and conflicts across older versions, so no birthday is supplied. No new institution record or exact institutional coordinates are guessed because custody involved more than one site. Personal-support website fields remain empty.

## Validation

150 in-memory assertions passed, covering insertion, replay, alias preservation, unchanged existing records, image bytes and partial death-date precision. All connections are purged before selecting the sole SQLite memory connection; file/storage/cache are mocked. A separate forced-dry-run, query-only live preview found both identities absent. Normalized name, component, alias and pending-batch checks against the 8,957-profile snapshot and batches 297–334 found no collision. Shell syntax and image checksums passed. Portraits visually reviewed; Salvador's caption strip removed by an exact original-pixel crop.

## Research handoff: queues 91–92

Aurelio Tolentino already exists under Junta de Amigos and Katipunan. Several main Guam prisoners also already exist, including Mabini, Ricarte, Pio del Pilar (accented spelling), Hizon, Llanera, Macario de Ocampo, Julian Gerona and Pablo Ocampo. Do not count exile, parole or an oath condition as continuous imprisonment without individual custody evidence.

- Juan Abad: official NCCA biography and the University of the Philippines book *Mga Eksilo, Inang Bayan at Panlipunang Pagbabago*, Noel Teodoro chapter, printed 187–188, confirm prosecution, bail and exile. His primary appeal is G.R. L-2535, August 9, 1906. Individual actual multi-day prison endpoints still need verification; do not describe writing during bail as writing in prison.
- Juan Matapang Cruz: July 5, 1903 arrest and reported full service of a two-year term appear in indexed drama scholarship, but the full cited source was not accessible. Obtain the original judgment or scholarly text before adding.
- Simeon Ola: official NCCA biography describes a 30-year sentence after September 25, 1903 surrender; other accounts describe a suspended sentence after turning state witness. Actual custody and release require reconciliation. Do not enter 30 years served.
- Pantaleon Garcia: capture and being held under surveillance are documented, but the nature and duration of imprisonment remain unresolved. May 5/6, 1900 capture dates conflict.
- Ruperto Rios: contemporary June 1903 newspaper reports conviction and death sentence; individual custody and execution record still needed. Santiago Alvarez requires careful distinction from the Cuban Miami namesake.
- Balayan prisoner ledger: NARA ID 404788077, *Register of Native Prisoners Confined, October 1900–May 1902*. A modern transcription includes repeat monthly entries, unclear names, conflicting capture dates, transfers and ordinary criminal prisoners. Obtain and visually compare original facing pages before creating identities; deduplicate monthly repeats and exclude ordinary offenses. This could be a substantial new lead, not yet a validated addition.

The queued affiliations remain open for further research; this pass does not establish completeness.

## Sources

- [National Historical Institute, Dionisio Magbuelas marker, November 6, 2009: surrender, bandolerismo sentence, commutation and death in Bilibid.](https://philhistoricsites.nhcp.gov.ph/registry_database/dionisio-magbuelas-papa-isio/)
- [Harry H. Bandholtz Papers, 1907 Bacolod prison portrait, Commons extracted image by Kguirnela; linked to University of Michigan Bentley Historical Library HS4544.](https://commons.wikimedia.org/wiki/File:Papa_Isio.jpg)
- [Papa Isio historical overview: alternative names Dionisio Seguela and Dionisio Papa y Barlucia, used as duplicate-search aliases. Unsupported birthday is not used.](https://en.wikipedia.org/wiki/Papa_Isio)
- [NHCP, Felipe Salvador historical marker, May 26, 2021: birth, movement, capture and execution. February 27, 1912 is the Supreme Court affirmation, not the original sentencing day.](https://philhistoricsites.nhcp.gov.ph/registry_database/felipe-salvador/)
- [United States v. Felipe Salvador (alias Apong Ipi), G.R. L-6705, February 27, 1912. Primary judgment reviewed: July 24, 1910 recapture, bandolerismo conviction and death sentence affirmed en consulta.](https://lawphil.net/judjuris/juri1912/feb1912/gr_l-6705_1912.html)
- [Commons, Felipe Apo Ipe Salvador portrait, anonymous photographer; source credited to Alex D. R. Castro reproduction from El Renacimiento Filipino. Commons exact 1894 capture date is not independently established.](https://commons.wikimedia.org/wiki/File:Felipe_%22Apo_Ipe%22_Salvador.png)
- [Alex D. R. Castro, Views from the Pampang, July 5, 2011. Identifies the portrait and original publication El Renacimiento Filipino; no exact issue or photographer supplied.](https://viewsfromthepampang.blogspot.com/2011/07/257-felipe-salvador-rebel-messiah-comes.html)
- [Balayan transcription and source identification](https://www.batangashistory.date/2024/09/american-held-prisoners-of-war-in.html)
- [NARA original register, ID 404788077](https://catalog.archives.gov/id/404788077)
- [UP book, 2024](https://cids.up.edu.ph/wp-content/uploads/2024/03/Mga-eksilo-Inang-Bayan-at-panlipunang-pagbabago.pdf)
- [Simeon Ola, NCCA](https://philippineculturaleducation.com.ph/ola-simeon/)
- [Competing Ola account and cited military history](https://www.malvar.net/chapters/the_last_general_issue.html)
