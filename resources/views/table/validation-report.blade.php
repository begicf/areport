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
        .vr-sum { display: grid; grid-template-columns: repeat(5, 1fr); gap: .6rem; margin: 1rem 0; }
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
        @media print { .app-navbar, .vr-bar, .vr-print { display: none !important; } .vreport { max-width: none; } }
        @media (max-width: 800px) { .vr-sum { grid-template-columns: repeat(2, 1fr); } }
    </style>
@endpush

@section('content')
    @php
        $rules = collect($report['rules'] ?? []);
        $order = ['failed' => 0, 'indeterminate' => 1, 'unsupported' => 2, 'passed' => 3];
        $rules = $rules->sortBy(fn ($r) => [$order[$r['status']] ?? 9, $r['severity'] === 'error' ? 0 : 1, $r['code']])->values();
        $s = $report['summary'] ?? [];
        $errorsFailed = $rules->where('status', 'failed')->where('severity', 'error')->count();
        $warnFailed = $rules->where('status', 'failed')->where('severity', 'warning')->count();
        $allTables = $rules->pluck('tables')->flatten()->unique()->sort()->values();
        $pass = ! $error && $errorsFailed === 0;
    @endphp

    <div class="vreport">
        <div class="vr-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-clipboard-check"></i> Validation report</span>
                <h1 class="ax-title" style="font-size:1.3rem">{{ $meta['module'] ?: 'Module' }}</h1>
                <div class="vr-meta">
                    @if($meta['entity'])<span>Entity: <b>{{ $meta['entity'] }}</b> @if($meta['lei'])<code>{{ $meta['lei'] }}</code>@endif</span>@endif
                    <span>Period: <b>{{ $meta['period'] }}</b></span>
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
                    <span class="vr-verdict pass"><i class="fas fa-circle-check"></i> Passed — ready to file</span>
                @else
                    <span class="vr-verdict fail"><i class="fas fa-circle-xmark"></i> {{ $errorsFailed }} error(s) to fix @if($warnFailed) · {{ $warnFailed }} warning(s)@endif</span>
                @endif
                <button type="button" class="btn btn-sm btn-outline-primary vr-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>

        @if($error)
            <div class="vr-empty" style="color:#b91c1c"><i class="fas fa-triangle-exclamation"></i> {{ $error }}</div>
        @else
            <section class="vr-sum">
                <div class="vr-stat total"><div class="k">Total rules</div><div class="v">{{ $s['total'] ?? $rules->count() }}</div></div>
                <div class="vr-stat passed"><div class="k">Passed</div><div class="v">{{ $s['passed'] ?? 0 }}</div></div>
                <div class="vr-stat failed"><div class="k">Failed</div><div class="v">{{ $s['failed'] ?? 0 }}</div></div>
                <div class="vr-stat ind"><div class="k">Indeterminate</div><div class="v">{{ $s['indeterminate'] ?? 0 }}</div></div>
                <div class="vr-stat unsup"><div class="k">Unsupported</div><div class="v">{{ $s['unsupported'] ?? 0 }}</div></div>
            </section>

            <div class="vr-bar">
                <label>Status
                    <select id="vrStatus">
                        <option value="actionable">To review (failed + indeterminate)</option>
                        <option value="failed">Failed</option>
                        <option value="passed">Passed</option>
                        <option value="indeterminate">Indeterminate</option>
                        <option value="unsupported">Unsupported</option>
                        <option value="all">All</option>
                    </select>
                </label>
                <label>Table
                    <select id="vrTable"><option value="">All tables</option>@foreach($allTables as $tb)<option value="{{ $tb }}">{{ $tb }}</option>@endforeach</select>
                </label>
                <label><input type="checkbox" id="vrCross"> Cross-table only</label>
                <div class="spacer"></div>
                <input id="vrSearch" placeholder="Search by rule code…" autocomplete="off">
                <span class="text-muted small" id="vrCount"></span>
            </div>

            <div class="vr-list" id="vrList">
                @forelse($rules as $r)
                    <div class="vr-rule {{ $r['status'] }}"
                         data-status="{{ $r['status'] }}" data-severity="{{ $r['severity'] }}"
                         data-cross="{{ $r['cross'] ? '1' : '0' }}" data-code="{{ strtolower($r['code']) }}"
                         data-tables="{{ strtolower(implode(' ', $r['tables'])) }}">
                        <div class="vr-rh">
                            <span class="vr-badge {{ $r['status'] }}">{{ $r['status'] }}</span>
                            <span class="vr-sev {{ $r['severity'] }}">{{ $r['severity'] }}</span>
                            <span class="vr-code">{{ $r['code'] }}</span>
                            @if($r['cross'])<span class="vr-badge vr-cross"><i class="fas fa-link"></i> cross-table</span>@endif
                            @if($r['tables'])<span class="vr-tables">@foreach($r['tables'] as $tb)<span class="vr-tbl">{{ $tb }}</span>@endforeach</span>@endif
                        </div>
                        @if($r['rule'])<div class="vr-expr">{{ $r['rule'] }}</div>@endif
                        @if($r['facts'])
                            <div class="vr-facts">
                                @foreach($r['facts'] as $f)
                                    <span class="vr-fact {{ $f['value'] === null ? 'null' : '' }}"><b>{{ $f['cell'] }}</b> = {{ $f['value'] === null ? '∅ empty' : $f['value'] }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($r['result'] !== null)<div class="vr-result">Computed result: <b>{{ $r['result'] }}</b>@if($r['error']) · {{ $r['error'] }}@endif</div>@endif
                    </div>
                @empty
                    <div class="vr-empty">No validation rules apply to this module in the active taxonomy.</div>
                @endforelse
            </div>
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
                    else if (st !== 'all') ok = (s === st);
                    if (ok && cross) ok = row.dataset.cross === '1';
                    if (ok && tb) ok = (' ' + row.dataset.tables + ' ').includes(tb);
                    if (ok && q) ok = row.dataset.code.includes(q);
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
