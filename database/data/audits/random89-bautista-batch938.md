# Batch 938: Jorge De Los Reyes Bautista Martinez custody correction

Fresh live snapshot had March12,2008 admission and March25,2026 release, despite a nominal three-year sentence. No prisoner register number attached. Original DOJ October29,2007 announcement directly identifies individual January26,2006 Colombian arrest, February20,2007 extradition to Florida, guilty plea, Judge JoanA.Lenard and prosecutor BrianSkaret. The earlier arrest stays unchanged; U.S. physical custody begins at the documented extradition date, not the unsupported2008start.

Official public BOP lookup: exact surname BAUTISTA-MARTINEZ, first JORGE returns Jorge DE LOS R BAUTISTA-MARTINEZ, register78415-004, Male/White, age66, MiamiFDC, actualRelease03/20/2008. Full composite surname and middle initials match, as does age47 in DOJ2007 versus66 in2026 and Miami jurisdiction. Not the other JorgeBautista results (ages38/43, different middle names, different registers and dates). Captcha false. BOP shows actual release, no projected date. Raw response retained tmp/pdf-review-all/bop-bautista-surname-0.json and relevant record embedded in committed payload.

Added missing BOP register, judge, prosecutor and guilty plea. Corrected U.S. admission and actual release; derived counter recalculated. Existing biography and unrelated fields guarded. Colombian custody not added to this U.S. interval. Legacy sentencing March12,2008 remains uncorroborated; not changed from a scheduled hearing date. DOJ chart search leads suggest amended time-served sentence in2008, but source downloads unavailable and no sentencing-date correction based on snippets. Primary DOJ scheduled January7,2008 is not actual sentencing evidence.

Sources: https://www.justice.gov/archive/opa/pr/2007/October/07_crm_862.html ; https://www.bop.gov/inmateloc/

Dry run, live write, idempotent replay and independent full raw readback passed. Verified U.S. interval counter394days; unrelated fields preserved except intended derived total. Public profile rendering unverified. Full100profile external review unfinished. No evidence PDF deleted.
