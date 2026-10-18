# Live charges-only cleanup verified

1666exact-match case updates applied; idempotent replay reports1666already applied and zero writes. All11953live rows read back: each target equals its reviewed replacement, all untargeted charges identical, all other exported fields unchanged. Transactional full-table hashes verified every case field except charges and every prisoner field, preserving all notes and timestamps. No fields cleared;11749nonemptycharge fields preserved.

Public checks:
https://politicalprisonercoalition.org/prisoner/huckleberry
https://politicalprisonercoalition.org/prisoner/janine-phillips-africa
https://politicalprisonercoalition.org/prisoner/zoe-larmey

The earlier5807case heuristic proposal was rejected by automatic approval review and never applied. Only38individually reviewed exact mappings were subsequently committed and deployed. Remaining proposals require further review and are not complete.
