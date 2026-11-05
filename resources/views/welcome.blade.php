<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $seoTitle = 'AReport — DPM & XBRL regulatory reporting software (EBA, EIOPA, COREP, FINREP, xBRL-CSV)';
        $seoDesc = 'AReport turns DPM/XBRL taxonomies (EBA, EIOPA, ECB or national) into reporting forms: enter or import COREP, FINREP, DORA or MREL data, validate it with DPM-XL rules and Arelle, sign it off as a team and export xBRL-CSV and xBRL-XML filings.';
        $seoImage = asset('images/landing/workspace.png');
        $faq = [
            ['What is AReport?', 'AReport is a web application for supervisory reporting with DPM/XBRL taxonomies. It renders the taxonomy tables as data entry forms, stores the reported facts, checks them and exports filing-ready xBRL-CSV and xBRL-XML instances.'],
            ['Which taxonomies does AReport support?', 'Any taxonomy built on the DPM/XBRL standard, DPM 1.0 or DPM 2.0: EBA, EIOPA, ECB or a national regulator\'s, including open tables, typed dimensions and national extensions. Tested with EBA 4.2 and 4.4 (COREP, FINREP, DORA, MREL, IRRBB, AE, MICA).'],
            ['Which file formats can I export?', 'xBRL-CSV report packages and xBRL-XML instances following the filing rules (e.g. the EBA filing rules), plus Excel, PDF and HTML copies of the tables. Exported instances can be checked with Arelle before filing.'],
            ['Can I bring in reports I already have?', 'Yes. An xBRL-CSV report package or an xBRL-XML instance opens as a new report: the module, reference date, entity and filing indicators come from the file and every fact goes into its cell, open tables and typed sheets included. Excel templates are read by their EBA row and column codes, and a block copied from Excel can be pasted straight into a table.'],
            ['How is the data validated?', 'Business rules written in DPM-XL run on the reported data and mark the cells to look at directly on the form: red for errors, amber for warnings. For a total that does not add up the form shows what its parts add up to and the difference, and sets it in one click. A validation report lists every rule with the facts behind it.'],
            ['Can I build my own taxonomy?', 'Yes, with the private DPM Architect plugin: design frameworks, modules, tables, the data dictionary and validation rules, release versions, and generate an XBRL taxonomy package that is checked with Arelle.'],
            ['Can a team review and approve reports?', 'Yes, with the private Teams plugin: members share reports and entities, with preparer, reviewer and approver roles, a four-eyes sign-off (draft, in review, approved, exported), comments on cells and an e-mail at every step.'],
            ['Is AReport open source?', 'The core application is on GitHub. The DPM Architect, Validation and Teams plugins are private and provided separately — use the contact form to ask about them.'],
        ];
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="keywords" content="EBA reporting, EIOPA reporting, DPM, XBRL, xBRL-CSV, xBRL-XML, COREP, FINREP, DORA, MREL, regulatory reporting software, supervisory reporting, DPM-XL, Arelle, taxonomy, data point model">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ url('/') }}">
    <meta name="theme-color" content="#2f557d">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AReport">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="AReport reporting workspace">
    <meta property="og:locale" content="en_US">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SoftwareApplication',
                'name' => 'AReport',
                'url' => url('/'),
                'applicationCategory' => 'BusinessApplication',
                'applicationSubCategory' => 'Regulatory reporting',
                'operatingSystem' => 'Web',
                'description' => $seoDesc,
                'image' => $seoImage,
                'screenshot' => [asset('images/landing/workspace.png'), asset('images/landing/form.png'), asset('images/landing/architect.png'), asset('images/landing/validation.png')],
                'featureList' => ['DPM 1.0 and DPM 2.0 taxonomies (EBA, EIOPA, ECB, national)', 'COREP, FINREP, DORA, MREL reporting forms', 'Import of xBRL-CSV packages and xBRL-XML instances', 'xBRL-CSV and xBRL-XML export', 'DPM-XL business rule validation', 'Arelle validation of exported instances', 'DPM Architect: taxonomy authoring (private plugin)', 'Teams: four-eyes sign-off (private plugin)'],
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR', 'description' => 'Demo workspace'],
                'sameAs' => ['https://github.com/begicf/areport'],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]], $faq),
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

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
        .btn{display:inline-flex;align-items:center;gap:.5rem;white-space:nowrap;border-radius:9px;padding:.6rem 1.05rem;font-weight:600;font-size:.9rem;border:1px solid transparent;cursor:pointer;transition:.15s}
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
        .hero .grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr);gap:2.6rem;align-items:center;padding-top:4rem;padding-bottom:3.4rem}
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
        .trust .lrow{display:flex;flex-wrap:wrap;gap:1.4rem 2.6rem;justify-content:center;padding-top:1rem;padding-bottom:1rem}
        .trust .t{font-size:.84rem;color:#52606d;display:flex;align-items:center;gap:.55rem;font-weight:500}
        .trust .t i{color:var(--amber)}

        /* sections */
        section.pad{padding-top:4rem;padding-bottom:4rem}
        .sec-head{text-align:center;max-width:42rem;margin:0 auto 2.4rem}
        .sec-head h2{font-size:1.9rem;letter-spacing:-.02em;margin:.5rem 0 0;font-weight:700;color:#16202c}
        .sec-head p{color:var(--muted);margin:.7rem 0 0;font-size:1.02rem}

        .benefits{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
        .bcard{border:1px solid var(--line);border-radius:12px;padding:1.3rem;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .bcard .ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--blue-soft);color:var(--blue);border:1px solid var(--blue-bd);font-size:1.05rem;margin-bottom:.9rem}
        .bcard h3{font-size:1rem;margin:0 0 .35rem;font-weight:600}
        .bcard p{font-size:.86rem;color:var(--muted);margin:0}

        /* plugin spotlight */
        .plugins{background:var(--band);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
        .feat{display:grid;grid-template-columns:repeat(2,1fr);gap:3rem;align-items:center;padding-top:3.2rem;padding-bottom:3.2rem}
        .feat.rev .ftext{order:2}
        .pill.private{background:#f8fafc;border-color:#cbd5e1;color:#475569}
        .private-note{display:inline-block;max-width:640px;margin-top:.9rem;line-height:1.5;padding:.5rem .9rem;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#475569;font-size:.86rem}
        .private-note i{color:#64748b;margin-right:.4rem}
        .pill{display:inline-flex;align-items:center;gap:.5rem;padding:.3rem .7rem;border-radius:999px;background:#fff;border:1px solid var(--blue-bd);color:var(--blue);font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
        .pill.amber{border-color:#f0d9bd;color:var(--amber);background:#fff}
        .feat h3{font-size:1.55rem;letter-spacing:-.01em;margin:.9rem 0 0;font-weight:700;color:#16202c}
        .feat p{color:var(--muted);margin:.8rem 0 0;font-size:.98rem}
        .feat ul{list-style:none;padding:0;margin:1.1rem 0 0;display:grid;gap:.55rem}
        .feat ul li{display:flex;gap:.6rem;align-items:flex-start;font-size:.92rem;color:#3a4654}
        .feat ul li i{color:var(--blue);margin-top:.2rem}

        /* sign-off mock (Teams) */
        .so{padding:1.2rem 1.3rem 1.3rem;font-size:.86rem;color:#3a4654}
        .so-head{display:flex;justify-content:space-between;gap:.8rem;align-items:baseline;flex-wrap:wrap}
        .so-head b{font-size:.98rem;color:#16202c}
        .so-head span{font-size:.76rem;color:var(--faint)}
        .so-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem;margin:1rem 0 1.1rem}
        .so-step{text-align:center;font-size:.72rem;font-weight:600;color:var(--faint);padding-top:.55rem;border-top:3px solid var(--line)}
        .so-step.done{color:#15803d;border-color:#22c55e}
        .so-step.now{color:var(--blue);border-color:var(--blue)}
        .so-log{display:grid;gap:.55rem}
        .so-log div{display:grid;grid-template-columns:28px 1fr;gap:.6rem;align-items:start}
        .so-log .av{width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#fff}
        .so-log p{margin:0;font-size:.82rem;line-height:1.4}
        .so-log p small{display:block;color:var(--faint);font-size:.72rem}
        .so-log q{color:#16202c}
        .so-foot{margin-top:1.1rem;display:flex;gap:.5rem;justify-content:flex-end}
        .so-btn{font-size:.76rem;font-weight:600;padding:.35rem .75rem;border-radius:7px;border:1px solid var(--line);background:#fff;color:#3a4654}
        .so-btn.go{background:var(--blue);border-color:var(--blue-d);color:#fff}

        /* workflow */
        .flow{display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;counter-reset:step}
        .step{border:1px solid var(--line);border-radius:12px;padding:1.3rem;background:#fff;position:relative}
        .step .n{width:30px;height:30px;border-radius:999px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem}
        .step h4{margin:.8rem 0 .3rem;font-size:1rem;font-weight:600}
        .step p{margin:0;font-size:.85rem;color:var(--muted)}

        /* CTA */
        .faq{max-width:860px}
        .faq details{border:1px solid var(--line);border-radius:10px;padding:.85rem 1.1rem;margin-top:.6rem;background:#fff}
        .faq summary{cursor:pointer;font-weight:600;color:var(--ink);list-style:none;display:flex;justify-content:space-between;gap:1rem}
        .faq summary::-webkit-details-marker{display:none}
        .faq summary::after{content:"+";color:var(--blue);font-weight:700}
        .faq details[open] summary::after{content:"–"}
        .faq details p{margin:.6rem 0 0;color:var(--muted);font-size:.93rem}
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
            .benefits{grid-template-columns:1fr}
            .hero h1{font-size:2.1rem}
            .feat.rev .ftext{order:0}
            .shot.tilt{transform:none}
            .topnav nav{display:none}
        }
        /* contact */
        .contact{display:grid;grid-template-columns:1fr 1.3fr;gap:3rem;align-items:start}
        .contact .lead2{color:var(--muted);font-size:.98rem}
        .contact .pts{list-style:none;padding:0;margin:1.2rem 0 0;display:grid;gap:.6rem;color:#3e4a57;font-size:.92rem}
        .contact .pts i{color:var(--blue);width:18px}
        .cform{background:#fff;border:1px solid var(--line);border-radius:14px;padding:1.4rem;box-shadow:0 10px 30px rgba(16,24,40,.06)}
        .cform .row2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
        .cform label{display:block;font-size:.78rem;font-weight:600;color:#3e4a57;margin:.75rem 0 .3rem}
        .cform input,.cform select,.cform textarea{width:100%;border:1px solid #cfd6df;border-radius:8px;padding:.6rem .7rem;font:inherit;font-size:.9rem;background:#fff;color:var(--ink)}
        .cform textarea{min-height:130px;resize:vertical}
        .cform input:focus,.cform select:focus,.cform textarea:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px rgba(47,85,125,.12)}
        .cform .hp{position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden}
        .cform .foot{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1rem;flex-wrap:wrap}
        .cform .note{font-size:.74rem;color:var(--faint)}
        .cform .err{color:#b42318;font-size:.76rem;margin-top:.25rem}
        .cmsg{border-radius:10px;padding:.7rem .9rem;font-size:.88rem;margin-bottom:.8rem}
        .cmsg.ok{background:#ecfdf3;border:1px solid #abefc6;color:#067647}
        .cmsg.bad{background:#fef3f2;border:1px solid #fecdca;color:#b42318}
        @media (max-width:900px){.contact{grid-template-columns:1fr}.cform .row2{grid-template-columns:1fr}}

        /* phones: 16px gutters, a one-line header, smaller type and tighter sections */
        @media (max-width:640px){
            .wrap{padding-left:1rem;padding-right:1rem}
            .topnav .lrow{height:56px;gap:.6rem}
            .brand img{height:26px}
            .brand small{display:none}
            .topnav .right{gap:.4rem}
            .topnav .btn{padding:.45rem .75rem;font-size:.82rem}
            .hero .grid{padding-top:2.2rem;padding-bottom:2.2rem;gap:1.8rem}
            .hero h1{font-size:1.85rem}
            .hero p.lead{font-size:1rem;margin:.9rem 0 1.3rem}
            .hero .meta{gap:.6rem 1.1rem;margin-top:1.3rem}
            .trust .lrow{gap:.7rem 1.4rem;justify-content:flex-start}
            section.pad{padding-top:2.6rem;padding-bottom:2.6rem}
            .sec-head{margin-bottom:1.6rem}
            .sec-head h2{font-size:1.5rem}
            .sec-head p{font-size:.96rem}
            .feat{padding-top:2.2rem;padding-bottom:2.2rem;gap:1.6rem}
            .feat h3{font-size:1.3rem}
            .shot-bar{height:24px;gap:5px;padding:0 9px}
            .shot-dot{width:8px;height:8px}
            .private-note{font-size:.84rem}
            .contact{gap:1.6rem}
            .cform{padding:1.1rem}
            .cform input,.cform select,.cform textarea{font-size:16px}
            .cta-band{padding:1.9rem 1.2rem;border-radius:14px}
            .cta-band h2{font-size:1.45rem}
            .cta-band .btn-lg{padding:.65rem 1rem;font-size:.92rem}
        }
        @media (max-width:360px){
            .topnav .btn-ghost{display:none}
            .hero h1{font-size:1.65rem}
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
            <a href="#faq">FAQ</a>
                <a href="#contact">Contact</a>
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
            <span class="eyebrow"><i class="fas fa-landmark"></i> DPM · XBRL supervisory reporting</span>
            <h1>Taxonomy-driven reporting, <span class="hl">modelled and validated</span> in one workspace.</h1>
            <p class="lead">
                AReport turns DPM/XBRL taxonomies (EBA, EIOPA, ECB or your regulator's) into a working reporting environment — browse modules,
                fill or import submission data, and export valid xBRL. Three private plugins let you
                <strong>author the data model</strong>, <strong>validate every figure</strong> and <strong>sign off as a team</strong> before you file.
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
                <span class="m"><i class="fas fa-circle-check"></i> DPM 1.0 &amp; 2.0</span>
                <span class="m"><i class="fas fa-circle-check"></i> xBRL-CSV · xBRL-XML · Excel</span>
                <span class="m"><i class="fas fa-circle-check"></i> DPM-XL · Arelle</span>
            </div>
        </div>
        <div class="shot tilt">
            <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
            <img src="{{ asset('images/landing/workspace.png') }}?v={{ @filemtime(public_path('images/landing/workspace.png')) }}" alt="AReport reporting workspace">
        </div>
    </div>
</div>

{{-- ===== Trust strip ===== --}}
<div class="trust">
    <div class="wrap lrow">
        <span class="t"><i class="fas fa-file-import"></i> Import existing xBRL-CSV &amp; xBRL-XML reports</span>
        <span class="t"><i class="fas fa-table-cells"></i> Excel by EBA row &amp; column codes</span>
        <span class="t"><i class="fas fa-shield-halved"></i> DPM-XL rules &amp; Arelle</span>
        <span class="t"><i class="fas fa-user-check"></i> Four-eyes sign-off</span>
        <span class="t"><i class="fas fa-file-zipper"></i> Generated XBRL taxonomy packages</span>
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
            <p>Loads any DPM-based taxonomy exactly as published (EBA, EIOPA or a national regulator's: COREP, FINREP, DORA, MREL and more) with the full dimensional model, open tables and typed sheets.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-keyboard"></i></div>
            <h3>Forms made for reporters</h3>
            <p>Real reporting tables with RC codes. Numbers in your style (1.234,56 or 1,234.56), a block pasted from Excel, Enter down the column, and last period copied or compared with the change in %.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-file-import"></i></div>
            <h3>Bring the reports you have</h3>
            <p>Open an existing xBRL-CSV package or xBRL-XML instance as a new report — module, date, entity and filing indicators come from the file. Excel templates are read by their EBA codes.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-right-left"></i></div>
            <h3>Filing-ready exports</h3>
            <p>xBRL-CSV report packages and xBRL-XML instances that follow the EBA filing rules, checked with Arelle, plus Excel, PDF and HTML copies of any table.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-list-check"></i></div>
            <h3>Know where each report stands</h3>
            <p>Every report shows its status — not validated, with errors, ready or exported — and its EBA remittance date. A pre-flight check covers entity (LEI from GLEIF), filing indicators and settings.</p>
        </div>
        <div class="bcard">
            <div class="ic"><i class="fas fa-clock-rotate-left"></i></div>
            <h3>A full audit trail</h3>
            <p>Every change to a cell is kept with who made it and when, so a figure can always be traced back — on screen or in the report's history.</p>
        </div>
    </div>
</section>

{{-- ===== Two plugins ===== --}}
<div class="plugins" id="plugins">
    <div class="wrap">
        <div class="sec-head" style="padding-top:3.4rem">
            <span class="eyebrow">Three private plugins</span>
            <h2>Model the data. Validate it. Sign it off.</h2>
            <p>Three optional extensions of the core workspace, each usable on its own.</p>
            <p class="private-note"><i class="fas fa-lock"></i> DPM Architect, Validation and Teams are <b>not publicly available</b> — they are not part of the open-source AReport and are provided separately, on request. <a href="#contact">Contact us →</a></p>
        </div>

        {{-- DPM Architect --}}
        <div class="feat">
            <div class="ftext">
                <span class="pill amber"><i class="fas fa-compass-drafting"></i> Plugin · DPM Architect</span> <span class="pill private"><i class="fas fa-lock"></i> Not public</span>
                <h3>Author your own data point model</h3>
                <p>A full DPM modelling environment: build frameworks, modules and tables, start from any EBA DPM 2.0 table, and generate the XBRL taxonomy your reporters file against — with DPM 1.0, 2.0 and newer support.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Structure editor for X / Y / Z axes: metric, dimensions and open (key) axes per coordinate</li>
                    <li><i class="fas fa-check"></i> Data dictionary of metrics, dimensions, domains, members &amp; hierarchies</li>
                    <li><i class="fas fa-check"></i> Validation rules in DPM-XL, alongside EBA's 12,000+ operations — also edited in Excel and imported back</li>
                    <li><i class="fas fa-check"></i> Releases: compare a version with the one before; a released version's files stay as filed</li>
                    <li><i class="fas fa-check"></i> Labels in every language you report in, with AI-suggested translations</li>
                    <li><i class="fas fa-check"></i> Generates tables, modules and a taxonomy package, checked with Arelle</li>
                </ul>
            </div>
            <div class="shot">
                <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
                <img src="{{ asset('images/landing/architect.png') }}?v={{ @filemtime(public_path('images/landing/architect.png')) }}" alt="DPM Architect">
            </div>
        </div>

        {{-- Validation --}}
        <div class="feat rev">
            <div class="ftext">
                <span class="pill"><i class="fas fa-shield-halved"></i> Plugin · Validation</span> <span class="pill private"><i class="fas fa-lock"></i> Not public</span>
                <h3>Catch every broken figure before you file</h3>
                <p>A dedicated DPM-XL engine runs the framework’s business rules against your instance and shows precisely what to fix — right on the reporting form.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Powered by the official EBA DPM-XL ANTLR grammar</li>
                    <li><i class="fas fa-check"></i> Rules straight from the EBA DPM 2.0 database for each module version, with EBA's rule deactivation packages applied</li>
                    <li><i class="fas fa-check"></i> A validation report per instance: errors, warnings, missing data, with the facts behind each rule — as a page, CSV or PDF</li>
                    <li><i class="fas fa-check"></i> A total that does not add up says what its parts add up to and the difference</li>
                    <li><i class="fas fa-check"></i> The exported xBRL-XML or xBRL-CSV file is checked with Arelle before you file</li>
                </ul>
            </div>
            <div class="shot">
                <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
                <img src="{{ asset('images/landing/validation.png') }}?v={{ @filemtime(public_path('images/landing/validation.png')) }}" alt="Validation report of an instance">
            </div>
        </div>

        {{-- Live validation on the form --}}
        <div class="feat">
            <div class="ftext">
                <span class="pill"><i class="fas fa-bolt"></i> Live on the form · Validation plugin</span> <span class="pill private"><i class="fas fa-lock"></i> Not public</span>
                <h3>See what breaks a rule while you enter the data</h3>
                <p>As soon as a table opens — and again after every change — the rules run in the background and the form marks the cells to look at. No separate step, no guessing which figure is wrong.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Only the total that does not add up turns red; the cells it adds up are outlined</li>
                    <li><i class="fas fa-check"></i> “R0100 is 56.938, should be 620.401” — set it with one click, or recalculate every total of the table</li>
                    <li><i class="fas fa-check"></i> Amber cells raise a warning; hover a cell to see its rules</li>
                    <li><i class="fas fa-check"></i> The table list shows which tables still have errors, and a fixed value clears its mark on its own</li>
                </ul>
            </div>
            <div class="shot">
                <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
                <img src="{{ asset('images/landing/form.png') }}?v={{ @filemtime(public_path('images/landing/form.png')) }}" alt="Reporting form with cells marked by live validation">
            </div>
        </div>

        {{-- Teams --}}
        <div class="feat rev">
            <div class="ftext">
                <span class="pill"><i class="fas fa-user-check"></i> Plugin · Teams</span> <span class="pill private"><i class="fas fa-lock"></i> Not public</span>
                <h3>Prepare, review and approve — as a team</h3>
                <p>Reports and entities are shared within the team, and nothing goes to the supervisor without a second pair of eyes.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Roles: preparer, reviewer, approver and admin</li>
                    <li><i class="fas fa-check"></i> Four-eyes sign-off: Draft → In review → Approved → Exported; only an approved report passes the filing export</li>
                    <li><i class="fas fa-check"></i> A team can require a clean validation before submitting; the approver is never who submitted or changed the data</li>
                    <li><i class="fas fa-check"></i> Comments on cells, and an e-mail at every step to whom it concerns</li>
                </ul>
            </div>
            <div class="shot" aria-label="Sign-off of a report">
                <div class="shot-bar"><span class="shot-dot" style="background:#ef6a5f"></span><span class="shot-dot" style="background:#f5bf4f"></span><span class="shot-dot" style="background:#61c454"></span></div>
                <div class="so">
                    <div class="so-head"><b>Own Funds — COREP · 31.12.2026</b><span>Demo Bank d.d. · CON</span></div>
                    <div class="so-steps">
                        <div class="so-step done"><i class="fas fa-check"></i> Draft</div>
                        <div class="so-step now">In review</div>
                        <div class="so-step">Approved</div>
                        <div class="so-step">Exported</div>
                    </div>
                    <div class="so-log">
                        <div><span class="av" style="background:#2f557d">PR</span><p>Submitted for review <small>Preparer · validated, 0 errors</small></p></div>
                        <div><span class="av" style="background:#c2731f">RV</span><p>Returned: <q>C 01.00 r0010 differs from the general ledger.</q> <small>Reviewer · comment on the cell</small></p></div>
                        <div><span class="av" style="background:#2f557d">PR</span><p>Corrected and submitted again <small>Preparer</small></p></div>
                        <div><span class="av" style="background:#15803d">RV</span><p>Comment: <q>Checked against the ledger, fine now.</q> <small>Reviewer</small></p></div>
                    </div>
                    <div class="so-foot"><span class="so-btn">Return</span><span class="so-btn go"><i class="fas fa-check"></i> Approve</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Workflow ===== --}}
<section class="pad wrap" id="workflow">
    <div class="sec-head">
        <span class="eyebrow">How it works</span>
        <h2>From taxonomy to compliant filing in five steps</h2>
    </div>
    <div class="flow">
        <div class="step"><div class="n">1</div><h4>Model</h4><p>Load a taxonomy (EBA, EIOPA, national) or author your own tables in DPM Architect and generate their taxonomy.</p></div>
        <div class="step"><div class="n">2</div><h4>Report</h4><p>Open a module and enter the data, paste it from Excel, or import a report you already have.</p></div>
        <div class="step"><div class="n">3</div><h4>Validate</h4><p>Run the DPM-XL rules and fix the cells marked on the form — totals are recalculated in one click.</p></div>
        <div class="step"><div class="n">4</div><h4>Sign off</h4><p>A reviewer checks it, an approver approves it — every step kept with its comment.</p></div>
        <div class="step"><div class="n">5</div><h4>Export</h4><p>Produce the xBRL-CSV package or xBRL-XML instance, checked with Arelle, and file it.</p></div>
    </div>
</section>

{{-- ===== Contact (address never on the page; honeypot + timing + rate limit against spam) ===== --}}
<section class="pad wrap" id="contact">
    <div class="contact">
        <div>
            <span class="eyebrow">Contact</span>
            <h2 style="margin:.4rem 0 .6rem;font-weight:700;letter-spacing:-.02em;color:#16202c">Contact us</h2>
            <p class="lead2">Interested in DPM Architect, Validation or Teams, or in using AReport for your regulatory reporting? Send us a message and we will reply by email.</p>
            <ul class="pts">
                <li><i class="fas fa-lock"></i> Access to the private plugins</li>
                <li><i class="fas fa-landmark"></i> Own taxonomies and modules for a supervisor</li>
                <li><i class="fas fa-envelope-open-text"></i> Your address is only used to answer you</li>
            </ul>
        </div>
        <form class="cform" id="contactForm" method="POST" action="{{ route('contact.store') }}" novalidate>
            @csrf
            <div id="contactStatus">
                @if (session('contact_status'))<div class="cmsg ok"><i class="fas fa-circle-check"></i> {{ session('contact_status') }}</div>@endif
                @if ($errors->any())<div class="cmsg bad">{{ $errors->first() }}</div>@endif
            </div>
            <input type="hidden" name="token" value="{{ \App\Http\Controllers\Contact\ContactController::formToken() }}">
            <div class="hp" aria-hidden="true"><label for="cWebsite">Website</label><input id="cWebsite" type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <div class="row2">
                <div><label for="cName">Name</label><input id="cName" name="name" required maxlength="120" value="{{ old('name') }}" autocomplete="name"></div>
                <div><label for="cEmail">Email</label><input id="cEmail" type="email" name="email" required maxlength="190" value="{{ old('email') }}" autocomplete="email"></div>
            </div>
            <div class="row2">
                <div><label for="cOrg">Organisation <span style="font-weight:400;color:var(--faint)">(optional)</span></label><input id="cOrg" name="organisation" maxlength="190" value="{{ old('organisation') }}" autocomplete="organization"></div>
                <div><label for="cTopic">Topic</label>
                    <select id="cTopic" name="topic">
                        @foreach (\App\Model\ContactMessage::TOPICS as $k => $v)<option value="{{ $k }}" @selected(old('topic', 'plugins') === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label for="cMessage">Message</label>
            <textarea id="cMessage" name="message" required minlength="10" maxlength="4000">{{ old('message') }}</textarea>
            <div class="foot">
                <span class="note">No newsletter, no tracking.</span>
                <button class="btn btn-primary" type="submit" id="contactSend">Send message <i class="fas fa-paper-plane"></i></button>
            </div>
        </form>
    </div>
</section>

<script>
    // Send in place (the form still works without JavaScript).
    document.getElementById('contactForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const f = e.target, btn = document.getElementById('contactSend'), box = document.getElementById('contactStatus');
        btn.disabled = true;
        fetch(f.action, { method: 'POST', body: new FormData(f), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (d) { return { r: r, d: d }; }); })
            .then(function (x) {
                if (x.r.ok) { box.innerHTML = '<div class="cmsg ok"><i class="fas fa-circle-check"></i> ' + x.d.message + '</div>'; f.querySelectorAll('input:not([type=hidden]):not([name=website]),textarea').forEach(function (i) { i.value = ''; }); return; }
                const msg = x.r.status === 429 ? 'Too many messages from your connection — please try again in a few minutes.'
                    : (x.d.errors ? Object.values(x.d.errors)[0][0] : (x.d.message || 'The message could not be sent.'));
                box.innerHTML = '<div class="cmsg bad">' + msg.replace(/</g, '&lt;') + '</div>';
            })
            .catch(function () { box.innerHTML = '<div class="cmsg bad">The message could not be sent — please try again.</div>'; })
            .finally(function () { btn.disabled = false; });
    });
</script>

{{-- ===== FAQ (also in the FAQPage structured data above) ===== --}}
<section class="pad wrap" id="faq">
    <div class="sec-head">
        <span class="eyebrow">FAQ</span>
        <h2>Questions about DPM / XBRL reporting with AReport</h2>
    </div>
    <div class="faq" style="margin:1.6rem auto 0">
        @foreach($faq as $i => $q)
            <details @if($i === 0) open @endif><summary>{{ $q[0] }}</summary><p>{{ $q[1] }}</p></details>
        @endforeach
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
