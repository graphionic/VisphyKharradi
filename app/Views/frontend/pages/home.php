<?= $this->extend('frontend/layouts/main') ?>

<?= $this->section('content') ?>

<!-- NAV -->
<header class="nav">
  <a class="brand" href="#" aria-label="FTPRENEUR by Visphy Kharradi — home">
    <img class="brand__logo" src="<?= base_url('assets/frontend/images/ftpreneur-logo.png') ?>" alt="FTPRENEUR by Visphy Kharradi" width="1324" height="1188" />
  </a>
  <nav class="nav__links" aria-label="Primary">
    <a href="#about">About</a>
    <a href="#approach">Approach</a>
    <a href="#programs">Programs</a>
    <a href="#faq">FAQ</a>
  </nav>
  <a class="nav__phone" href="tel:+919574293300" aria-label="Call +91 95742 93300">+91 95742 93300</a>
  <a class="nav__cta" href="#start">
    <span>Get started</span>
    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h9M8.5 4.5 12 8l-3.5 3.5" /></svg>
  </a>
  <button class="nav__menu" type="button" aria-label="Open menu" aria-expanded="false"><span></span><span></span></button>
</header>

<section class="hero" aria-labelledby="hero-title">

  <!-- 01 · architectural grid -->
  <div class="grid" aria-hidden="true"><span></span><span></span><span></span></div>

  <!-- orbit system (frames Visphy) -->
  <div class="layer" data-depth="-3" aria-hidden="true">
    <div class="orbit">
      <svg class="orbit__svg" viewBox="0 0 1000 1000">
        <circle class="orbit__outer" cx="500" cy="500" r="499" />
        <circle class="orbit__inner" cx="500" cy="500" r="420" />
      </svg>
      <span class="orbit__node"></span>
      <span class="orbit__mint"></span>
    </div>
  </div>

  <!-- ultramarine disc -->
  <div class="layer" data-depth="-1.5" aria-hidden="true">
    <div class="field-clip"><div class="field"><div class="field__light"></div></div></div>
  </div>

  <!-- marigold orbital accent -->
  <div class="layer" data-depth="4" aria-hidden="true">
    <span class="sun"></span>
  </div>

  <!-- PEOPLE. (solid) — sits BEHIND Visphy -->
  <div class="layer layer--you" data-depth="1.5">
    <div class="you you--solid" aria-hidden="true"><span class="you__w">PEOPLE</span><span class="you__dot">.</span></div>
  </div>

  <!-- Visphy -->
  <div class="layer layer--portrait" data-depth="4">
    <img class="portrait" src="<?= base_url('assets/frontend/images/visphy-cutout.webp') ?>" width="660" height="1400"
         alt="Visphy Kharradi, founder and coach of FTPRENEUR — black-and-white portrait" />
  </div>

  <!-- PEOPLE. (outline) — drawn OVER Visphy, masked to his silhouette -->
  <div class="layer layer--you-front" data-depth="1.5" aria-hidden="true">
    <div class="you-front">
      <div class="you you--line"><span class="you__w">PEOPLE</span><span class="you__dot">.</span></div>
    </div>
  </div>

  <!-- identity + disciplines, attached to the portrait -->
  <div class="layer layer--meta" data-depth="2">
    <div class="vname" aria-hidden="true">
      <span class="vname__role">FOUNDER / COACH</span>
      <span class="vname__name"><span class="vname__a">VISPHY</span><span class="vname__b">KHARRADI</span></span>
    </div>
  </div>

  <!-- message -->
  <div class="layer layer--msg" data-depth="1">
    <p class="eyebrow"><i aria-hidden="true"></i><span>Personalised health <b>+</b><br class="br-m" /> disease management</span></p>

    <h1 id="hero-title" class="headline">
      <span class="line"><span class="line__in">EMPOWERING</span></span>
      <span class="sr-only">people.</span>
    </h1>

    <div class="copy">
      <p class="lede">
        A personalised approach to nutrition, strength and lifestyle — designed around
        <em>your body</em>, your <em>health condition</em>, your goals and everyday life.
      </p>

      <ol class="pillars" aria-label="How the method works">
        <li><a href="#nutrition"><span class="pillars__n">01</span><span class="pillars__w">Nutrition</span></a></li>
        <li><a href="#strength"><span class="pillars__n">02</span><span class="pillars__w">Strength</span></a></li>
        <li><a href="#lifestyle"><span class="pillars__n">03</span><span class="pillars__w">Lifestyle</span></a></li>
      </ol>

      <div class="ctas">
        <a class="btn-primary" href="#programs">
          <span class="btn-primary__label">Explore programs</span>
          <span class="btn-primary__tile" aria-hidden="true">
            <svg viewBox="0 0 20 20"><path d="M4 10h11M10.5 5.5 15 10l-4.5 4.5" /></svg>
          </span>
        </a>
        <a class="btn-ghost" href="#approach">
          <span class="btn-ghost__label">How it works</span>
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3v9M4.5 8.5 8 12l3.5-3.5" /></svg>
        </a>
      </div>

      <p class="note">Programs are personalised. Individual outcomes vary.</p>

      <!-- Editorial animated loop element (lower-left horizontal) -->
      <div class="editorial-loop" aria-hidden="true">
        <span class="editorial-loop__word">MANAGE</span>
        <span class="editorial-loop__fixed">BETTER.</span>
      </div>
    </div>
  </div>

  <div class="scroll" aria-hidden="true"><span class="scroll__line"></span><span>SCROLL</span></div>

  <div class="grain" aria-hidden="true"></div>
</section>

<!-- SECTION 02 · TRUST + CREDIBILITY (THE RECORD ARENA) -->
<section class="credibility-section bg-navy" id="credibility" aria-labelledby="credibility-title">
  
  <!-- Atmosphere: Technical Grid & Ghost Numerals -->
  <div class="credibility__bg-grid" aria-hidden="true">
    <div class="credibility__bg-line credibility__bg-line--h1"></div>
    <div class="credibility__bg-line credibility__bg-line--h2"></div>
    <div class="credibility__bg-line credibility__bg-line--v1"></div>
    <div class="credibility__bg-line credibility__bg-line--v2"></div>
    <span class="credibility__bg-ghost">02</span>
  </div>

  <!-- Section Top Chapter Marker -->
  <div class="credibility__chapter-bar" data-reveal="chapter">
    <div class="credibility__chapter-id">
      <span class="credibility__chapter-num">02</span>
      <span class="credibility__chapter-slash">/</span>
      <span class="credibility__chapter-label">CREDIBILITY</span>
    </div>
    <div class="credibility__chapter-divider" aria-hidden="true"></div>
    <span class="credibility__chapter-tag">PERFORMANCE ARENA · PROOF OF DISCIPLINE</span>
  </div>

  <!-- Performance Arena Layout Container -->
  <div class="credibility__arena">
    
    <!-- Headline & Editorial Anchor -->
    <div class="credibility__headline-block" data-reveal="headline">
      <h2 id="credibility-title" class="credibility__headline">
        <span class="credibility__headline-primary">NOT THEORY.</span>
        <span class="credibility__headline-secondary">LIVED <span class="credibility__headline-accent">DISCIPLINE.</span></span>
      </h2>
      <p class="credibility__sub" data-reveal="sub">The experience behind Ftpreneur.</p>
    </div>

    <!-- Central Figure & Performance Arena Framing -->
    <div class="credibility__stage" data-reveal="stage">
      <!-- Arena Geometry (1px technical arcs & coordinate ticks) -->
      <div class="credibility__arena-geometry" aria-hidden="true">
        <svg class="credibility__arena-svg" viewBox="0 0 500 500">
          <circle class="credibility__ring credibility__ring--outer" cx="250" cy="250" r="238" />
          <circle class="credibility__ring credibility__ring--inner" cx="250" cy="250" r="195" />
          <line class="credibility__axis credibility__axis--h" x1="0" y1="250" x2="500" y2="250" />
          <line class="credibility__axis credibility__axis--v" x1="250" y1="0" x2="250" y2="500" />
        </svg>
      </div>

      <!-- Central Panoramic Action Frame (Wide Editorial Stage) -->
      <div class="credibility__figure">
        <div class="credibility__figure-mask">
          <img class="credibility__portrait" 
               src="<?= base_url('assets/frontend/images/vispy-hercules-pillars-record.jpg') ?>" 
               alt="Vispy Kharradi performing the Hercules Pillars strength challenge" 
               loading="lazy" 
               width="1779" 
               height="884" />
          <div class="credibility__figure-overlay" aria-hidden="true"></div>
        </div>

        <!-- Technical Corner Accents -->
        <span class="credibility__corner credibility__corner--tl" aria-hidden="true"></span>
        <span class="credibility__corner credibility__corner--tr" aria-hidden="true"></span>
        <span class="credibility__corner credibility__corner--bl" aria-hidden="true"></span>
        <span class="credibility__corner credibility__corner--br" aria-hidden="true"></span>
      </div>

      <!-- Signature Detail: Vertical Technical Performance Rail -->
      <div class="credibility__rail" aria-hidden="true">
        <span class="credibility__rail-line"></span>
        <ul class="credibility__rail-items">
          <li class="credibility__rail-item" data-metric="DISCIPLINE">DISCIPLINE</li>
          <li class="credibility__rail-item" data-metric="STRENGTH">STRENGTH</li>
          <li class="credibility__rail-item" data-metric="ENDURANCE">ENDURANCE</li>
          <li class="credibility__rail-item" data-metric="CONTROL">CONTROL</li>
        </ul>
      </div>
    </div>

    <!-- Distributed Interactive Record Nodes -->
    <div class="credibility__nodes">
      
      <!-- Node 01: Top Left -->
      <article class="credibility__node credibility__node--tl" data-node="01" tabindex="0" data-reveal="node-1">
        <div class="credibility__node-header">
          <span class="credibility__node-tag">RECORD // 01</span>
          <span class="credibility__node-dot" aria-hidden="true"></span>
        </div>
        <div class="credibility__node-val-wrap">
          <span class="credibility__node-val">17</span>
          <span class="credibility__node-unit">×</span>
        </div>
        <div class="credibility__node-content">
          <h3 class="credibility__node-title">GUINNESS WORLD RECORDS</h3>
          <p class="credibility__node-desc">TITLE ACHIEVEMENTS</p>
        </div>
        <div class="credibility__node-connector" aria-hidden="true">
          <span class="credibility__connector-line"></span>
          <span class="credibility__connector-dot"></span>
        </div>
      </article>

      <!-- Node 02: Bottom Left -->
      <article class="credibility__node credibility__node--bl" data-node="02" tabindex="0" data-reveal="node-2">
        <div class="credibility__node-header">
          <span class="credibility__node-tag">RECORD // 02</span>
          <span class="credibility__node-dot" aria-hidden="true"></span>
        </div>
        <div class="credibility__node-val-wrap">
          <span class="credibility__node-val">261</span>
          <span class="credibility__node-unit">KG</span>
        </div>
        <div class="credibility__node-content">
          <h3 class="credibility__node-title">HERCULES PILLARS</h3>
          <p class="credibility__node-desc">WORLD RECORD</p>
        </div>
        <div class="credibility__node-connector" aria-hidden="true">
          <span class="credibility__connector-line"></span>
          <span class="credibility__connector-dot"></span>
        </div>
      </article>

      <!-- Node 03: Top Right -->
      <article class="credibility__node credibility__node--tr" data-node="03" tabindex="0" data-reveal="node-3">
        <div class="credibility__node-header">
          <span class="credibility__node-tag">RECORD // 03</span>
          <span class="credibility__node-dot" aria-hidden="true"></span>
        </div>
        <div class="credibility__node-val-wrap">
          <span class="credibility__node-val">2:10.75</span>
        </div>
        <div class="credibility__node-content">
          <h3 class="credibility__node-title">HERCULES PILLARS</h3>
          <p class="credibility__node-desc">ENDURANCE RECORD</p>
        </div>
        <div class="credibility__node-connector" aria-hidden="true">
          <span class="credibility__connector-line"></span>
          <span class="credibility__connector-dot"></span>
        </div>
      </article>

      <!-- Node 04: Bottom Right -->
      <article class="credibility__node credibility__node--br" data-node="04" tabindex="0" data-reveal="node-4">
        <div class="credibility__node-header">
          <span class="credibility__node-tag">RECORD // 04</span>
          <span class="credibility__node-dot" aria-hidden="true"></span>
        </div>
        <div class="credibility__node-val-wrap">
          <span class="credibility__node-val credibility__node-val--text">STEEL MAN</span>
        </div>
        <div class="credibility__node-content">
          <h3 class="credibility__node-title">TITLE HONOR</h3>
          <p class="credibility__node-desc">OF INDIA</p>
        </div>
        <div class="credibility__node-connector" aria-hidden="true">
          <span class="credibility__connector-line"></span>
          <span class="credibility__connector-dot"></span>
        </div>
      </article>

    </div>

  </div>
</section>

<?= $this->endSection() ?>
