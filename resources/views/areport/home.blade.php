@extends('layouts.app')

@push('styles')
    <style>
        .home-dashboard { padding: .9rem 1rem 2rem; }

        /* Flat page header (matches DPM Architect / Settings) */
        .home-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem;
            background: #fff; border: 1px solid #cbd5e1; border-top: 2px solid #0f766e; padding: .7rem .9rem; margin-bottom: .6rem; }
        .home-kicker { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #0f766e; display: inline-flex; align-items: center; gap: .4rem; }
        .home-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: .15rem 0 0; }
        .home-lead { font-size: .82rem; color: #64748b; margin: .25rem 0 0; }
        .home-head-actions { display: flex; gap: .4rem; flex-shrink: 0; }

        /* Flat stat cards */
        .home-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; margin-bottom: .6rem; }
        .home-stat-card { background: #fff; border: 1px solid #cbd5e1; padding: .7rem .85rem; }
        .home-stat-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .home-stat-label { font-size: .68rem; letter-spacing: .1em; text-transform: uppercase; color: #64748b; margin-bottom: .3rem; }
        .home-stat-value { font-size: 1.35rem; line-height: 1; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
        .home-stat-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
            background: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4; font-size: .8rem; }

        /* Flat panel */
        .home-panel { background: #fff; border: 1px solid #cbd5e1; }
        .home-panel-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
            padding: .7rem .9rem; border-bottom: 1px solid #e2e8f0; }
        .home-panel-kicker { color: #0f766e; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; display: inline-flex; align-items: center; gap: .4rem; }
        .home-panel-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: .2rem 0 .15rem; }
        .home-panel-copy { margin: 0; color: #64748b; font-size: .8rem; }

        .home-status-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
            padding: .45rem .9rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: .8rem; }
        .home-status-label { display: inline-flex; align-items: center; gap: .5rem; font-weight: 600; color: #0f172a; }
        .home-status-meta { color: #64748b; }

        .home-table-shell { padding: .3rem .4rem; }

        .home-dashboard .bootstrap-table .fixed-table-toolbar { padding: .45rem .5rem 0; }
        .home-dashboard .bootstrap-table .fixed-table-toolbar .search input {
            height: 32px; border-radius: 2px; border: 1px solid #cbd5e1; background: #fff; box-shadow: none; font-size: .8rem; }
        .home-dashboard .bootstrap-table .fixed-table-toolbar .search input:focus { border-color: #0f766e; box-shadow: 0 0 0 .15rem rgba(15,118,110,.12); }
        .home-dashboard .fixed-table-container thead th {
            border-bottom: 1px solid #cbd5e1; background: #f1f5f9; color: #475569; font-size: .68rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em; padding: .45rem .55rem; }
        .home-dashboard .fixed-table-container tbody tr { background: #fff; }
        .home-dashboard .fixed-table-container tbody tr:hover { background: #f0fdfa; }
        .home-dashboard .fixed-table-container td { padding: .4rem .55rem; border-top: 1px solid #eef2f6; vertical-align: middle; }

        .home-period-badge { display: inline-flex; align-items: center; padding: .14rem .45rem; background: #e0f2fe; color: #075985; font-weight: 700; font-size: .72rem; white-space: nowrap; }
        .home-period-muted { background: #f1f5f9; color: #64748b; }
        .home-module-cell { display: flex; flex-direction: column; gap: .05rem; }
        .home-module-title { font-size: .84rem; font-weight: 700; color: #0f172a; }
        .home-module-caption { color: #94a3b8; font-size: .72rem; }
        .home-path-chip { display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            padding: .18rem .45rem; background: #f1f5f9; color: #334155; font-family: "SFMono-Regular","Consolas",monospace; font-size: .72rem; }
        .home-open-form { display: inline-flex; margin: 0; }
        .home-open-btn { padding: .28rem .6rem; background: #0f766e; border: 0; color: #fff; font-size: .76rem; font-weight: 600; }
        .home-open-btn:hover, .home-open-btn:focus { color: #fff; background: #0b5c55; }

        .home-dashboard .fixed-table-pagination { padding: .5rem .6rem; }
        .home-dashboard .pagination .page-link { border-radius: 2px !important; border: 1px solid #e2e8f0; margin: 0 .12rem; color: #334155; }
        .home-dashboard .page-item.active .page-link { background: #0f766e; border-color: #0f766e; color: #fff; }

        @media (max-width: 991.98px) {
            .home-metrics { grid-template-columns: 1fr; }
            .home-head { flex-direction: column; align-items: flex-start; }
        }
    </style>
@endpush

@section('content')
    <div class="home-dashboard">
        <div class="home-head">
            <div>
                <span class="home-kicker"><i class="fas fa-bolt"></i> Financial Reporting</span>
                <h1 class="home-title">Financial module overview</h1>
                <p class="home-lead" id="homeLoadMeta">Search, sort, and open reporting modules.</p>
            </div>
            <div class="home-head-actions">
                <span class="d-none" id="homeLoadStatus">Ready</span>
                <button type="button" id="refreshHomeTable" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-rotate-right"></i> Refresh
                </button>
            </div>
        </div>

        @include('flash.flash-message')

        <section class="home-metrics" aria-label="Dashboard metrics">
            <article class="home-stat-card">
                <div class="home-stat-header">
                    <div>
                        <div class="home-stat-label">Total modules</div>
                        <div class="home-stat-value" id="homeModulesCount">0</div>
                    </div>
                    <span class="home-stat-icon"><i class="fas fa-layer-group"></i></span>
                </div>
            </article>
            <article class="home-stat-card">
                <div class="home-stat-header">
                    <div>
                        <div class="home-stat-label">Filtered rows</div>
                        <div class="home-stat-value" id="homeFilteredCount">0</div>
                    </div>
                    <span class="home-stat-icon"><i class="fas fa-filter"></i></span>
                </div>
            </article>
            <article class="home-stat-card">
                <div class="home-stat-header">
                    <div>
                        <div class="home-stat-label">Visible periods</div>
                        <div class="home-stat-value" id="homePeriodsCount">0</div>
                    </div>
                    <span class="home-stat-icon"><i class="fas fa-calendar-days"></i></span>
                </div>
            </article>
        </section>

        <section class="home-panel" id="moduleRegistry">
            <div class="home-panel-header">
                <div>
                    <div class="home-panel-kicker"><i class="fas fa-wave-square"></i> Registry</div>
                    <h2 class="home-panel-title">Modules, paths, and quick entry points</h2>
                    <p class="home-panel-copy">Search the list, sort by period or name, and open a module directly.</p>
                </div>
                <div id="homeTableToolbar">
                    <button type="button" id="refreshTableButton" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrows-rotate"></i> Refresh data
                    </button>
                </div>
            </div>

            <div class="home-status-bar">
                <div class="home-status-label">
                    <i class="fas fa-circle-notch"></i>
                    <span id="homeTableStatus">The table is ready.</span>
                </div>
                <div class="home-status-meta">
                    Last update:
                    <strong id="homeLastUpdated">not loaded yet</strong>
                </div>
            </div>

            <div class="home-table-shell">
                <table id="table"
                       data-toggle="table"
                       data-height="760"
                       data-toolbar="#homeTableToolbar"
                       data-show-fullscreen="true"
                       data-ajax="ajaxRequest"
                       data-search="true"
                       data-side-pagination="server"
                       data-page-size="12"
                       data-page-list="[12, 24, 48, 96, all]"
                       data-pagination="true"
                       data-classes="table table-borderless align-middle mb-0">
                    <thead>
                    <tr>
                        <th data-field="period"
                            data-width="190"
                            data-sortable="true"
                            data-align="center"
                            data-formatter="periodFormatter">
                            Period
                        </th>
                        <th data-field="module_name"
                            data-width="400"
                            data-sortable="true"
                            data-formatter="moduleNameFormatter">
                            Module
                        </th>
                        <th data-field="module_path"
                            data-sortable="true"
                            data-formatter="modulePathFormatter">
                            Module path
                        </th>
                        <th data-field="open"
                            data-width="180"
                            data-align="center"
                            data-formatter="actionOpen">
                            Action
                        </th>
                    </tr>
                    </thead>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const $table = $('#table');
        const statusElement = document.getElementById('homeTableStatus');
        const statusIcon = document.querySelector('.home-status-label i');
        const loadStatusElement = document.getElementById('homeLoadStatus');
        const loadMetaElement = document.getElementById('homeLoadMeta');

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function formatNow() {
            return new Intl.DateTimeFormat('en-GB', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }).format(new Date());
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
            const uniquePeriods = new Set(rows.map(function (row) {
                return row.period;
            }).filter(Boolean));

            document.getElementById('homeModulesCount').textContent = String(data.all_total ?? rows.length);
            document.getElementById('homeFilteredCount').textContent = String(data.total ?? rows.length);
            document.getElementById('homePeriodsCount').textContent = String(uniquePeriods.size);
            document.getElementById('homeLastUpdated').textContent = formatNow();

            if (rows.length > 0) {
                loadStatusElement.textContent = 'Data loaded';
                loadMetaElement.textContent = 'The current view contains ' + rows.length + ' rows ready for review.';
            } else {
                loadStatusElement.textContent = 'No results';
                loadMetaElement.textContent = 'Try another search term or clear the filters to load a broader result set.';
            }
        }

        function periodFormatter(value) {
            if (!value) {
                return "<span class='home-period-badge home-period-muted'>No period</span>";
            }

            return "<span class='home-period-badge'>" + escapeHtml(value) + "</span>";
        }

        function moduleNameFormatter(value, row) {
            const moduleName = value || 'Unnamed module';
            const moduleId = row && row.id ? row.id : '-';

            return "" +
                "<div class='home-module-cell'>" +
                "<div class='home-module-title'>" + escapeHtml(moduleName) + "</div>" +
                "<div class='home-module-caption'>Record ID #" + escapeHtml(moduleId) + "</div>" +
                "</div>";
        }

        function modulePathFormatter(value) {
            return "<span class='home-path-chip' title='" + escapeHtml(value || '-') + "'>" + escapeHtml(value || '-') + "</span>";
        }

        function actionOpen(value, row) {
            return "" +
                "<form method='POST' action='{{ url('/table') }}' class='home-open-form'>" +
                "<input type='hidden' name='_token' value='{{ csrf_token() }}'>" +
                "<input type='hidden' name='period' value='" + escapeHtml(row.period || '') + "'>" +
                "<input type='hidden' name='id' value='" + escapeHtml(row.id || '') + "'>" +
                "<button name='view_home' value='true' class='btn home-open-btn' type='submit'>" +
                "<span class='fas fa-layer-group' aria-hidden='true'></span>" +
                "Open" +
                "</button>" +
                "</form>";
        }

        function ajaxRequest(params) {
            updateStatus('Loading modules...', 'loading');

            $.ajax({
                method: 'POST',
                url: '/areport/json',
                data: {
                    _token: '{{ csrf_token() }}',
                    sort: params.data.sort,
                    order: params.data.order,
                    offset: params.data.offset,
                    limit: params.data.limit,
                    search: params.data.search,
                },
                dataType: 'json',
                success: function (data) {
                    updateDashboard(data);
                    updateStatus((data.total ?? 0) > 0 ? 'Data refreshed successfully.' : 'No results for the current filter.', (data.total ?? 0) > 0 ? 'success' : 'empty');
                    params.success(data);
                },
                error: function (error) {
                    loadStatusElement.textContent = 'Load failed';
                    loadMetaElement.textContent = 'The server did not return data. Try refreshing once more.';
                    updateStatus('Unable to load data.', 'error');

                    if (typeof params.error === 'function') {
                        params.error(error);
                    }
                }
            });
        }

        function refreshHomeTable() {
            $table.bootstrapTable('refresh', {
                silent: true
            });
        }

        $('#refreshHomeTable, #refreshTableButton').on('click', refreshHomeTable);

        $table.on('search.bs.table sort.bs.table page-change.bs.table', function () {
            updateStatus('Updating the view...', 'loading');
        });
    </script>
@endpush
