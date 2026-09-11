# Chronological prisoner image research — 100 additions

Added verified photographs, portraits, silhouettes, or historical narrative depictions to 100 live prisoner entries whose image fields were empty immediately before the update.

## Coverage

- Reviewed all 106 image-free entries dated to, or assigned to, the 1700s before moving to the 1800s.
- Reviewed all 208 image-free entries dated to, or assigned to, the 1800s before moving to the 1900s.
- Continued through the early 1900s and First World War cases, reaching the target while researching the 1918 cohort. The 1918 cohort and later years are not exhausted. Follow-up sources sometimes revealed images for other entries in the same era.
- Additions by the existing case-date/era classification: 7 in the 1700s, 33 in the 1800s, and 60 in the 1900s.
- Four additions restore previously removed images: the identified silhouettes of Henry Drinker, John Pemberton and Samuel Pleasants, and the photograph of Eugene Debs. These fill empty fields but are not described as newly discovered likenesses.

A first-pass negative means no verified usable image was found in the searches performed, not that no image exists. The [314-entry early review ledger](chronological-portraits-2026-09-11-early-review.csv) records each early entry. The [retained search queries](chronological-portraits-2026-09-11-queries.json) support continuing the research.

## Sources and image treatment

[All 100 image previews, credits, identity evidence and originals](../photos/CREDITS-chronological-portraits-2026-09-11.md) are preserved, with [machine-readable provenance](../fixes/chronological-portraits-2026-09-11.json). Institutional archives, contemporary newspapers and journals, historical books, and corroborated biographical sources were used. The source log distinguishes contemporary photos, later portraits, narrative scenes, and full group images.

Only rectangular crops, lossless right-angle rotations and ordinary file conversions were used; the Grosser frontispiece required normal PDF rendering to combine its segmented scan. No AI image generation, restoration, colorization or retouching. Original files, crop coordinates, dimensions and checksums are retained. Small or faded sources retain their original limitations.

Rights are recorded per image. Several reproductions have incomplete rights metadata, including the 1961 UPI Max Sandin clipping; no open-license or public-domain claim is made for those. Period publication or an explicit institutional statement supports the public-domain claims where given.

## Verification

- Production dry run identified exactly 100 empty, identity-matched entries.
- Production transaction filled exactly 100 photo fields.
- Every other raw prisoner field, including timestamps, and every associated case record remained unchanged in before/after comparison.
- All 100 public image URLs returned images whose SHA-256 hashes match the prepared files.
- Representative public prisoner pages displayed the new image paths.
- Original hashes, output hashes, dimensions, and pixel equality for each crop/rotation/conversion were checked locally.

[Per-image public verification](chronological-portraits-2026-09-11-verification.json). The [guarded application script](../run-chronological-portraits-2026-09-11.sh) preserves nonempty photo fields, checks file hashes and identity, and clears the prisoner/museum caches.

## Deliberately unresolved examples

Common names were not treated as identity evidence. Holds include ambiguous father/son James Bartlett identification, contradictory Henry/David Fry labels, John Corbly misidentification, Lauro Aguirre the educator versus the older political exile, Tomas/Juan Sarabia conflicting labels, and ambiguous Madeleine Watson/Gertrude Crocker catalog attribution. Several Everett and conscientious-objector collections have finding aids but no accessible identified image. These require further research.
