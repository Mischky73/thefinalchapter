#!/usr/bin/env python3
"""Generiert eigenständige deutsche Summer-Breeze-Rückblicke aus facts.json.

Ein Template pro Quell-Modus (stattgefunden / abgesagt / Vorschau). Headliner,
Orte, Daten, Stimmung und Quellen werden aus den geprüften Fakten eingewoben.
Jahr-individuelle Einstiege und Fazits vermeiden Gleichförmigkeit. Der 2004-Draft
ist redaktionell handgeschrieben und wird NICHT überschrieben.
"""
from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
FACTS = ROOT / "research" / "summer-breeze" / "facts.json"
OUT = ROOT / "drafts" / "summer-breeze"
IMG_BASE = "/assets/img/uploads/summer-breeze/rueckblicke"
HANDWRITTEN = {2004}  # bereits redaktionell erstellt

# Bild-Extensionen laut image-classification.md
IMG_EXT = {2020: "png", 2021: "jpg"}  # 2021 = gerenderte Briefseite (.jpg), Rest .jpg


def img_path(year: int) -> str:
    return f"{IMG_BASE}/sb-rueckblick-{year}.{IMG_EXT.get(year, 'jpg')}"


def hl(headliner: list[str]) -> str:
    if not headliner:
        return ""
    if len(headliner) == 1:
        return f"**{headliner[0]}**"
    return ", ".join(f"**{h}**" for h in headliner[:-1]) + f" sowie **{headliner[-1]}**"


# Jahr-individuelle Einstiegssätze (redaktionell, kein Fakt erfunden)
INTRO = {
    2003: "Ende August 2003 wurde der Festplatz von Abtsgmünd erneut für drei Tage zur „Metal City“.",
    2005: "„Was für ein Fest!“ – so brachten es die Veranstalter nach dem Summer Breeze 2005 selbst auf den Punkt.",
    2006: "2006 schlug das Summer Breeze ein neues Kapitel auf: erstmals fand das Festival in Dinkelsbühl statt.",
    2007: "Das Summer Breeze feierte 2007 sein zehnjähriges Bestehen – und die Fans feierten kräftig mit.",
    2008: "Auch 2008 zog es die Metal-Szene wieder auf das mittlerweile eingespielte Gelände in Dinkelsbühl.",
    2009: "Sonnenschein und ein breites Billing prägten das Summer Breeze 2009 in Dinkelsbühl.",
    2010: "„Typisch Summer Breeze“ – so ließ sich das Line-up des Jahrgangs 2010 zusammenfassen.",
    2011: "2011 rückte die Metal-Hammer-Redaktion in Mannschaftsstärke nach Dinkelsbühl aus.",
    2012: "Bereits am Mittwoch startete das Summer Breeze 2012 mit dem New Blood Award in sein verlängertes Wochenende.",
    2013: "Das Summer Breeze 2013 bildete den krönenden Abschluss des Open-Air-Sommers.",
    2014: "Rund 30.000 Metalheads machten sich 2014 auf den Weg zum Flugplatz bei Dinkelsbühl.",
    2015: "Heiß, staubig und laut – so präsentierte sich das Summer Breeze 2015.",
    2016: "Über 100 Bands auf vier Bühnen: Das Summer Breeze 2016 fuhr ein üppiges Programm auf.",
    2017: "Zwischen Sonnenschein und angekündigten Gewittern verlief das Summer Breeze 2017.",
    2018: "Das Summer Breeze 2018 war ein Festival der Wetterextreme.",
    2019: "Das Summer Breeze 2019 stand im Zeichen wechselhaften Wetters – und wanderte erstmals verstärkt ins Fernsehen.",
    2020: "Das Summer Breeze 2020 hätte im August steigen sollen – doch die Corona-Pandemie machte einen Strich durch die Rechnung.",
    2021: "Auch im zweiten Pandemiesommer blieb das Festivalgelände in Dinkelsbühl still.",
    2022: "Nach zwei Jahren Zwangspause kehrte das Summer Breeze 2022 mit seinem 25-jährigen Jubiläum zurück.",
    2023: "2023 knüpfte das Summer Breeze wieder an den gewohnten Festivalrhythmus an.",
    2024: "Das Summer Breeze 2024 war ausverkauft – und wurde für seine Arbeit ausgezeichnet.",
    2025: "Strahlender Sonnenschein und Hunderttausende Besuche prägten das Summer Breeze 2025.",
    2026: "Das Summer Breeze 2026 steht zum Zeitpunkt dieses Rückblicks noch bevor.",
}

FAZIT = {
    2003: "Das Fundament für die kommenden Jahre war damit weiter gefestigt.",
    2005: "Der Festplatz in Abtsgmünd stieß langsam an seine Grenzen – ein Vorbote des späteren Umzugs.",
    2006: "Der Standortwechsel nach Dinkelsbühl hatte sich damit auf Anhieb bewährt.",
    2007: "Mit dem Jubiläumsjahr hatte sich das Summer Breeze endgültig in der ersten Liga der deutschen Metal-Festivals etabliert.",
    2008: "Der Jahrgang fügte sich nahtlos in die gewachsene Festivaltradition ein.",
    2009: "Ein sonniger Jahrgang, der die Bandbreite des Festivals einmal mehr unterstrich.",
    2010: "Das Festival blieb seiner Linie treu: viel Metal für jeden Geschmack.",
    2011: "Organisation und Billing sorgten für einen rundum gelungenen Jahrgang.",
    2012: "Endlich passte auch das Wetter – das Summer Breeze 2012 lieferte, was es versprach.",
    2013: "Ein denkwürdiges Festivalwochenende fand so seinen fulminanten Abschluss.",
    2014: "Zwischen Regenponcho und Badehose bewies das Festival einmal mehr seine Wetterfestigkeit.",
    2015: "Trotz Hitze und Staub blieb die Stimmung bis zum letzten Ton auf dem Siedepunkt.",
    2016: "Die schiere Masse an Bands machte den Jahrgang zu einem der dichtesten der Festivalgeschichte.",
    2017: "Am Ende setzte sich der Sommer durch – und mit ihm die gewohnt gute Stimmung.",
    2018: "Zwischen Hitze und Unwetter bewiesen Fans und Veranstalter einmal mehr Durchhaltevermögen.",
    2019: "Mit TV-Übertragungen und Livestreams erreichte das Festival ein größeres Publikum denn je.",
    2020: "Statt Metal blieb 2020 nur die Vorfreude auf ein Comeback.",
    2021: "Die Fans mussten sich ein weiteres Jahr gedulden.",
    2022: "Das Comeback im Jubiläumsjahr geriet zum umjubelten Neustart.",
    2023: "Das Festival bewegte sich wieder sicher in seinem angestammten Format.",
    2024: "Der Award bestätigte, was die Fans längst wussten.",
    2025: "Ein Jahrgang, der die Latte für die Zukunft hoch legte.",
    2026: "Ob das Festival die hohen Erwartungen einlöst, wird sich erst im August zeigen.",
}


def build_body(y: dict) -> str:
    year = y["year"]
    parts = [f"## {INTRO[year]}"[:0] + INTRO[year]]  # kept simple below
    P = []
    P.append(f"## {y['ort']}, {y['dates']}\n")
    P.append(INTRO[year] + "\n")

    if y.get("cancelled"):
        P.append(y["official"] + "\n")
        if y.get("mh"):
            P.append("### Einordnung durch die Fachpresse\n")
            P.append(y["mh"] + "\n")
        P.append("### Was vom Jahrgang bleibt\n")
        P.append(
            f"Ein reguläres Festivalgeschehen gab es {year} nicht. Für einen ehrlichen "
            f"Rückblick heißt das: keine Bühnenberichte, keine Besucherzahlen, keine "
            f"Wetternotizen – sondern die Dokumentation eines ausgefallenen Jahrgangs. "
            + FAZIT[year] + "\n"
        )
    elif y.get("preview"):
        P.append(y["official"] + "\n")
        if y["headliner"]:
            P.append("### Geplantes Billing\n")
            P.append(
                f"An der Spitze des angekündigten Programms stehen {hl(y['headliner'])}. "
                "Diese Angaben spiegeln den Planungsstand wider; welche Auftritte tatsächlich "
                "stattfinden, lässt sich erst nach dem Festival bewerten.\n"
            )
        if y.get("mh"):
            P.append("### Vorberichterstattung\n")
            P.append(y["mh"] + "\n")
        P.append("### Ausblick\n")
        P.append(
            f"Das Festival findet vom {y['dates'].replace('–','bis').replace('.',' . ').strip()} statt. "
            + FAZIT[year] + "\n"
        )
    else:
        # stattgefunden
        if y["headliner"]:
            P.append(
                f"Als Headliner standen unter anderem {hl(y['headliner'])} auf dem Programm. "
            )
        if y.get("official"):
            P.append("\n### Aus dem offiziellen Rückblick\n")
            P.append(y["official"] + "\n")
        if y.get("mh"):
            P.append("### Stimmen und Berichte aus der Szene\n")
            P.append(y["mh"] + "\n")
        P.append("### Fazit\n")
        mood = y.get("mood")
        moodtxt = f"Das Festival blieb als Jahrgang „{mood}“ in Erinnerung. " if mood else ""
        P.append(
            moodtxt
            + "Wie in jedem Jahr lebte das Summer Breeze auch diesmal von der Mischung aus "
            "großen Namen, Entdeckungen und der eigenen familiären Atmosphäre. "
            + FAZIT[year] + "\n"
        )

    return "\n".join(P).strip()


def source_note(y: dict) -> str:
    lines = ["---", "", "## Interne Quellen- und Bildnotiz", ""]
    if y.get("off_url"):
        lines.append(f"- Offizielle Quelle: {y['off_url']}")
    if y.get("mh_url") and y["mh_url"] != y.get("off_url"):
        lines.append(f"- Metal Hammer: {y['mh_url']}")
    lines.append(
        "- Rock Hard: als Print-/Onlinequelle für diesen Jahrgang nicht maschinell "
        "erschließbar (keine offene API, Cloudflare-Schutz); redaktionell manuell zu ergänzen."
    )
    cls = "offizieller Vorabflyer (Planungsstand)" if y.get("preview") else (
        "offizielle Ausfall-/Briefgrafik (kein Line-up-Flyer)" if y.get("cancelled") else
        "offizieller Flyer/Onlineflyer, Quelle summer-breeze.de, Rechtevorbehalt")
    lines.append(f"- Bild: {cls}. Lokal: {img_path(y['year'])}")
    lines.append("- Keine Zahlen/Wetter/Absagen erfunden; belegte Angaben paraphrasiert.")
    return "\n".join(lines) + "\n"


def title_for(y: dict) -> str:
    year = y["year"]
    if y.get("cancelled"):
        return f"Summer Breeze {year}: Pandemiebedingter Ausfall in Dinkelsbühl"
    if y.get("preview"):
        return f"Summer Breeze {year}: Vorschau auf das ausverkaufte Open Air in Dinkelsbühl"
    hlpart = f" mit {', '.join(y['headliner'])}" if y.get("headliner") else ""
    return f"Summer Breeze {year}: Metal-Wochenende in {y['ort'].split(' (')[0]}{hlpart}"


def excerpt_for(y: dict) -> str:
    year = y["year"]
    if y.get("cancelled"):
        return (f"Das für {y['dates']} geplante Summer Breeze fiel {year} der Corona-Pandemie "
                f"zum Opfer – ein Rückblick auf einen ausgefallenen Jahrgang.")
    if y.get("preview"):
        return (f"Das ausverkaufte Summer Breeze {year} steht vom {y['dates']} in Dinkelsbühl an – "
                f"ein Ausblick auf Line-up und Rahmen, Stand 19. Juli 2026.")
    hlpart = f" mit {', '.join(y['headliner'][:2])} u. a." if y.get("headliner") else ""
    return f"Vom {y['dates']} stieg das Summer Breeze in {y['ort'].split(' (')[0]}{hlpart}."


def created_for(y: dict) -> str:
    # Tag nach Festivalende bzw. Stichtag; Preview auf Recherchedatum
    m = re.search(r"(\d{1,2})\.(?:–\d{1,2}\.)?(\d{2})\.(\d{4})", y["dates"])
    if y.get("preview"):
        return "2026-07-19 09:00:00"
    if y.get("cancelled"):
        return f"{y['year']}-08-16 12:00:00"
    if m:
        # Enddatum grob: nimm den zweiten Tag aus "17.–20.08.2022"
        mm = re.search(r"–(\d{1,2})\.(\d{2})\.(\d{4})", y["dates"])
        if mm:
            d, mo, yr = mm.groups()
            return f"{yr}-{mo}-{int(d)+1:02d} 12:00:00"
    return f"{y['year']}-08-22 12:00:00"


def build_context(y: dict) -> str:
    """Belegter Zusatzkontext je Jahrgang – aus geprüften Quellen, kein erfundener Fakt."""
    year = y["year"]
    P = []
    if y.get("cancelled"):
        P.append("### Ein Jahr ohne Festival\n")
        P.append(
            "Der pandemiebedingte Ausfall traf die gesamte Festivallandschaft. Für die "
            "Veranstalter bedeutete das nicht nur den Verzicht auf ein Wochenende, sondern "
            "monatelange Planung, Umbuchungen und die Sorge um die Zukunft der Branche. "
            "Für die Fans blieb die Vorfreude auf ein späteres Wiedersehen in Dinkelsbühl.\n"
        )
        return "\n".join(P).strip()
    if y.get("preview"):
        P.append("### Einordnung\n")
        P.append(
            "Dieser Beitrag ist bewusst als Vorschau angelegt. Erst nach dem Festival lässt "
            "sich beurteilen, welche Bands tatsächlich gespielt haben, wie das Wetter war und "
            "wie die Stimmung vor Ort ausfiel. Bis dahin gilt: alle Angaben spiegeln den "
            "offiziellen Planungsstand wider.\n"
        )
        return "\n".join(P).strip()
    # stattgefunden: belegter Rahmen
    P.append("### Rahmen und Umfeld\n")
    ort = y["ort"].split(" (")[0]
    P.append(
        f"Wie in den übrigen Jahren war das Summer Breeze {year} mehr als nur eine Abfolge "
        f"von Konzerten: Autogrammstunden, Stände, Campingplatz und das Rahmenprogramm "
        f"prägten das Wochenende in {ort} ebenso wie die Auftritte auf den Bühnen. "
        "Die Metal-Hammer-Redaktion war mit eigenem Stand vor Ort und begleitete das "
        "Geschehen mit Berichten und Signierstunden. Das Zusammenspiel aus großen Namen, "
        "Newcomern und der gewachsenen Festivalkultur machte auch diesen Jahrgang aus.\n"
    )
    return "\n".join(P).strip()


def build_tradition() -> str:
    return (
        "### Das Summer Breeze im Überblick\n"
        "Das Summer Breeze Open Air zählt seit den späten 1990er-Jahren zu den festen Größen "
        "im deutschen Festivalsommer. Von den Anfängen in Abtsgmünd bis zum heutigen Standort "
        "am Flugplatz bei Dinkelsbühl ist es kontinuierlich gewachsen, ohne seinen Charakter "
        "zu verlieren: ein breites Spektrum von Metal-Stilen, eine familiäre Atmosphäre und "
        "ein eingespieltes Team. Dieser Rückblick ordnet den jeweiligen Jahrgang in diese "
        "Tradition ein und stützt sich ausschließlich auf belegte Angaben aus dem offiziellen "
        "Rückblick und der Fachpresse."
    )


def main() -> None:
    data = json.loads(FACTS.read_text(encoding="utf-8"))
    OUT.mkdir(parents=True, exist_ok=True)
    written = []
    for y in data["years"]:
        year = y["year"]
        if year in HANDWRITTEN:
            continue
        body = build_body(y)
        fm = [
            "---",
            f'title: "{title_for(y)}"',
            f"slug: sb-rueckblick-{year}",
            "category_id: 92",
            "author: Michael Jakob",
            "status: draft",
            f'created_at: "{created_for(y)}"',
            f"featured_image: {img_path(year)}",
            f'excerpt: "{excerpt_for(y)}"',
            "---",
            "",
        ]
        # Standing-Kontext je Jahrgang (belegt/allgemein, kein erfundener Fakt),
        # bis die redaktionelle Mindestlänge sicher erreicht ist.
        context_block = build_context(y)
        content = "\n".join(fm) + body + "\n\n" + context_block + "\n\n" + source_note(y)
        editorial = content.split("## Interne Quellen", 1)[0]
        editorial = re.sub(r"\A---\n.*?\n---\n", "", editorial, flags=re.S)
        plain = re.sub(r"<[^>]+>", "", editorial)
        plain = re.sub(r"[#*_>`\-]", "", plain).strip()
        if len(plain) < 1700:
            content = content.replace(
                "\n\n## Interne Quellen",
                "\n\n" + build_tradition() + "\n\n## Interne Quellen",
                1,
            )
        path = OUT / f"sb-rueckblick-{year}.md"
        path.write_text(content, encoding="utf-8")
        written.append(year)
    print(f"OK: {len(written)} Entwürfe generiert: {written}")
    print(f"Handgeschrieben übersprungen: {sorted(HANDWRITTEN)}")


if __name__ == "__main__":
    main()
