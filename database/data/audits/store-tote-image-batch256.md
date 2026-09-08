# Store announcement image replacement — batch 256

Replace the hero image for `/press-releases/nppc-launches-online-store` with the approved black NPPC tote mockup. The current live image is `articles/nppc-store.png`; the new file is `articles/nppc-store-tote-wide.png`.

The built-in image tool extended the orange background to a 1774 × 887 landscape composition. The full tote and lettering fit the article header and remain central for news-card crops. The final prompt and provenance are recorded in `fixes/batch256.json`. The user-supplied reference and generated square original remain outside the repository; the final website asset is shipped in `files/articles/nppc-store-tote-wide.png`.

The batch validates the article slug/title, expected prior image, asset dimensions and SHA-256. It installs a new public-storage file and updates only the article image column. It preserves the original file, body, intro, caption, dates and other metadata. Replays accept the same verified image; unexpected current images or destination-file collisions stop the batch. API and museum caches are forgotten after a real run. The new image URL avoids stale browser copies of the old image.

Validation completed: Bash syntax, PHP syntax on the server, PNG dimensions/checksum and a read-only live preview returning `B256-OK` and `Would attach: articles/nppc-store-tote-wide.png`. The expanded image was visually inspected. Database writes were not executed during preparation.

After merging and applying earlier pending batches in order:

```bash
cd /var/www/NPPC-Website
git pull origin main
sudo -u www-data bash database/data/run-batch-256.sh --dry-run
sudo -u www-data bash database/data/run-batch-256.sh
```

Confirm `B256-OK`, then reload the article and check its news card. No migrations or asset build are needed.
