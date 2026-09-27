import fs from 'node:fs';
import path from 'node:path';

const dir = path.dirname(new URL(import.meta.url).pathname.replace(/^\/(.:)/, '$1'));
const entries = JSON.parse(fs.readFileSync(path.join(dir, 'photo-manifest.json'), 'utf8'));
const pageSlug = entry => entry.name === 'Dion Diamond' ? 'dion-diamond-2' : entry.slug;
const failures = [];
for (const entry of entries) {
  for (const [kind, url] of [
    ['page', `https://politicalprisonercoalition.org/prisoner/${pageSlug(entry)}`],
    ['image', `https://politicalprisonercoalition.org/storage/${entry.photo}`],
  ]) {
    const response = await fetch(url, {redirect: 'follow'});
    if (!response.ok || (kind === 'image' && !response.headers.get('content-type')?.startsWith('image/'))) {
      failures.push({name: entry.name, kind, status: response.status, contentType: response.headers.get('content-type'), url});
    }
  }
}
if (failures.length) {
  console.error(JSON.stringify(failures, null, 2));
  process.exit(1);
}
console.log(JSON.stringify({verifiedProfiles: entries.length, verifiedPages: entries.length, verifiedImages: entries.length}, null, 2));
