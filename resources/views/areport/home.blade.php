@extends('layouts.app')

@push('styles')
    <style>
        .home-dashboard { padding: .9rem 1rem 1.6rem; }

        .home-status-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
            padding: .45rem .9rem; border-bottom: 1px solid #f1f3f6; font-size: .76rem; }
        .home-status-label { display: inline-flex; align-items: center; gap: .5rem; color: var(--muted); }
        .home-dashboard .bootstrap-table .fixed-table-toolbar { padding: 0 1.1rem; }
        .home-dashboard .bootstrap-table .fixed-table-toolbar .search input {
            height: 34px; border-radius: 7px; border: 1px solid var(--blue-bd); background: #fff; box-shadow: none; font-size: .82rem; min-width: 240px; }
        .home-dashboard .bootstrap-table .fixed-table-toolbar .search input:focus {
            border-color: var(--blue); box-shadow: 0 0 0 .18rem rgba(47,85,125,.12); }

        .home-period { display: inline-flex; padding: .16rem .55rem; background: #f3f5f8; color: #52606d; border-radius: 6px;
            font-weight: 500; font-size: .76rem; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .home-period-muted { background: #f1f5f9; color: var(--faint); }
        .home-module-title { font-size: .88rem; font-weight: 600; color: var(--ink); }
        .home-module-caption { color: var(--faint); font-size: .72rem; margin-top: .1rem; }
        .home-path { max-width: 540px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--faint);
            font-family: "SFMono-Regular","Consolas",monospace; font-size: .7rem; margin-top: .25rem; display: block; }
        .home-open-form { display: inline-flex; margin: 0; }
        .home-open-btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;
            background: var(--blue-soft); border: 1px solid var(--blue-bd); color: var(--blue); border-radius: 8px; transition: all .15s ease; }
        .home-open-btn:hover, .home-open-btn:focus { color: #fff; background: var(--blue); border-color: var(--blue);
            box-shadow: 0 1px 4px rgba(47,85,125,.3); }
        .home-open-btn i { font-size: .8rem; }
        .home-actions { display: inline-flex; gap: .4rem; align-items: center; justify-content: flex-end; }
        .home-val-btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;
            background: #e8f4ee; border: 1px solid #cce6d8; color: #2f8a5a; border-radius: 8px; transition: all .15s ease; }
        .home-val-btn:hover:not(:disabled) { background: #2f8a5a; border-color: #2f8a5a; color: #fff; box-shadow: 0 1px 4px rgba(47,138,90,.3); }
        .home-val-btn:disabled { opacity: .4; cursor: not-allowed; background: #f1f3f6; border-color: var(--line); color: var(--faint); }
        .home-val-btn i { font-size: .8rem; }
        .home-hist-btn { color: var(--blue); background: #f3f6fa; border-color: #dbe4ef; }
        .home-hist-btn:hover { background: var(--blue) !important; border-color: var(--blue) !important; color: #fff !important; box-shadow: none !important; }
        .home-tax { display: inline-block; font-size: .64rem; font-weight: 600; padding: .05rem .4rem; margin-right: .3rem; border-radius: 4px;
            background: #eef3f9; color: var(--blue); border: 1px solid #dbe4ef; vertical-align: 1px; }
        .home-tax-other { background: #fff7ed; color: #b45309; border-color: #fed7aa; }
        .home-lei { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .68rem; }
        .home-muted { color: var(--faint); font-style: italic; }
        .mod-val-pills { display: flex; gap: .4rem; flex-wrap: wrap; }
        .mod-val-pill { font-size: .72rem; font-weight: 700; padding: .12rem .5rem; border-radius: 3px; }
        .mod-val-pill.ok { background: #dcfce7; color: #166534; } .mod-val-pill.err { background: #fee2e2; color: #b91c1c; }
        .mod-val-pill.ind { background: #fef3c7; color: #b45309; } .mod-val-pill.mut { background: #f1f5f9; color: #64748b; }
        .mod-val-list { max-height: 48vh; overflow: auto; border: 1px solid var(--line); border-radius: 8px; }
        .mod-val-fail { padding: .45rem .6rem; border-bottom: 1px solid #f1f3f6; font-size: .8rem; }
        .mod-val-fail code { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0 .3rem; border-radius: 3px; font-size: .72rem; }
        .mod-val-facts { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .25rem; }
        .mod-val-facts span { font-size: .72rem; background: #f8fafc; border: 1px solid var(--line); padding: .05rem .4rem; border-radius: 3px; }
        .mod-val-err { color: #b91c1c; font-size: .85rem; } .mod-val-okmsg { color: #16a34a; } .mod-val-note { color: #b45309; font-size: .82rem; }

        /* Status filter: one segmented bar, a colour per status (no top rules). */
        .home-tiles { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; overflow: hidden; }
        .home-tile { --c: #2f557d; --tint: #eef3f9; text-align: left; border: 0; border-left: 1px solid #e2e8f0; background: #fff; padding: .55rem .85rem;
            cursor: pointer; transition: background .15s ease; display: grid; grid-template-columns: 1fr auto; align-items: center; column-gap: .6rem; }
        .home-tile:first-child { border-left: 0; }
        .home-tile:hover { background: #f8fafc; }
        .home-tile.active { background: var(--tint); }
        .home-tile .k { font-size: .66rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #64748b; display: flex; align-items: center; gap: .4rem; }
        .home-tile .k::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--c); flex: 0 0 auto; }
        .home-tile.active .k { color: var(--c); }
        .home-tile .v { grid-row: 1 / span 2; grid-column: 2; font-size: 1.45rem; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; color: var(--c); }
        .home-tile .d { font-size: .68rem; color: #94a3b8; margin-top: .1rem; }
        .home-tile.t-all { --c: #2f557d; --tint: #eef3f9; }
        .home-tile.t-draft { --c: #64748b; --tint: #f1f5f9; }
        .home-tile.t-errors { --c: #c62828; --tint: #fdeeee; }
        .home-tile.t-ready { --c: #2e7d4f; --tint: #e9f6ee; }
        .home-tile.t-exported { --c: #6d4fc2; --tint: #f1edfb; }
        .home-st { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 600; padding: .14rem .5rem; border-radius: 999px; white-space: nowrap; }
        .home-st.draft { background: #f1f5f9; color: #52606d; }
        .home-badges { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .25rem; }
        .home-badge { font-size: .66rem; font-weight: 600; padding: .08rem .45rem; border-radius: 4px; border: 1px solid #d5dbe3; color: #52606d; background: #fff; white-space: nowrap; }
        .home-badge.tone-warn { border-color: #f0c36d; color: #92400e; background: #fffbeb; }
        .home-badge.tone-ok { border-color: #86c7a0; color: #166534; background: #f0faf4; }
        .home-badge.tone-danger { border-color: #f1a7a7; color: #b91c1c; background: #fef5f5; }
        .home-st.errors { background: #fdecec; color: #b91c1c; }
        .home-st.warnings { background: #fff7ed; color: #b45309; }
        .home-st.validated { background: #e8f6ee; color: #166534; }
        .home-st.exported { background: #efecf8; color: #5b4a9e; }
        .home-changed { font-size: .76rem; color: #52606d; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .home-changed small { display: block; color: var(--faint); font-size: .68rem; }
        .home-scope { font-size: .62rem; font-weight: 700; color: #52606d; background: #f1f5f9; border-radius: 4px; padding: .02rem .35rem; margin-left: .3rem; }
        @media (max-width: 991.98px) { .home-tiles { grid-template-columns: repeat(2, 1fr); } .home-tile { border-top: 1px solid #e2e8f0; } }

        .home-dashboard .fixed-table-pagination { padding: .7rem 1.1rem; font-size: .78rem; }
        .home-dashboard .pagination .page-link { border-radius: 7px !important; border: 1px solid var(--line); margin: 0 .12rem; color: #52606d; }
        .home-dashboard .page-item.active .page-link { background: var(--blue); border-color: var(--blue); color: #fff; }

        @media (max-width: 991.98px) {
            .ax-summary { grid-template-columns: repeat(2, 1fr); }
            .ax-head { flex-direction: column; align-items: flex-start; }
        }

        /* v2 — same language as the Architect's Structure view: flat, accent rule, one toolbar */
        .home-tiles, .home-tile, .home-list, .home-search input, .home-st, .home-period, .home-tax, .ax-badge, .home-scope,
        .home-act, .home-open, .home-dashboard .pagination .page-link { border-radius: 2px !important; }
        .home-tile.active { box-shadow: inset 0 -2px 0 var(--c); }
        .home-list { --c: #2f557d; --tint: #eef3f9; background: #fff; border: 1px solid #e2e8f0; border-left: 3px solid var(--c); }
        .home-list[data-status="draft"] { --c: #64748b; --tint: #f1f5f9; }
        .home-list[data-status="errors"] { --c: #c62828; --tint: #fdeeee; }
        .home-list[data-status="ready"] { --c: #2e7d4f; --tint: #e9f6ee; }
        .home-list[data-status="exported"] { --c: #6d4fc2; --tint: #f1edfb; }
        .home-list-h { display: flex; align-items: center; gap: .6rem; padding: .5rem .8rem; background: var(--tint); border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
        .home-list-i { color: var(--c); font-size: .8rem; }
        .home-list-t { font-size: .88rem; font-weight: 700; color: #0f172a; margin: 0; }
        .home-list-n { font-size: .68rem; font-weight: 700; color: var(--c); background: #fff; border: 1px solid var(--c); padding: 0 .4rem; line-height: 1.5; font-variant-numeric: tabular-nums; }
        .home-search { position: relative; flex: 1 1 260px; max-width: 420px; margin-left: .6rem; }
        .home-search i { position: absolute; left: .6rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .72rem; }
        .home-search input { width: 100%; height: 30px; border: 1px solid #cbd5e1; background: #fff; padding: 0 .6rem 0 1.8rem; font-size: .78rem; outline: none; }
        .home-search input:focus { border-color: var(--c); box-shadow: 0 0 0 2px rgba(47,85,125,.12); }
        .home-list-h .home-status-label { margin-left: auto; font-size: .72rem; color: #64748b; }
        .home-table thead th { font-size: .6rem !important; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8 !important;
            background: #fff !important; border-bottom: 1px solid #eef2f7 !important; padding: .45rem .8rem !important; }
        .home-table tbody td { padding: .42rem .8rem !important; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .home-table tbody tr:hover td { background: #f8fafc; }
        .home-period { background: transparent; padding: 0; color: #334155; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .76rem; font-weight: 500; }
        .home-module-title { font-size: .84rem; }
        .home-st { border: 1px solid transparent; }
        .home-st.errors { border-color: #f6cdcd; } .home-st.validated { border-color: #cce6d8; } .home-st.exported { border-color: #ddd6f3; }
        .home-act { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid transparent; background: transparent;
            color: #94a3b8; font-size: .78rem; text-decoration: none; padding: 0; }
        .home-act:hover { color: #2f557d; background: #eef3f9; border-color: #dbe4ef; }
        .home-act.is-del:hover { color: #b91c1c; background: #fdecec; border-color: #f5c2c2; }
        .home-act.is-val:hover { color: #2e7d4f; background: #e9f6ee; border-color: #cce6d8; }
        .home-act:disabled { opacity: .35; cursor: not-allowed; }
        .home-open { height: 28px; padding: 0 .65rem; border: 1px solid #2f557d; background: #fff; color: #2f557d; font-size: .74rem; font-weight: 600; display: inline-flex; align-items: center; gap: .35rem; }
        .home-open:hover { background: #2f557d; color: #fff; }
        .home-open i { font-size: .66rem; }
        .home-dashboard .fixed-table-pagination { border-top: 1px solid #eef2f7; }
        .home-dashboard .bootstrap-table .fixed-table-container { border: 0; }
        .home-dashboard .bootstrap-table .fixed-table-toolbar { display: none; }
        .home-table tbody td .home-module-caption { margin-top: 0; }
        .home-due { line-height: 1.2; font-variant-numeric: tabular-nums; }
        .home-due small { display: block; font-size: .68rem; font-weight: 600; }
        .home-due .due-late { color: #b91c1c; } .home-due .due-soon { color: #b45309; }
        .home-due .due-later { color: #64748b; font-weight: 500; } .home-due .due-ok { color: #2e7d4f; }
        .gs { position: relative; background: #fff; border: 1px solid #dbe4ef; border-left: 4px solid #e4902f; padding: .8rem 1rem .85rem; }
        .gs h2 { font-size: .95rem; font-weight: 700; margin: 0 0 .15rem; color: #16202c; }
        .gs .sub { font-size: .78rem; color: #64748b; margin: 0 0 .6rem; }
        .gs ol { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .5rem; }
        .gs li { display: flex; gap: .55rem; align-items: flex-start; font-size: .8rem; color: #334155; background: #f8fafc; border: 1px solid #eef2f7; padding: .5rem .6rem; }
        .gs li .n { flex: 0 0 22px; height: 22px; background: #2f557d; color: #fff; font-weight: 700; font-size: .72rem; display: flex; align-items: center; justify-content: center; }
        .gs li b { display: block; color: #16202c; font-size: .8rem; }
        .gs li a { font-weight: 600; }
        .gs .x { position: absolute; top: .45rem; right: .55rem; border: 0; background: none; color: #94a3b8; font-size: 1rem; }
        .gs .x:hover { color: #334155; }
        .gs .demo { font-size: .74rem; color: #2f557d; margin-top: .55rem; }
    </style>
@endpush

@section('content')
    <div class="home-dashboard ax-stack">

        <div class="ax-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-landmark"></i> Regulatory reporting</span>
                <h1 class="ax-title">Reporting instances</h1>
                <p class="ax-sub" id="homeLoadMeta">Where each report stands: not validated, with errors, ready, or exported.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ url('/modules') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> New instance</a>
                <button type="button" id="refreshHomeTable" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-rotate-right"></i> Refresh
                </button>
            </div>
        </div>

        @include('flash.flash-message')

        {{-- First steps (dismissed per browser) --}}
        <section class="gs" id="gettingStarted" hidden>
            <button type="button" class="x" id="gsClose" title="Hide"><i class="fas fa-xmark"></i></button>
            <h2><i class="fas fa-route"></i> Getting started</h2>
            <p class="sub">From an EBA taxonomy to a valid filing in a few clicks.</p>
            <ol>
                <li><span class="n">1</span><span><b>Open a report</b>Click Open on a report below, or <a href="{{ url('/modules') }}">create one</a> from a module.</span></li>
                <li><span class="n">2</span><span><b>Fill the tables</b>Rules run as you type: red cells fail a rule, amber cells warn.</span></li>
                <li><span class="n">3</span><span><b>Validate</b>The report lists every rule with the facts behind it.</span></li>
                <li><span class="n">4</span><span><b>Export</b>xBRL-CSV or xBRL-XML, checked with Arelle before you file.</span></li>
                @if(\Illuminate\Support\Facades\Route::has('areport-pro.dpm-model.index'))
                    <li><span class="n">5</span><span><b>Model your own</b><a href="{{ route('areport-pro.dpm-model.index') }}">DPM Architect</a>: tables, dictionary, rules, taxonomy packages.</span></li>
                @endif
            </ol>
            @if(\App\Support\Demo::restricted())
                <div class="demo"><i class="fas fa-circle-info"></i> This is a shared demo: the sample reports and the DPM Architect model are read-only for everyone, your own reports are yours to edit.</div>
            @endif
        </section>

        <section class="home-tiles" aria-label="Instances by status">
            <button type="button" class="home-tile t-all active" data-status="all"><div class="k">All instances</div><div class="v" id="tileAll">0</div><div class="d">everything you report</div></button>
            <button type="button" class="home-tile t-draft" data-status="draft"><div class="k">To validate</div><div class="v" id="tileDraft">0</div><div class="d">not validated or changed since</div></button>
            <button type="button" class="home-tile t-errors" data-status="errors"><div class="k">With errors</div><div class="v" id="tileErrors">0</div><div class="d">blocking rules failed</div></button>
            <button type="button" class="home-tile t-ready" data-status="ready"><div class="k">Ready to file</div><div class="v" id="tileReady">0</div><div class="d">validated, no errors</div></button>
            <button type="button" class="home-tile t-exported" data-status="exported"><div class="k">Exported</div><div class="v" id="tileExported">0</div><div class="d">package produced, unchanged since</div></button>
        </section>

        <section class="home-list" id="moduleRegistry" data-status="all">
            <header class="home-list-h">
                <i class="fas fa-list-ul home-list-i"></i>
                <h2 class="home-list-t" id="homeListTitle">All instances</h2>
                <span class="home-list-n" id="homeListCount">0</span>
                <div class="home-search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <label for="homeSearch" class="visually-hidden">Search instances</label>
                    <input type="search" id="homeSearch" placeholder="Search by name, entity, LEI or framework…" autocomplete="off">
                </div>
                <span class="home-status-label"><i class="fas fa-circle-notch"></i> <span id="homeTableStatus">The table is ready.</span></span>
            </header>
            <div id="homeTableToolbar" class="d-none"></div>

            <table id="table"
                   data-toggle="table"
                   data-toolbar="#homeTableToolbar"
                   data-ajax="ajaxRequest"
                   data-side-pagination="server"
                   data-page-size="12"
                   data-page-list="[12, 24, 48, 96, all]"
                   data-pagination="true"
                   data-sort-name="changed_at"
                   data-sort-order="desc"
                   data-classes="table home-table align-middle mb-0">
                <thead>
                <tr>
                    <th data-field="period" data-width="120" data-sortable="true" data-formatter="periodFormatter">Reference date</th>
                    <th data-field="module_name" data-sortable="true" data-formatter="moduleNameFormatter">Instance &amp; entity</th>
                    <th data-field="status" data-width="190" data-sortable="true" data-formatter="statusFormatter">Status</th>
                    <th data-field="module_path" data-width="230" data-formatter="modulePathFormatter">Framework</th>
                    <th data-field="due" data-width="130" data-sortable="true" data-formatter="dueFormatter">Due</th>
                    <th data-field="changed_at" data-width="160" data-sortable="true" data-formatter="changedFormatter">Last change</th>
                    <th data-field="open" data-width="150" data-align="right" data-formatter="actionOpen"></th>
                </tr>
                </thead>
            </table>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const box = document.getElementById('gettingStarted');
            let hidden = false;
            try { hidden = localStorage.getItem('areport.gettingStarted') === 'hidden'; } catch (e) {}
            if (!hidden) box.hidden = false;
            document.getElementById('gsClose').addEventListener('click', function () {
                box.hidden = true;
                try { localStorage.setItem('areport.gettingStarted', 'hidden'); } catch (e) {}
            });
        })();
    </script>
    <script>
        const $table = $('#table');
        const statusElement = document.getElementById('homeTableStatus');
        const statusIcon = document.querySelector('.home-status-label i');
        const loadMetaElement = document.getElementById('homeLoadMeta');
        let statusFilter = 'all', searchText = '';

        function escapeHtml(value) { return $('<div>').text(value ?? '').html(); }

        // Pull a clean "FRAMEWORK · version" label out of an EBA module path.
        function frameworkLabel(path) {
            const m = /\/fws\/([^\/]+)\/([^\/]+)\//i.exec(String(path || ''));
            return m ? m[1].toUpperCase() + ' · ' + m[2] : '';
        }

        function updateStatus(message, tone) {
            const toneIcon = {
                loading: 'fas fa-circle-notch fa-spin',
                success: 'fas fa-circle-check',
                empty: 'fas fa-box-open',
                error: 'fas fa-triangle-exclamation'
            };
            statusElement.textContent = message;
            statusIcon.className = toneIcon[tone] || toneIcon.success;
        }

        function updateDashboard(data) {
            const st = data.stats || {};
            document.getElementById('homeListCount').textContent = data.total ?? 0;
            document.getElementById('tileAll').textContent = st.all || 0;
            document.getElementById('tileDraft').textContent = st.draft || 0;
            document.getElementById('tileErrors').textContent = st.errors || 0;
            document.getElementById('tileReady').textContent = (st.validated || 0) + (st.warnings || 0);
            document.getElementById('tileExported').textContent = st.exported || 0;
        }

        function periodFormatter(value, row) {
            if (!value) return "<span class='home-period home-period-muted'>No date</span>";
            return "<span class='home-period'>" + escapeHtml(row.period_label || value) + "</span>";
        }

        function moduleNameFormatter(value, row) {
            const moduleName = value || 'Unnamed module';
            const entity = row && row.entity_name
                ? escapeHtml(row.entity_name) + (row.entity_lei ? " · <span class='home-lei'>" + escapeHtml(row.entity_lei) + "</span>" : '')
                : "<span class='home-muted'>No entity</span>";
            return "<div><div class='home-module-title'>" + escapeHtml(moduleName) + "<span class='home-scope'>" + escapeHtml(row.consolidation || 'CON') + "</span></div>" +
                "<div class='home-module-caption'>" + entity + "</div></div>";
        }

        function statusFormatter(value, row) {
            const icons = { draft: 'fa-circle-half-stroke', errors: 'fa-circle-xmark', warnings: 'fa-triangle-exclamation', validated: 'fa-circle-check', exported: 'fa-paper-plane' };
            // Badges from plugins (e.g. a sign-off: in review, approved) under the status.
            const badges = (row.badges || []).map(function (b) {
                return "<span class='home-badge tone-" + escapeHtml(b.tone || 'info') + "' data-key='" + escapeHtml(b.key) + "'>" + escapeHtml(b.label) + "</span>";
            }).join('');
            return "<span class='home-st " + escapeHtml(value) + "'><i class='fas " + (icons[value] || 'fa-circle') + "'></i>" + escapeHtml(row.status_label || value) + "</span>"
                + (badges ? "<div class='home-badges'>" + badges + "</div>" : '');
        }

        function modulePathFormatter(value, row) {
            const fw = frameworkLabel(value);
            const badge = fw ? "<span class='ax-badge'>" + escapeHtml(fw) + "</span>" : "";
            // Instances of a non-active taxonomy still open (in their own taxonomy); flag them.
            const tax = row && row.taxonomy_name
                ? "<span class='home-tax" + (row.taxonomy_active ? '' : ' home-tax-other') + "' title='" + (row.taxonomy_active ? 'Active taxonomy' : 'Opens in its own taxonomy') + "'>" + escapeHtml(row.taxonomy_name) + "</span>"
                : '';
            return "<div title='" + escapeHtml(value || '') + "'>" + tax + badge + "</div>";
        }

        // Filing deadline: overdue / due soon / later, or done once exported.
        function dueFormatter(value, row) {
            if (!value) return "<span class='home-muted'>—</span>";
            const d = row.due_days;
            let tag;
            if (row.status === 'exported') tag = "<small class='due-ok'>exported</small>";
            else if (d < 0) tag = "<small class='due-late'>" + (-d) + (d === -1 ? ' day' : ' days') + " overdue</small>";
            else if (d === 0) tag = "<small class='due-soon'>due today</small>";
            else if (d <= 14) tag = "<small class='due-soon'>in " + d + (d === 1 ? ' day' : ' days') + "</small>";
            else tag = "<small class='due-later'>in " + d + " days</small>";
            return "<div class='home-due'>" + escapeHtml(row.due_label) + tag + "</div>";
        }

        function changedFormatter(value, row) {
            if (!value) return "<span class='home-muted'>—</span>";
            return "<div class='home-changed'>" + escapeHtml(row.changed_label) + (row.changed_by ? "<small>by " + escapeHtml(row.changed_by) + "</small>" : '') + "</div>";
        }

        const validationAvailable = @json(class_exists(\Begicf\AReportDpm\Services\InstanceValidator::class) && \App\Support\License::covers('validation'));

        function actionOpen(value, row) {
            const valBtn = validationAvailable
                ? "<a class='home-act is-val' href='{{ url('/table/validation-report') }}?id=" + encodeURIComponent(row.id || '') + "' target='_blank' rel='noopener' title='Validate' aria-label='Validate'><i class='fas fa-circle-check'></i></a>"
                : "<button type='button' class='home-act' title='Validation plugin not installed' aria-label='Validate (unavailable)' disabled><i class='fas fa-circle-check'></i></button>";
            const histBtn = "<a class='home-act' href='{{ url('/table/history') }}?id=" + encodeURIComponent(row.id || '') + "' title='Change history' aria-label='Change history'><i class='fas fa-clock-rotate-left'></i></a>";
            const delBtn = row.deletable
                ? "<button type='button' class='home-act is-del' data-delete='" + escapeHtml(row.id || '') + "' data-name='" + escapeHtml((row.module_name || '') + ' · ' + (row.period_label || row.period || '')) + "' title='Delete this report' aria-label='Delete'><i class='fas fa-trash'></i></button>"
                : '';
            return "<div class='home-actions'>" + delBtn + histBtn + valBtn +
                "<a class='home-open' href='{{ url('/table/open') }}?id=" + encodeURIComponent(row.id || '') + "' title='Open the instance' aria-label='Open'>" +
                "Open <i class='fas fa-arrow-right' aria-hidden='true'></i></a></div>";
        }

        function ajaxRequest(params) {
            updateStatus('Loading instances…', 'loading');
            $.ajax({
                method: 'POST', url: '/areport/json', dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    sort: params.data.sort, order: params.data.order,
                    offset: params.data.offset, limit: params.data.limit,
                    status: statusFilter, search: searchText,
                },
                success: function (data) {
                    updateDashboard(data);
                    updateStatus((data.total ?? 0) > 0 ? 'Data refreshed successfully.' : 'No instances for the current filter.', (data.total ?? 0) > 0 ? 'success' : 'empty');
                    params.success(data);
                },
                error: function (error) {
                    loadMetaElement.textContent = 'The server did not return data. Try refreshing once more.';
                    updateStatus('Unable to load data.', 'error');
                    if (typeof params.error === 'function') params.error(error);
                }
            });
        }

        document.querySelectorAll('.home-tile').forEach(function (tile) {
            tile.addEventListener('click', function () {
                statusFilter = tile.dataset.status;
                document.querySelectorAll('.home-tile').forEach(t => t.classList.toggle('active', t === tile));
                document.getElementById('homeListTitle').textContent = tile.querySelector('.k').textContent;
                document.getElementById('moduleRegistry').dataset.status = statusFilter;
                $table.bootstrapTable('refresh', { pageNumber: 1 });
            });
        });

        $('#refreshHomeTable').on('click', function () { $table.bootstrapTable('refresh', { silent: true }); });
        $table.on('click', '[data-delete]', function () {
            const btn = this;
            if (!confirm('Delete ' + btn.dataset.name + '?\n\nIts values, change history and comments are deleted too. This cannot be undone.')) return;
            btn.disabled = true;
            fetch(@json(url('/instance')) + '/' + encodeURIComponent(btn.dataset.delete), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { if (!r.ok || d.ok === false) throw new Error(d.error || d.message || 'The report could not be deleted.'); }); })
                .then(function () { $table.bootstrapTable('refresh', { silent: true }); }, function (e) { alert(e.message); btn.disabled = false; });
        });
        let searchTimer = null;
        document.getElementById('homeSearch').addEventListener('input', function (e) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { searchText = e.target.value.trim(); $table.bootstrapTable('refresh', { pageNumber: 1 }); }, 250);
        });
        $table.on('search.bs.table sort.bs.table page-change.bs.table', function () { updateStatus('Updating the view…', 'loading'); });
    </script>
@endpush
