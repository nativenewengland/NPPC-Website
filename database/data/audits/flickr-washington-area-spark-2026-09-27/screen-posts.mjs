import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const directory = path.dirname(fileURLToPath(import.meta.url));
const posts = fs.readFileSync(path.join(directory, 'photostream.jsonl'), 'utf8').trim().split('\n').map(JSON.parse);
const prisoners = JSON.parse(fs.readFileSync(path.join(directory, 'live-prisoners.json'), 'utf8'));

const decode = (value = '') => value
  .replace(/<a\b[^>]*>(.*?)<\/a>/gis, '$1')
  .replace(/<[^>]+>/g, ' ')
  .replace(/&nbsp;/g, ' ')
  .replace(/&amp;/g, '&')
  .replace(/&quot;/g, '"')
  .replace(/&#39;|&apos;/g, "'")
  .replace(/\s+/g, ' ')
  .trim();

const normalize = (value = '') => decode(value).normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
  .toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
const custodyPattern = /\b(arrest(?:ed|s|ing)?|jail(?:ed)?|imprison(?:ed|ment)?|prison(?:er|ers|ed)?|incarcerat(?:ed|ion)|convict(?:ed|ion)?|sentenc(?:ed|e)|detain(?:ed|ment)?|custody|indict(?:ed|ment)?|charg(?:ed|es)|intern(?:ed|ment)|court[- ]martial(?:ed)?|chain gang|political prisoner|held without bail|served (?:a |\d)|time in (?:jail|prison))\b/i;
const activismPattern = /\b(protest|demonstrat|strike|union|labor|civil rights|anti[- ]war|peace|communist|socialist|radical|activist|organizer|boycott|sit[- ]in|freedom|segregat|vietnam|draft|suffrag|black panther|young lords|wobbl|iww|abolition|resistance|revolution)\w*/i;

const nameForms = [];
for (const prisoner of prisoners) {
  for (const form of [prisoner.name, ...(prisoner.aka ?? '').split(/\s*;\s*/)]) {
    const normalized = normalize(form);
    if (normalized.split(' ').length >= 2 && normalized.length >= 7) nameForms.push([normalized, prisoner.name]);
  }
}

const screened = posts.map((post) => {
  const text = decode(`${post.title ?? ''} ${post.description?._content ?? post.description ?? ''} ${post.tags ?? ''}`);
  const normalized = normalize(text);
  const matchedProfiles = [...new Set(nameForms.filter(([form]) => normalized.includes(form)).map(([, name]) => name))];
  return {
    id: String(post.id),
    url: `https://www.flickr.com/photos/washington_area_spark/${post.id}`,
    title: decode(post.title),
    description: decode(post.description?._content ?? post.description ?? ''),
    tags: post.tags ?? '',
    dateupload: post.dateupload ?? null,
    datetaken: post.datetaken ?? null,
    custodyKeyword: custodyPattern.test(text),
    activismKeyword: activismPattern.test(text),
    matchedProfiles,
  };
});

const custody = screened.filter((row) => row.custodyKeyword);
const priority = custody.filter((row) => row.activismKeyword);
fs.writeFileSync(path.join(directory, 'custody-posts.jsonl'), `${custody.map((row) => JSON.stringify(row)).join('\n')}\n`);
fs.writeFileSync(path.join(directory, 'priority-posts.jsonl'), `${priority.map((row) => JSON.stringify(row)).join('\n')}\n`);
fs.writeFileSync(path.join(directory, 'screen-summary.json'), `${JSON.stringify({
  posts: posts.length,
  postsWithCustodyLanguage: custody.length,
  postsWithCustodyAndActivismLanguage: priority.length,
  custodyPostsMatchingExistingProfiles: custody.filter((row) => row.matchedProfiles.length).length,
  custodyPostsWithoutExistingProfileMatch: custody.filter((row) => !row.matchedProfiles.length).length,
}, null, 2)}\n`);
console.log(fs.readFileSync(path.join(directory, 'screen-summary.json'), 'utf8'));
