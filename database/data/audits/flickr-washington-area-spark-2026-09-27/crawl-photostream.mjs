import fs from 'node:fs';

const accountUrl = 'https://www.flickr.com/photos/washington_area_spark/';
const outputPath = new URL('./photostream.jsonl', import.meta.url);
const summaryPath = new URL('./crawl-summary.json', import.meta.url);

const accountHtml = await (await fetch(accountUrl)).text();
const keyMatch = accountHtml.match(/site_key\s*=\s*"([a-f0-9]+)"/i);
if (!keyMatch) throw new Error('Flickr public site key not found');

const apiKey = keyMatch[1];
const userId = '57753972@N05';
const extras = [
  'description', 'date_upload', 'date_taken', 'last_update', 'tags',
  'machine_tags', 'media', 'path_alias', 'url_o', 'license',
].join(',');

const all = [];
let page = 1;
let pages = 1;
let reportedTotal = null;
do {
  const parameters = new URLSearchParams({
    method: 'flickr.people.getPublicPhotos', api_key: apiKey, user_id: userId,
    extras, per_page: '500', page: String(page), format: 'json', nojsoncallback: '1',
  });
  const response = await fetch(`https://api.flickr.com/services/rest/?${parameters}`);
  if (!response.ok) throw new Error(`Flickr API HTTP ${response.status} on page ${page}`);
  const payload = await response.json();
  if (payload.stat !== 'ok') throw new Error(JSON.stringify(payload));
  pages = Number(payload.photos.pages);
  reportedTotal = Number(payload.photos.total);
  all.push(...payload.photos.photo);
  process.stderr.write(`page ${page}/${pages}: ${all.length}\n`);
  page += 1;
} while (page <= pages);

const unique = [...new Map(all.map((photo) => [String(photo.id), photo])).values()];
fs.writeFileSync(outputPath, `${unique.map((photo) => JSON.stringify(photo)).join('\n')}\n`);
fs.writeFileSync(summaryPath, `${JSON.stringify({
  account: accountUrl,
  accountDisplayedCount: Number(accountHtml.match(/([\d,]+) Photos/)?.[1]?.replaceAll(',', '') ?? 0),
  apiReportedTotal: reportedTotal,
  retrievedRows: all.length,
  uniquePublicPosts: unique.length,
  pages,
  crawledAt: new Date().toISOString(),
}, null, 2)}\n`);
