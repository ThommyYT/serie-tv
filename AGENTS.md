# SerieTV Streaming - Agent Instructions

These instructions apply to the whole repository.

## Role

Act as a pragmatic full-stack co-pilot: debug, implement features, refactor, and improve scraping/rendering code without over-scoping.

## Environment

- OS: Windows
- Local runtime: XAMPP (Apache + PHP)
- Backend: PHP 8.5.3 on Apache
- DB: MariaDB (MySQL-compatible) 10.4.27
- Frontend: HTML5, CSS3, TypeScript compiled to browser JS
- UI: Bootstrap 5
- JS libs: jQuery (used for quick interactions / compatibility)
- External helper: FlareSolverr (used as an HTTP proxy/solver for protected pages)

## Runtime Boundaries

- Do not start/stop XAMPP or other local software; the user runs services manually.

## Project Layout (expected)

- `php/`: backend scripts (fetching/scraping/render helpers)
- `php/logs/`: application/runtime logs used for debugging
- `html/`: HTML templates and pages
- `html/template/`: reusable template fragments (e.g. navigation)
- `js/`: compiled JavaScript consumed by the browser
- `css/`, `img/`: static assets

## Debug & Logs

- When diagnosing errors, prioritize logs in `php/logs/` and the browser console.
- Prefer targeted fixes over broad rewrites.

## Scraping / HTML Manipulation

- Fetch data with `cURL` (SSL enabled + explicit User-Agent) and/or `file_get_contents` when appropriate.
- When needed, route requests through FlareSolverr (proxy/solver) instead of hitting protected origins directly.
- Parse/manipulate fetched HTML with QueryPath (CSS-selector style).
- Before rendering, strip unnecessary DOM nodes (ads, external scripts, noisy containers).
- Extract: video links, metadata (title, poster, plot), and stream sources.
- Map extracted data to JSON structures intended for the frontend.

## TypeScript Integration

- Define `interface`/`type` for data coming from PHP (JSON) and keep the TS types aligned with the backend output.
- Keep TS build output browser-safe (no Node-only APIs).

## Safety / Performance

- Ensure `php_curl` is enabled in `php.ini` when curl-based fetching is used.
- Use QueryPath efficiently on large HTML pages (avoid repeated full-document scans).
