# Package Experience — Concept 01 · "The Training Log"

A standalone design prototype. It uses mock data only, with no backend, payment or site integration.

Open it at `http://localhost:8080/packages-concept-01/` (serve the repo root with `python3 -m http.server 8080`).

## Files
- `data.js`: 20 mock programs (`window.PK_PROGRAMS`). Edit copy and prices here.
- `index.html`: the section plus one static bottom-sheet drawer. JS fills the `data-f` fields.
- `packages.css`: all styles, namespaced `.pk` (section) and `.pkd` (drawer).
- `packages.js`: vanilla JS for:
  - the filter;
  - show more / show less (6 at a time);
  - the drawer: open, close, prev/next, index scroll-spy, drag-down close, Esc, ←/→ and focus trap;
  - `#program=slug` deep links.
- `assets/`: fonts (Bricolage Grotesque, Karla, JetBrains Mono).
- `previews/`: QA screenshots.

## Idea
Each card is a page from a training log:
- a program number;
- a category accent rail;
- one tick per week, which draws in on hover;
- the footer fills with the program accent on hover.

The Signature program (#2) is ultramarine with a slow edge trace and a "Recommended start" label, so the signal does not rely on colour alone. The drawer takes on the accent of the selected card.

## Porting notes (CodeIgniter)
- Render the cards server-side with the same markup, or keep `data.js` as a JSON feed.
- The "Choose this program" and "Talk to us" handlers only show a toast right now. Wire them to the real checkout and contact routes.
- Styles are namespaced, so there are no global selectors to leak.
