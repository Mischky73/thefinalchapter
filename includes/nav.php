<?php
/**
 * Hauptmenü aus der Datenbank laden (mit Dropdown-Unterstützung).
 * Die Tabelle ist klein; bewusst ohne Session-Cache, damit Änderungen im
 * Backend sofort in allen Browsern sichtbar werden.
 */
function getNavItems(): array {
    try {
        $db = getDB();
        $rows = $db->query(
            "SELECT * FROM nav_items ORDER BY parent_id ASC, sort_order ASC, id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $top = [];
        $children = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] === null) {
                $top[] = $row;
            } else {
                $children[(int)$row['parent_id']][] = $row;
            }
        }

        return ['top' => $top, 'children' => $children];
    } catch (Throwable $e) {
        error_log('Navigation konnte nicht geladen werden: ' . $e->getMessage());
        return ['top' => [], 'children' => []];
    }
}
