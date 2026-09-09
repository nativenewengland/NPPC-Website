# Consolidate NPPC author identities — batch 257

The user requested that NPPC Communications become National Political Prisoner Coalition. The live database has an existing canonical coalition author with 71 published articles and an avatar, plus a duplicate Communications author with three published articles and no avatar.

This batch reassigns the store, mobile-app and quiz announcements to the existing coalition author, then removes the empty duplicate inside the same transaction. After this batch the canonical author has 74 articles, assuming no intervening additions. It changes only `articles.author_id`; article text, images, dates, timestamps, captions and the canonical author profile are preserved.

Exact author IDs, names, slugs and the three article slugs are recorded in the payload. Unexpected author assignments, new articles under the duplicate author, changed identities or missing articles stop the batch. Replay succeeds without additional changes. The three publishing commands now use the canonical author slug, and the old `/author/nppc-communications` address redirects permanently to the canonical author page.

Validation: Bash/PHP syntax; read-only live preview confirms three reassignments and `B257-OK`; isolated in-memory SQLite tests confirm dry-run preservation, successful reassignment/removal, preservation of all other fields, idempotency, and refusal on unexpected authors or additional articles (`B257-TESTS-OK`). Production was not modified during preparation.

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-257.sh --dry-run
sudo -u www-data bash database/data/run-batch-257.sh
```

Expect `B257-OK`. No migration or frontend build is needed.
