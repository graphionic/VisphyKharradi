# FTPRENEUR — Hero · Concept 01 (final)

Plain HTML + CSS + vanilla JS with no dependencies, ready to move into a CodeIgniter view.

```
index.html   hero markup only (nav + hero)
hero.css     tokens per breakpoint · layout · interactions · entrance · ambient motion
hero.js      entrance trigger · pointer depth · YOU. toggle · menu state
assets/      portrait cutout (backdrop removed, person untouched) · original · self-hosted fonts
previews/    final screenshots (desktop 1440 · mobile 390)
```

## Copy (final)
- Eyebrow: PERSONALISED HEALTH + DISEASE MANAGEMENT
- Headline: BUILT / AROUND / YOU.
- Supporting: A personalised approach to nutrition, strength and lifestyle — designed around
  **your body**, your **health condition**, your goals and everyday life.
- Disciplines: 01 Nutrition · 02 Strength · 03 Lifestyle
- Micro-line (desktop ≥1200): MANAGE BETTER. MOVE BETTER. LIVE BETTER.
- Qualifier (desktop/tablet): Programs are personalised. Individual outcomes vary.
- CTAs: EXPLORE PROGRAMS · HOW IT WORKS

No cure, reversal, guarantee, clinical, credential or statistic claims.

## Layout system
All geometry lives in custom properties on `.hero`:
`--pcx --pt --ph` (portrait), `--fr --fcy` (disc), `--or` (orbit, concentric with disc),
`--h1 --yfs` (type), `--sa --ma` (marigold / mint angles on the orbit, via CSS `cos()/sin()`).
Breakpoints override tokens: ≥1200 · 1024–1199 · 600–1023 · <600 · ≤410 · short phones.

YOU. is drawn twice: solid **behind** Visphy, outline **in front**, masked by the portrait's alpha
(`mask-image: visphy-cutout.webp`). JS keeps the mask aligned when pointer depth moves layers
(`--mdx/--mdy`). If you change a `data-depth`, update `PORTRAIT`/`YOU` in hero.js.

## Motion
Entrance ≈ 1.8s (grid → nav → eyebrow → BUILT → AROUND → disc → portrait → YOU → name → pillars → copy → CTAs).
Ambient motion only moves the geometry: orbit node, dotted orbit, marigold drift, light inside the disc.
Type and Visphy stay still. Pointer depth runs on fine pointers only (portrait 4px, marigold 4px,
orbit −3px, disc −1.5px, type 1–2px). `prefers-reduced-motion` disables all of it.
