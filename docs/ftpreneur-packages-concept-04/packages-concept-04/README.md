# FTPRENEUR · Program Experience · Concept 04: Premium Visual Program Grid

A standalone design prototype. It is not wired into the site and has no backend, checkout or payments.

**Run it:** `python3 -m http.server 8083` in this folder, then open `http://localhost:8083/`.
**Deep link:** `index.html#program=metabolic-health-program`

## Files
| File | Purpose |
|---|---|
| `index.html` | Section markup, one card `<template>`, bottom drawer |
| `posters.css` | All styles. Namespaced: `.pv` section · `.pv-card` card · `.pvd` drawer |
| `posters.js` | Rendering, filter, show more, drawer. Vanilla JS, no dependencies |
| `data.js` | **Same contract as Concepts 01/02** (`window.ED_PROGRAMS`, `window.ED_JOURNEY_NOTES`) |
| `assets/programs/program-NN-<word>.webp` | Demo photos. Replace the file under the same name to swap one |
| `previews/` | Reference screenshots |

## The idea
Every card is a small campaign poster. The photo runs full-bleed; a white panel cuts across it on an editorial diagonal. The cut **mirrors in a checkerboard** (the large outlined number always sits on the deeper side of the photo), which gives the grid rhythm while every card keeps the same height.

- **Hierarchy:** number (outlined, over the photo) → category (dot + tag, category rail) → NAME → positioning line → duration → investment → EXPLORE PROGRAM →
- **Idle:** photos are desaturated; there's a 44px category rail and the number is outlined.
- **Hover / focus:** colour · scale 1.035 · rail runs full width · number fills solid and lifts 4px · CTA bar wipes to ultramarine · arrow moves 5px · card lifts 3px.
- **Signature #02:** 2px ultramarine frame + marigold folded corner + "Signature program" ribbon + a near-colour photo at idle. Same size as the other cards.
- **Category accents** (small signals only): Weight/Lifestyle ultramarine · Metabolic/Nutrition mint · Strength marigold · Complete ink.
- **Missing image** (11, 12): a labelled slot with the expected filename, same card geometry.

## Grid behaviour
- 6 cards visible. **Show more programs** adds 6 at a time with a staggered rise and a photo clip reveal. It then becomes **Show fewer**. Works for any number of programs.
- There's a "06 / 12" counter with a progress line, and "Showing 06 of 12" in the filter bar.
- **Filters** are editorial text with superscript counts and a sliding underline. Filtering resets to 6.
- **Layout:** 3 columns ≥1024px · 2 columns on tablet · 1 column on mobile (natural scroll, no carousel).

## Drawer: Premium program profile
- A bottom sheet at 90vh (95svh on mobile), top corners rounded, page dimmed with a 6px blur.
- **Header:** Program 02 / Metabolic · name · 12 weeks · **Previous / Next / Close**
- **Body:** a sticky 42% photo column (rail, number, signature, demo label) and a scrolling 58% column with a sticky anchor nav and a progress line:
  - **Overview:** lead, health note, who it's for, Duration / Format / Reviews
  - **Focus:** a proportional emphasis bar (Lead/Core/Support) plus focus areas
  - **Included:** a checked list
  - **Journey:** numbered stages above a week rail. Review weeks are marked; hover or click fills the rail to that stage.
  - **Investment:** plus a "Next program" teaser
- **Persistent footer:** 12 weeks · 1:1 personalised · disclaimer · price · Talk to us first · **Start this program →** (prototype toast only)
- **Controls:** Esc, ←/→ between programs, Tab trap, drag the header down to close, `#program=slug` deep link, focus returns to the card.
- Reduced motion is respected.

## Porting notes (CodeIgniter)
Loop the card `<template>` markup server-side with the same fields, or keep `data.js` fed from a JSON endpoint. All selectors are namespaced, and none touch the hero or the other sections.
