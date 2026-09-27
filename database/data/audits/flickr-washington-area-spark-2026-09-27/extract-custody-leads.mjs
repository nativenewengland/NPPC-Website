import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const directory = path.dirname(fileURLToPath(import.meta.url));
const rows = fs.readFileSync(path.join(directory, 'custody-posts.jsonl'), 'utf8').trim().split('\n').map(JSON.parse);
const prisoners = JSON.parse(fs.readFileSync(path.join(directory, 'live-prisoners.json'), 'utf8'));
const custodyPattern = /\b(arrest(?:ed|s|ing)?|jail(?:ed)?|imprison(?:ed|ment)?|prison(?:er|ers|ed)?|incarcerat(?:ed|ion)|convict(?:ed|ion)?|sentenc(?:ed|e)|detain(?:ed|ment)?|custody|indict(?:ed|ment)?|charg(?:ed|es)|intern(?:ed|ment)?|court[- ]martial(?:ed)?|chain gang|political prisoner|held without bail|time in (?:jail|prison))\b/i;
const namePattern = /\b(?:[A-Z][A-Za-zÀ-ÖØ-öø-ÿ'’.-]+|[A-Z]\.)(?:\s+(?:(?:de|del|la|van|von|bin|al|El|O’|O')\s+)?(?:[A-Z][A-Za-zÀ-ÖØ-öø-ÿ'’.-]+|[A-Z]\.|Jr\.?|Sr\.?|II|III|IV)){1,4}\b/g;
const reject = /^(?:The|This|That|These|Those|When|After|Before|During|While|Police|Federal|United States|Washington|District of Columbia|New York|Black Panther|Communist Party|Socialist Party|Civil Rights|Supreme Court|Court of Appeals|Department of Justice|National Guard|American Civil Liberties|University of|House of|Senate|Congress|World War|Cold War|Vietnam War|Jim Crow|Ku Klux Klan|May Day|Memorial Day|Labor Day)\b/i;
const normalize = (value = '') => value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
const liveNameIndex = new Map();
for (const prisoner of prisoners) {
  for (const form of [prisoner.name, ...(prisoner.aka ?? '').split(/\s*;\s*/)]) {
    if (normalize(form)) liveNameIndex.set(normalize(form), prisoner.name);
  }
}

const sentences = new Map();
const candidates = new Map();
for (const row of rows) {
  const parts = row.description.split(/(?<=[.!?])\s+(?=[A-Z“"'])|\s*\n+\s*/).filter(Boolean);
  for (let index = 0; index < parts.length; index += 1) {
    if (!custodyPattern.test(parts[index])) continue;
    const context = [parts[index - 1], parts[index], parts[index + 1]].filter(Boolean).join(' ');
    const key = normalize(parts[index]);
    const entry = sentences.get(key) ?? { sentence: parts[index], context, postIds: [], urls: [], titles: [], matchedProfiles: new Set() };
    entry.postIds.push(row.id);
    entry.urls.push(row.url);
    entry.titles.push(row.title);
    for (const match of row.matchedProfiles) entry.matchedProfiles.add(match);
    sentences.set(key, entry);

    for (const rawName of context.match(namePattern) ?? []) {
      const name = rawName.replace(/[.,;:]+$/, '').trim();
      if (reject.test(name) || name.split(' ').length > 5) continue;
      const nameKey = normalize(name);
      const candidate = candidates.get(nameKey) ?? { name, exactDatabaseMatch: liveNameIndex.get(nameKey) ?? null, mentions: 0, custodySentences: new Set(), postIds: new Set(), urls: new Set(), matchedProfiles: new Set() };
      candidate.mentions += 1;
      candidate.custodySentences.add(parts[index]);
      candidate.postIds.add(row.id);
      candidate.urls.add(row.url);
      for (const match of row.matchedProfiles) candidate.matchedProfiles.add(match);
      candidates.set(nameKey, candidate);
    }
  }
}

const serialize = (entry) => ({
  ...entry,
  postIds: [...entry.postIds], urls: [...entry.urls], titles: entry.titles ? [...new Set(entry.titles)] : undefined,
  matchedProfiles: [...entry.matchedProfiles], custodySentences: entry.custodySentences ? [...entry.custodySentences] : undefined,
});
const sentenceRows = [...sentences.values()].map(serialize).sort((a, b) => b.postIds.length - a.postIds.length || a.sentence.localeCompare(b.sentence));
const candidateRows = [...candidates.values()].map(serialize).sort((a, b) => b.mentions - a.mentions || a.name.localeCompare(b.name));
fs.writeFileSync(path.join(directory, 'unique-custody-sentences.jsonl'), `${sentenceRows.map((row) => JSON.stringify(row)).join('\n')}\n`);
fs.writeFileSync(path.join(directory, 'name-candidates.jsonl'), `${candidateRows.map((row) => JSON.stringify(row)).join('\n')}\n`);
fs.writeFileSync(path.join(directory, 'lead-summary.json'), `${JSON.stringify({
  custodyPosts: rows.length,
  uniqueCustodySentences: sentenceRows.length,
  extractedNameCandidates: candidateRows.length,
  candidatesWithoutExactDatabaseMatch: candidateRows.filter((row) => !row.exactDatabaseMatch).length,
  candidatesInPostsWithoutMatchedProfiles: candidateRows.filter((row) => !row.matchedProfiles.length).length,
}, null, 2)}\n`);
console.log(fs.readFileSync(path.join(directory, 'lead-summary.json'), 'utf8'));
