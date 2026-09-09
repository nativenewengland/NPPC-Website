# Missing portraits — batch 259

This second batch prepares ten additional photos for profiles in the September 8 backlog: Anne Waldman, Dennis Batt, Eugene Debs, Henry E. Peck, Howard Morland, Juanita Morrow Nelson, Norman Mailer, Paula Jakobi, Ralph Plumb and William F. Dunne. See [previews and source credits](../photos/CREDITS-batch259.md).

An initial image-index search covered 119 backlog candidates whose descriptions identify writers, professors, organizers or public figures, plus targeted suffragist and conscientious-objector searches. Index matches were only leads: selected files were checked against profile identities and source captions and visually inspected. No result in an index does not mean no portrait exists. Research progress and unresolved leads are recorded in the existing backlog CSV.

Two mismatches were excluded: a Charles James Faulkner photograph depicts the younger senator rather than the older diplomat, and a Per Laursen photograph depicts a darts player rather than the conscientious objector. Wendell Furry, Marion Bachrach and John L. Spivak remain pending for further source/rights review. No records were merged or otherwise corrected during this photo-only task.

The batch preserves existing photos and checks exact IDs, slugs, names, file formats and checksums. It copies the reviewed assets to public storage, fills only an empty photo field, and clears the prisoner API and museum caches after writes. Historical newspaper images have limited resolution, and Juanita Nelson is shown full-length. Source-side edits and Wikimedia thumbnails are documented; no new pixel edits were made.

Validation: Bash/PHP syntax checks, visual review of all ten selected files, and a read-only live preview. Preparation does not change production data. After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-259.sh --dry-run
sudo -u www-data bash database/data/run-batch-259.sh
```
