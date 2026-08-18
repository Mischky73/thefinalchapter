<?php
require_once __DIR__ . '/db.php';

// ---- Hilfsfunktionen ----

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


function normalizeEditorialText(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', (string)$text);
    $replacements = [
        '/\bnew\s+Single\s*\/\s*Clip\b/iu' => 'neue Single samt Clip',
        '/\bnew\s+Single\s*\/\s*Video\b/iu' => 'neue Single samt Video',
        '/\bSingle\s*\/\s*Video\b/iu' => 'Single samt Video',
        '/\bnew\s+album\b/iu' => 'neues Album',
        '/\bnew\s+EP\b/iu' => 'neue EP',
        '/\bnew\s+track\b/iu' => 'neuer Track',
        '/\bnew\s+music\s+video\b/iu' => 'neues Musikvideo',
        '/\bmusic\s+video\b/iu' => 'Musikvideo',
        '/\bdetails\s+of\s+debut\s+album\b/iu' => 'Details zum Debütalbum',
        '/\bdetails\s+of\s+neues\s+Album\b/iu' => 'Details zum neuen Album',
        '/\bdetails\s+of\s+new\s+album\b/iu' => 'Details zum neuen Album',
        '/\bRemix\s+of\b/iu' => 'Remix von',
        '/\bmit\.\s+/iu' => 'mit ',
        '/\bveröffentlichen\s+Musikvideo\b/iu' => 'veröffentlichen neues Musikvideo',
        '/\bkündigen\s+an\s+neue\s+Single\s+samt\s+Video\b/iu' => 'kündigen neue Single samt Video an',
        '/\bkündigen\s+an\s+neues\s+Album\b/iu' => 'kündigen neues Album an',
        '/\bnew\s+lyric\s+video\b/iu' => 'neues Lyric-Video',
        '/\bnew\s+visualizer\b/iu' => 'neuer Visualizer',
        '/\bnew\s+veröffentlichen\s+neues\s+Video\s+Now\b/iu' => 'veröffentlicht neues Video',
        '/\bnew\s+veröffentlichen\s+neues\s+Video\b/iu' => 'veröffentlicht neues Video',
        '/\bveröffentlichen\s+neues\s+Video\s+Now\b/iu' => 'veröffentlicht neues Video',
        '/\bfrom\s+new\s+album\b/iu' => 'vom neuen Album',
        '/\bfrom\s+upcoming\s+new\s+album\b/iu' => 'vom kommenden neuen Album',
        '/\bfrom\s+upcoming\s+new\s+EP\b/iu' => 'von der kommenden neuen EP',
        '/\bfrom\s+upcoming\s+debut\s+album\b/iu' => 'vom kommenden Debütalbum',
        '/\bFrom Upcoming\b/u' => 'vom kommenden',
        '/\bGuests\b/u' => 'als Gast dabei',
        '/\bFeat(?:uring)?\b/iu' => 'mit',
        '/\bPremieres\b/u' => 'präsentiert',
        '/\bannounce\b/iu' => 'kündigen an',
        '/\bannounces\b/iu' => 'kündigt an',
        '/\breleased\b/iu' => 'veröffentlicht',
        '/\brelease\b/iu' => 'veröffentlichen',
        '/\blaunch\b/iu' => 'veröffentlichen',
        '/\bunveils\b/iu' => 'stellt vor',
        '/\bUnveils\b/u' => 'stellt vor',
        '/\bOut\s+Now\b/u' => 'jetzt draußen',
        '/\bSingel\b/u' => 'Single',
        '/\bneuee\b/iu' => 'neue',
        '/\bsant\b/iu' => 'samt',
        '/\bveroeffentlichen\b/u' => 'veröffentlichen',
        '/\benthuellt\b/u' => 'enthüllt',
        '/\bpraesentieren\b/u' => 'präsentieren',
        '/\bkuendigt\b/u' => 'kündigt',
        '/\bkuendigen\b/u' => 'kündigen',
        '/\bzurueck\b/u' => 'zurück',
    ];
    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, (string)$text);
    }
    $text = preg_replace('/\s+([,.!?:;])/u', '$1', (string)$text);
    $text = preg_replace('/\b(Clip|Video|Album|Single)“(?=\p{Lu})/u', '$1 „', (string)$text);
    $text = preg_replace('~(Clip|Video|Album|Single)([„“"‘\'])~u', '$1 $2', (string)$text);
    $text = preg_replace('/(["„])\s+/u', '$1', (string)$text);
    $text = preg_replace('/\s+(["“])/u', '$1', (string)$text);
    return trim(preg_replace('/\s+/', ' ', (string)$text), " \t\n\r\0\x0B-–—:.;");
}

function isReviewReportGalleryTitle(string $title): bool {
    $title = trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return $title === '' || preg_match('/^(Review|Bericht|Galerie)\s*:/iu', $title) === 1;
}

function displayArticleTitle(string $title): string {
    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = preg_replace('/^(?:news|meldung|pressemeldung)\s*[.:\-–—]+\s*/iu', '', $title);
    $title = trim(preg_replace('/\s+/', ' ', (string)$title), " \t\n\r\0\x0B-–—:.;");
    $replacements = [
        '/\bPremiere(?:s)?\s+New\s+Single\s*&\s*Music\s+Video\b/iu' => 'veröffentlichen neue Single samt Video',
        '/\bPremiere(?:s)?\s+New\s+Single\s+and\s+Music\s+Video\b/iu' => 'veröffentlichen neue Single samt Video',
        '/\bPremiere(?:s)?\s+New\s+Single\s*&\s*Lyric\s+Video\s+For\b/iu' => 'veröffentlichen neue Single samt Lyric-Video zu',
        '/\bPremiere(?:s)?\s+New\s+Lyric\s+Video\s+For\b/iu' => 'veröffentlichen neues Lyric-Video zu',
        '/\bPremiere(?:s)?\s+New\s+Single\s*&\s*Visualizer\b/iu' => 'veröffentlichen neue Single samt Visualizer',
        '/\bPremiere(?:s)?\s+New\s+Single\b/iu' => 'veröffentlichen neue Single',
        '/\brelease\s+second\s+Single\s*\/\s*Video\b/iu' => 'veröffentlichen zweite Single samt Video',
        '/\bmusic\s+video\s+out\s*;\s*on\s+Tour\s+with\b/iu' => 'veröffentlichen neues Video und gehen auf Tour mit',
        '/\bmusic\s+video\s+out\b/iu' => 'veröffentlichen neues Video',
        '/\bon\s+Tour\s+with\b/iu' => 'auf Tour mit',
        '/\bdrop\s+new\s+advance\s+single\b/iu' => 'veröffentlichen neue Vorab-Single',
        '/\bdrop\s+new\s+single\b/iu' => 'veröffentlichen neue Single',
        '/\breleased\s+a\s+new\s+single\b/iu' => 'veröffentlichen neue Single',
        '/\bhas\s+released\s+a\s+new\s+single\b/iu' => 'veröffentlicht neue Single',
        '/\bhave\s+announced\b/iu' => 'kündigen an',
        '/\bhas\s+announced\b/iu' => 'kündigt an',
        '/\bhave\s+unveiled\b/iu' => 'stellen vor',
        '/\bhas\s+unveiled\b/iu' => 'stellt vor',
        '/\bReimagine\s+[’\']?80s\s+Classic\b/iu' => 'interpretieren Achtziger-Klassiker neu',
        '/\bannounces\s+new\s+album\b/iu' => 'kündigt neues Album an',
        '/\bunveils\s+first\s+single\s*\/\s*video\b/iu' => 'stellt erste Single samt Video vor',
        '/\bunveils\s+new\s+single\b/iu' => 'stellt neue Single vor',
        '/\bfrom\s+upcoming\s+new\s+self-titled\s+album\b/iu' => 'vom kommenden selbstbetitelten Album',
        '/\bfrom\s+upcoming\s+new\s+album\b/iu' => 'vom kommenden neuen Album',
        '/\bout\s+Oct\.?\s*/iu' => 'erscheint im Oktober ',
        '/\bout\s+September\b/iu' => 'erscheint im September',
    ];
    foreach ($replacements as $pattern => $replacement) {
        $title = preg_replace($pattern, $replacement, $title);
    }
    $title = preg_replace('/„([^“]{2,80})(:\s*(?:neuer Song|neues Futter|Termine|frischer Stoff|klare Ansage)\b)/u', '„$1“$2', (string)$title);
    return normalizeEditorialText((string)$title);
}


function cleanArticleSnippet(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', (string)$text);
    $text = preg_replace('/\s*Read More\/Discuss.*$/iu', '', (string)$text);
    $replacements = [
        '/^International\s+([A-Z][A-Za-z\s\-]+?)\s+band\s+/u' => '$1 ',
        '/\brelease\s+second\s+Single\s*\/\s*Video\b/iu' => 'veröffentlichen zweite Single samt Video',
        '/\bmusic\s+video\s+out\s*;\s*on\s+Tour\s+with\b/iu' => 'veröffentlichen neues Video und gehen auf Tour mit',
        '/\bmusic\s+video\s+out\b/iu' => 'veröffentlichen neues Video',
        '/\bon\s+Tour\s+with\b/iu' => 'auf Tour mit',
        '/\bdrop\s+new\s+advance\s+single\b/iu' => 'veröffentlichen neue Vorab-Single',
        '/\bdrop\s+new\s+single\b/iu' => 'veröffentlichen neue Single',
        '/\bhave\s+released\b/iu' => 'veröffentlichen',
        '/\breleased\s+a\s+new\s+single\b/iu' => 'veröffentlichen eine neue Single',
        '/\bhas\s+released\s+a\s+new\s+single\b/iu' => 'veröffentlicht eine neue Single',
        '/\bthe\s+release\s+was\s+recorded\s+at\b/iu' => 'aufgenommen wurde die Veröffentlichung in',
        '/\bwill\s+release\s+their\s+new\s+album\b/iu' => 'veröffentlichen ihr neues Album',
        '/\bwill\s+release\s+his\s+new\s+album\b/iu' => 'veröffentlicht sein neues Album',
        '/\bannounce(?:s|d)?\s+new\s+album\b/iu' => 'kündigen ein neues Album an',
        '/\bannounce(?:s|d)?\s+new\s+single\b/iu' => 'kündigen eine neue Single an',
        '/\bunveil(?:s|ed)?\s+new\s+single\b/iu' => 'stellen eine neue Single vor',
        '/\bpremiere(?:s|d)?\s+new\s+single\b/iu' => 'präsentieren eine neue Single',
        '/\bmusic\s+video\b/iu' => 'Musikvideo',
        '/\blyric\s+video\b/iu' => 'Lyric-Video',
        '/\bupcoming\s+album\b/iu' => 'kommendes Album',
        '/\bItalian\s+symphonic\s+power\s+metallers\s+/iu' => 'Die italienischen Symphonic-Power-Metaller ',
        '/\bdelve\s+deeper\s+into\s+their\s+personal\s+deck\s+of\s+Tarot\s+with\s+the\s+release\s+of\b/iu' => 'legen mit der Veröffentlichung von',
        '/\bwith\s+the\s+release\s+of\b/iu' => 'mit der Veröffentlichung von',
        '/\bis\s+an\s+uncompromising\s+examination\s+of\s+humanity\s+at\s+its\s+most\s+brutal\b/iu' => 'ist ein kompromissloser Blick auf die brutalsten Seiten der Menschheit',
        '/\bThe\s+new\s+full-length\s+is\s+scheduled\s+t(?:o|…)\b/iu' => 'Das neue Album ist angekündigt',
        '/\bis\s+scheduled\s+t(?:o|…)\b/iu' => 'ist angekündigt',
        '/\bAustralian\s+deathcore\s+band\s+/iu' => 'Die australische Deathcore-Band ',
        '/\bnoted\s+animal\s+rights\s+activists\b/iu' => 'bekannten Tierrechtsaktivisten',
        '/\bhave\s+released\s+their\s+new\s+single\b/iu' => 'veröffentlichen ihre neue Single',
        '/\btheir\s+take\s+on\b/iu' => 'ihre Version von',
        '/\brecorded\s+during\s+a\s+recent?\b/iu' => 'aufgenommen bei einer aktuellen',
        '/\brecorded\s+during\b/iu' => 'aufgenommen bei',
        '/\bchoose\s+continuous\s+escalation\s+with\s+their\s+next\s+advance\s+track\b/iu' => 'setzen mit ihrem nächsten Vorab-Track weiter auf Eskalation',
        '/\bAnother\s+new\s+track\s+from\b/iu' => 'Ein weiterer neuer Track von',
        '/\blong-awaited\s+kommendes\s+Album\b/iu' => 'lange erwarteten kommenden Album',
        '/\blong-awaited\s+upcoming\s+album\b/iu' => 'lange erwarteten kommenden Album',
        '/\bhas\s+arrived\b/iu' => 'ist da',
        '/\bveterans\s+have\s+premiered\b/iu' => 'Veteranen präsentieren',
        '/\bhave\s+premiered\b/iu' => 'präsentieren',
        '/\bhas\s+premiered\b/iu' => 'präsentiert',
        '/\bhave\s+announced\b/iu' => 'kündigen an',
        '/\bhas\s+announced\b/iu' => 'kündigt an',
        '/\bhave\s+unveiled\b/iu' => 'stellen vor',
        '/\bhas\s+unveiled\b/iu' => 'stellt vor',
        '/\bGerman\s+electronicore\s+band\s+/iu' => 'Die deutsche Electronicore-Band ',
        '/\btheir\s+personal\s+deck\s+of\s+Tarot\b/iu' => 'ihr persönliches Tarot-Deck',
        '/\bEast\s+Coast\s+thrash\s+metal\s+veterans\b/iu' => 'East-Coast-Thrash-Veteranen',
    ];
    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, (string)$text);
    }
    return normalizeEditorialText((string)$text);
}

function displayArticleExcerpt(?string $excerpt, int $limit = 130): string {
    $excerpt = cleanArticleSnippet((string)$excerpt);
    if ($excerpt === '') {
        return '';
    }
    if (mb_strlen($excerpt) <= $limit) {
        return $excerpt;
    }
    return rtrim(mb_substr($excerpt, 0, $limit), " ,;:-–—") . '…';
}

function articleSourceLabel(array $article): ?string {
    $content = (string)($article['content'] ?? '');
    if (preg_match('/Quelle \/ mehr dazu bei\s*([^<]+)/u', $content, $m)) {
        return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if (preg_match('/href="https?:\/\/([^"\/]+)/iu', $content, $m)) {
        return $m[1];
    }
    return null;
}

/** Gibt ausschließlich einen kanonischen, lokalen Bildpfad unter /assets/img/ zurück. */
function sanitizeLocalImagePath(string $imagePath): string {
    $imagePath = trim(html_entity_decode($imagePath, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($imagePath === '' || preg_match('/[\x00-\x1F\x7F\\\\?#]/', $imagePath)) {
        return '';
    }

    $decoded = $imagePath;
    for ($i = 0; $i < 4; $i++) {
        $next = rawurldecode($decoded);
        if ($next === $decoded) {
            break;
        }
        $decoded = $next;
    }
    if (str_contains($decoded, '%') || str_contains($decoded, '..')) {
        return '';
    }

    return preg_match('#^/assets/img/(?:[a-z0-9_-]+/)*[a-z0-9_.-]+\.(?:jpe?g|png|webp|gif|svg)$#i', $decoded)
        ? $decoded
        : '';
}

/** Zentrales Artikelbild: ersetzt fremde/ungültige Angaben durch das lokale TLC-Standardmotiv. */
function articleImage(?string $image): string {
    $image = sanitizeLocalImagePath((string)$image);
    return $image !== '' ? $image : '/assets/img/article-fallback.svg';
}

/** Attribut für defekte oder nicht erreichbare Bilddateien. */
function articleImageErrorFallback(): string {
    return "this.onerror=null;this.src='/assets/img/article-fallback.svg';this.classList.add('is-fallback-image')";
}

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(['ä','ö','ü','ß'], ['ae','oe','ue','ss'], $text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function formatDate(string $date): string {
    return date('d.m.Y', strtotime($date));
}

function paginate(int $total, int $perPage, int $current): array {
    $pages = (int)ceil($total / $perPage);
    return ['total' => $total, 'pages' => $pages, 'current' => $current, 'per_page' => $perPage];
}

// ---- Artikel-Funktionen ----

function getLatestArticles(int $limit = 10, int $offset = 0): array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.status = "published"
         ORDER BY a.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function countPublishedArticles(): int {
    return (int)getDB()->query('SELECT COUNT(*) FROM articles WHERE status="published"')->fetchColumn();
}

/**
 * Interne Zuordnung der ausdrücklich freigegebenen öffentlichen Profile.
 * Login-, E-Mail- und Berechtigungsdaten werden hier bewusst nicht geführt.
 *
 * @return array<string, array{slug:string,name:string,role:string,bio:string,image_path:string,is_visible:bool,sort_order:int,article_authors:array<int,string>}>
 */
function getPublicAuthorProfileDefinitions(): array {
    return [
        'michael-jakob' => [
            'slug' => 'michael-jakob',
            'name' => 'Michael Jakob',
            'role' => 'Chefredaktion / Herausgeber',
            'bio' => 'Michael hält The Final Chapter zusammen: Themen, Ton, Technik und der Blick dafür, was in der Szene wirklich hängen bleibt. Seit den frühen Neunzigern mit Metal sozialisiert, schreibt er direkt, unabhängig und lieber mit Kante als mit Werbetext-Lack.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 10,
            'article_authors' => ['Michael Jakob'],
        ],
        'thomas-schwarz' => [
            'slug' => 'thomas-schwarz',
            'name' => 'Thomas Schwarz',
            'role' => 'Redaktion / Reviews',
            'bio' => 'Thomas steht für die klassische TFC-DNA: zuhören, einordnen, benennen. Seine Texte schauen nicht nur auf Lautstärke und Namen, sondern darauf, ob Songs Substanz, Haltung und Wiederhörwert haben.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 20,
            'article_authors' => ['Thomas Schwarz'],
        ],
        'kay-herzer' => [
            'slug' => 'kay-herzer',
            'name' => 'Kay Herzer',
            'role' => 'Redaktion / Reviews',
            'bio' => 'Kay bringt die Hartnäckigkeit mit, die gute Reviews brauchen: nicht beim ersten Riff jubeln, nicht beim ersten Hänger abschalten. Entscheidend ist für ihn, ob eine Platte nach dem ersten Eindruck noch nachglüht.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 30,
            'article_authors' => ['Kay Herzer', 'Kai Herzer'],
        ],
        'matthias-eichhorn' => [
            'slug' => 'matthias-eichhorn',
            'name' => 'Dr. med. Matthias Eichhorn',
            'role' => 'Redaktion',
            'bio' => 'Matthias ergänzt das Team mit ruhigem Blick, genauer Beobachtung und Gespür für Zwischentöne. Bei TFC zählt für ihn nicht der Hype, sondern ob Musik und Auftritt auch abseits großer Namen tragen.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 40,
            'article_authors' => ['Dr. med. Matthias Eichhorn', 'Matthias Eichhorn'],
        ],
        'alexander-goehring' => [
            'slug' => 'alexander-goehring',
            'name' => 'Alexander Göhring',
            'role' => 'Redaktion',
            'bio' => 'Alexander ist Teil der TFC-Redaktion und verstärkt den Blick auf Szene, Veröffentlichungen und Livekultur. Sein Fokus: klare Einschätzungen, nachvollziehbare Eindrücke und kein unnötiges Drumherum.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 50,
            'article_authors' => ['Alexander Göhring', 'Alexander Goehring'],
        ],
        'heiko-mueller' => [
            'slug' => 'heiko-mueller',
            'name' => 'Heiko Müller',
            'role' => 'Redaktion',
            'bio' => 'Heiko erweitert das Team mit Ohr für harte Gitarren und Blick für das, was zwischen Bühne, Anlage und Szene passiert. Bei ihm zählt, ob eine Meldung oder Platte mehr ist als nur der nächste kurze Ausschlag im Feed.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 60,
            'article_authors' => ['Heiko Müller', 'Heiko Mueller'],
        ],
        'enrico-reuter' => [
            'slug' => 'enrico-reuter',
            'name' => 'Enrico Reuter',
            'role' => 'Redaktion',
            'bio' => 'Enrico steht für bodenständige Metal-Perspektive ohne unnötige Pose. Er schaut auf Songs, Bands und Entwicklungen mit dem Anspruch, Lesern Orientierung statt PR-Nebel zu geben.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 70,
            'article_authors' => ['Enrico Reuter'],
        ],
        'jan-kullowatz' => [
            'slug' => 'jan-kullowatz',
            'name' => 'Jan Kullowatz',
            'role' => 'Redaktion',
            'bio' => 'Jan bringt frische Energie in die Redaktion und hält den Blick offen für neue Veröffentlichungen, Festivalstoff und Szene-Meldungen. Wichtig bleibt: ehrlich einordnen, kurz fassen, trotzdem Haltung zeigen.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 80,
            'article_authors' => ['Jan Kullowatz'],
        ],
        'patricia-ferrantino' => [
            'slug' => 'patricia-ferrantino',
            'name' => 'Patricia Ferrantino',
            'role' => 'Redaktion',
            'bio' => 'Patricia ergänzt The Final Chapter um eine weitere Stimme in der Redaktion. Ihr Platz ist dort, wo Musik, Atmosphäre und Szenegefühl zusammenkommen – mit klarer Sprache statt austauschbarer Promo-Floskeln.',
            'image_path' => '',
            'is_visible' => true,
            'sort_order' => 90,
            'article_authors' => ['Patricia Ferrantino'],
        ],
    ];
}

function publicProfileInitials(string $name): string {
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials !== '' ? $initials : 'TFC';
}

function isAllowedPublicProfileImage(string $imagePath): bool {
    $imagePath = trim($imagePath);
    return $imagePath === '' || sanitizeLocalImagePath($imagePath) !== '';
}

/**
 * Alle freigegebenen Profile für die Backend-Bearbeitung, inklusive ausgeblendeter Profile.
 *
 * @return array<string, array<string,mixed>>
 */
function getEditableAuthorProfiles(): array {
    $profiles = getPublicAuthorProfileDefinitions();
    try {
        $slugs = array_keys($profiles);
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $stmt = getDB()->prepare(
            "SELECT slug, display_name, role_label, bio, image_path, is_visible, sort_order
             FROM author_profiles WHERE slug IN ({$placeholders})"
        );
        $stmt->execute($slugs);
        foreach ($stmt->fetchAll() as $row) {
            $slug = (string)$row['slug'];
            if (!isset($profiles[$slug])) {
                continue;
            }
            $profiles[$slug]['name'] = trim((string)$row['display_name']) !== '' ? (string)$row['display_name'] : $profiles[$slug]['name'];
            $profiles[$slug]['role'] = trim((string)$row['role_label']) !== '' ? (string)$row['role_label'] : $profiles[$slug]['role'];
            $profiles[$slug]['bio'] = trim((string)($row['bio'] ?? '')) !== '' ? (string)$row['bio'] : $profiles[$slug]['bio'];
            $profiles[$slug]['image_path'] = sanitizeLocalImagePath((string)($row['image_path'] ?? ''));
            $profiles[$slug]['is_visible'] = (bool)$row['is_visible'];
            $profiles[$slug]['sort_order'] = (int)$row['sort_order'];
        }
    } catch (PDOException $e) {
        // Fallback hält die öffentlichen Seiten während einer noch nicht ausgeführten Migration verfügbar.
    }

    uasort($profiles, static fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);
    foreach ($profiles as &$profile) {
        $profile['initials'] = publicProfileInitials((string)$profile['name']);
    }
    unset($profile);
    return $profiles;
}

function savePublicAuthorProfile(string $slug, array $data): bool {
    $definitions = getPublicAuthorProfileDefinitions();
    if (!isset($definitions[$slug])) {
        return false;
    }

    foreach (['display_name', 'role_label', 'bio', 'image_path'] as $field) {
        if (isset($data[$field]) && !is_string($data[$field])) {
            return false;
        }
    }
    $name = trim((string)($data['display_name'] ?? ''));
    $role = trim((string)($data['role_label'] ?? ''));
    $bio = trim((string)($data['bio'] ?? ''));
    $imagePathInput = trim((string)($data['image_path'] ?? ''));
    $imagePath = $imagePathInput === '' ? '' : sanitizeLocalImagePath($imagePathInput);
    if ($name === '' || $role === '' || mb_strlen($name) > 120 || mb_strlen($role) > 120
        || mb_strlen($bio) > 5000 || mb_strlen($imagePathInput) > 500
        || ($imagePathInput !== '' && $imagePath === '')) {
        return false;
    }

    try {
        $sortOrder = (int)($definitions[$slug]['sort_order'] ?? 100);
        $stmt = getDB()->prepare(
            'INSERT INTO author_profiles (slug, display_name, role_label, bio, image_path, is_visible, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               display_name = VALUES(display_name),
               role_label = VALUES(role_label),
               bio = VALUES(bio),
               image_path = VALUES(image_path),
               is_visible = VALUES(is_visible)'
        );
        $stmt->execute([$slug, $name, $role, $bio !== '' ? $bio : null, $imagePath !== '' ? $imagePath : null,
            !empty($data['is_visible']) ? 1 : 0, $sortOrder]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Bewusst freigegebene öffentliche Autorenprofile ohne Login- oder Kontaktdaten.
 *
 * @return array<string, array<string,mixed>>
 */
function getPublicTeamMembers(): array {
    return array_filter(
        getEditableAuthorProfiles(),
        static fn(array $profile): bool => !empty($profile['is_visible'])
    );
}

function getPublicTeamMemberBySlug(string $slug): ?array {
    $members = getPublicTeamMembers();
    return $members[$slug] ?? null;
}

function publicAuthorSlugForName(string $name): ?string {
    foreach (getPublicTeamMembers() as $slug => $member) {
        if (in_array($name, $member['article_authors'], true)) {
            return $slug;
        }
    }
    return null;
}

/** @return array<int, string> */
function normalizePublicArticleAuthors(array $authors): array {
    $authors = array_filter(array_map(
        static fn(mixed $author): string => is_string($author) ? trim($author) : '',
        $authors
    ));
    return array_values(array_unique($authors));
}

function countPublishedArticlesByAuthors(array $authors): int {
    $authors = normalizePublicArticleAuthors($authors);
    if ($authors === []) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($authors), '?'));
    $stmt = getDB()->prepare(
        "SELECT COUNT(*) FROM articles WHERE status = 'published' AND author IN ({$placeholders})"
    );
    $stmt->execute($authors);
    return (int)$stmt->fetchColumn();
}

function getPublishedArticlesByAuthors(array $authors, int $limit = 24, int $offset = 0): array {
    $authors = normalizePublicArticleAuthors($authors);
    if ($authors === []) {
        return [];
    }

    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $placeholders = implode(',', array_fill(0, count($authors), '?'));
    $stmt = getDB()->prepare(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON c.id = a.category_id
         WHERE a.status = 'published' AND a.author IN ({$placeholders})
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT {$limit} OFFSET {$offset}"
    );
    $stmt->execute($authors);
    return $stmt->fetchAll();
}

function getArticleBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.slug = ? AND a.status = "published"'
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getArticleById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getCategoryScopeIds(int $catId): array {
    $stmt = getDB()->prepare('SELECT id FROM categories WHERE id = ? OR parent_id = ?');
    $stmt->execute([$catId, $catId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}


function categoryScopeIsNewsLike(array $categoryIds): bool {
    if ($categoryIds === []) {
        return false;
    }
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $stmt = getDB()->prepare(
        "SELECT COUNT(*)
         FROM categories c
         LEFT JOIN categories p ON p.id = c.parent_id
         WHERE c.id IN ({$placeholders})
           AND (c.slug = 'news' OR c.slug LIKE '%-news' OR p.slug = 'festival-news')"
    );
    $stmt->execute($categoryIds);
    return (int)$stmt->fetchColumn() > 0;
}

function newsLikeTitleSql(string $alias = 'a'): string {
    return "TRIM({$alias}.title) <> '' AND {$alias}.slug <> '' AND {$alias}.title NOT REGEXP '^(Review|Bericht|Galerie)[[:space:]]*:'";
}

function getArticlesByCategory(int $catId, int $limit = 10, int $offset = 0): array {
    $db = getDB();
    $categoryIds = getCategoryScopeIds($catId);
    if ($categoryIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $stmt = $db->prepare(
        "SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.category_id IN ({$placeholders}) AND a.status = \"published\"
         ORDER BY a.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute([...$categoryIds, $limit, $offset]);
    return $stmt->fetchAll();
}

function countArticlesByCategory(int $catId): int {
    $categoryIds = getCategoryScopeIds($catId);
    if ($categoryIds === []) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $stmt = getDB()->prepare(
        "SELECT COUNT(*)
         FROM articles
         WHERE category_id IN ({$placeholders}) AND status = \"published\""
    );
    $stmt->execute($categoryIds);
    return (int)$stmt->fetchColumn();
}

function getAllArticlesAdmin(): array {
    return getAllArticlesAdminFiltered();
}

/** @return array{0: string, 1: array<int, int|string>} */
function buildAdminArticleFilter(string $status = '', int $categoryId = 0): array {
    $where = [];
    $params = [];

    if (in_array($status, ['draft', 'published', 'archived'], true)) {
        $where[] = 'a.status = ?';
        $params[] = $status;
    } else {
        $where[] = 'a.status != "archived"';
    }

    if ($categoryId > 0) {
        $where[] = 'a.category_id = ?';
        $params[] = $categoryId;
    }

    return [' WHERE ' . implode(' AND ', $where), $params];
}

function countAdminArticlesFiltered(string $status = '', int $categoryId = 0): int {
    [$whereSql, $params] = buildAdminArticleFilter($status, $categoryId);
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM articles a JOIN categories c ON a.category_id = c.id' . $whereSql
    );
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function normalizeNonNegativeIntInput(mixed $value, int $default = 0): int {
    $default = max(0, $default);
    if (is_int($value)) {
        return max(0, $value);
    }
    if (!is_string($value) || preg_match('/^[+-]?[0-9]+$/D', $value) !== 1) {
        return $default;
    }
    return max(0, (int)$value);
}

function buildAdminArticlePageUrl(
    int $page,
    string $status = '',
    int $categoryId = 0
): string {
    $params = [];
    if (in_array($status, ['draft', 'published', 'archived'], true)) {
        $params['status'] = $status;
    }
    if ($categoryId > 0) {
        $params['category'] = $categoryId;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }

    return 'articles.php' . ($params === [] ? '' : '?' . http_build_query($params));
}

function getAdminArticlesPage(
    string $status = '',
    int $categoryId = 0,
    int $limit = 50,
    int $offset = 0
): array {
    $limit = max(1, min(200, $limit));
    $offset = max(0, $offset);
    [$whereSql, $params] = buildAdminArticleFilter($status, $categoryId);

    $sql = 'SELECT a.id, a.title, a.slug, a.category_id, a.author, a.status, a.created_at,
                   c.name AS category_name
            FROM articles a
            JOIN categories c ON a.category_id = c.id' .
            $whereSql .
            ' ORDER BY a.created_at DESC, a.id DESC
              LIMIT ' . $limit . ' OFFSET ' . $offset;

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Unbegrenzte Abfrage für bestehende CLI- und Integrationstests.
 * Admin-Oberflächen müssen getAdminArticlesPage() verwenden.
 */
function getAllArticlesAdminFiltered(string $status = '', int $categoryId = 0): array {
    [$whereSql, $params] = buildAdminArticleFilter($status, $categoryId);
    $sql = 'SELECT a.*, c.name AS category_name
            FROM articles a
            JOIN categories c ON a.category_id = c.id' .
            $whereSql .
            ' ORDER BY a.created_at DESC, a.id DESC';

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function normalizeArticleStatus(mixed $status): string {
    return in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft';
}

function sanitizeArticleUrl(string $url, bool $image = false): string {
    $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return '';
    }
    if ($image) {
        return sanitizeLocalImagePath($url);
    }
    if (str_starts_with($url, '#') || (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, '..'))) {
        return $url;
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    $allowedSchemes = ['http', 'https', 'mailto'];
    return in_array($scheme, $allowedSchemes, true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
}

/**
 * Bereinigt redaktionelles HTML serverseitig. Der Browser-Editor ist keine Sicherheitsgrenze.
 */
function sanitizeArticleHtml(string $html): string {
    if ($html === '') {
        return '';
    }
    if (!class_exists('DOMDocument')) {
        return nl2br(htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    $allowed = [
        'p' => ['class'], 'br' => [], 'h2' => ['class'], 'h3' => ['class'], 'h4' => ['class'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 'ul' => ['class'], 'ol' => ['class'],
        'li' => [], 'blockquote' => ['class'], 'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'class'],
        'figure' => ['class'], 'figcaption' => ['class'], 'hr' => [], 'code' => [], 'pre' => ['class'],
        'span' => ['class'], 'div' => ['class'], 'table' => ['class'], 'thead' => [], 'tbody' => [],
        'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];
    $dropEntirely = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option'];

    $dom = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="tfc-sanitizer-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $dom->getElementById('tfc-sanitizer-root');
    if (!$root) {
        return '';
    }

    $walk = function (DOMNode $parent) use (&$walk, $allowed, $dropEntirely): void {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMComment) {
                $parent->removeChild($node);
                continue;
            }
            if (!$node instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($node->tagName);
            if (in_array($tag, $dropEntirely, true)) {
                $parent->removeChild($node);
                continue;
            }
            if (!isset($allowed[$tag])) {
                $walk($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if (!in_array($name, $allowed[$tag], true)) {
                    $node->removeAttributeNode($attribute);
                    continue;
                }
                $value = trim($attribute->value);
                if ($name === 'href') {
                    $value = sanitizeArticleUrl($value, false);
                } elseif ($name === 'src') {
                    $value = sanitizeArticleUrl($value, true);
                } elseif ($name === 'class' && !preg_match('/^[a-z0-9 _-]{1,200}$/i', $value)) {
                    $value = '';
                } elseif (in_array($name, ['width', 'height', 'colspan', 'rowspan'], true)
                    && (!ctype_digit($value) || (int)$value < 1 || (int)$value > 12000)) {
                    $value = '';
                } elseif ($name === 'target' && !in_array($value, ['_blank', '_self'], true)) {
                    $value = '';
                } elseif ($name === 'loading' && !in_array($value, ['lazy', 'eager'], true)) {
                    $value = '';
                }
                if ($value === '' && $name !== 'allowfullscreen') {
                    $node->removeAttribute($name);
                } else {
                    $node->setAttribute($name, $value);
                }
            }
            if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img' && !$node->hasAttribute('loading')) {
                $node->setAttribute('loading', 'lazy');
            }
            $walk($node);
        }
    };
    $walk($root);

    $clean = '';
    foreach ($root->childNodes as $child) {
        $clean .= $dom->saveHTML($child);
    }
    return trim($clean);
}

function saveArticle(array $data, ?int $id = null): bool {
    $db = getDB();
    $content = sanitizeArticleHtml(is_string($data['content'] ?? null) ? $data['content'] : '');
    $featuredImage = sanitizeLocalImagePath(is_string($data['featured_image'] ?? null) ? $data['featured_image'] : '');
    $createdAt = $data['created_at'] ?? null;
    $requestedStatus = normalizeArticleStatus($data['status'] ?? null);
    $editableStatus = in_array($requestedStatus, ['draft', 'published'], true) ? $requestedStatus : null;
    if ($id) {
        $stmt = $db->prepare(
            'UPDATE articles SET title=?, slug=?, content=?, excerpt=?, category_id=?,
             author=?, featured_image=?,
             status=CASE WHEN status="archived" OR ? IS NULL THEN status ELSE ? END,
             created_at=COALESCE(?, created_at) WHERE id=?'
        );
        return $stmt->execute([
            $data['title'], $data['slug'], $content, $data['excerpt'],
            $data['category_id'], $data['author'], $featuredImage,
            $editableStatus, $editableStatus, $createdAt, $id
        ]);
    } else {
        $editableStatus ??= 'draft';
        $stmt = $db->prepare(
            'INSERT INTO articles (title, slug, content, excerpt, category_id, author, featured_image, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        return $stmt->execute([
            $data['title'], $data['slug'], $content, $data['excerpt'],
            $data['category_id'], $data['author'], $featuredImage, $editableStatus,
            $createdAt ?? date('Y-m-d H:i:s')
        ]);
    }
}

function deleteArticle(int $id): bool {
    return archiveArticles([$id]) === 1;
}

/**
 * @param array<int, int|string> $ids
 * @return int[]
 */
function normalizeArticleIds(array $ids): array {
    $normalized = [];
    foreach ($ids as $id) {
        if (is_int($id) && $id > 0) {
            $normalized[] = $id;
            continue;
        }
        if (is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id) === 1) {
            $integerId = (int)$id;
            if ($integerId > 0 && (string)$integerId === $id) {
                $normalized[] = $integerId;
            }
        }
    }
    return array_values(array_unique($normalized));
}

/** @param array<int, int|string> $ids */
function bulkMoveArticles(array $ids, int $categoryId): int {
    $ids = normalizeArticleIds($ids);
    if ($ids === [] || $categoryId <= 0) {
        return 0;
    }

    $db = getDB();
    $category = $db->prepare('SELECT 1 FROM categories WHERE id = ?');
    $category->execute([$categoryId]);
    if (!$category->fetchColumn()) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("UPDATE articles SET category_id = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
    $stmt->execute([$categoryId, ...$ids]);
    return $stmt->rowCount();
}

/** @param array<int, int|string> $ids */
function archiveArticles(array $ids): int {
    $ids = normalizeArticleIds($ids);
    if ($ids === []) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = getDB()->prepare(
        "UPDATE articles
         SET archived_from_status = status, status = 'archived', archived_at = NOW()
         WHERE id IN ({$placeholders}) AND status IN ('draft', 'published')"
    );
    $stmt->execute($ids);
    return $stmt->rowCount();
}

/** @param array<int, int|string> $ids */
function restoreArchivedArticles(array $ids): int {
    $ids = normalizeArticleIds($ids);
    if ($ids === []) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = getDB()->prepare(
        "UPDATE articles
         SET status = archived_from_status, archived_from_status = NULL, archived_at = NULL
         WHERE id IN ($placeholders)
           AND status = 'archived'
           AND archived_from_status IN ('draft', 'published')"
    );
    $stmt->execute($ids);
    return $stmt->rowCount();
}

/** @param array<int, int|string> $ids */
function permanentlyDeleteArchivedArticles(array $ids): int {
    $ids = normalizeArticleIds($ids);
    if ($ids === []) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = getDB()->prepare("DELETE FROM articles WHERE id IN ({$placeholders}) AND status = 'archived'");
    $stmt->execute($ids);
    return $stmt->rowCount();
}

/** @param array<int, int|string> $ids */
function bulkDeleteArticles(array $ids): int {
    return permanentlyDeleteArchivedArticles($ids);
}

// ---- Kategorie-Funktionen ----

function getAllCategories(): array {
    $rows = getDB()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    $childrenByParent = [];

    foreach ($rows as $row) {
        $parentId = (int)($row['parent_id'] ?? 0);
        $childrenByParent[$parentId][] = $row;
    }

    $ordered = [];
    $appendCategoryTree = function (int $parentId) use (&$appendCategoryTree, &$childrenByParent, &$ordered): void {
        foreach ($childrenByParent[$parentId] ?? [] as $category) {
            $ordered[] = $category;
            $appendCategoryTree((int)$category['id']);
        }
    };

    $appendCategoryTree(0);

    return $ordered;
}

function getCategoryBySlug(string $slug): ?array {
    $stmt = getDB()->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getTopLevelCategories(): array {
    return getDB()->query('SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name')->fetchAll();
}

function getCategoriesWithCount(): array {
    return getDB()->query(
        'SELECT c.*,
                (SELECT COUNT(*)
                 FROM articles a
                 JOIN categories ac ON a.category_id = ac.id
                 WHERE a.status="published" AND (ac.id = c.id OR ac.parent_id = c.id)) AS article_count
         FROM categories c
         ORDER BY article_count DESC'
    )->fetchAll();
}

function getCategoryById(int $id): ?array {
    $stmt = getDB()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function saveCategory(array $data, ?int $id = null): bool {
    $db = getDB();
    if ($id) {
        $stmt = $db->prepare('UPDATE categories SET name=?, slug=?, parent_id=?, description=? WHERE id=?');
        return $stmt->execute([$data['name'], $data['slug'], $data['parent_id'] ?: null, $data['description'], $id]);
    } else {
        $stmt = $db->prepare('INSERT INTO categories (name, slug, parent_id, description) VALUES (?, ?, ?, ?)');
        return $stmt->execute([$data['name'], $data['slug'], $data['parent_id'] ?: null, $data['description']]);
    }
}

function deleteCategory(int $id): bool {
    $db = getDB();

    $category = getCategoryById($id);
    if (!$category) {
        return false;
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM articles WHERE category_id = ?');
    $stmt->execute([$id]);
    $articleCount = (int)$stmt->fetchColumn();
    if ($articleCount > 0) {
        throw new RuntimeException(
            'Kategorie kann nicht gelöscht werden, weil ihr noch ' . $articleCount . ' Artikel zugeordnet sind. Bitte Artikel zuerst verschieben oder archivieren.'
        );
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = ?');
    $stmt->execute([$id]);
    $childCount = (int)$stmt->fetchColumn();
    if ($childCount > 0) {
        throw new RuntimeException(
            'Kategorie kann nicht gelöscht werden, weil sie noch ' . $childCount . ' Unterkategorie(n) enthält. Bitte Unterkategorien zuerst verschieben oder löschen.'
        );
    }

    try {
        $db->beginTransaction();

        $nav = $db->prepare('DELETE FROM nav_items WHERE url = ? OR url = ? OR label = ?');
        $nav->execute([
            'category.php?slug=' . $category['slug'],
            '/category.php?slug=' . $category['slug'],
            $category['name'],
        ]);

        $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $deleted = $stmt->rowCount() > 0;

        $db->commit();
        return $deleted;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw new RuntimeException('Kategorie konnte nicht gelöscht werden: ' . $e->getMessage());
    }
}

// ---- Suche ----

function searchArticles(string $q, int $limit = 20, int $offset = 0): array {
    $db   = getDB();
    $like = '%' . $q . '%';
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.status = "published"
           AND (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
         ORDER BY a.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$like, $like, $like, $limit, $offset]);
    return $stmt->fetchAll();
}

function countSearchResults(string $q): int {
    $like = '%' . $q . '%';
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM articles
         WHERE status = "published"
           AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?)'
    );
    $stmt->execute([$like, $like, $like]);
    return (int)$stmt->fetchColumn();
}

// ---- Verwandte Artikel ----

function getRelatedArticles(int $categoryId, int $excludeId, int $limit = 3): array {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.category_id = ? AND a.id != ? AND a.status = "published"
         ORDER BY a.created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$categoryId, $excludeId, $limit]);
    return $stmt->fetchAll();
}

// ---- Neueste Artikel für Sidebar ----

function getLatestArticlesSidebar(int $limit = 5): array {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT a.id, a.title, a.slug, a.featured_image, a.created_at,
                c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.status = "published"
         ORDER BY a.created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getLatestArticlesByCategorySlug(string $categorySlug, int $limit = 5): array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT a.id, a.title, a.slug, a.featured_image, a.created_at,
                c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.status = "published"
           AND (c.slug = ? OR c.parent_id = (SELECT id FROM categories WHERE slug = ? LIMIT 1))
         ORDER BY a.created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$categorySlug, $categorySlug, $limit]);
    return $stmt->fetchAll();
}

function getFestivalSidebarMetadata(): array {
    return [
        'party-san-news' => [
            'display_name' => 'Party San',
            'start_date' => '2026-08-06',
            'end_date' => '2026-08-08',
            'logo' => '/assets/img/festival-logos/party-san.webp',
        ],
        'wacken-news' => [
            'display_name' => 'Wacken',
            'start_date' => '2026-07-29',
            'end_date' => '2026-08-01',
            'logo' => '/assets/img/festival-logos/wacken.webp',
        ],
        'full-rewind-news' => [
            'display_name' => 'Full Rewind',
            'start_date' => '2026-07-30',
            'end_date' => '2026-08-01',
            'logo' => '/assets/img/festival-logos/full-rewind.webp',
        ],
        'in-flammen-news' => [
            'display_name' => 'In Flammen',
            'start_date' => '2027-07-15',
            'end_date' => '2027-07-17',
            'logo' => '/assets/img/festival-logos/in-flammen.webp',
        ],
        'rockharz-news' => [
            'display_name' => 'Rockharz',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-04',
            'logo' => '/assets/img/festival-logos/rockharz.webp',
        ],
        'summer-breeze-news' => [
            'display_name' => 'Summer Breeze',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-15',
            'logo' => '/assets/img/festival-logos/summer-breeze.webp',
        ],
    ];
}

function getFestivalCountdown(
    string $startDate,
    string $endDate,
    ?DateTimeImmutable $now = null
): array {
    $timezone = new DateTimeZone('Europe/Berlin');
    $now = ($now ?? new DateTimeImmutable('now', $timezone))->setTimezone($timezone);
    $today = $now->setTime(0, 0);
    $start = new DateTimeImmutable($startDate . ' 00:00:00', $timezone);
    $end = new DateTimeImmutable($endDate . ' 23:59:59', $timezone);

    if ($today < $start) {
        $days = (int)$today->diff($start)->days;
        $countdownLabel = $days === 1 ? 'Noch 1 Tag' : "Noch {$days} Tage";
        $phase = 'upcoming';
    } elseif ($now <= $end) {
        $countdownLabel = 'Läuft jetzt';
        $phase = 'ongoing';
    } else {
        $countdownLabel = 'Beendet';
        $phase = 'past';
    }

    $startLabel = $start->format('d.m.Y');
    $endLabel = $end->format('d.m.Y');
    if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
        $dateLabel = $startLabel;
    } elseif ($start->format('Y-m') === $end->format('Y-m')) {
        $dateLabel = $start->format('d.') . '–' . $endLabel;
    } else {
        $dateLabel = $start->format('d.m.') . '–' . $endLabel;
    }

    return [
        'date_label' => $dateLabel,
        'countdown_label' => $countdownLabel,
        'phase' => $phase,
    ];
}

function isFestivalArticleNew(string $createdAt, ?DateTimeImmutable $now = null): bool {
    $timezone = new DateTimeZone('Europe/Berlin');
    $now = ($now ?? new DateTimeImmutable('now', $timezone))->setTimezone($timezone);
    $created = new DateTimeImmutable($createdAt, $timezone);

    return $created <= $now && $created >= $now->modify('-48 hours');
}

function getFestivalNewsSidebarGroups(
    int $limitPerFestival = 5,
    ?DateTimeImmutable $now = null
): array {
    $limitPerFestival = max(1, min(20, $limitPerFestival));
    $timezone = new DateTimeZone('Europe/Berlin');
    $now = ($now ?? new DateTimeImmutable('now', $timezone))->setTimezone($timezone);
    $metadata = getFestivalSidebarMetadata();
    $db = getDB();
    $categories = $db->query(
        'SELECT c.id, c.name, c.slug
         FROM categories c
         JOIN categories parent ON parent.id = c.parent_id
         WHERE parent.slug = "festival-news"
           AND c.slug <> "wff-news"'
    )->fetchAll(PDO::FETCH_ASSOC);

    $articleStmt = $db->prepare(
        'SELECT a.id, a.title, a.slug, a.featured_image, a.created_at,
                c.name AS category_name, c.slug AS category_slug
         FROM articles a
         JOIN categories c ON c.id = a.category_id
         WHERE a.status = "published" AND a.category_id = ?
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT ?'
    );

    foreach ($categories as &$category) {
        $festivalMetadata = $metadata[$category['slug']] ?? null;
        if ($festivalMetadata) {
            $category = array_merge(
                $category,
                $festivalMetadata,
                getFestivalCountdown($festivalMetadata['start_date'], $festivalMetadata['end_date'], $now)
            );
        } else {
            $category += [
                'start_date' => null,
                'end_date' => null,
                'logo' => null,
                'date_label' => 'Termin folgt',
                'countdown_label' => 'Noch offen',
                'phase' => 'unknown',
            ];
        }

        $articleStmt->execute([(int)$category['id'], $limitPerFestival]);
        $category['articles'] = $articleStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($category);

    $pinned = ['party-san-news' => 0, 'wacken-news' => 1];
    $phaseOrder = ['ongoing' => 0, 'upcoming' => 1, 'past' => 2, 'unknown' => 3];
    usort($categories, static function (array $a, array $b) use ($pinned, $phaseOrder): int {
        $aPinned = $pinned[$a['slug']] ?? 99;
        $bPinned = $pinned[$b['slug']] ?? 99;
        if ($aPinned !== $bPinned) {
            return $aPinned <=> $bPinned;
        }
        if ($aPinned !== 99) {
            return 0;
        }

        $phaseComparison = ($phaseOrder[$a['phase']] ?? 99) <=> ($phaseOrder[$b['phase']] ?? 99);
        if ($phaseComparison !== 0) {
            return $phaseComparison;
        }

        if ($a['phase'] === 'past') {
            return strcmp((string)$b['end_date'], (string)$a['end_date']);
        }
        if (in_array($a['phase'], ['ongoing', 'upcoming'], true)) {
            return strcmp((string)$a['start_date'], (string)$b['start_date']);
        }
        return strcasecmp($a['name'], $b['name']);
    });

    return $categories;
}

// ---- Tourdaten ----

function ensureTourEventsTable(): bool {
    try {
        getDB()->exec('CREATE TABLE IF NOT EXISTS tour_events (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          artist VARCHAR(160) NOT NULL,
          event_title VARCHAR(255) NOT NULL,
          venue VARCHAR(180) NOT NULL DEFAULT "",
          city VARCHAR(120) NOT NULL DEFAULT "",
          region VARCHAR(120) NOT NULL DEFAULT "",
          country VARCHAR(80) NOT NULL DEFAULT "",
          starts_at DATETIME NOT NULL,
          ticket_url VARCHAR(500) NOT NULL DEFAULT "",
          source VARCHAR(80) NOT NULL DEFAULT "manual",
          source_event_id VARCHAR(160) NOT NULL DEFAULT "",
          status ENUM("published","draft","cancelled") NOT NULL DEFAULT "published",
          last_seen_at DATETIME NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY uniq_tour_source_event (source, source_event_id),
          KEY idx_tour_status_date (status, starts_at),
          KEY idx_tour_artist_date (artist, starts_at),
          KEY idx_tour_city_date (city, starts_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function tourEventsTableExists(): bool {
    try {
        getDB()->query('SELECT 1 FROM tour_events LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return ensureTourEventsTable();
    }
}

function getUpcomingTourEvents(int $limit = 8, array $filters = []): array {
    if (!tourEventsTableExists()) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $where = ['status = "published"', 'starts_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)'];
    $params = [];

    foreach (['artist', 'city', 'country'] as $field) {
        $value = trim((string)($filters[$field] ?? ''));
        if ($value !== '') {
            $where[] = $field . ' LIKE ?';
            $params[] = '%' . $value . '%';
        }
    }

    $stmt = getDB()->prepare(
        'SELECT * FROM tour_events WHERE ' . implode(' AND ', $where) . ' ORDER BY starts_at ASC, artist ASC LIMIT ?'
    );
    $params[] = $limit;
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countUpcomingTourEvents(array $filters = []): int {
    if (!tourEventsTableExists()) {
        return 0;
    }
    $where = ['status = "published"', 'starts_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)'];
    $params = [];
    foreach (['artist', 'city', 'country'] as $field) {
        $value = trim((string)($filters[$field] ?? ''));
        if ($value !== '') {
            $where[] = $field . ' LIKE ?';
            $params[] = '%' . $value . '%';
        }
    }
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM tour_events WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function formatTourEventDate(string $startsAt): string {
    $timestamp = strtotime($startsAt);
    return $timestamp ? date('d.m.Y · H:i', $timestamp) . ' Uhr' : $startsAt;
}

function normalizeTourEventUrl(string $url): string {
    $url = trim($url);
    return preg_match('#^https?://#i', $url) ? $url : '';
}

// ---- Stats ----

function getStats(): array {
    $db = getDB();
    return [
        'articles_total'     => (int)$db->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
        'articles_published' => (int)$db->query('SELECT COUNT(*) FROM articles WHERE status="published"')->fetchColumn(),
        'articles_draft'     => (int)$db->query('SELECT COUNT(*) FROM articles WHERE status="draft"')->fetchColumn(),
        'categories'         => (int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    ];
}
