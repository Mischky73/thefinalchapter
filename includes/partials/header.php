<?php
// Gemeinsamer Header für alle Frontend-Seiten
if (!isset($pageTitle))  $pageTitle  = SITE_NAME;
if (!isset($activePage)) $activePage = '';
if (!isset($pageDescription)) {
  $pageDescription = 'The Final Chapter ist dein Metal-Magazin aus Südthüringen: News, Reviews, Festivalberichte und Rückblicke von Wacken bis Summer Breeze.';
}
if (!isset($canonicalUrl)) {
  $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $requestQuery = $_SERVER['QUERY_STRING'] ?? '';
  $canonicalUrl = SITE_URL . $requestPath . ($requestQuery !== '' ? '?' . $requestQuery : '');
}
$pageOgTitle = trim(strip_tags((string)$pageTitle));
$pageOgDescription = trim(strip_tags((string)$pageDescription));
$pageOgImage = $pageImage ?? (SITE_URL . '/assets/img/bg-finalchapter.jpg');
require_once __DIR__ . '/../nav.php';
$nav = getNavItems();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= h($pageDescription) ?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<meta name="theme-color" content="#0d0d0d">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="canonical" href="<?= h($canonicalUrl) ?>">
<link rel="icon" href="<?= SITE_URL ?>/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/img/favicon.png">
<meta property="og:type" content="<?= isset($article) && is_array($article ?? null) ? 'article' : 'website' ?>">
<meta property="og:locale" content="de_DE">
<meta property="og:site_name" content="<?= h(SITE_NAME) ?>">
<meta property="og:title" content="<?= h($pageOgTitle) ?>">
<meta property="og:description" content="<?= h($pageOgDescription) ?>">
<meta property="og:url" content="<?= h($canonicalUrl) ?>">
<meta property="og:image" content="<?= h($pageOgImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($pageOgTitle) ?>">
<meta name="twitter:description" content="<?= h($pageOgDescription) ?>">
<meta name="twitter:image" content="<?= h($pageOgImage) ?>">
<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => SITE_NAME,
  'url' => SITE_URL . '/',
  'description' => $pageOgDescription,
  'inLanguage' => 'de-DE',
  'publisher' => [
    '@type' => 'Organization',
    'name' => SITE_NAME,
    'logo' => [
      '@type' => 'ImageObject',
      'url' => SITE_URL . '/assets/img/favicon.png',
    ],
  ],
  'potentialAction' => [
    '@type' => 'SearchAction',
    'target' => SITE_URL . '/search.php?q={search_term_string}',
    'query-input' => 'required name=search_term_string',
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
<style>
/* ── Dropdown-Menü ──────────────────────────────────────────────── */
.main-nav ul { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:0; }
.main-nav li { position:relative; }
.main-nav li a {
  display:block; padding:.55rem 1.1rem;
  color:var(--text-muted); text-decoration:none;
  font-size:.9rem; letter-spacing:.04em; text-transform:uppercase;
  transition:color .15s;
  white-space:nowrap;
}
.main-nav li a:hover,
.main-nav li a.active { color:var(--white); }

/* Dropdown */
.main-nav li ul {
  display:none; position:absolute; top:100%; left:0;
  background:var(--bg2); border:1px solid var(--border);
  border-top:2px solid var(--accent); min-width:180px;
  z-index:200; flex-direction:column;
}
.main-nav li:hover > ul,
.main-nav li:focus-within > ul,
.main-nav li.submenu-open > ul { display:flex; }
.main-nav .dropdown-toggle {
  display:none; position:absolute; right:.15rem; top:.15rem;
  width:2.25rem; height:2.25rem; border:0; background:transparent;
  color:var(--text-light); cursor:pointer;
}
.main-nav li ul li a {
  padding:.5rem 1.1rem; border-bottom:1px solid var(--border2);
  font-size:.85rem;
}
.main-nav li ul li:last-child a { border-bottom:none; }

/* Pfeil bei Dropdown-Eltern */
.main-nav li.has-children > a::after {
  content: ' ▾'; font-size:.7rem; opacity:.6;
}
@media (max-width: 600px) {
  .main-nav li.has-children > a { padding-right:2.75rem; }
  .main-nav li.has-children > a::after { content:''; }
  .main-nav .dropdown-toggle { display:block; }
  .main-nav li ul {
    position:static; min-width:0; margin-left:1rem;
    border-top:0; border-left:1px solid var(--border2);
  }
  .main-nav li:hover > ul:not(:focus-within) { display:none; }
  .main-nav li.submenu-open > ul { display:flex; }
}
</style>
</head>
<body>

<header class="site-header">
  <div class="container">
    <div class="header-top">
      <a href="<?= SITE_URL ?>" class="site-logo">The Final Chapter</a>
      <div class="header-actions">
        <div class="header-search" role="search">
          <button class="search-toggle" aria-label="Suche" aria-expanded="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </button>
          <form class="header-search-form" action="<?= SITE_URL ?>/search.php" method="get">
            <input type="search" name="q" class="header-search-input" placeholder="Suchen..." autocomplete="off" required>
            <button type="submit" aria-label="Suchen">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
            </button>
          </form>
        </div>
        <button class="nav-toggle" aria-label="Menü" aria-expanded="false" aria-controls="main-nav">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>

    <nav class="main-nav" id="main-nav" role="navigation" aria-label="Hauptnavigation">
      <ul>
        <?php foreach ($nav['top'] as $item):
          $hasChildren = !empty($nav['children'][$item['id']]);
          $url = $item['url'];
          // Interne URLs mit SITE_URL prefixen
          if ($url !== '#' && !str_starts_with($url, 'http')) $url = SITE_URL . $url;
        ?>
        <li class="<?= $hasChildren ? 'has-children' : '' ?>">
          <a href="<?= htmlspecialchars($url) ?>"
             <?= $item['target'] === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
            <?= htmlspecialchars($item['label']) ?>
          </a>
          <?php if ($hasChildren): ?>
          <button type="button" class="dropdown-toggle" aria-label="Untermenü <?= h($item['label']) ?> öffnen" aria-expanded="false">▾</button>
          <ul>
            <?php foreach ($nav['children'][$item['id']] as $child):
              $curl = $child['url'];
              if ($curl !== '#' && !str_starts_with($curl, 'http')) $curl = SITE_URL . $curl;
            ?>
            <li>
              <a href="<?= htmlspecialchars($curl) ?>"
                 <?= $child['target'] === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                <?= htmlspecialchars($child['label']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

  </div>
</header>
