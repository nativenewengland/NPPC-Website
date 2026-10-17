# Batch 985: round91 initial additions

Mike Sun: DOJ February9,2026 sentencing report explicitly states guilty plea in October2025; fill only NULL plea and conviction fields. Existing arrest month and custody date retained pending verification; no new exact plea day inferred.
https://www.justice.gov/usao-cdca/pr/political-operative-sentenced-4-years-federal-prison-acting-covert-agent-peoples

Hridindu Roychowdhury: DOJ April10,2024 sentencing report identifies JudgeWilliamM.Conley, prosecutors ElizabethAltman and JustinSher, initial Boston appearance attorney AmandaBeck, and guilty plea December1,2023. Fill missing judge, prosecutor and plea only. Existing March27,2023 admission precedes March28 arrest confirmed by this report: flag for custody-document verification rather than changing existing dates during missing-field pass. No biography edited.
https://www.justice.gov/usao-wdwi/pr/madison-man-sentenced-7-12-years-prison-attempted-firebombing-madison-building-2022

Live October17,2026: dry run, application, idempotent replay and full raw readback passed. Every unrelated field preserved; public HTML not independently verified.
