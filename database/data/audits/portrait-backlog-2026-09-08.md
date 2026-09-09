# Missing portrait backlog — September 8, 2026

Read-only live snapshot: 8,612 public prisoner profiles, 2,228 with attached photo files, 6,384 with a null or empty photo field. The CSV preserves all 6,384 missing-photo identities for continued research; it is a snapshot, not a live inventory or proof of exhaustive research.

Batch 258 prepares six verified portraits: Aaron Dixon, Betsy Graves Reyneau, Catherine M. Flanagan, Julia Hurlbut, Mary Winsor, and Louisine Havemeyer. See [photo previews, credits and licenses](../photos/CREDITS-batch258.md). Contemporary photographer descriptions and archival captions were checked against each existing profile, and every selected file was visually inspected. No new image edits were made.

Elizabeth Selden Rogers and Betty Gram have group-photo leads awaiting individual framing. Samuel Austin Worcester has a candidate whose source documentation is incomplete. These three remain unassigned. Other queued rows have not necessarily been individually researched. A missing photo does not imply that a verifiable portrait exists online.

The script checks exact profile IDs, slugs and names, validates image formats and checksums, and fills only an empty photo field. Existing photos are preserved, including ones attached after this snapshot. Bios, personal websites, dates and cases are outside this batch. Prisoner API and museum caches are invalidated after a successful write.

After all six additions deploy, if no other records change, coverage becomes 2,234 / 8,612 (25.94%), leaving 6,378 without photos. Deployment has not occurred during preparation.

After merge, pull main and apply earlier pending batches in order, then run:

```bash
sudo -u www-data bash database/data/run-batch-258.sh --dry-run
sudo -u www-data bash database/data/run-batch-258.sh
```
