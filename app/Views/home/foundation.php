<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ftpreneur — Foundation Ready</title>
    <meta name="description" content="Ftpreneur — Visphy Kharradi. Foundation phase complete.">
    <meta name="robots" content="noindex, nofollow">
    <style>
        *,*::before,*::after{box-sizing:border-box}
        html,body{margin:0;padding:0}
        body{
            font-family:ui-sans,system-ui,-apple-system,Segoe UI,Roboto,Inter,Helvetica,Arial,sans-serif;
            background:#fdfbf7;
            color:#0f0f0f;
            -webkit-font-smoothing:antialiased;
            display:flex;
            min-height:100vh;
            flex-direction:column;
        }
        .wrap{
            max-width:720px;
            margin:auto;
            padding:2.5rem 1.5rem;
            text-align:center;
        }
        .badge{
            display:inline-block;
            font-size:.75rem;
            letter-spacing:.14em;
            text-transform:uppercase;
            color:#c9a86a;
            background:#0a1a3a;
            padding:.35rem .6rem;
            border-radius:999px;
        }
        h1{
            font-family:ui-serif,Georgia,serif;
            font-weight:700;
            font-size:clamp(1.9rem,5vw,2.6rem);
            line-height:1.15;
            margin:1rem 0 .5rem;
            color:#0a1a3a;
        }
        h1 span{color:#c9a86a}
        p{
            color:#3a3a3a;
            line-height:1.6;
            margin:.5rem 0;
        }
        .meta{
            margin-top:1.5rem;
            padding:1rem;
            background:#fff;
            border:1px solid #e9e6e1;
            border-radius:12px;
            font-size:.875rem;
            text-align:left;
        }
        .meta code{
            font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
            background:#f3f1ee;
            padding:.15rem .35rem;
            border-radius:4px;
            font-size:.85em;
        }
        .meta dl{margin:0;display:grid;grid-template-columns:140px 1fr;gap:.35rem .75rem}
        .meta dt{color:#6b6b6b}
        .meta dd{margin:0;font-weight:600;color:#0a1a3a}
        .foot{
            margin-top:2rem;
            font-size:.8rem;
            color:#8a8a8a;
        }
        a{color:#2a5ad6;text-decoration:none}
        a:hover{text-decoration:underline}
    </style>
</head>
<body>
<main class="wrap" role="main">
    <div class="badge">Phase 1 — Foundation</div>
    <h1>Ftpreneur <span>Foundation</span> Ready</h1>
    <p>Visphy Kharradi — Nutrition, Strength Training and Disease Management.</p>
    <p>CodeIgniter&nbsp;4 foundation is booted and routing is verified. This is a temporary Phase&nbsp;1 view — not the final landing page.</p>

    <div class="meta" aria-label="Foundation details">
        <dl>
            <dt>Environment</dt><dd><code><?= esc(ENVIRONMENT) ?></code></dd>
            <dt>CI Version</dt><dd><code><?= esc(\CodeIgniter\CodeIgniter::CI_VERSION) ?></code></dd>
            <dt>PHP</dt><dd><code><?= esc(PHP_VERSION) ?></code></dd>
            <dt>Timezone</dt><dd><code><?= esc(app_timezone()) ?></code></dd>
            <dt>Rendered</dt><dd><code><?= esc(date('Y-m-d H:i:s T')) ?></code></dd>
        </dl>
    </div>

    <p class="foot">
        Next: Phase&nbsp;2 — Database &amp; migrations. See <code>docs/DEVELOPMENT_PHASES.md</code>. &middot;
        <a href="https://ftpreneur.com" rel="noopener">ftpreneur.com</a>
    </p>
</main>
</body>
</html>
