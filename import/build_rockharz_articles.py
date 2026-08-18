#!/usr/bin/env python3
import json,html,os,re,unicodedata
from urllib.parse import urlparse,unquote
B='/home/michael/projects/thefinalchapter';details=json.load(open(B+'/import/rockharz_official_details.json',encoding='utf8'));images=json.load(open(B+'/import/rockharz_image_map.json',encoding='utf8'));rw={}
for i,n in ((1,30),(2,30),(3,29)):
 p=f'{B}/import/rockharz_rewrite_output_{i}.json'
 if not os.path.isfile(p):raise SystemExit('Fehlt: '+p)
 a=json.load(open(p,encoding='utf8'))
 if len(a)!=n:raise SystemExit(f'Teil {i}: {len(a)} statt {n}')
 for x in a:
  if x['url'] in rw:raise SystemExit('Doppelte URL: '+x['url'])
  rw[x['url']]=x
if set(rw)!={x['url'] for x in details}:raise SystemExit('URL-Mengen stimmen nicht überein')
def slug(u):
 s=unquote(urlparse(u).path.rstrip('/').split('/')[-1]);s=unicodedata.normalize('NFKD',s).encode('ascii','ignore').decode().lower();s=re.sub(r'[^a-z0-9]+','-',s).strip('-');return 'rockharz-offiziell-'+(s or 'meldung')
out=[]
for src in details:
 r=rw[src['url']];out.append({'date':src['date'],'title':r['title'].strip(),'slug':slug(src['url']),'excerpt':r['excerpt'].strip(),'content':r['content'].strip()+f'\n<p><strong>Quelle:</strong> <a href="{html.escape(src["url"],quote=True)}" target="_blank" rel="noopener noreferrer">Offizielle Meldung des ROCKHARZ Festivals</a></p>','image':images[src['url']],'source_url':src['url']})
if len({x['slug'] for x in out})!=len(out):raise SystemExit('Slug-Kollision')
json.dump(out,open(B+'/import/rockharz_articles_data.json','w'),ensure_ascii=False,indent=2);print('Erstellt:',len(out),'Artikel')
