# Explicitly approved non-charge fields

User explicitly approved clearing fields that contain only event/detention descriptions and no named charge, after the earlier bulk heuristic update was rejected. Reviewed307distinct candidate strings, excluded15groups that name charges or specific statutory proceedings (vagrancy, criminal syndicalism, bail-jumping/aggravated battery, illegal reentry, treason, parole violation and other uncertain cases). Selected only the committed exact original strings, not a live heuristic.

1887fields will become null. No case notes or other case/profile fields change, not even timestamps. All original text remains in the repository payload and audit, not case notes. Existing case dates and custody intervals remain. Runner preflights identity and original charges, verifies every target, hashes all unrelated fields and profiles and supports idempotent replay.
