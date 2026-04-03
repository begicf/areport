@extends('layouts.app')

@push('styles')
    <style>
        .home-dashboard {
            padding: 0 0.85rem 2rem;
        }

        .home-hero {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            padding: 1rem 1.15rem;
            margin-bottom: 0.75rem;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.14), transparent 24%),
                radial-gradient(circle at bottom right, rgba(20, 184, 166, 0.2), transparent 28%),
                linear-gradient(135deg, #081122 0%, #163b75 48%, #0e7490 100%);
            color: #f8fbff;
            box-shadow: 0 18px 34px rgba(8, 17, 34, 0.16);
        }

        .home-hero::before,
        .home-hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .home-hero::before {
            width: 150px;
            height: 150px;
            top: -70px;
            right: -30px;
        }

        .home-hero::after {
            width: 110px;
            height: 110px;
            bottom: -55px;
            left: -24px;
        }

        .home-hero-content,
        .home-highlight-card {
            position: relative;
            z-index: 1;
        }

        .home-kicker,
        .home-panel-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.76rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .home-kicker {
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            font-size: 0.68rem;
        }

        .home-title {
            font-size: clamp(1.35rem, 2.2vw, 2rem);
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin: 0.7rem 0 0.35rem;
            max-width: none;
        }

        .home-lead {
            max-width: 72ch;
            font-size: 0.92rem;
            line-height: 1.55;
            margin-bottom: 0.85rem;
            color: rgba(248, 251, 255, 0.82);
        }

        .home-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .home-primary-btn,
        .home-secondary-btn,
        .home-toolbar-btn,
        .home-open-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            border-radius: 999px;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
        }

        .home-primary-btn {
            padding: 0.6rem 0.95rem;
            background: #f8fbff;
            color: #0f1b2d;
            box-shadow: 0 10px 18px rgba(8, 17, 34, 0.14);
            font-size: 0.9rem;
        }

        .home-primary-btn:hover,
        .home-primary-btn:focus {
            color: #0f1b2d;
            transform: translateY(-1px);
        }

        .home-secondary-btn {
            padding: 0.6rem 0.95rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #f8fbff;
            font-size: 0.9rem;
        }

        .home-secondary-btn:hover,
        .home-secondary-btn:focus {
            color: #f8fbff;
            transform: translateY(-1px);
            border-color: rgba(255, 255, 255, 0.28);
        }

        .home-highlight-card {
            border-radius: 18px;
            padding: 0.75rem 0.9rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(12px);
        }

        .home-highlight-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(248, 251, 255, 0.75);
        }

        .home-highlight-value {
            font-size: 1.15rem;
            line-height: 1.2;
            font-weight: 800;
            margin: 0.35rem 0 0.2rem;
        }

        .home-highlight-meta {
            font-size: 0.84rem;
            color: rgba(248, 251, 255, 0.78);
            line-height: 1.45;
        }

        .home-metrics,
        .home-stat-card,
        .home-panel {
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            box-shadow: 0 22px 42px rgba(15, 23, 42, 0.08);
        }

        .home-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding: 0.75rem;
        }

        .home-stat-card {
            padding: 0.8rem 0.9rem;
        }

        .home-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0;
        }

        .home-stat-label {
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #5b6b83;
            margin-bottom: 0.35rem;
        }

        .home-stat-value {
            font-size: 1.2rem;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #081122;
        }

        .home-stat-copy {
            display: none;
        }

        .home-stat-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dbeafe, #ccfbf1);
            color: #164e63;
            font-size: 0.82rem;
        }

        .home-panel {
            padding: 1rem;
        }

        .home-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.85rem;
        }

        .home-panel-kicker {
            color: #0f766e;
            margin-bottom: 0.4rem;
            font-size: 0.7rem;
        }

        .home-panel-title {
            font-size: clamp(1.1rem, 1.7vw, 1.45rem);
            line-height: 1.15;
            letter-spacing: -0.03em;
            font-weight: 800;
            color: #081122;
            margin-bottom: 0.3rem;
        }

        .home-panel-copy {
            max-width: 56ch;
            margin: 0;
            color: #5b6b83;
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .home-toolbar-btn {
            border: 1px solid rgba(15, 23, 42, 0.12);
            padding: 0.6rem 0.85rem;
            background: #f8fafc;
            color: #0f172a;
            font-size: 0.9rem;
        }

        .home-toolbar-btn:hover,
        .home-toolbar-btn:focus {
            transform: translateY(-1px);
            background: #eef5ff;
            color: #0f172a;
        }

        .home-status-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.7rem 0.85rem;
            border-radius: 18px;
            background: linear-gradient(135deg, #eff6ff 0%, #f0fdfa 100%);
            border: 1px solid rgba(8, 145, 178, 0.12);
            margin-bottom: 0.85rem;
        }

        .home-status-label {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-weight: 700;
            color: #0f172a;
        }

        .home-status-meta {
            color: #66768d;
            font-size: 0.84rem;
        }

        .home-table-shell {
            overflow: hidden;
            border-radius: 20px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #f8fafc;
        }

        .home-dashboard .bootstrap-table .fixed-table-toolbar {
            padding: 0.55rem 0.65rem 0;
        }

        .home-dashboard .bootstrap-table .fixed-table-toolbar .search input {
            height: 36px;
            border-radius: 10px;
            border: 1px solid rgba(15, 23, 42, 0.1);
            background: #ffffff;
            box-shadow: none;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            font-size: 0.82rem;
        }

        .home-dashboard .bootstrap-table .fixed-table-toolbar .search input:focus {
            border-color: rgba(14, 116, 144, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(14, 116, 144, 0.12);
        }

        .home-dashboard .bootstrap-table .fixed-table-loading {
            padding: 0.55rem 0;
            background: rgba(248, 250, 252, 0.92);
        }

        .home-dashboard .bootstrap-table .fixed-table-loading .loading-text {
            font-size: 0.9rem !important;
            font-weight: 700;
            color: #334155;
        }

        .home-dashboard .bootstrap-table .fixed-table-container {
            border: 0;
            background: transparent;
        }

        .home-dashboard .fixed-table-container thead th {
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: #ffffff;
            color: #4f5f76;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding-top: 0.58rem;
            padding-bottom: 0.58rem;
        }

        .home-dashboard .fixed-table-container tbody tr {
            background: #ffffff;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .home-dashboard .fixed-table-container tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: inset 0 0 0 999px rgba(239, 246, 255, 0.78);
        }

        .home-dashboard .fixed-table-container td {
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
            vertical-align: middle;
        }

        .home-period-badge,
        .home-path-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            white-space: nowrap;
        }

        .home-period-badge {
            padding: 0.24rem 0.52rem;
            background: #e0f2fe;
            color: #0c4a6e;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .home-period-muted {
            background: #e5e7eb;
            color: #4b5563;
        }

        .home-module-cell {
            display: flex;
            flex-direction: column;
            gap: 0.08rem;
        }

        .home-module-title {
            font-size: 0.86rem;
            font-weight: 700;
            color: #081122;
        }

        .home-module-caption {
            color: #66768d;
            font-size: 0.74rem;
        }

        .home-path-chip {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0.26rem 0.55rem;
            background: #f1f5f9;
            color: #334155;
            font-family: "SFMono-Regular", "Consolas", "Liberation Mono", monospace;
            font-size: 0.72rem;
        }

        .home-open-form {
            display: inline-flex;
            justify-content: center;
            margin: 0;
        }

        .home-open-btn {
            padding: 0.34rem 0.58rem;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            border: 0;
            color: #ffffff;
            box-shadow: 0 10px 18px rgba(29, 78, 216, 0.18);
            font-size: 0.76rem;
        }

        .home-open-btn:hover,
        .home-open-btn:focus {
            color: #ffffff;
            transform: translateY(-1px);
        }

        .home-dashboard .fixed-table-pagination {
            padding: 0.58rem 0.65rem;
        }

        .home-dashboard .pagination .page-link {
            border-radius: 12px !important;
            border: 0;
            margin: 0 0.18rem;
            color: #334155;
        }

        .home-dashboard .page-item.active .page-link {
            background: #0f172a;
            color: #ffffff;
        }

        .home-dashboard .alert {
            border: 0;
            border-radius: 18px;
        }

        @media (max-width: 991.98px) {
            .home-dashboard {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .home-hero,
            .home-metrics,
            .home-panel {
                padding: 1rem;
            }

            .home-title {
                max-width: none;
            }

            .home-hero-actions {
                width: 100%;
            }

            .home-hero-actions .btn {
                flex: 1 1 auto;
            }

            .home-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="home-dashboard container-fluid px-0 px-lg-2">
        <section class="home-hero">
            <div class="row g-3 align-items-center">
                <div class="col-xl-8">
                    <div class="home-hero-content">
                        <span class="home-kicker">
                            <i class="fas fa-bolt"></i>
                            Financial Reporting
                        </span>
                        <h1 class="home-title">Financial module overview</h1>
                        <p class="home-lead">
                            A compact operational view for searching, sorting, and opening reporting modules while
                            preserving more screen space for data.
                        </p>
                        <div class="home-hero-actions">
                            <button type="button" id="refreshHomeTable" class="btn home-primary-btn">
                                <i class="fas fa-rotate-right"></i>
                                Refresh
                            </button>
                            <a href="#moduleRegistry" class="btn home-secondary-btn">
                                <i class="fas fa-table"></i>
                                Open table
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="home-highlight-card">
                        <div class="home-highlight-label">Status</div>
                        <div class="home-highlight-value" id="homeLoadStatus">Ready to review</div>
                        <div class="home-highlight-meta" id="homeLoadMeta">
                            Active rows and load state stay concise so the focus remains on the data grid.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('flash.flash-message')

        <section class="home-metrics" aria-label="Dashboard metrics">
            <article class="home-stat-card">
                <div class="home-stat-header">
                    <div>
                        <div class="home-stat-label">Total modules</div>
                        <div class="home-stat-value" id="homeModulesCount">0</div>
                    </div>
                    <span class="home-stat-icon">
                        <i class="fas fa-layer-group"></i>
                    </span>
                </div>
            </article>
            <article class="home-stat-card">
                <div class="home-stat-header">
                    <div>
                        <div class="home-stat-label">Filtered rows</div>
                        <div class="home-stat-value" id="homeFilteredCount">0</div>
                    </div>
                    <span class="home-stat-icon">
                        <i class="fas fa-filter"></i>
                    </span>
                </div>
            </article>
            <article class="home-stat-card">
                <div class="home-stat-header">
                    <div>
                        <div class="home-stat-label">Visible periods</div>
                        <div class="home-stat-value" id="homePeriodsCount">0</div>
                    </div>
                    <span class="home-stat-icon">
                        <i class="fas fa-calendar-days"></i>
                    </span>
                </div>
            </article>
        </section>

        <section class="home-panel" id="moduleRegistry">
            <div class="home-panel-header">
                <div>
                    <div class="home-panel-kicker">
                        <i class="fas fa-wave-square"></i>
                        Registry
                    </div>
                    <h2 class="home-panel-title">Modules, paths, and quick entry points</h2>
                    <p class="home-panel-copy">
                        Search the list, sort by period or name, and open the selected module directly from the dashboard.
                    </p>
                </div>
                <div id="homeTableToolbar">
                    <button type="button" id="refreshTableButton" class="btn home-toolbar-btn">
                        <i class="fas fa-arrows-rotate"></i>
                        Refresh data
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
