# Charges-only cleanup

Explicit user direction: remove extra material from charges, add nothing to case notes. Snapshot11953cases. 1666 updates limited to38 individually reviewed exact text mappings. No fields are cleared; no heuristic transformations execute on the server. Offense names, counts and statutes retained where present; contextual event labels and outcomes removed. Original and replacement charge strings are preserved in the committed payload and CSV audit, not public case notes.

Runner is transactional, validates identity and original charge strings, refuses concurrent changes, verifies every target, checks full-table hashes of every other case field and every profile, and verifies untouched cases. It changes charges only, including preserving timestamps. Idempotent replay supported. No biographies, notes, dates, cases or profiles are added.
