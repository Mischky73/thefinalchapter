#!/usr/bin/env python3
import json,html,os
from urllib.parse import urlparse
BASE='/home/michael/projects/thefinalchapter'
details=json.load(open(BASE+'/import/summer_breeze_official_details.json',encoding='utf8'))
images=json.load(open(BASE+'/import/summer_breeze_image_map.json',encoding='utf8'))
rewrites={}
for i in (1,2):
 p=f'{BASE}/import/summer_breeze_rewrite_output_{i}.json'
 if not os.path.isfile(p):raise SystemExit('Fehlt: '+p)
 part=json.load(open(p,encoding='utf8'))
 if len(part)!=23:raise SystemExit(f'Teil {i}: {len(part)} statt 23')
 for x in part:
  if x['url'] in rewrites:raise SystemExit('Doppelte URL: '+x['url'])
  rewrites[x['url']]=x
if set(rewrites)!={x['url'] for x in details}:raise SystemExit('URL-Mengen stimmen nicht überein')
out=[]
for src in details:
 r=rewrites[src['url']]
 tail=urlparse(src['url']).path.rstrip('/').split('/')[-1]
 out.append({'date':src['date'],'title':r['title'].strip(),'slug':'summer-breeze-offiziell-'+tail,'excerpt':r['excerpt'].strip(),'content':r['content'].strip()+f'\n<p><strong>Quelle:</strong> <a href="{html.escape(src["url"],quote=True)}" target="_blank" rel="noopener noreferrer">Offizielle Meldung des Summer Breeze Open Air</a></p>','image':images[src['url']],'source_url':src['url']})
json.dump(out,open(BASE+'/import/summer_breeze_articles_data.json','w'),ensure_ascii=False,indent=2)
print('Erstellt:',len(out),'Artikel')
