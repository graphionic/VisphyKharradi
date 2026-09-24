# Visphy Kharradi — Static HTML / CSS / JS

A plain HTML/CSS/JS version of the React + Vite + Tailwind project. No build step and no framework.
The design and breakpoints match the React app exactly.

```
index.html      – all 43 sections, pre-rendered markup (icons inlined as SVG)
css/styles.css  – compiled stylesheet (Tailwind utilities + custom animations)
js/main.js      – vanilla JS: condition chips (S27), FAQ accordion (S31)
```

Open `index.html` in a browser, or serve the folder (e.g. `python3 -m http.server`).

Breakpoints (same as the original): sm 640px · md 768px · lg 1024px · xl 1280px (sidebar appears).

External resources: Google Fonts and Pexels images load from the internet.

Regenerate after editing the React source: `node scripts/export-static.mjs` (from the repo root).
