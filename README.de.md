# Bavarian Rank Engine

![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-blue)
![WordPress 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![License: GPL-2.0](https://img.shields.io/badge/License-GPL--2.0--or--later-green)
![Version](https://img.shields.io/badge/Version-1.3.5-orange)
![Tests](https://img.shields.io/badge/Tests-112%20passing-brightgreen)

🇬🇧 [English version → README.md](README.md)

**Website:** [bavarianrankengine.com](https://bavarianrankengine.com) &nbsp;·&nbsp; [How To](https://bavarianrankengine.com/howto.html) &nbsp;·&nbsp; [FAQ](https://bavarianrankengine.com/faq.html) &nbsp;·&nbsp; [Changelog](https://bavarianrankengine.com/changelog.html)

---

Bavarian Rank Engine ist ein schlankes SEO- & GEO-Plugin für WordPress. Es generiert KI-Metabeschreibungen, gibt Schema.org-Strukturdaten aus, erstellt GEO-Inhaltsblöcke für KI-Engines und verwaltet den Crawler-Zugriff über robots.txt und llms.txt — alles in einem Plugin, ohne dass etwas hinter einer Paywall versteckt wird.

Es funktioniert mit oder ohne KI-Key. Es integriert sich ohne Konflikte in Rank Math, Yoast, AIOSEO und SEOPress. Kein SaaS. Keine Telemetrie. Keine Upsells.

---

## Warum dieses Plugin existiert

Die meisten WordPress-SEO-Plugins haben sich in die gleiche Richtung entwickelt: aufgeblähte Feature-Sets, Dashboards voller Metriken, die niemand gebraucht hat, und ein Preismodell, das die nützlichen Funktionen hinter einem monatlichen Abo versteckt.

Die KI-Welle hat es schlimmer gemacht. Plugins fingen an, „KI-gestützte" Features anzubieten — aber als Proxy-Dienst. Man zahlt eine monatliche Gebühr, die Inhalte werden über deren Server geleitet, sie rufen die KI-API im eigenen Namen auf und schlagen eine Marge drauf.

BRE verfolgt einen anderen Ansatz:

- **Direkter API-Zugriff.** Du hinterlegst deinen eigenen Key von OpenAI, Anthropic, Google oder xAI. BRE ruft die API direkt auf. Kein Mittelsmann, keine Marge, keine Daten über Server Dritter.
- **Klarer Output, kein Lärm.** Metabeschreibungen, Strukturdaten, KI-Inhaltsblöcke für GEO, Bot-Steuerung. Keine Lesbarkeits-Scores, keine Keyword-Dichte-Meter, keine Upsell-Banner.
- **Keine Subscription.** GPL-2.0. Kostenlos auf beliebig vielen Sites nutzbar. Die einzigen Kosten sind die API-Nutzung — typischerweise Bruchteile eines Cents pro Beitrag.
- **Keine Telemetrie.** BRE sendet keine Daten nach Hause. Kein Usage-Tracking, kein Remote-Logging, keine Analytics, die den eigenen Server verlassen.
- **Funktioniert ohne KI.** Kein API-Key? Der Fallback-Extraktor erzeugt eine brauchbare Metabeschreibung aus dem Artikelinhalt per Satzgrenzenerkennung. Jeder Beitrag bekommt eine Beschreibung.

Entwickelt in Passau, Bayern — für [Donau2Space](https://donau2space.de), einen persönlichen KI-Blog, für den ich genau das gebraucht habe — und nichts mehr.

---

## Inhaltsverzeichnis

- [Warum dieses Plugin existiert](#warum-dieses-plugin-existiert)
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Features](#features)
- [Datenspeicherung](#datenspeicherung)
- [Sicherheit](#sicherheit)
- [KI-Provider](#ki-provider)
- [Hooks & Erweiterbarkeit](#hooks--erweiterbarkeit)
- [AJAX-Schnittstellen](#ajax-schnittstellen)
- [Installation](#installation)
- [Technischer Stack](#technischer-stack)
- [Lizenz](#lizenz)

---

## Verzeichnisstruktur

```
bavarian-rank-engine/
├── bavarian-rank-engine.php      # Plugin-Header, Konstanten (BRE_VERSION, BRE_DIR, BRE_URL)
├── uninstall.php                 # Aufräumen bei Plugin-Löschung
├── assets/
│   ├── admin.css                 # Gemeinsames Admin-Stylesheet
│   ├── admin.js                  # Provider-Selektor, Verbindungstest
│   ├── bulk.js                   # Bulk-Generator AJAX-Loop + Progress-UI
│   ├── editor-meta.js            # Meta Editor Box: Live-Zähler, KI-Regen-Button
│   ├── geo-editor.js             # GEO Block Editor: Generieren / Löschen Button
│   ├── geo-frontend.css          # Minimales Stylesheet für .bre-geo auf dem Frontend
│   ├── link-suggest.js           # Interne Link-Vorschläge: Trigger, UI, Apply (Gutenberg + Classic)
│   └── seo-widget.js             # SEO Analyse Widget: Live-Auswertung im Editor
├── includes/
│   ├── Core.php                  # Singleton-Bootstrap, lädt alle Abhängigkeiten
│   ├── Admin/
│   │   ├── AdminMenu.php         # Menüstruktur + Dashboard-Render
│   │   ├── BulkPage.php          # Bulk Generator Admin-Seite
│   │   ├── GeoEditorBox.php      # GEO Block Meta-Box im Post-Editor
│   │   ├── GeoPage.php           # GEO Block Einstellungsseite
│   │   ├── LinkAnalysis.php      # AJAX-Handler für Link-Analyse Dashboard
│   │   ├── LinkSuggestPage.php   # Einstellungsseite für interne Link-Vorschläge
│   │   ├── MetaEditorBox.php     # Meta Description Meta-Box im Post-Editor
│   │   ├── MetaPage.php          # Meta Generator Einstellungsseite
│   │   ├── ProviderPage.php      # AI Provider Einstellungsseite
│   │   ├── SchemaMetaBox.php     # Schema.org per-Post Meta-Box
│   │   ├── TxtPage.php           # TXT-Dateien-Seite: llms.txt + robots.txt (Tabs)
│   │   ├── SchemaPage.php        # Schema.org Einstellungsseite
│   │   ├── SeoWidget.php         # SEO Analyse Sidebar Widget
│   │   ├── SettingsPage.php      # Zentrales getSettings() — mergt alle Option-Keys
│   │   └── views/                # PHP-Templates für alle Admin-Seiten
│   ├── Features/
│   │   ├── CrawlerLog.php        # KI-Bot-Besuche loggen (eigene DB-Tabelle)
│   │   ├── GeoBlock.php          # GEO Quick Overview Block (Frontend-Ausgabe)
│   │   ├── LlmsTxt.php           # /llms.txt Endpunkt mit ETag/Cache
│   │   ├── LinkSuggest.php       # Interne Link-Vorschläge: Matching-Engine + AJAX-Handler + Meta-Box
│   │   ├── MetaGenerator.php     # Kernlogik: KI-Aufruf, Speichern, Bulk, AJAX
│   │   ├── RobotsTxt.php         # robots.txt Bot-Blocking via WP-Filter
│   │   └── SchemaEnhancer.php    # JSON-LD Schema.org Ausgabe in wp_head
│   ├── Helpers/
│   │   ├── BulkQueue.php         # Mutex-Lock für Bulk-Prozesse (Transient-basiert)
│   │   ├── FallbackMeta.php      # Meta-Extraktion aus Post-Content ohne KI
│   │   ├── KeyVault.php          # API-Key Verschleierung vor dem Schreiben in die DB
│   │   └── TokenEstimator.php    # Grobe Token-Schätzung für Kostenvorschau im Bulk
│   └── Providers/
│       ├── ProviderInterface.php # Interface: getId, getName, getModels, testConnection, generateText
│       ├── ProviderRegistry.php  # Registry-Pattern: Provider registrieren und abrufen
│       ├── AnthropicProvider.php # Claude API (Messages API)
│       ├── GeminiProvider.php    # Google Gemini (generateContent API)
│       ├── GrokProvider.php      # xAI Grok (OpenAI-kompatibler Endpunkt)
│       └── OpenAIProvider.php    # OpenAI GPT (Chat Completions API)
└── vendor/                       # Composer-Abhängigkeiten (nur Produktionsstand)
```

---

## Features

### AI Meta Generator

Generiert SEO-optimierte Meta-Beschreibungen (150–160 Zeichen) automatisch beim Veröffentlichen eines Beitrags. Der Prompt ist vollständig anpassbar; unterstützte Platzhalter: `{title}`, `{content}`, `{excerpt}`, `{language}`.

**Spracherkennung:** Die Zielsprache wird automatisch aus Polylang, WPML oder dem WordPress-Locale ermittelt und im Prompt übergeben — ohne Konfiguration.

**SEO-Plugin-Integration:** Generierte Beschreibungen landen nicht nur in `_bre_meta_description`, sondern auch direkt im nativen Feld des aktiven SEO-Plugins:

| SEO-Plugin | Meta-Feld |
|---|---|
| Rank Math | `rank_math_description` |
| Yoast SEO | `_yoast_wpseo_metadesc` |
| AIOSEO | `_aioseo_description` |
| SEOPress | `_seopress_titles_desc` |
| (keins aktiv) | BRE gibt `<meta name="description">` selbst aus |

**Token-Modus:** Wahlweise wird der gesamte Artikelinhalt gesendet (`full`) oder auf eine konfigurierbare Token-Anzahl (100–8000) gekürzt (`limit`). Das Kürzen erfolgt über `TokenEstimator` — eine wortbasierte Schätzung ohne externe Bibliothek.

**Fallback ohne KI:** `FallbackMeta::extract()` liefert immer eine brauchbare Beschreibung — auch ohne API-Key oder bei Fehlern. Vollständig multibyte-safe via `mb_substr` / `mb_strrpos`.

---

### GEO Block (Quick Overview)

Generiert KI-gestützte Inhaltsblöcke direkt im Artikeltext für Generative Engine Optimization:

- **Summary** — Kurzüberblick des Artikels
- **Key Points** — Stichpunktliste der wichtigsten Aussagen
- **FAQ** — Frage-Antwort-Paare (nur ab konfiguriertem Wort-Schwellenwert, Standard: 350 Wörter)

**Einfügeposition** (konfigurierbar): nach dem ersten Absatz (Standard), oben, unten.

**Ausgabe-Modi:**

| Modus | Verhalten |
|---|---|
| `details_collapsible` | Natives HTML `<details>` — zugeklappt, kein JavaScript nötig |
| `open_always` | Block immer sichtbar |
| `store_only_no_frontend` | Nur in DB speichern, keine Frontend-Ausgabe (z.B. für FAQPage-Schema) |

Alle Labels, Akzentfarbe, Farbschema (Auto/Hell/Dunkel) und Custom CSS sind über die Admin-Seite konfigurierbar — ohne Code.

---

### Schema.org Enhancer

Gibt JSON-LD-Strukturdaten und Meta-Tags in `<head>` aus. Einstellungen unter **Bavarian Rank → Schema.org**. Jeder Typ ist einzeln aktivierbar:

| Typ | Schema.org-Type | Hinweis |
|---|---|---|
| `organization` | `Organization` | Name, URL, Logo, `sameAs`-Links |
| `author` | `Person` | Autorenname, Profil-URL, optionaler Twitter-`sameAs` |
| `speakable` | `WebPage` + `SpeakableSpecification` | CSS-Selektoren auf H1 und ersten Absatz |
| `article_about` | `Article` | Headline, Publish/Modified, Description, Publisher |
| `breadcrumb` | `BreadcrumbList` | Automatisch unterdrückt wenn Rank Math oder Yoast aktiv |
| `ai_meta_tags` | — | `<meta name="robots">` mit `max-snippet:-1` |
| `faq_schema` | `FAQPage` | Automatisch aus GEO Block Daten befüllt |
| `blog_posting` | `BlogPosting` / `Article` | Mit eingebettetem `author` und Featured Image |
| `image_object` | `ImageObject` | Featured Image mit Dimensionen und Caption |
| `video_object` | `VideoObject` | YouTube/Vimeo wird automatisch erkannt |
| `howto` | `HowTo` | Schrittweise Anleitung — Daten per Metabox |
| `review` | `Review` | Bewertung mit `ratingValue` — Daten per Metabox |
| `recipe` | `Recipe` | Zutaten, Zeiten, Nährwerte — Daten per Metabox |
| `event` | `Event` | Datum, Ort, Veranstalter — Daten per Metabox |

---

### llms.txt

Bedient `/llms.txt` und paginierte Folgedateien über einen `parse_request`-Hook mit Priorität 1 — vor WordPress-Routing, kein Rewrite-Rule-Flush nötig.

**HTTP-Caching:** ETag, Last-Modified, Cache-Control. Transient-Cache wird bei jeder Einstellungsänderung automatisch invalidiert.

**Rank Math Konfliktwarnung:** Falls Rank Math ebenfalls eine llms.txt ausliefern will, zeigt BRE einen Admin-Hinweis an — BRE hat wegen Priorität 1 automatisch Vorrang.

---

### robots.txt Manager

Hängt `Disallow`-Blöcke über den WordPress-Filter `robots_txt` an — die WordPress-eigene robots.txt bleibt erhalten. 13 KI-Bots einzeln steuerbar: GPTBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot, Applebot-Extended, Bytespider, DataForSeoBot, ImagesiftBot, omgili, Diffbot, FacebookBot, Amazonbot.

---

### Bulk Generator

Batch-Verarbeitung aller veröffentlichten Beiträge ohne Meta-Beschreibung. Läuft als AJAX-Request im Browser — kein WP-Cron, keine CLI nötig. 1–20 Beiträge pro Batch, 6s Delay, bis zu 3 Versuche je Post, Mutex-Lock via Transient.

---

### Crawler Log

Loggt Besuche bekannter KI-Bots in der Tabelle `{prefix}bre_crawler_log` (bot_name, ip_hash SHA-256, url, visited_at). Einträge älter als 90 Tage werden automatisch bereinigt. Dashboard zeigt 30-Tage-Zusammenfassung.

---

## Datenspeicherung

### WordPress Options (wp_options)

| Option-Key | Inhalt |
|---|---|
| `bre_settings` | Aktiver Provider, API-Keys (verschleiert), Modell-Auswahl, Token-Kosten, `ai_enabled`-Flag |
| `bre_meta_settings` | Meta Generator: Auto-Modus, Post-Types, Token-Modus, Prompt |
| `bre_schema_settings` | Schema.org: aktivierte Typen, Organization sameAs-URLs |
| `bre_geo_settings` | GEO Block: Modus, Position, Labels, CSS, Prompt, Farbschema |
| `bre_robots_settings` | robots.txt: blockierte Bots |
| `bre_llms_settings` | llms.txt: Titel, Beschreibung, Featured-Links, Footer, Seitenanzahl |
| `bre_usage_stats` | Akkumulierte Token-Nutzung: `tokens_in`, `tokens_out`, `count` |
| `bre_first_activated` | Unix-Timestamp der Erstaktivierung (für Welcome Notice) |

### Post Meta (wp_postmeta)

| Meta-Key | Inhalt |
|---|---|
| `_bre_meta_description` | Generierte Meta-Beschreibung |
| `_bre_meta_source` | Quelle: `ai` / `fallback` / `manual` |
| `_bre_bulk_failed` | Letzter Fehler beim Bulk-Versuch |
| `_bre_geo_summary` | GEO Block Summary |
| `_bre_geo_bullets` | GEO Block Key Points (JSON-Array) |
| `_bre_geo_faq` | GEO Block FAQ (JSON-Array) |

### Transients

| Transient | TTL | Zweck |
|---|---|---|
| `bre_llms_cache_{n}` | 1 Stunde | Gecachter llms.txt Inhalt je Seite |
| `bre_link_analysis` | 1 Stunde | Dashboard Link-Analyse Ergebnis |
| `bre_bulk_running` | 15 Minuten | Mutex-Lock für den Bulk Generator |
| `bre_meta_stats` | 5 Minuten | Dashboard Meta-Coverage-Abfrage |
| `bre_crawler_summary` | 5 Minuten | Dashboard Crawler-Zusammenfassung (letzte 30 Tage) |

> **Uninstall:** `uninstall.php` löscht `bre_settings` und `_bre_meta_description` für alle Posts. Die übrigen Option-Keys und die `bre_crawler_log`-Tabelle müssen manuell gelöscht werden.

---

## Sicherheit

### API-Key Verschleierung (KeyVault)

```
Klartextkey  →  XOR(key, sha256(AUTH_KEY . SECURE_AUTH_KEY))  →  base64  →  "bre1:<base64>"
```

Kein `openssl_*` oder externe Extension nötig — läuft auf jeder PHP 8.0+ Installation. Das Präfix `bre1:` ermöglicht spätere Migration ohne Breaking Change.

**Sicherheitsgrenzen:** XOR mit statischem Salt ist Verschleierung, keine kryptografische Verschlüsselung. Für maximale Sicherheit können Keys als `wp-config.php`-Konstanten definiert werden:

```php
define( 'BRE_OPENAI_KEY',    'sk-...' );
define( 'BRE_ANTHROPIC_KEY', 'sk-ant-...' );
define( 'BRE_GEMINI_KEY',    'AI...' );
define( 'BRE_GROK_KEY',      'xai-...' );
```

### CSRF-Schutz und Capability Checks

Jeder AJAX-Handler ohne Ausnahme:

```php
check_ajax_referer( 'bre_admin', 'nonce' );
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( 'Unauthorized', 403 );
}
```

Kein `wp_ajax_nopriv_`-Handler — alle Endpunkte erfordern `manage_options`.

### CSS-Sanitierung (GEO Block)

Das Custom-CSS-Feld des GEO-Blocks wird durch `Helpers\Css::sanitize_declarations()` bereinigt — entfernt Kommentare, geschweifte Klammern, At-Regeln (`@import`, `@media` usw.), `url()`, `expression()` und `javascript:`, bevor die Ausgabe über `wp_add_inline_style()` injiziert wird.

### Datenschutz (DSGVO)

CrawlerLog speichert IPs ausschließlich als SHA-256-Hash. Originalwert wird nie persistiert. Einträge nach 90 Tagen automatisch gelöscht.

---

## KI-Provider

| Provider | Klasse | API-Basis-URL |
|---|---|---|
| OpenAI | `OpenAIProvider` | `https://api.openai.com/v1/chat/completions` |
| Anthropic | `AnthropicProvider` | `https://api.anthropic.com/v1/messages` |
| Google Gemini | `GeminiProvider` | `https://generativelanguage.googleapis.com/...` |
| xAI Grok | `GrokProvider` | `https://api.x.ai/v1/chat/completions` |

Neuen Provider hinzufügen: `ProviderInterface` implementieren, in `Core.php` via `$registry->register()` eintragen — erscheint automatisch in allen Dropdowns.

---

## Hooks & Erweiterbarkeit

### `bre_prompt` (Filter)

```php
add_filter( 'bre_prompt', function( string $prompt, WP_Post $post ): string {
    $keyword = get_post_meta( $post->ID, 'focus_keyword', true );
    return $keyword ? $prompt . "\nFokus-Keyword: {$keyword}" : $prompt;
}, 10, 2 );
```

### `bre_meta_saved` (Action)

```php
add_action( 'bre_meta_saved', function( int $post_id, string $description ): void {
    my_cdn_purge( get_permalink( $post_id ) );
}, 10, 2 );
```

---

## AJAX-Schnittstellen

Alle Endpunkte erfordern `manage_options` (kein `nopriv`).

| Action | Handler | Beschreibung |
|---|---|---|
| `bre_regen_meta` | `MetaEditorBox::ajax_regen` | Meta-Beschreibung für einzelnen Post neu generieren |
| `bre_test_connection` | `ProviderPage::ajax_test_connection` | API-Key und Verbindung testen |
| `bre_get_default_prompt` | `ProviderPage::ajax_get_default_prompt` | Standard-Prompt zurücksetzen |
| `bre_link_analysis` | `LinkAnalysis::ajax_analyse` | Link-Analyse ausführen |
| `bre_link_suggestions` | `LinkSuggest::ajax_suggest` | Top-10 interne Link-Vorschläge für aktuellen Beitrag zurückgeben |
| `bre_geo_generate` | `GeoEditorBox::ajax_generate` | GEO Block generieren |
| `bre_geo_clear` | `GeoEditorBox::ajax_clear` | GEO Block löschen |
| `bre_llms_clear_cache` | `TxtPage::ajax_clear_cache` | llms.txt Cache leeren |
| `bre_dismiss_llms_notice` | `LlmsTxt::ajax_dismiss_notice` | Rank-Math-Hinweis ausblenden |
| `bre_dismiss_welcome` | `AdminMenu::ajax_dismiss_welcome` | Welcome Notice per User ausblenden |
| `bre_bulk_generate` | `MetaGenerator::ajaxBulkGenerate` | Nächsten Batch verarbeiten |
| `bre_bulk_stats` | `MetaGenerator::ajaxBulkStats` | Fortschritt abrufen |
| `bre_bulk_release` | `MetaGenerator::ajaxBulkRelease` | Mutex-Lock manuell freigeben |
| `bre_bulk_status` | `MetaGenerator::ajaxBulkStatus` | Lock-Status prüfen |

---

## Installation

**Via GitHub Release (empfohlen):**
1. `bavarian-rank-engine.zip` vom [neuesten Release](https://github.com/noschmarrn/bavarianrankengine/releases/latest) herunterladen
2. In WordPress unter *Plugins → Installieren → Plugin hochladen* einspielen

**Manuell (clone):**
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/noschmarrn/bavarianrankengine.git bavarian-rank-engine
wp plugin activate bavarian-rank-engine
```

**Nach der Aktivierung:**
1. *Bavarian Rank → AI Provider* — Provider wählen, API-Key hinterlegen, Verbindungstest
2. *Meta Generator* — Auto-Modus aktivieren, Post-Types auswählen

Kein JavaScript-Build-Step. Alle Assets unter `assets/` sind direkte JS/CSS-Dateien.

---

## Technischer Stack

| Komponente | Technologie |
|---|---|
| Backend | PHP 8.0+, WordPress Plugin API |
| Namespace | `BavarianRankEngine\` |
| Architektur | Singleton-Core, Registry-Pattern (Provider), Feature-Klassen mit `register()` |
| Datenbank | WordPress Options API, `wpdb` (eigene Tabelle für CrawlerLog) |
| Caching | WordPress Transients |
| Frontend | Vanilla JS + jQuery (WordPress-integriert), kein Build-Step |
| I18n | `.pot`-File, Text-Domain `bavarian-rank-engine` |
| Tests | PHPUnit (102 Tests, 216 Assertions) |
| Coding Standard | WordPress PHPCS |
| Lizenz | GPL-2.0-or-later |

---

## Lizenz

GPL-2.0-or-later — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

Copyright (c) 2025–2026 [Donau2Space](https://donau2space.de)
