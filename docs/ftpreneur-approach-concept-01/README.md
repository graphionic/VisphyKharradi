# Section 05 / The Approach — Concept 01: "Bold Conversion Manifesto"

A standalone design prototype for **Section 05 / The Approach** of the Ftpreneur landing page.

It is built as an isolated visual prototype under `docs/ftpreneur-approach-concept-01/`. It does not modify existing CodeIgniter production files, Hero, Section 02, Section 03, Section 04, backend, or database models.

---

## Concept Overview
- **Visual Rhythm Shift**: Breaks the visual chain after two light sections (Section 03 & Section 04) with a dramatic, high-end **Dark Ink / Navy canvas** (`#141B31` / `#16213A`).
- **Manifesto Statement**:
  > **NOT A GENERIC PLAN.**  
  > **A METHOD BUILT**  
  > **AROUND *YOU.***  
  Oversized Bricolage Grotesque display typography (weight `800`, `"opsz" 96`) dominating the visual field with Ultramarine (`#2E47FF`) emphasis.
- **5-Stage Method Process Rail**:
  `01 ASSESS` — `02 PERSONALISE` — `03 BUILD` — `04 REVIEW` — `05 PROGRESS`  
  Connected by an interactive horizontal process rail with an advancing Ultramarine progress line and crossfading stage explanation cards.
- **Conversion Bridge**:
  > **YOUR HEALTH ISN'T GENERIC.**  
  > **YOUR NEXT STEP SHOULDN'T BE EITHER.**  
  Prominent **FIND MY PROGRAM →** primary CTA button, trust microcopy, and disclaimer.

---

## Production Typography System Used
- **Display**: `Bricolage Grotesque` (`font-weight: 800`, `"opsz" 96`)
- **Body**: `Karla` (`font-weight: 400`, `line-height: 1.6`)
- **Mono / Technical**: `JetBrains Mono` (`font-weight: 700`, `letter-spacing: 0.14em`)

---

## Files
- `index.html`: Section HTML structure and dialog markup.
- `approach.css`: Isolated CSS styles using production tokens and grid padding `var(--gx)`.
- `approach.js`: Vanilla JS managing process rail interaction, active nodes, fill width, and stage card crossfades.
