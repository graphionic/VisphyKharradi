# FTPRENEUR · Program Experience · Concept 02: Visual Program Editions

This is a design prototype only:
- mock data and demo photography;
- no backend, checkout or payment;
- no changes to the live site, the CodeIgniter project or Concept 01.

Serve this folder and open it:

    cd packages-concept-02 && python3 -m http.server 8081

## Idea
Each program is an **edition cover**, not a pricing card.

- **Spine:** vertical edition number and category, with an accent tick.
- **Cover:** the photograph sits in a white inset frame, in monochrome.
- **Below the cover:** eyebrow, title (sentence case), two-line summary, investment and Explore.

**One interaction language, the cover bleed.** On hover or focus:
- the photo breaks out of its frame to the card edge and turns to colour;
- the crop shifts slightly;
- the spine fills with the program accent;
- the number lifts, making room for the program's first three focus areas;
- the Explore arrow extends.

Touch devices show the photos in colour at rest, since they can't hover.

**Signature program (#02):**
- ultramarine frame and spine;
- a "Signature program" label with a marigold signal;
- the photo is always in colour.

It uses the same grid size as every other card, and it doesn't rely on colour alone.

## Drawer (bottom sheet)
**Opening sequence:**
- the page dims and the selected card holds its explored state;
- the sheet rises (about 86vh on desktop, 94svh on mobile);
- the photo wipes up from the bottom with a 1.08 → 1 settle;
- the content fades in with a 12px rise.

**Layout:**
- **Left:** a large photo panel that stays in place. It shows the edition number and a caption that tracks the current section.
- **Middle:** a numbered vertical section rail with progress: 01 Overview · 02 Focus · 03 Included · 04 Journey · 05 Investment.
- **Right:** the scrolling story:
  - facts;
  - the focus dot meter (Lead / Core / Support);
  - focus-area words;
  - the numbered inclusions;
  - the interactive journey (hover or click a step to read what happens);
  - the investment panel;
  - a "Next edition" link with a photo.
- **Sticky dock:** price and Start this program. It hides while the investment panel is on screen.

**Controls:**
- previous / next buttons, the ← → keys and "Next edition" all switch programs;
- close with Esc, the scrim, the Close button or by dragging the bar down;
- Tab stays inside the drawer;
- `#program=<slug>` opens a program directly.

## Data and images
- `data.js`: `window.ED_PROGRAMS`, using the brief's schema plus two additions:
  - `imageFocus`, the crop control;
  - `disciplines[].weight`, where 1 is support, 2 is core and 3 is lead.
- **Cards:** built from one `<template id="ed-card-tpl">`; there is no hand-written card markup.
- **Images:** stored as `assets/programs/program-NN-<word>.webp`.
  - Replace a file with the same name and the design adapts, with no code change.
  - If a file is missing, the card and drawer show a labelled **image slot** in the same geometry. Programs 11 and 12 demonstrate this on purpose.
- The photos for programs 01–10 are **AI-generated demo images**, labelled "Demo image". Swap them for real program photography before any real use.

## Files
- `index.html`: the section, the card template and the drawer.
- `editions.css`: all styles, namespaced `.ed`, `.ed-card` and `.edd`.
- `editions.js`: vanilla JS for rendering, filtering, show more / less, the drawer, scroll-spy, keys, drag and deep links.
- `assets/`: fonts and program photos.
- `previews/`: QA screenshots.
