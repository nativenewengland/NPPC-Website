import fs from 'node:fs';
import path from 'node:path';

const dir = path.dirname(new URL(import.meta.url).pathname.replace(/^\/(.:)/, '$1'));
const ids = [
  '32121116817','33188405358','40099164593','47012457322','46340973894','46150163905','47063671261','47063460261','40099164753','33188405828',
  '49430697446','50052186911','18738610279','38686939440','18708003175','49460150493','8394393448','49440731607','33113183760','33561033751','15820019564',
  '33543869258','22675315353','21015381546','35338412690','26973410425','48550652996','8297283078','49691552157','49527168392','35615046081',
  '17096322617','8306115890','32667353404','45473641825','15674821642','31478558887','8584672326','28937943145','48716471002','47595369781'
];
const photos = new Map(fs.readFileSync(path.join(dir,'photostream.jsonl'),'utf8').trim().split(/\r?\n/).map(JSON.parse).map(p=>[p.id,p]));
const target = path.join(dir,'sources');
fs.mkdirSync(target,{recursive:true});
for (const id of ids) {
  const p = photos.get(id);
  if (!p?.url_o) throw new Error(`Missing original URL for ${id}`);
  if (fs.readdirSync(target).some(file => file.startsWith(`${id}.`))) continue;
  const output = path.join(target,`${id}.jpg`);
  const sourceUrl = p.url_o.replace(/_o\.[a-z0-9]+$/i, '_b.jpg');
  const response = await fetch(sourceUrl);
  if (!response.ok) throw new Error(`${id}: ${response.status}`);
  fs.writeFileSync(output, Buffer.from(await response.arrayBuffer()));
  console.log(`${id}\t${p.title}\t${output}`);
}
