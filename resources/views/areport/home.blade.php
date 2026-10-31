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
                <span class="ax-eyebrow"><i class="fas fa-landmark"></i> Financial reporting</span>
                <h1 class="ax-title">Financial module overview</h1>
                <p class="ax-sub" id="homeLoadMeta">Search, sort, and open reporting modules for the active taxonomy.</p>
            </div>
            <button type="button" id="refreshHomeTable" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-rotate-right"></i> Refresh
            </button>
        </div>

        @include('flash.flash-message')

        <section class="ax-summary" aria-label="Summary">
            <div class="ax-stat"><div class="ax-stat-k">Total modules</div><div class="ax-stat-v" id="homeModulesCount">0</div></div>
            <div class="ax-stat"><div class="ax-stat-k">Filtered rows</div><div class="ax-stat-v" id="homeFilteredCount">0</div></div>
            <div class="ax-stat"><div class="ax-stat-k">Visible periods</div><div class="ax-stat-v" id="homePeriodsCount">0</div></div>
            <div class="ax-stat"><div class="ax-stat-k">Last update</div><div class="ax-stat-v sm" id="homeLastUpdated">—</div></div>
        </section>

        <section class="ax-card" id="moduleRegistry">
            <div class="ax-panel-head">
                <div>
                    <span class="ax-eyebrow"><i class="fas fa-list-ul"></i> Registry</span>
                    <h2 class="ax-panel-title">Modules, paths &amp; entry points</h2>
                </div>
                <div id="homeTableToolbar"></div>
            </div>

            <div class="home-status-bar">
                <div class="home-status-label">
                    <i class="fas fa-circle-notch"></i>
                    <span id="homeTableStatus">The table is ready.</span>
                </div>
                <div class="home-status-label" style="color:var(--faint)">Open a module to start or continue an instance.</div>
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
                   data-classes="table ax-table align-middle mb-0">
                <thead>
                <tr>
                    <th data-field="period" data-width="150" data-sortable="true" data-formatter="periodFormatter">Period</th>
                    <th data-field="module_name" data-width="300" data-sortable="true" data-formatter="moduleNameFormatter">Module</th>
                    <th data-field="module_path" data-sortable="true" data-formatter="modulePathFormatter">Framework &amp; path</th>
                    <th data-field="open" data-width="130" data-align="right" data-formatter="actionOpen">Action</th>
                </tr>
                </thead>
            </table>
        </section>
    </div>

    <div class="modal fade app-modal-shell" id="moduleValidation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <span class="ax-eyebrow"><i class="fas fa-circle-check"></i> Module validation</span>
                        <h4 class="modal-title mb-0" id="modValTitle">Validation</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modValBody"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const $table = $('#table');
        const statusElement = document.getElementById('homeTableStatus');
        const statusIcon = document.querySelector('.home-status-label i');
        const loadMetaElement = document.getElementById('homeLoadMeta');

        function escapeHtml(value) { return $('<div>').text(value ?? '').html(); }

        function formatNow() {
            return new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date());
        }

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
            const rows = Array.isArray(data.rows) ? data.rows : [];
            const uniquePeriods = new Set(rows.map(r => r.period).filter(Boolean));
            document.getElementById('homeModulesCount').textContent = String(data.all_total ?? rows.length);
            document.getElementById('homeFilteredCount').textContent = String(data.total ?? rows.length);
            document.getElementById('homePeriodsCount').textContent = String(uniquePeriods.size);
            document.getElementById('homeLastUpdated').textContent = formatNow();
            loadMetaElement.textContent = rows.length > 0
                ? 'The current view contains ' + rows.length + ' row(s) ready for review.'
                : 'No modules match the current filter — adjust the search to broaden the result set.';
        }

        function periodFormatter(value) {
            if (!value) return "<span class='home-period home-period-muted'>No period</span>";
            return "<span class='home-period'>" + escapeHtml(value) + "</span>";
        }

        function moduleNameFormatter(value, row) {
            const moduleName = value || 'Unnamed module';
            const moduleId = row && row.id ? row.id : '-';
            return "<div><div class='home-module-title'>" + escapeHtml(moduleName) + "</div>" +
                "<div class='home-module-caption'>Record ID #" + escapeHtml(moduleId) + "</div></div>";
        }

        function modulePathFormatter(value) {
            const fw = frameworkLabel(value);
            const badge = fw ? "<span class='ax-badge'>" + escapeHtml(fw) + "</span>" : "";
            return "<div>" + badge + "<span class='home-path' title='" + escapeHtml(value || '-') + "'>" + escapeHtml(value || '-') + "</span></div>";
        }

        const validationAvailable = @json(Route::has('areport-pro.dpm-model.index'));

        function actionOpen(value, row) {
            const valBtn = validationAvailable
                ? "<button type='button' class='btn home-val-btn' title='Validate module' aria-label='Validate module' onclick='validateModule(" + escapeHtml(row.id || '') + ")'><i class='fas fa-circle-check'></i></button>"
                : "<button type='button' class='btn home-val-btn' title='Validation plugin not installed' aria-label='Validate (unavailable)' disabled><i class='fas fa-circle-check'></i></button>";
            return "<div class='home-actions'>" + valBtn +
                "<form method='POST' action='{{ url('/table') }}' class='home-open-form'>" +
                "<input type='hidden' name='_token' value='{{ csrf_token() }}'>" +
                "<input type='hidden' name='period' value='" + escapeHtml(row.period || '') + "'>" +
                "<input type='hidden' name='id' value='" + escapeHtml(row.id || '') + "'>" +
                "<button name='view_home' value='true' class='btn home-open-btn' type='submit' title='Open' aria-label='Open'>" +
                "<i class='fas fa-up-right-from-square' aria-hidden='true'></i></button></form></div>";
        }

        function validateModule(id) {
            const box = document.getElementById('modValBody');
            document.getElementById('modValTitle').textContent = 'Validating module…';
            box.innerHTML = '<div class="dpm-loading">Running validation…</div>';
            window.appModal ? window.appModal.show('moduleValidation') : $('#moduleValidation').modal('show');
            $.ajax({
                method: 'POST', url: '/table/validate-module', dataType: 'json',
                data: { _token: '{{ csrf_token() }}', id: id }
            }).done(function (d) {
                if (!d.ok) { document.getElementById('modValTitle').textContent = 'Validation'; box.innerHTML = '<div class="mod-val-err">' + escapeHtml(d.error || 'Validation failed.') + '</div>'; return; }
                const s = d.summary || {};
                document.getElementById('modValTitle').textContent = escapeHtml(d.module || 'Module') + ' — ' + d.tables + ' table(s)';
                let html = '<div class="mod-val-pills">'
                    + '<span class="mod-val-pill ok">' + (s.passed || 0) + ' passed</span>'
                    + '<span class="mod-val-pill err">' + (s.failed || 0) + ' failed</span>'
                    + '<span class="mod-val-pill ind">' + (s.indeterminate || 0) + ' indeterminate</span>'
                    + '<span class="mod-val-pill mut">' + (s.unsupported || 0) + ' unsupported</span>'
                    + '</div><div class="text-muted small mt-1">' + (d.values || 0) + ' values · ' + (s.total || 0) + ' rules</div>';
                if ((d.failed || []).length) {
                    html += '<div class="mod-val-list mt-2">' + d.failed.map(function (f) {
                        return '<div class="mod-val-fail"><b>' + escapeHtml(f.code) + '</b> <code>' + escapeHtml(f.rule || '') + '</code>'
                            + '<div class="mod-val-facts">' + (f.facts || []).map(function (x) { return '<span>' + escapeHtml(x.cell) + ' = ' + escapeHtml(x.value === null ? '∅' : x.value) + '</span>'; }).join('') + '</div></div>';
                    }).join('') + '</div>';
                } else if (d.note) {
                    html += '<div class="mod-val-note mt-2">' + escapeHtml(d.note) + '</div>';
                } else {
                    html += '<div class="mod-val-okmsg mt-2"><i class="fas fa-check"></i> No rule violations.</div>';
                }
                box.innerHTML = html;
            }).fail(function (xhr) {
                document.getElementById('modValTitle').textContent = 'Validation';
                const e = (xhr.responseJSON && xhr.responseJSON.error) || 'Validation request failed.';
                box.innerHTML = '<div class="mod-val-err">' + escapeHtml(e) + '</div>';
            });
        }

        function ajaxRequest(params) {
            updateStatus('Loading modules…', 'loading');
            $.ajax({
                method: 'POST', url: '/areport/json', dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    sort: params.data.sort, order: params.data.order,
                    offset: params.data.offset, limit: params.data.limit, search: params.data.search,
                },
                success: function (data) {
                    updateDashboard(data);
                    updateStatus((data.total ?? 0) > 0 ? 'Data refreshed successfully.' : 'No results for the current filter.', (data.total ?? 0) > 0 ? 'success' : 'empty');
                    params.success(data);
                },
                error: function (error) {
                    loadMetaElement.textContent = 'The server did not return data. Try refreshing once more.';
                    updateStatus('Unable to load data.', 'error');
                    if (typeof params.error === 'function') params.error(error);
                }
            });
        }

        $('#refreshHomeTable').on('click', function () { $table.bootstrapTable('refresh', { silent: true }); });
        $table.on('search.bs.table sort.bs.table page-change.bs.table', function () { updateStatus('Updating the view…', 'loading'); });
    </script>
@endpush
