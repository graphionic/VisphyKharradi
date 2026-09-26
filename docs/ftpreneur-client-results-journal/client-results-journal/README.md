# FTPRENEUR · Client Results · Transformation Journal: 3-story carousel (08A.1 refinement)

A visual prototype only. No production, backend, ClientResultService, database, admin or brand-guideline changes, and Phase 09 is not built.

**Run it:** `python3 -m http.server 8085` in this folder, then open `http://localhost:8085/`.

| File | Purpose |
|---|---|
| `index.html` | Section markup. The dashed strip at the top is review chrome only |
| `journal.css` | Styles, namespaced `.crj` (section) and `.crj-s` (one story) |
| `stories.js` | Shared story data for the carousel and the drawer (future Client Result record shape) |
| `journal.js` | Carousel rendering and the grouped carousel. "View full story" opens the drawer |
| `drawer.css` / `drawer.js` | Full Story bottom drawer and inner report viewer (`.crd` namespace) |
| `previews/` | Screenshots: desktop / tablet / mobile, pages 1–3 |

## What changed from the approved Journal
Same visual language (cream, organic brand shapes, initials, result arrows). It's recomposed for density:
- **One warm canvas with three stories side by side**, separated only by hairlines. There are no card borders. Rows line up across the three stories using CSS subgrid.
- **Identity tile (refined):** one component with two states, both exactly 104×88 on desktop and tablet and 88×76 on mobile, so there is no layout shift.
  - **Image state:** if `image` exists, it shows as `<img>` with `object-fit: cover` and rounded corners.
  - **Initials fallback:** otherwise, bold Bricolage initials on a pale tint set by the story's tone (ultramarine / mint / marigold) with one small corner dot. No other decoration.
  - If an image fails to load, the tile switches to the initials automatically.
  - Story 02 uses a neutral sample photo, `assets/sample/client-photo-sample.jpg`: a blurred silhouette, not a client, labelled "Sample image". Production uses the uploaded client photo.
- **Story order:** number → art → name / subtitle → program · weeks → focus (max 2, then "+N") → quote (Karla, clamped at 4 lines) → **2 metrics** (the first two in display order) → "View full story →" as a text link.
- **Arrow tone** alternates by story: ultramarine / mint / marigold. The number style stays the same throughout.
- **One navigation for the group:** 01 ━━ 03 progress, "Stories 1–3 of 8", Previous / Next stories.
- **Section height:** about 760px at 1440×900 (heading + carousel + navigation).

## Grouping
- ≥1100px: 3 per page. 700–1099px: 2 per page. <700px: 1 per page (counter 01 / 08).
- When the breakpoint changes, the carousel regroups and keeps the first visible story in view.
- **Controls:** buttons, ← / → keys, and swipe/drag with an axis lock so vertical scrolling still works.

## Data
- Stories 01–04 use the supplied demo records.
- **Stories 05–08 (Nisha P., Isha R., Kabir T., Ananya V.) are PLACEHOLDERS** for layout only, marked `placeholder: true` in `journal.js`. Replace them with the real Client Result records.

## Full Story bottom drawer (prototype of Phase 09)
- **Opens** from any "View full story" on the same page, with no route change.
  - Sheet width min(88vw, 1380px), height 86vh, rounded top corners. Mobile: 96svh.
  - Rises over ~420ms and closes over ~300ms. Ink scrim at 42% with a 3px blur.
- **Header:** "Client result / 01" and the close button. Once the overview scrolls away, a compact identity (tile, name, program · weeks) fades into the header.
- **One scroll container.** The landing page is scroll-locked and its position restored on close.
- **Order:** overview (identity + transformation focus) → testimonial → client story (reading column + journey details) → recorded outcomes (all public metrics, optional dates) → before & after → transformation gallery → client story gallery → reports & evidence → closing CTA → previous / next story.
- **Sections without content are not rendered, and numbering closes up.**
  - Aarav demonstrates every component.
  - Meera: story + outcomes + one report.
  - Vikram and the others: story + outcomes only.
- **Report viewer:** an inner layer that slides over the story (PDF pages or a large image). "Back to story", the close button or Esc returns to the story with focus on the "View report" row. The drawer never closes for this.
- **Prev / next story** switches content inside the drawer and moves the carousel to the matching page.
- **Close:** close button, backdrop or Esc. Focus returns to the "View full story" of the story last shown.
- **Accessibility:** role="dialog", aria-modal, aria-labelledby, Tab trap, drag-down on touch, reduced motion.
- **Placeholders:** all before/after, gallery and report visuals are neutral placeholders with review-only labels. No fabricated client media.
- **Prototype copy:** the story paragraphs for 01–04 were written for layout review. Production renders `full_story`.
- **CTAs:** "Find my program" and "Explore programs" only show a prototype notice.
