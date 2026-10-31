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

        <section class="home-tiles" aria-label="Instances by status">
            <button type="button" class="home-tile t-all active" data-status="all"><div class="k">All instances</div><div class="v" id="tileAll">0</div><div class="d">everything you report</div></button>
            <button type="button" class="home-tile t-draft" data-status="draft"><div class="k">To validate</div><div class="v" id="tileDraft">0</div><div class="d">not validated or changed since</div></button>
            <button type="button" class="home-tile t-errors" data-status="errors"><div class="k">With errors</div><div class="v" id="tileErrors">0</div><div class="d">blocking rules failed</div></button>
            <button type="button" class="home-tile t-ready" data-status="ready"><div class="k">Ready to file</div><div class="v" id="tileReady">0</div><div class="d">validated, no errors</div></button>
            <button type="button" class="home-tile t-exported" data-status="exported"><div class="k">Exported</div><div class="v" id="tileExported">0</div><div class="d">package produced, unchanged since</div></button>
        </section>

        <section class="ax-card" id="moduleRegistry">
            <div class="ax-panel-head">
                <div>
                    <span class="ax-eyebrow"><i class="fas fa-list-ul"></i> Instances</span>
                    <h2 class="ax-panel-title" id="homeListTitle">All instances</h2>
                </div>
                <div id="homeTableToolbar"></div>
            </div>

            <div class="home-status-bar">
                <div class="home-status-label">
                    <i class="fas fa-circle-notch"></i>
                    <span id="homeTableStatus">The table is ready.</span>
                </div>
                <div class="home-status-label" style="color:var(--faint)">Validate an instance to update its status; export runs the pre-export checks.</div>
            </div>

            <table id="table"
                   data-toggle="table"
                   data-toolbar="#homeTableToolbar"
                   data-ajax="ajaxRequest"
                   data-search="true"
                   data-side-pagination="server"
                   data-page-size="12"
                   data-page-list="[12, 24, 48, 96, all]"
                   data-pagination="true"
                   data-sort-name="changed_at"
                   data-sort-order="desc"
                   data-classes="table ax-table align-middle mb-0">
                <thead>
                <tr>
                    <th data-field="period" data-width="120" data-sortable="true" data-formatter="periodFormatter">Reference date</th>
                    <th data-field="module_name" data-sortable="true" data-formatter="moduleNameFormatter">Instance &amp; entity</th>
                    <th data-field="status" data-width="190" data-sortable="true" data-formatter="statusFormatter">Status</th>
                    <th data-field="module_path" data-width="230" data-formatter="modulePathFormatter">Framework</th>
                    <th data-field="changed_at" data-width="160" data-sortable="true" data-formatter="changedFormatter">Last change</th>
                    <th data-field="open" data-width="130" data-align="right" data-formatter="actionOpen">Actions</th>
                </tr>
                </thead>
            </table>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const $table = $('#table');
        const statusElement = document.getElementById('homeTableStatus');
        const statusIcon = document.querySelector('.home-status-label i');
        const loadMetaElement = document.getElementById('homeLoadMeta');
        let statusFilter = 'all';

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
            return "<span class='home-st " + escapeHtml(value) + "'><i class='fas " + (icons[value] || 'fa-circle') + "'></i>" + escapeHtml(row.status_label || value) + "</span>";
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

        function changedFormatter(value, row) {
            if (!value) return "<span class='home-muted'>—</span>";
            return "<div class='home-changed'>" + escapeHtml(row.changed_label) + (row.changed_by ? "<small>by " + escapeHtml(row.changed_by) + "</small>" : '') + "</div>";
        }

        const validationAvailable = @json(Route::has('areport-pro.dpm-model.index'));

        function actionOpen(value, row) {
            const valBtn = validationAvailable
                ? "<a class='btn home-val-btn' href='{{ url('/table/validation-report') }}?id=" + encodeURIComponent(row.id || '') + "' target='_blank' rel='noopener' title='Validate' aria-label='Validate'><i class='fas fa-circle-check'></i></a>"
                : "<button type='button' class='btn home-val-btn' title='Validation plugin not installed' aria-label='Validate (unavailable)' disabled><i class='fas fa-circle-check'></i></button>";
            const histBtn = "<a class='btn home-val-btn home-hist-btn' href='{{ url('/table/history') }}?id=" + encodeURIComponent(row.id || '') + "' title='Change history' aria-label='Change history'><i class='fas fa-clock-rotate-left'></i></a>";
            return "<div class='home-actions'>" + histBtn + valBtn +
                "<form method='POST' action='{{ url('/table') }}' class='home-open-form'>" +
                "<input type='hidden' name='_token' value='{{ csrf_token() }}'>" +
                "<input type='hidden' name='id' value='" + escapeHtml(row.id || '') + "'>" +
                "<button name='view_home' value='true' class='btn home-open-btn' type='submit' title='Open' aria-label='Open'>" +
                "<i class='fas fa-up-right-from-square' aria-hidden='true'></i></button></form></div>";
        }

        function ajaxRequest(params) {
            updateStatus('Loading instances…', 'loading');
            $.ajax({
                method: 'POST', url: '/areport/json', dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    sort: params.data.sort, order: params.data.order,
                    offset: params.data.offset, limit: params.data.limit, search: params.data.search,
                    status: statusFilter,
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
                $table.bootstrapTable('refresh', { pageNumber: 1 });
            });
        });

        $('#refreshHomeTable').on('click', function () { $table.bootstrapTable('refresh', { silent: true }); });
        $table.on('search.bs.table sort.bs.table page-change.bs.table', function () { updateStatus('Updating the view…', 'loading'); });
    </script>
@endpush
