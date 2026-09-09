# Batch 263 portrait audit — 2026-09-09

## Scope

Fill 100 existing prisoner records only when the photo field is null or empty: 66 National Archives Leavenworth photographs, 33 individually identified portraits from Labor Defender (1926 and 1930), and Becky Edelsohn from the Library of Congress Bain collection. No biographies, case information, dates, names, support websites or attached photos are replaced.

The working research backlog had 6,118 records after excluding batches 258–262. That is a snapshot count, not a newly measured live percentage. The final read-only preflight separately confirmed all 100 selected records still lack photos.

## Identification and provenance

- Archive names, visible inscriptions, inmate numbers, caption positions and the existing case context were checked. Named group photographs are cropped according to printed row orders; their originals retain the captions for review.
- National Archives originals, available reverses, catalog metadata and the NARA IWW roster are preserved. Labor Defender originals are the embedded images extracted from the linked PDF issues without re-encoding. Shared scans are stored once. The Bain full-resolution original and Commons rights metadata are preserved.
- Different Carl Larson profiles are deliberately distinguished: the Swedish wartime deportee and the Buffalo 1930 organizer have different UUIDs, slugs, case histories and photographs.
- W. H. Butler was deferred because the available photograph's inmate chronology conflicts with the short July 1918 case; James A. G. Perry because the available James Perry photograph did not establish the same individual; George P. Yarlott because the candidate was George R. Yarlott. No photos were assigned to these uncertain matches.
- Catalog/inscription discrepancies for Frank Moran and Perley J. Burns, and spelling variants including Albert J. Bloss / Albert Bloss Jr., are explicitly recorded with supporting context in the individual credits. Existing database text remains untouched.

## Image handling

All 100 finished PNGs were visually reviewed. Exact rectangular crops retain the original pixels; some magazine scans were first rotated by a lossless 90- or 270-degree transpose. Coordinates refer to the rotated image, and original dimensions and hashes are recorded. No resampling, retouching or AI generation was used. Historical fading and halftone printing remain visible.

[View all portraits, source links, identity notes and credits](../photos/CREDITS-batch263.md).

## Validation

- 100 unique UUIDs and 100 unique output hashes; no selected-ID overlap with batches 258–262.
- All original, identifying-reverse and output hashes verified. Every output pixel equals the corresponding original crop after any recorded right-angle transpose.
- Bash and embedded PHP syntax passed; the runner differs from batch 262 only in batch number. The single-quoted PHP block contains no apostrophes.
- The unchanged transaction/planning portion of the runner passed a forced read-only live preflight: `Would update rows: 100` and `B263-OK`. This checked UUID/name/slug, empty photo fields and storage destination collisions. Photo decoding and byte equality were checked locally; no photos were copied and no database rows were changed on the server.
- Git whitespace and explicit file-manifest checks passed before commit.

## Deployment

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-263.sh --dry-run
sudo -u www-data bash database/data/run-batch-263.sh
```

Run the final command only after the dry run completes with `B263-OK`. The idempotent batch validates identities and assets before writes, fills only empty photo fields, protects existing destination files and concurrent edits, and clears the prisoner API and museum caches. Its application-owned PsySH directory avoids the www-data home-directory permission error.
