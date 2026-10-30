<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'AReport') }} — Taxonomy-driven regulatory reporting</title>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root{
            --blue:#2f557d; --blue-d:#243f5c; --blue-soft:#eef3f9; --blue-bd:#dbe4ef;
            --amber:#c2731f; --amber-soft:#e4902f;
            --ink:#1f2933; --muted:#6b7684; --faint:#9aa5b1; --line:#e6e9ee; --band:#f6f8fb;
        }
        *{box-sizing:border-box}
        body{margin:0;background:#fff;color:var(--ink);font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;line-height:1.5}
        a{text-decoration:none;color:var(--blue)}
        .wrap{max-width:1160px;margin:0 auto;padding:0 1.4rem}
        .btn{display:inline-flex;align-items:center;gap:.5rem;border-radius:9px;padding:.6rem 1.05rem;font-weight:600;font-size:.9rem;border:1px solid transparent;cursor:pointer;transition:.15s}
        .btn-primary{background:var(--blue);color:#fff;border-color:var(--blue-d)}
        .btn-primary:hover{background:var(--blue-d)}
        .btn-ghost{background:#fff;color:var(--ink);border-color:var(--line)}
        .btn-ghost:hover{border-color:#b9c2cd}
        .btn-lg{padding:.75rem 1.3rem;font-size:.98rem}
        .eyebrow{font-size:.68rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase;color:var(--amber)}

        /* top nav */
        .topnav{position:sticky;top:0;z-index:20;background:#fff;border-bottom:1px solid var(--line);box-shadow:0 1px 3px rgba(16,24,40,.05)}
        .topnav .lrow{display:flex;align-items:center;gap:1.2rem;height:62px;flex-wrap:nowrap}
        .brand{display:flex;align-items:center;gap:.6rem}
        .brand img{height:30px}
        .brand b{font-size:1.02rem;color:var(--ink)}
        .brand small{display:block;font-size:.56rem;letter-spacing:.14em;text-transform:uppercase;color:var(--faint);margin-top:1px}
        .topnav nav{display:flex;gap:.3rem;margin-left:.6rem}
        .topnav nav a{color:#52606d;font-weight:500;font-size:.88rem;padding:.4rem .7rem;border-radius:7px}
        .topnav nav a:hover{color:var(--ink);background:var(--band)}
        .topnav .right{margin-left:auto;display:flex;align-items:center;gap:.6rem}

        /* hero */
        .hero{position:relative;overflow:hidden;background:
            radial-gradient(1100px 420px at 80% -10%, rgba(47,85,125,.10), transparent 60%),
            radial-gradient(760px 360px at 0% 110%, rgba(228,144,47,.09), transparent 60%);}
        .hero .grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr);gap:2.6rem;align-items:center;padding:4rem 0 3.4rem}
        .hero h1{font-size:2.7rem;line-height:1.08;letter-spacing:-.02em;margin:.7rem 0 0;font-weight:700;color:#16202c}
        .hero h1 .hl{color:var(--blue)}
        .hero p.lead{font-size:1.08rem;color:var(--muted);margin:1rem 0 1.6rem;max-width:34rem}
        .hero .cta{display:flex;gap:.7rem;flex-wrap:wrap}
        .hero .meta{display:flex;gap:1.4rem;margin-top:1.8rem;flex-wrap:wrap}
        .hero .meta .m{font-size:.82rem;color:var(--muted);display:flex;align-items:center;gap:.5rem}
        .hero .meta .m i{color:var(--blue)}

        /* browser-framed screenshot */
        .shot{border:1px solid var(--line);border-radius:13px;overflow:hidden;box-shadow:0 26px 60px rgba(16,24,40,.16);background:#fff}
        .shot.tilt{transform:perspective(1400px) rotateY(-7deg) rotateX(3deg)}
        .shot-bar{height:32px;background:#f1f4f8;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:7px;padding:0 13px}
        .shot-dot{width:10px;height:10px;border-radius:50%}
        .shot img{width:100%;display:block}

        /* trust strip */
        .trust{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:var(--band)}
        .trust .lrow{display:flex;flex-wrap:wrap;gap:1.4rem 2.6rem;justify-content:center;padding:1rem 0}
        .trust .t{font-size:.84rem;color:#52606d;display:flex;align-items:center;gap:.55rem;font-weight:500}
        .trust .t i{color:var(--amber)}

        /* sections */
        section.pad{padding:4rem 0}
        .sec-head{text-align:center;max-width:42rem;margin:0 auto 2.4rem}
        .sec-head h2{font-size:1.9rem;letter-spacing:-.02em;margin:.5rem 0 0;font-weight:700;color:#16202c}
        .sec-head p{color:var(--muted);margin:.7rem 0 0;font-size:1.02rem}

        .benefits{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
        .bcard{border:1px solid var(--line);border-radius:12px;padding:1.3rem;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .bcard .ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--blue-soft);color:var(--blue);border:1px solid var(--blue-bd);font-size:1.05rem;margin-bottom:.9rem}
        .bcard h3{font-size:1rem;margin:0 0 .35rem;font-weight:600}
        .bcard p{font-size:.86rem;color:var(--muted);margin:0}

        /* plugin spotlight */
        .plugins{background:var(--band);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
        .feat{display:grid;grid-template-columns:repeat(2,1fr);gap:3rem;align-items:center;padding:3.2rem 0}
        .feat.rev .ftext{order:2}
        .pill{display:inline-flex;align-items:center;gap:.5rem;padding:.3rem .7rem;border-radius:999px;background:#fff;border:1px solid var(--blue-bd);color:var(--blue);font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
        .pill.amber{border-color:#f0d9bd;color:var(--amber);background:#fff}
        .feat h3{font-size:1.55rem;letter-spacing:-.01em;margin:.9rem 0 0;font-weight:700;color:#16202c}
        .feat p{color:var(--muted);margin:.8rem 0 0;font-size:.98rem}
        .feat ul{list-style:none;padding:0;margin:1.1rem 0 0;display:grid;gap:.55rem}
        .feat ul li{display:flex;gap:.6rem;align-items:flex-start;font-size:.92rem;color:#3a4654}
        .feat ul li i{color:var(--blue);margin-top:.2rem}

        /* workflow */
        .flow{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;counter-reset:step}
        .step{border:1px solid var(--line);border-radius:12px;padding:1.3rem;background:#fff;position:relative}
        .step .n{width:30px;height:30px;border-radius:999px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem}
        .step h4{margin:.8rem 0 .3rem;font-size:1rem;font-weight:600}
        .step p{margin:0;font-size:.85rem;color:var(--muted)}

        /* CTA */
        .cta-band{background:linear-gradient(135deg,#132a44,#2f557d 70%,#356089);color:#fff;border-radius:18px;padding:2.6rem;text-align:center;overflow:hidden;position:relative}
        .cta-band h2{font-size:1.8rem;margin:0 0 .5rem;font-weight:700;letter-spacing:-.01em}
        .cta-band p{color:rgba(255,255,255,.82);margin:0 0 1.4rem}
        .cta-band .btn-ghost{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.3)}
        .cta-band .btn-ghost:hover{background:rgba(255,255,255,.2)}
        .cta-band .btn-primary{background:var(--amber-soft);border-color:var(--amber)}
        .cta-band .btn-primary:hover{background:var(--amber)}

        footer{border-top:1px solid var(--line);padding:2rem 0;color:var(--muted);font-size:.85rem}
        footer .lrow{display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between}

        @media (max-width:900px){
            .hero .grid,.feat,.benefits,.flow{grid-template-columns:1fr}
            .hero h1{font-size:2.1rem}
            .feat.rev .ftext{order:0}
            .shot.tilt{transform:none}
            .topnav nav{display:none}
        }
    </style>
</head>
<body>

{{-- ===== Top navigation ===== --}}
<header class="topnav">
    <div class="wrap lrow">
        <a class="brand" href="{{ url('/') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="AReport">
            <span><b>{{ config('app.name', 'AReport') }}</b><small>Regulatory reporting</small></span>
        </a>
        <nav>
            <a href="#features">Features</a>
            <a href="#plugins">Plugins</a>
            <a href="#workflow">Workflow</a>
            <a target="_blank" href="https://github.com/begicf/areport">GitHub</a>
        </nav>
        <div class="right">
            @auth
                <a class="btn btn-primary" href="{{ url('/home') }}">Open workspace <i class="fas fa-arrow-right"></i></a>
            @else
                @if (Route::has('login'))<a class="btn btn-ghost" href="{{ route('login') }}">Sign in</a>@endif
                @if (Route::has('register'))<a class="btn btn-primary" href="{{ route('register') }}">Try the app <i class="fas fa-arrow-right"></i></a>@endif
            @endauth
        </div>
    </div>
</header>

{{-- ===== Hero ===== --}}
<div class="hero">
    <div class="wrap grid">
        <div>
            <span class="eyebrow"><i class="fas fa-landmark"></i> EBA DPM · XBRL reporting</span>
            <h1>Taxonomy-driven reporting, <span class="hl">modelled and validated</span> in one workspace.</h1>
            <p class="lead">
                AReport turns EBA taxonomies into a working reporting environment — browse modules,
                fill submission data, and export valid xBRL. Two built-in plugins let you
                <strong>author the data model</strong> and <strong>validate every figure</strong> before you file.
            </p>
            <div class="cta">
                @auth
                    <a class="btn btn-primary btn-lg" href="{{ url('/home') }}">Open workspace <i class="fas fa-arrow-right"></i></a>
                @else
                    @if (Route::has('register'))<a class="btn btn-primary btn-lg" href="{{ route('register') }}">Try the app <i class="fas fa-arrow-right"></i></a>@endif
                    @if (Route::has('login'))<a class="btn btn-ghost btn-lg" href="{{ route('login') }}">Sign in</a>@endif
                @endauth
            </div>
            <div class="meta">
                <span class="m"><i class="fas fa-circle-check"></i> EBA DPM 1.0 &amp; 2.0</span>
                <span class="m"><i class="fas fa-circle-check"></i> xBRL-XML · OIM CSV · JSON</span>
                <span class="m"><i class="fas fa-circle-check"></i> DPM-XL validation</span>
            </div>
        </div>
        <div class="shot tilt">
            <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
            <img src="{{ asset('images/landing/workspace.png') }}" alt="AReport reporting workspace">
        </div>
    </div>
</div>

{{-- ===== Trust strip ===== --}}
<div class="trust">
    <div class="wrap lrow">
        <span class="t"><i class="fas fa-layer-group"></i> Framework → Table group → Table</span>
        <span class="t"><i class="fas fa-cubes"></i> Dimensions, domains &amp; members</span>
        <span class="t"><i class="fas fa-file-import"></i> Import &amp; export instances</span>
        <span class="t"><i class="fas fa-shield-halved"></i> Rule-based data quality</span>
    </div>
</div>

{{-- ===== Benefits ===== --}}
<section class="pad wrap" id="features">
    <div class="sec-head">
        <span class="eyebrow">Why AReport</span>
        <h2>Everything a reporting team needs, in one focused place</h2>
        <p>No scattered spreadsheets. Model the taxonomy, enter the data, check it against the rules, and produce a compliant filing — end to end.</p>
    </div>
    <div class="benefits">
        <div class="bcard">
            <div class="ic"><i class="fas fa-sitemap"></i></div>
            <h3>Taxonomy-native</h3>
            <p>Loads EBA frameworks exactly as published — COREP, FINREP, DORA, MREL and more — with the full dimensional model.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-table-cells"></i></div>
            <h3>Real reporting tables</h3>
            <p>Render authored and official tables with RC notation, nested headers and open axes, ready for direct data entry.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-right-left"></i></div>
            <h3>Open formats</h3>
            <p>Import and export xBRL-XML, OIM xBRL-CSV and JSON. Round-trips are lossless, so nothing is lost in translation.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-circle-check"></i></div>
            <h3>Confidence to file</h3>
            <p>Business rules run on your data and highlight exactly which cells to fix — before the submission ever leaves your desk.</p>
        </div>
    </div>
</section>

{{-- ===== Two plugins ===== --}}
<div class="plugins" id="plugins">
    <div class="wrap">
        <div class="sec-head" style="padding-top:3.4rem">
            <span class="eyebrow">Two built-in plugins</span>
            <h2>Model the data. Validate the data.</h2>
            <p>AReport ships with two optional power tools that extend the core workspace.</p>
        </div>

        {{-- DPM Architect --}}
        <div class="feat">
            <div class="ftext">
                <span class="pill amber"><i class="fas fa-compass-drafting"></i> Plugin · DPM Architect</span>
                <h3>Author your own data point model</h3>
                <p>A full DPM modelling environment: build frameworks, table groups and tables, define dimensions, domains and members, and nest the structure visually — with DPM 1.0, 2.0 and newer support.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Visual structure editor with X / Y / Z axes and parent–child nesting</li>
                    <li><i class="fas fa-check"></i> Dictionary of dimensions, domains, members &amp; metrics with search</li>
                    <li><i class="fas fa-check"></i> Write &amp; edit validation rules in DPM-XL, the official EBA grammar</li>
                    <li><i class="fas fa-check"></i> Groundwork for generating taxonomy &amp; module artefacts</li>
                </ul>
            </div>
            <div class="shot">
                <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
                <img src="{{ asset('images/landing/architect.png') }}" alt="DPM Architect">
            </div>
        </div>

        {{-- Validation --}}
        <div class="feat rev">
            <div class="ftext">
                <span class="pill"><i class="fas fa-shield-halved"></i> Plugin · Validation</span>
                <h3>Catch every broken figure before you file</h3>
                <p>A dedicated DPM-XL engine runs the framework’s business rules against your instance and shows precisely what to fix — right on the reporting form.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Powered by the official EBA DPM-XL ANTLR grammar</li>
                    <li><i class="fas fa-check"></i> Validate a live form or an imported EBA sample instance</li>
                    <li><i class="fas fa-check"></i> Failing cells are highlighted in red, with the rule and its facts</li>
                    <li><i class="fas fa-check"></i> Supports both legacy and OIM cell notations</li>
                </ul>
            </div>
            <div class="shot">
                <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
                <img src="{{ asset('images/landing/validation.png') }}" alt="Instance validation">
            </div>
        </div>
    </div>
</div>

{{-- ===== Workflow ===== --}}
<section class="pad wrap" id="workflow">
    <div class="sec-head">
        <span class="eyebrow">How it works</span>
        <h2>From taxonomy to compliant filing in four steps</h2>
    </div>
    <div class="flow">
        <div class="step"><div class="n">1</div><h4>Model</h4><p>Load an EBA taxonomy or author your own structure in DPM Architect.</p></div>
        <div class="step"><div class="n">2</div><h4>Report</h4><p>Open a module and enter submission data in real reporting tables.</p></div>
        <div class="step"><div class="n">3</div><h4>Validate</h4><p>Run DPM-XL rules and fix the cells highlighted on the form.</p></div>
        <div class="step"><div class="n">4</div><h4>Export</h4><p>Produce a valid xBRL-XML, OIM CSV or JSON instance to file.</p></div>
    </div>
</section>

{{-- ===== Final CTA ===== --}}
<section class="wrap" style="padding-bottom:3.5rem">
    <div class="cta-band">
        <h2>Ready to model, report and validate?</h2>
        <p>Spin up a workspace and open your first reporting module in minutes.</p>
        <div class="cta" style="justify-content:center;display:flex;gap:.7rem;flex-wrap:wrap">
            @auth
                <a class="btn btn-primary btn-lg" href="{{ url('/home') }}">Open workspace <i class="fas fa-arrow-right"></i></a>
            @else
                @if (Route::has('register'))<a class="btn btn-primary btn-lg" href="{{ route('register') }}">Try the app <i class="fas fa-arrow-right"></i></a>@endif
                @if (Route::has('login'))<a class="btn btn-ghost btn-lg" href="{{ route('login') }}">Sign in</a>@endif
            @endauth
        </div>
    </div>
</section>

<footer>
    <div class="wrap lrow">
        <div class="brand"><img src="{{ asset('images/logo.svg') }}" alt="AReport" style="height:24px"><span><b style="font-size:.95rem">{{ config('app.name', 'AReport') }}</b></span></div>
        <div>© {{ date('Y') }} {{ config('app.name', 'AReport') }} · Financial reporting workspace</div>
        <a target="_blank" href="https://github.com/begicf/areport"><i class="fab fa-github"></i> GitHub</a>
    </div>
</footer>

</body>
</html>
