# Requested activist photo audit — September 8, 2026

Read-only inspection of the live prisoner records and public-storage files: **8 missing photos; 7 already attached**. Batch 254 targets only the eight empty photo fields. Existing biographies, cases, dates, coordinates, and websites are outside scope.

| Person | Audit result | Action |
| --- | --- | --- |
| Bill Epton | Attached: `prisoners/bill-epton.jpg` | Leave alone |
| Carl Hampton | Missing | Fill only if still empty |
| Connie Matthews | Missing | Fill only if still empty |
| Grace Thorpe | Attached: `prisoners/grace-thorpe.jpg` | Leave alone |
| Janet McCloud | Missing | Fill only if still empty |
| Kent Ford | Missing | Fill only if still empty |
| Larry Gossett | Missing | Fill only if still empty |
| Larry Little | Missing | Fill only if still empty |
| Mabel Williams | Missing | Fill only if still empty |
| Madonna Thunder Hawk | Attached: `prisoners/madonna-thunder-hawk.jpg` | Leave alone |
| Malik Rahim | Attached: `prisoners/malik-rahim.jpg` | Leave alone |
| Martin Sostre | Attached: `prisoners/martin-sostre.webp` | Leave alone |
| Pete O'Neal | Attached: `prisoners/pete-oneal.jpg` | Leave alone |
| Ramona Bennett | Attached: `prisoners/ramona-bennett.jpg` | Leave alone |
| Scott Camil | Missing | Fill only if still empty |

Bill Epton and Pete O’Neal already have attached photos and are not in the payload. All seven attached files existed on the public disk at audit time. An attached value is preserved even if its file later disappears; this is not a broken-photo replacement batch.

Sources, identification evidence, original image links, credit and rights statements: [photo credits](../photos/CREDITS-batch254.md) and [payload](../fixes/batch254.json). Kent Ford and Larry Gossett have Creative Commons portraits; the Hampton uploader labels CC BY-NC; the McCloud publisher labels public domain. The remaining source images have no verified open reuse license; their actual rights statements are retained.

## Deployment

Validation passed on September 8, 2026: shell and PHP syntax checks; a read-only live preview found eight eligible rows; an isolated in-memory SQLite test copied the fifteen requested profiles and their cases, then verified all eight photo assignments, source hashes, preservation of the seven existing photos and every non-photo profile/case field, dry-run behavior, idempotent replay, newly attached photos, identity mismatch and file-collision rejection. Test photo writes used a unique temporary directory and were cleaned up. Production database connections were purged before mutation tests. All images were visually inspected. The four requested crops were verified pixel-for-pixel against the decoded source rectangles and saved as lossless PNGs.

No production writes were made during research. After merge, run from `/var/www/NPPC-Website`, following all earlier pending batches in order:

```sh
git pull origin main
sudo -u www-data bash database/data/run-batch-254.sh --dry-run
sudo -u www-data bash database/data/run-batch-254.sh
```

The batch validates all eight identities and checksums, checks destination collisions, preserves any nonempty photo, and clears the prisoner API cache after updates. It updates only `photo` and the normal `updated_at` timestamp. PsySH uses application-owned storage, avoiding the `/var/www/.config/psysh` permission error. A failed database transaction may leave a verified, unassigned asset file; replay reuses matching bytes safely.
