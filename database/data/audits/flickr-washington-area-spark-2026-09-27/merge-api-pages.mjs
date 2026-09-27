import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const directory = path.dirname(fileURLToPath(import.meta.url));
const files = fs.readdirSync(directory)
  .filter((name) => /^api-page-\d+\.json$/.test(name))
  .sort((left, right) => Number(left.match(/\d+/)[0]) - Number(right.match(/\d+/)[0]));

const rows = files.flatMap((name) => JSON.parse(fs.readFileSync(path.join(directory, name), 'utf8')).photos.photo);
const unique = [...new Map(rows.map((row) => [String(row.id), row])).values()];
fs.writeFileSync(path.join(directory, 'photostream.jsonl'), `${unique.map((row) => JSON.stringify(row)).join('\n')}\n`);
fs.writeFileSync(path.join(directory, 'crawl-summary.json'), `${JSON.stringify({
  account: 'https://www.flickr.com/photos/washington_area_spark/',
  accountDisplayedCount: 5356,
  apiReportedTotal: Number(JSON.parse(fs.readFileSync(path.join(directory, files[0]), 'utf8')).photos.total),
  retrievedRows: rows.length,
  uniquePublicPosts: unique.length,
  pages: files.length,
  crawledAt: new Date().toISOString(),
}, null, 2)}\n`);
console.log(JSON.stringify({ files: files.length, rows: rows.length, unique: unique.length }));
