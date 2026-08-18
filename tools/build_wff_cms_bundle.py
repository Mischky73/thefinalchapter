#!/usr/bin/env python3
"""Build a deterministic local CMS import bundle from reviewed WFF Markdown drafts."""
from __future__ import annotations

import json
import re
from datetime import datetime, timedelta
from pathlib import Path

import markdown
import yaml

ROOT = Path(__file__).resolve().parents[1]
DRAFT_DIR = ROOT / "drafts" / "with-full-force"
OUTPUT = ROOT / "research" / "wff" / "cms-import.json"
INTERNAL_MARKER = "\n---\n\n## Interne Quellen- und Bildnotiz"


def parse_draft(path: Path) -> dict:
    raw = path.read_text(encoding="utf-8")
    match = re.match(r"\A---\n(.*?)\n---\n(.*)\Z", raw, flags=re.S)
    if not match:
        raise ValueError(f"Ungültiges Frontmatter: {path}")

    meta = yaml.safe_load(match.group(1))
    editorial = match.group(2)
    if INTERNAL_MARKER in editorial:
        editorial = editorial.split(INTERNAL_MARKER, 1)[0]
    editorial = editorial.strip()

    year_match = re.search(r"(20\d{2})", path.stem)
    if not year_match:
        raise ValueError(f"Jahr fehlt: {path}")
    year = int(year_match.group(1))

    event_range = str(meta["event_date"])
    end_date = datetime.strptime(event_range.split("/")[-1], "%Y-%m-%d")
    created_at = (end_date + timedelta(days=1)).replace(hour=12).strftime("%Y-%m-%d %H:%M:%S")

    image = str(meta.get("featured_image", "")).strip()
    if image == "PENDING_VERIFIED_FLYER":
        image = ""
    if image and not image.startswith("/assets/"):
        raise ValueError(f"Nichtlokaler Bildpfad in {path}: {image}")

    html = markdown.markdown(editorial, extensions=["extra", "sane_lists"])
    if len(re.sub(r"<[^>]+>", "", html)) < 1500:
        raise ValueError(f"Artikel zu kurz: {path}")

    return {
        "year": year,
        "title": str(meta["title"]),
        "slug": str(meta["slug"]),
        "content": html,
        "excerpt": str(meta["excerpt"]),
        "category_id": int(meta["category_id"]),
        "author": str(meta["author"]),
        "featured_image": image,
        "status": "draft",
        "created_at": created_at,
        "source_file": str(path.relative_to(ROOT)),
    }


def main() -> None:
    files = sorted(DRAFT_DIR.glob("with-full-force-20??.md"))
    articles = [parse_draft(path) for path in files]
    years = [article["year"] for article in articles]
    expected = list(range(2003, 2017))
    if years != expected:
        raise ValueError(f"Erwartet {expected}, erhalten {years}")
    if len({article["slug"] for article in articles}) != len(articles):
        raise ValueError("Doppelte Slugs im Importbundle")
    if any(article["category_id"] != 53 for article in articles):
        raise ValueError("Falsche Zielkategorie")

    payload = {
        "generated_at": "2026-07-19",
        "scope": "LOCAL – production unchanged",
        "category": {"id": 53, "slug": "wff", "name": "With Full Force"},
        "articles": articles,
    }
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"OK: {len(articles)} Artikel, Jahre {years[0]}–{years[-1]}, Ausgabe {OUTPUT}")


if __name__ == "__main__":
    main()
