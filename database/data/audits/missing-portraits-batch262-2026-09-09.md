# Batch 262 portrait audit — 2026-09-09

## Scope

100 existing prisoner records receive a photo only if their current photo is null or empty. Every selected image comes from a named National Archives Leavenworth photographic item. Existing biographies, case data, dates and websites are preserved. No database write or production asset copy is performed during preparation.

## Selection and verification

- Compared public archive catalog names with the missing-photo backlog, then checked the existing incarceration context and inmate identifiers. Rejected unrelated same-name prisoners and avoided duplicate photos of a single person.
- Visually reviewed the 100 original photographs and available identifying reverses. The [NARA IWW roster](https://www.archives.gov/files/kansas-city/press/newsletter/2017-october.pdf#page=6) corroborates the Chicago group.
- Preserved the original files and public catalog metadata. All crops retain the original pixels and are saved losslessly as PNG. Each image has a source URL, inmate number, identity explanation, rights statement and hashes.
- The database snapshot had 6,218 remaining entries after excluding batches 258–261; this batch selects another 100. This is a research backlog count, not a newly measured live percentage.
- Other candidate photos, including additional Leavenworth inmates and four named Zeigler defendants in Labor Defender, remain for future research. They are not included in this batch.

## Validation

- Verified 100 distinct profile IDs and 100 valid PNG portraits, with no overlap in selected IDs with batches 258–261.
- Verified every source, reverse and output checksum, and exact pixel equality between all 100 PNGs and their documented source rectangles. Visually reviewed all 100 finished crops.
- Bash and embedded PHP syntax checks passed. The runner is identical to the previously validated batch 261 runner except for the batch number.
- Read-only live preflight executed the runner's unchanged transaction/planning section with dry-run forced on and destination paths supplied in memory. All 100 UUID/name/slug matches passed; all 100 current photo fields were empty; no destination collisions occurred. Result: `Would update rows: 100` and `B262-OK`.
- Full source-image integrity was checked locally; the live preflight did not upload or copy the photo bytes. No production database rows or photo files were changed.

## Deployment

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-262.sh --dry-run
sudo -u www-data bash database/data/run-batch-262.sh
```

Run the final command only after the dry run completes with `B262-OK`. The runner verifies UUID/name/slug, source checksums and destination collisions, preserves attached photos, and clears the API and museum caches after writes. Reruns preserve the photos already filled.
