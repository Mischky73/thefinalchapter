#!/usr/bin/env python3
import json,re,html,os
from urllib.parse import urlparse
BASE='/home/michael/projects/thefinalchapter'
details=json.load(open(BASE+'/import/wacken_official_details.json',encoding='utf8'))
images=json.load(open(BASE+'/import/wacken_image_map.json',encoding='utf8'))
rewrites={}
expected=(77,76,76)
for i,count in enumerate(expected,1):
 p=f'{BASE}/import/wacken_rewrite_output_{i}.json'
 if not os.path.isfile(p): raise SystemExit(f'Fehlt: {p}')
 part=json.load(open(p,encoding='utf8'))
 if len(part)!=count: raise SystemExit(f'Teil {i}: erwartet {count}, erhalten {len(part)}')
 for x in part:
  if x['url'] in rewrites:raise SystemExit('Doppelte URL: '+x['url'])
  rewrites[x['url']]=x
if set(rewrites)!={x['url'] for x in details}:raise SystemExit('URL-Menge der Redaktionen stimmt nicht mit Inventar überein.')
articles=[]
for src in details:
 r=rewrites[src['url']]
 slug='woa-offiziell-'+urlparse(src['url']).path.rstrip('/').split('/')[-1]
 content=r['content'].strip()+f'\n<p><strong>Quelle:</strong> <a href="{html.escape(src["url"],quote=True)}" target="_blank" rel="noopener noreferrer">Offizielle Meldung des Wacken Open Air</a></p>'
 articles.append({'date':src['date'],'title':r['title'].strip(),'slug':slug,'excerpt':r['excerpt'].strip(),'content':content,'image':images[src['url']],'source_url':src['url']})
json.dump(articles,open(BASE+'/import/wacken_articles_data.json','w'),ensure_ascii=False,indent=2)
print('Erstellt:',len(articles),'Artikel')
