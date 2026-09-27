import fs from 'node:fs';
import path from 'node:path';

const dir = path.dirname(new URL(import.meta.url).pathname.replace(/^\/(.:)/, '$1'));
const names = [
  'Jorge Luis Jimenez','Manuel Rabago Torres','Juan Bernardo Lebron','Juan Francisco Ortiz Medina','Armando Diaz Matos','Carmelo Alvarez Roman','Jose Antonio Otero Otero','Juan Hernandez Valle','Maximino Pedraza Martinez','Esteban Quinones Escute','Angel Luis Arzola Velez','Antonio Herrera Moreno','Carmen Dolores Otero de Torresola','Pedro Aviles Vargas','Julio Flores Medina','Miguel Vargas Nieves',
  'William H. Johnson','Lacey McKinney','Ethel Mae Thompson','Mary M. Carroll','S. P. Callahan','Reginald Booker','Thomas Rooney','Thomas Coleman','John A. Mote','Bill Bricker','Cade Ware','Lawrence “Deacon” McCubbin','Sammie Abbott','George Wiley','Debbie Danielle','Elizabeth Murphy Oliver','Laurence Henry','Frederick C. Weaver','Hugo Gellert','Livia Gellert','Joseph Winkowsky','Nanie Leah Washburn','Julius Hobson','Dion Diamond',
  'Walter Fauntroy','Yolanda King','Amy Carter','Coleman Young','Sandra Perrin','Theodore H. Parrish','Joseph Brinton “Brint” Dillingham','Samuel Yudkin','Carroll Carrozza','Carolyn Banks','Joan Hardy','William “Preacherman” Fesperman','Judy Erickson','Nancy Willis',
  'Marianne W. Fowler','Jean Marshall Clarke','Rose Smith',
  'David H. Whittlesey','Pamela C. Haynes','Marta C. Kusic','Carol J. Lawson','Jessie W. McQueen','Sheila P. Ryan','Robert E. Wooten','J. Holmes Smith','Ralph T. Templin','Marjorie Kendrick','Jane Fulton','Harold R. Lefever','Leonard P. Matlovich','Patrick B. “Paddy” Whalen',
  'Younos Mokhtarzada','Gregory Dunkel','Steve Moore','Marc Cullen','Betsy Banes Bell','Edward Stubbs','William Treanor',
  'Jesse L. Jackson','Jesse Jackson Jr.','Jonathan Luther Jackson','Harry Belafonte','Stevie Wonder','Coretta Scott King','James Abourezk','John N. Sturdivant','Sean McManus','Joan Brown Campbell','Alfred Dunston Jr.'
];

const fold = s => s.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[“”‘’.'-]/g, ' ').replace(/\s+/g, ' ').toLowerCase().trim();
const aliases = name => {
  const n = fold(name).replace(/\b(jr|h|b)\b/g, '').replace(/\s+/g, ' ').trim();
  const bits = n.split(' ');
  const out = new Set([fold(name), n, `${bits[0]} ${bits.at(-1)}`]);
  if (name.includes('McCubbin')) out.add('deacon mccubbin');
  if (name.includes('Dillingham')) out.add('brint dillingham');
  if (name.includes('Fesperman')) out.add('preacherman fesperman');
  if (name.includes('Whalen')) out.add('paddy whalen');
  if (name === 'Jesse L. Jackson') out.add('jesse jackson');
  return [...out].filter(x => x.length > 5);
};
const photos = fs.readFileSync(path.join(dir, 'photostream.jsonl'),'utf8').trim().split(/\r?\n/).map(JSON.parse);
const results = {};
for (const name of names) {
  const aa = aliases(name);
  const hits = [];
  for (const p of photos) {
    const title = fold(p.title || '');
    const description = fold(p.description?._content || '');
    let score = 0;
    for (const a of aa) {
      if (title.includes(a)) score = Math.max(score, 100 + a.length);
      if (description.includes(a)) score = Math.max(score, 20 + a.length);
    }
    if (score) hits.push({score,id:p.id,title:p.title,url_o:p.url_o,width:p.width_o,height:p.height_o,description:(p.description?._content||'').replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').slice(0,700)});
  }
  results[name] = hits.sort((a,b)=>b.score-a.score).slice(0,12);
}
fs.writeFileSync(path.join(dir,'photo-candidates.json'), JSON.stringify(results,null,2));
console.log(JSON.stringify(Object.fromEntries(Object.entries(results).map(([n,h])=>[n,h.length])),null,2));
