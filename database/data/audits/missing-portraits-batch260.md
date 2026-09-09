# Missing portraits — batch 260

This batch prepares **50 additional photographs** beyond the sixteen prepared in batches 258 and 259. No existing photograph, biography, date, case or personal/support website is replaced. [All previews, identity notes and source credits](../photos/CREDITS-batch260.md).

Initial encyclopedia image-index lookups covered 1,400 priority candidates and 600 additional backlog records. Those were discovery leads, not 2,000 completed archival investigations. The final fifty underwent individual identity/source checks and visual review. Known same-name errors (including the two Charles James Faulkners and the elder William Sloane Coffin) were excluded. The existing backlog CSV records progress without treating an unsuccessful lookup as proof that no portrait exists.

Selected assets include seven individual Scottsboro portraits, archival union-organizer and suffrage photographs, contemporary credited portraits, and photographs from Densho and the National Park Service. Source-side crops and thumbnails are retained unchanged. Several historical images have low resolution; none were reconstructed. Watermarked group-image candidates and posthumous paintings were left out.

The linked credits distinguish public-domain and Creative Commons images from copyrighted biographical images and the noncommercial license on James Omura. Source labels are retained accurately; no blanket reuse license is claimed.

The script validates all fifty identities and asset checksums before writing. Only null/empty photo fields are filled; a photo attached since research is preserved. Public storage collisions and concurrent changes are guarded. The prisoner API and museum caches are cleared after a successful write. No production data is changed during preparation.

Validation passed: Bash/PHP syntax checks, decoding and checksum validation for all fifty distinct images, visual review, no overlap with batches 258/259, and a read-only live execution of the batch reporting `Would update rows: 50` and `B260-OK`. The live preview did not install files or update database rows.

After merge and earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-260.sh --dry-run
sudo -u www-data bash database/data/run-batch-260.sh
```

## Profiles

- Abram Flaxer
- Arthur Lee Washington Jr.
- Ben Boloff
- Bill Ayers
- Bill Gebert
- Charles James Faulkner
- Charles Krumbein
- Charles S. Morehead
- Chitto Harjo
- Clennon Washington King Jr.
- Dominador Gómez
- Doris Stevens
- Edward James Olmos
- Harry Winitsky
- Henry David Thoreau
- Hosea Williams
- Jack Weinberg
- Jim Wallis
- John L. Spivak
- John R. Lawson
- Karl Soehnlein
- Leon Czolgosz
- Ludwig E. Katterfeld
- Maria Butina
- Marion Bachrach
- Murray Bookchin
- Phil Shinnick
- Rommie Loudd
- Rudolf Grossmann
- Tommy Sheridan
- Charlie Weems
- Olen Montgomery
- Ozie Powell
- Willie Roberson
- Inez García
- Ricardo Chavez-Ortiz
- Ross Winans
- Andy Wright
- Roy Wright
- Eugene Williams
- Charles Langston
- J. Tony Serra
- Wendell Furry
- Hank Adams
- Michael ZinZun
- Vernon Bellecourt
- William Sloane Coffin
- James Omura
- Frank Emi
- LaNada War Jack
