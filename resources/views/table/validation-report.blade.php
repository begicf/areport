@extends('layouts.app')

@push('styles')
    <style>
        .vreport { padding: 1rem 1.2rem 2.5rem; max-width: 1280px; margin: 0 auto; }
        .vr-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .vr-meta { display: flex; flex-wrap: wrap; gap: .3rem 1.4rem; font-size: .8rem; color: var(--muted); margin-top: .5rem; }
        .vr-meta b { color: var(--ink); font-weight: 600; }
        .vr-verdict { display: inline-flex; align-items: center; gap: .55rem; padding: .5rem .9rem; border-radius: 10px; font-weight: 700; font-size: .95rem; }
        .vr-verdict.pass { background: #e8f6ee; color: #166534; border: 1px solid #cce6d8; }
        .vr-verdict.fail { background: #fdecec; color: #b91c1c; border: 1px solid #f6cdcd; }
        .vr-sum { display: grid; grid-template-columns: repeat(7, 1fr); gap: .6rem; margin: 1rem 0; }
        .vr-stat { border: 1px solid var(--line); border-radius: 10px; padding: .6rem .8rem; background: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.04); border-top: 3px solid var(--line); }
        .vr-stat .k { font-size: .62rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--faint); }
        .vr-stat .v { font-size: 1.5rem; font-weight: 700; line-height: 1.1; margin-top: .1rem; font-variant-numeric: tabular-nums; }
        .vr-stat.passed { border-top-color: #4a9d6e; } .vr-stat.passed .v { color: #166534; }
        .vr-stat.failed { border-top-color: #dc2626; } .vr-stat.failed .v { color: #b91c1c; }
        .vr-stat.ind { border-top-color: #e4902f; } .vr-stat.ind .v { color: #b45309; }
        .vr-stat.unsup { border-top-color: #94a3b8; } .vr-stat.unsup .v { color: #64748b; }
        .vr-stat.total .v { color: var(--ink); }
        .vr-bar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin: .8rem 0; padding: .6rem .7rem; border: 1px solid var(--line); border-radius: 10px; background: #fff; }
        .vr-bar select, .vr-bar input { height: 32px; border: 1px solid var(--line); border-radius: 7px; font-size: .8rem; padding: 0 .5rem; }
        .vr-bar label { font-size: .78rem; color: var(--muted); display: inline-flex; align-items: center; gap: .35rem; }
        .vr-bar .spacer { flex: 1; }
        .vr-list { display: flex; flex-direction: column; gap: .5rem; }
        .vr-rule { border: 1px solid var(--line); border-radius: 10px; background: #fff; padding: .6rem .8rem; box-shadow: 0 1px 2px rgba(16,24,40,.03); border-left: 4px solid var(--line); }
        .vr-rule.failed { border-left-color: #dc2626; } .vr-rule.passed { border-left-color: #4a9d6e; }
        .vr-rule.indeterminate { border-left-color: #e4902f; } .vr-rule.unsupported { border-left-color: #94a3b8; }
        .vr-rh { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .vr-badge { font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: .1rem .45rem; border-radius: 999px; }
        .vr-badge.failed { background: #fee2e2; color: #b91c1c; } .vr-badge.passed { background: #dcfce7; color: #166534; }
        .vr-badge.indeterminate { background: #fef3c7; color: #b45309; } .vr-badge.unsupported { background: #f1f5f9; color: #64748b; }
        .vr-cross { background: #efe9f8; color: #6d5ab5; border: 1px solid #ddd6f1; }
        .vr-sev { font-size: .64rem; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .vr-sev.error { color: #b91c1c; } .vr-sev.warning { color: #b45309; }
        .vr-code { font-weight: 700; color: var(--ink); font-size: .84rem; }
        .vr-tables { display: inline-flex; gap: .25rem; flex-wrap: wrap; }
        .vr-tbl { font-size: .64rem; font-weight: 700; padding: .06rem .4rem; background: var(--blue-soft); color: var(--blue); border: 1px solid var(--blue-bd); border-radius: 5px; }
        .vr-expr { margin-top: .4rem; font-family: "SFMono-Regular","Consolas",monospace; font-size: .74rem; color: #334155; background: var(--band); border: 1px solid var(--line); border-radius: 6px; padding: .35rem .5rem; overflow-x: auto; }
        .vr-facts { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
        .vr-fact { font-size: .72rem; background: #f8fafc; border: 1px solid var(--line); border-radius: 5px; padding: .08rem .45rem; font-variant-numeric: tabular-nums; }
        .vr-fact b { color: var(--blue); }
        .vr-fact.null { color: #b45309; }
        .vr-result { font-size: .72rem; color: var(--muted); margin-top: .35rem; }
        .vr-empty { text-align: center; color: var(--muted); padding: 2rem; }
        .vr-stat.warn { border-top-color: #e4902f; } .vr-stat.warn .v { color: #b45309; }
        .vr-stat.na .v { color: #94a3b8; }
        .vr-stat .h { font-size: .64rem; color: var(--faint); margin-top: .1rem; line-height: 1.25; }
        .vr-rule.not_evaluated { border-left-color: #94a3b8; } .vr-rule.not_applicable { border-left-color: #e2e8f0; opacity: .85; }
        .vr-rule.failed.warning { border-left-color: #e4902f; }
        .vr-badge.not_evaluated { background: #f1f5f9; color: #64748b; } .vr-badge.not_applicable { background: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; }
        .vr-text { margin-top: .35rem; font-size: .82rem; color: #1f2937; }
        .vr-text .ref { font-weight: 600; color: var(--blue); white-space: nowrap; }
        .vr-fact a { color: inherit; text-decoration: none; }
        .vr-fact a:hover b { text-decoration: underline; }
        .vr-fact .go { font-size: .62rem; color: var(--faint); margin-left: .25rem; }
        .vr-prec { font-size: .72rem; color: var(--faint); margin-top: .3rem; }
        .vr-legend { font-size: .72rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: .3rem 1.2rem; margin: -.4rem 0 .8rem; }
        .vr-legend b { color: var(--ink); font-weight: 600; }
        @media print { .app-navbar, .vr-bar, .vr-print { display: none !important; } .vreport { max-width: none; } }
        @media (max-width: 800px) { .vr-sum { grid-template-columns: repeat(2, 1fr); } }
    </style>
@endpush

@section('content')
    @php
        $rules = collect($report['rules'] ?? []);
        $order = ['failed' => 0, 'indeterminate' => 1, 'not_evaluated' => 2, 'passed' => 3, 'not_applicable' => 4];
        $rules = $rules->sortBy(fn ($r) => [$order[$r['status']] ?? 9, $r['severity'] === 'error' ? 0 : 1, $r['code']])->values();
        // A module can have tens of thousands of rule variants: list every failure, cap the rest
        // per status (the CSV has everything).
        $cap = 300;
        $hidden = $rules->where('status', '!=', 'failed')->groupBy('status')->map(fn ($g) => max(0, $g->count() - $cap))->filter();
        $rules = $rules->groupBy('status')->flatMap(fn ($g, $st) => $st === 'failed' ? $g : $g->take($cap))->values();
        $s = $report['summary'] ?? [];
        $errorsFailed = (int) ($s['failed_errors'] ?? 0);
        $warnFailed = (int) ($s['failed_warnings'] ?? 0);
        $allTables = $rules->pluck('tables')->flatten()->unique()->sort()->values();
        $pass = ! $error && $errorsFailed === 0;
        $statusLabels = ['failed' => 'Failed', 'indeterminate' => 'Missing data', 'not_evaluated' => 'Not evaluated', 'passed' => 'Passed', 'not_applicable' => 'Not applicable'];
        // {C_01.00,r0010,c0010,} → C 01.00 r0010 c0010 (readable cell references)
        $readable = function (string $rule) {
            $html = e($rule);
            return preg_replace_callback('/\{([A-Z]_[0-9.A-Za-z]+),([^}]*)\}/', function ($m) {
                // Positional row, column, sheet; bare numbers get their r / c / s prefix.
                $parts = [];
                foreach (array_map('trim', explode(',', $m[2])) as $i => $p) {
                    if ($p === '' || $i > 2) {
                        continue;
                    }
                    $parts[] = ctype_digit($p) ? ['r', 'c', 's'][$i].$p : $p;
                }
                return '<span class="ref">'.str_replace('_', ' ', $m[1]).($parts ? ' '.implode(' ', $parts) : '').'</span>';
            }, $html);
        };
        // fact key "tc_01.00,r0010,c0010" → [table, form cell code c0010r0010]
        $target = function (string $key) {
            $parts = explode(',', $key);
            $table = ltrim($parts[0] ?? '', 't');
            $row = collect($parts)->first(fn ($p) => str_starts_with($p, 'r'));
            $col = collect($parts)->first(fn ($p) => str_starts_with($p, 'c'));
            return [$table, ($col && $row) ? $col.$row : ''];
        };
    @endphp

    <div class="vreport">
        <div class="vr-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-clipboard-check"></i> Validation report</span>
                <h1 class="ax-title" style="font-size:1.3rem">{{ $meta['module'] ?: 'Module' }}</h1>
                <div class="vr-meta">
                    @if($meta['entity'])<span>Entity: <b>{{ $meta['entity'] }}</b> @if($meta['lei'])<code>{{ $meta['lei'] }}</code>@endif</span>@endif
                    <span>Reference date: <b>{{ $meta['period_label'] ?? $meta['period'] }}</b></span>
                    <span>Taxonomy: <b>{{ $meta['taxonomy'] }}</b></span>
                    <span>Tables: <b>{{ $meta['tables'] }}</b></span>
                    <span>Values: <b>{{ $meta['values'] }}</b></span>
                    <span>Generated: <b>{{ $meta['generated'] }}</b></span>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                @if($error)
                    <span class="vr-verdict fail"><i class="fas fa-triangle-exclamation"></i> Cannot validate</span>
                @elseif($pass)
                    <span class="vr-verdict pass"><i class="fas fa-circle-check"></i> No blocking errors @if($warnFailed)· {{ $warnFailed }} warning(s) to review @endif</span>
                @else
                    <span class="vr-verdict fail"><i class="fas fa-circle-xmark"></i> {{ $errorsFailed }} error(s) to fix @if($warnFailed) · {{ $warnFailed }} warning(s)@endif</span>
                @endif
                <div class="d-flex gap-2 vr-print">
                    @unless($error)<a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}"><i class="fas fa-file-csv"></i> CSV</a>@endunless
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="fas fa-print"></i> Print / PDF</button>
                </div>
            </div>
        </div>

        @if($error)
            <div class="vr-empty" style="color:#b91c1c"><i class="fas fa-triangle-exclamation"></i> {{ $error }}</div>
        @else
            <section class="vr-sum">
                <div class="vr-stat total"><div class="k">Rules checked</div><div class="v">{{ $s['total'] ?? 0 }}</div><div class="h">for this module</div></div>
                <div class="vr-stat failed"><div class="k">Errors</div><div class="v">{{ $errorsFailed }}</div><div class="h">blocking, must be fixed</div></div>
                <div class="vr-stat warn"><div class="k">Warnings</div><div class="v">{{ $warnFailed }}</div><div class="h">to review and explain</div></div>
                <div class="vr-stat passed"><div class="k">Passed</div><div class="v">{{ $s['passed'] ?? 0 }}</div><div class="h">conditions met</div></div>
                <div class="vr-stat ind"><div class="k">Missing data</div><div class="v">{{ $s['indeterminate'] ?? 0 }}</div><div class="h">a referenced cell is empty</div></div>
                <div class="vr-stat unsup"><div class="k">Not evaluated</div><div class="v">{{ $s['not_evaluated'] ?? 0 }}</div><div class="h">rule form not supported yet</div></div>
                <div class="vr-stat na"><div class="k">Not applicable</div><div class="v">{{ $s['not_applicable'] ?? 0 }}</div><div class="h">template not reported</div></div>
            </section>

            <div class="vr-legend">
                <span><b>Missing data</b>: the rule needs a cell that has no value; fill it or confirm it is not reported.</span>
                <span><b>Not applicable</b>: the rule's precondition (a filing indicator) is not met.</span>
                <span><b>Severity</b> is the module's own (EBA lists some rules as warnings per module).</span>
            </div>

            <div class="vr-bar">
                <label>Status
                    <select id="vrStatus">
                        <option value="failed" @selected(($s['failed'] ?? 0) > 0)>Failed</option>
                        <option value="actionable" @selected(($s['failed'] ?? 0) === 0)>To review (failed + missing data)</option>
                        <option value="errors">Errors only</option>
                        <option value="indeterminate">Missing data</option>
                        <option value="not_evaluated">Not evaluated</option>
                        <option value="passed">Passed</option>
                        <option value="not_applicable">Not applicable</option>
                        <option value="all">All</option>
                    </select>
                </label>
                <label>Table
                    <select id="vrTable"><option value="">All tables</option>@foreach($allTables as $tb)<option value="{{ $tb }}">{{ $tb }}</option>@endforeach</select>
                </label>
                <label><input type="checkbox" id="vrCross"> Cross-table only</label>
                <div class="spacer"></div>
                <input id="vrSearch" placeholder="Search rule code or text…" autocomplete="off">
                <span class="text-muted small" id="vrCount"></span>
            </div>

            <div class="vr-list" id="vrList">
                @forelse($rules as $r)
                    <div class="vr-rule {{ $r['status'] }} {{ $r['severity'] }}"
                         data-status="{{ $r['status'] }}" data-severity="{{ $r['severity'] }}"
                         data-cross="{{ $r['cross'] ? '1' : '0' }}" data-search="{{ strtolower($r['code'].' '.$r['rule']) }}"
                         data-tables="{{ strtolower(implode(' ', $r['tables'])) }}">
                        <div class="vr-rh">
                            <span class="vr-badge {{ $r['status'] }}">{{ $statusLabels[$r['status']] ?? $r['status'] }}</span>
                            <span class="vr-sev {{ $r['severity'] }}">{{ $r['severity'] }}</span>
                            <span class="vr-code">{{ $r['code'] }}</span>
                            @if($r['cross'])<span class="vr-badge vr-cross"><i class="fas fa-link"></i> cross-table</span>@endif
                            @if($r['tables'])<span class="vr-tables">@foreach($r['tables'] as $tb)<span class="vr-tbl">{{ $tb }}</span>@endforeach</span>@endif
                        </div>
                        @if($r['rule'])<div class="vr-text">{!! $readable($r['rule']) !!}</div>@endif
                        @if($r['facts'])
                            <div class="vr-facts">
                                @foreach($r['facts'] as $f)
                                    @php([$tbl, $cell] = $target($f['key'] ?? ''))
                                    <span class="vr-fact {{ $f['value'] === null ? 'null' : '' }}">
                                        <a href="{{ url('/table/open') }}?id={{ $meta['id'] }}&table={{ urlencode($tbl) }}&cell={{ urlencode($cell) }}" target="_blank" rel="noopener" title="Open this cell in the form">
                                            <b>{{ $f['cell'] }}</b> = {{ $f['value'] === null ? '∅ empty' : $f['value'] }}<i class="fas fa-up-right-from-square go"></i>
                                        </a>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @if($r['status'] === 'not_applicable' && $r['precondition'])
                            <div class="vr-prec"><i class="fas fa-filter"></i> Applies when: <code>{{ $r['precondition'] }}</code></div>
                        @elseif($r['status'] === 'not_evaluated' && $r['error'])
                            <div class="vr-prec"><i class="fas fa-circle-info"></i> {{ \Illuminate\Support\Str::limit($r['error'], 160) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="vr-empty">No validation rules apply to the tables of this instance.</div>
                @endforelse
            </div>
            @if($hidden->isNotEmpty())
                <div class="vr-empty" style="padding:1rem">
                    Showing the first {{ $cap }} per status.
                    Not listed: @foreach($hidden as $st => $n){{ $n }} {{ strtolower($statusLabels[$st] ?? $st) }}@if(! $loop->last), @endif @endforeach
                    — <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">download the full report (CSV)</a>.
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const list = document.getElementById('vrList');
            if (!list) return;
            const rows = Array.from(list.querySelectorAll('.vr-rule'));
            const fStatus = document.getElementById('vrStatus');
            const fTable = document.getElementById('vrTable');
            const fCross = document.getElementById('vrCross');
            const fSearch = document.getElementById('vrSearch');
            const count = document.getElementById('vrCount');

            function apply() {
                const st = fStatus.value, tb = fTable.value.toLowerCase(), cross = fCross.checked, q = fSearch.value.trim().toLowerCase();
                let shown = 0;
                rows.forEach(function (row) {
                    const s = row.dataset.status;
                    let ok = true;
                    if (st === 'actionable') ok = (s === 'failed' || s === 'indeterminate');
                    else if (st === 'errors') ok = (s === 'failed' && row.dataset.severity === 'error');
                    else if (st !== 'all') ok = (s === st);
                    if (ok && cross) ok = row.dataset.cross === '1';
                    if (ok && tb) ok = (' ' + row.dataset.tables + ' ').includes(tb);
                    if (ok && q) ok = row.dataset.search.includes(q);
                    row.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                count.textContent = shown + ' shown';
            }
            [fStatus, fTable, fCross].forEach(el => el.addEventListener('change', apply));
            fSearch.addEventListener('input', apply);
            apply();
        })();
    </script>
@endpush
