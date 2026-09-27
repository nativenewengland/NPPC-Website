import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const directory = path.dirname(fileURLToPath(import.meta.url));
const rows = fs.readFileSync(path.join(directory, 'custody-posts.jsonl'), 'utf8').trim().split('\n').map(JSON.parse);
const normalize = (value = '') => value.toLowerCase().replace(/\s+/g, ' ').trim();
const groups = new Map();
for (const row of rows) {
  const normalized = normalize(row.description);
  const key = crypto.createHash('sha1').update(normalized).digest('hex');
  const group = groups.get(key) ?? { hash: key, description: row.description, postIds: [], urls: [], titles: new Set(), matchedProfiles: new Set() };
  group.postIds.push(row.id);
  group.urls.push(row.url);
  group.titles.add(row.title);
  for (const name of row.matchedProfiles) group.matchedProfiles.add(name);
  groups.set(key, group);
}
const output = [...groups.values()].map((group) => ({
  ...group,
  titles: [...group.titles],
  matchedProfiles: [...group.matchedProfiles],
})).sort((a, b) => b.postIds.length - a.postIds.length);
fs.writeFileSync(path.join(directory, 'custody-description-groups.jsonl'), `${output.map((row) => JSON.stringify(row)).join('\n')}\n`);
console.log(JSON.stringify({ posts: rows.length, uniqueDescriptions: output.length, groupsWithoutExistingMatches: output.filter((row) => !row.matchedProfiles.length).length }));
