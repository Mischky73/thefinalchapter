#!/usr/bin/env python3
import json,os,re
B='/home/michael/projects/thefinalchapter'
d=json.load(open(B+'/import/summer_breeze_articles_data.json',encoding='utf8'))
i=json.load(open(B+'/import/summer_breeze_official_details.json',encoding='utf8'))
assert len(d)==46 and len({x['slug'] for x in d})==46
assert {x['source_url'] for x in d}=={x['url'] for x in i}
for x in d:
 assert '2025-01-01'<=x['date']<='2026-07-15'
 assert x['source_url'].startswith('https://www.summer-breeze.de/de/news/')
 assert x['source_url'] in x['content']
 assert x['image'].startswith('/assets/img/uploads/summer-breeze/')
 assert os.path.isfile(B+x['image']) and os.path.getsize(B+x['image'])>1000
 assert x['title'] and x['excerpt'] and len(x['content'])>100
 assert not re.search(r'<(?:script|iframe)\b',x['content'],re.I)
print('VALID: 46 Artikel, Quellen, Slugs und lokale Bilder vollständig.')
