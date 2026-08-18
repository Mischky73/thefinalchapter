#!/usr/bin/env python3
import json,os,re,sys
base='/home/michael/projects/thefinalchapter'
data=json.load(open(base+'/import/wacken_articles_data.json',encoding='utf8'))
inv=json.load(open(base+'/import/wacken_official_details.json',encoding='utf8'))
assert len(data)==229, len(data)
assert len({x['slug'] for x in data})==229
assert len({x['source_url'] for x in data})==229
assert {x['source_url'] for x in data}=={x['url'] for x in inv}
for x in data:
 assert x['source_url'].startswith('https://www.wacken.com/de/news-details/')
 assert x['source_url'] in x['content']
 assert re.fullmatch(r'202[56]-\d\d-\d\d',x['date'])
 assert x['title'].strip() and x['excerpt'].strip() and x['content'].strip()
 assert len(x['title'])<=255 and len(x['slug'])<=255
 assert x['image'].startswith('/assets/img/uploads/wacken-open-air/')
 assert os.path.isfile(base+x['image']) and os.path.getsize(base+x['image'])>500
 assert '<script' not in x['content'].lower() and '<iframe' not in x['content'].lower()
print('VALID: 229 Artikel, Quellen, Slugs und lokale Bilder vollständig.')
