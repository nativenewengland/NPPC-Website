# Live verification

Applied batch1135 to the live site on October 18, 2026. Dry run: 2,919 changes; apply: 2,919 changes; replay: zero changes, 2,919 already applied. Transaction hashes confirmed every unrelated case field (including charges, notes, dates and timestamps), all prisoner profiles, and all untargeted cases preserved. API cache cleared.

Independent live readback of all 11,953 cases confirmed every targeted sentence equals its committed replacement and every untargeted sentence is unchanged. Zero sentence fields retain either exact label. 226 fields became null; 11,233 sentence fields remain nonempty. Original text retained in committed payload and CSV only.
