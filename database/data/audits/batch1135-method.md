# Sentence block removal

User explicitly requested removal of the 2,919 appended charges/verdict blocks identified by the live audit. Removes text beginning with the exact labels Charges as recorded: or Verdict as recorded: through that paragraph only. Preserves preceding sentence text and subsequent separate paragraphs. Fields containing only removed blocks become null. No content is added to case notes. Full before/after values retained in payload and CSV. Transaction guards identity and original sentence; hashes every other case field, all profiles, and all untargeted cases; idempotent replay required.

Affected cases: 2,919. Fields becoming empty: 226.
