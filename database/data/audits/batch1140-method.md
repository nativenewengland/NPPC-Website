# Individual review of remaining ambiguous sentence fields

Read all 1,140 remaining fields individually against their complete existing text. No blanket rules or inferred sentence lengths are applied. Each decision is recorded with original/replacement text, case/profile identity, and decision category in the JSON and CSV.

Selected 1,061 exact sentence-only replacements. 383 fields contain no usable imposed sentence and become null. Retain prison terms, fines, restitution, forfeiture, probation, supervised release, credit, concurrency, suspended terms, reductions, pardons and reversals where applicable. Do not convert statutory maximums, time served or pretrial custody into imposed sentences. Preserve uncertainty where sentence figures conflict. 62 fields need no change; 17 conflicting or damaged entries remain unchanged pending source verification. This completes the individual text review, not factual verification of every historical prosecution.

Only the sentence column changes. No notes are added or moved. Identity/original-text guards, transaction digests of every other case field and all profiles, independent live readback, and idempotent replay are required. Dates, counters, timestamps and unrelated fields remain unchanged.
