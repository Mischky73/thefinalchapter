#!/usr/bin/env python3
import html
import os
import re
import sys
import urllib.parse
import urllib.request
import http.cookiejar

base = os.environ.get('TFC_TEST_URL', 'http://127.0.0.1:7788')
username = os.environ.get('TFC_TEST_USER')
password = os.environ.get('TFC_TEST_PASSWORD')
if not username or not password:
    print('TFC_TEST_USER und TFC_TEST_PASSWORD werden benötigt.', file=sys.stderr)
    sys.exit(2)

cookies = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))
login_html = opener.open(base + '/admin/login.php').read().decode()
token = re.search(r'name="csrf_token" value="([^"]+)"', login_html)
assert token, 'CSRF-Token fehlt'
post = urllib.parse.urlencode({
    'csrf_token': html.unescape(token.group(1)),
    'username': username,
    'password': password,
}).encode()
opener.open(urllib.request.Request(base + '/admin/login.php', data=post)).read()

def load(path: str) -> str:
    return opener.open(base + path).read().decode()

page = load('/admin/articles.php')
assert 'name="status"' in page, 'Statusfilter fehlt'
assert 'value="draft"' in page and 'value="published"' in page, 'Statusoptionen fehlen'
assert 'name="category"' in page, 'Kategorienfilter fehlt'
assert 'Filter anwenden' in page, 'Filter-Schaltfläche fehlt'

status_page = load('/admin/articles.php?status=draft')
assert 'badge-draft' in status_page, 'Entwurfsfilter liefert keine Entwürfe'
assert 'badge-published' not in status_page, 'Entwurfsfilter enthält veröffentlichte Artikel'
assert '<option value="draft" selected>' in status_page, 'Statusauswahl bleibt nicht erhalten'

category_page = load('/admin/articles.php?category=48')
assert 'In Flammen' in category_page, 'Kategorienfilter liefert keine Treffer'
assert '<option value="48" selected>' in category_page, 'Kategorieauswahl bleibt nicht erhalten'

combined = load('/admin/articles.php?status=published&category=48')
assert 'badge-published' in combined, 'Kombinationsfilter liefert keine veröffentlichten Artikel'
assert 'badge-draft' not in combined, 'Kombinationsfilter enthält Entwürfe'

print('OK: Backend-Filterformular und Filterausgabe funktionieren.')
