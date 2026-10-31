@extends('layouts.app')

@push('styles')
    <style>
        .vhist { padding: 1rem 1.2rem 2.5rem; max-width: 1280px; margin: 0 auto; }
        .vhist-meta { display: flex; flex-wrap: wrap; gap: .3rem 1.4rem; font-size: .8rem; color: var(--muted); margin: .5rem 0 1rem; }
        .vhist-meta b { color: var(--ink); font-weight: 600; }
        .vhist-card { border: 1px solid var(--line); border-radius: 10px; background: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.04); overflow: hidden; }
        .vhist-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .vhist-table th { font-size: .62rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--faint);
            text-align: left; padding: .55rem .75rem; background: #f8fafc; border-bottom: 1px solid var(--line); }
        .vhist-table td { padding: .45rem .75rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .vhist-table tr:last-child td { border-bottom: 0; }
        .vhist-when { white-space: nowrap; color: #52606d; font-variant-numeric: tabular-nums; }
        .vhist-cell { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .74rem; white-space: nowrap; }
        .vhist-val { font-variant-numeric: tabular-nums; word-break: break-word; }
        .vhist-old { color: #b91c1c; text-decoration: line-through; text-decoration-color: rgba(185,28,28,.4); }
        .vhist-new { color: #166534; font-weight: 600; }
        .vhist-none { color: var(--faint); font-style: italic; }
        .vhist-kind { font-size: .62rem; font-weight: 700; padding: .05rem .4rem; border-radius: 4px; text-transform: uppercase; letter-spacing: .04em; }
        .vhist-kind.added { background: #e8f6ee; color: #166534; }
        .vhist-kind.changed { background: #eef3f9; color: #2f557d; }
        .vhist-kind.removed { background: #fdecec; color: #b91c1c; }
        .vhist-empty { padding: 2rem; text-align: center; color: var(--faint); }
        .vhist-pager { margin-top: .8rem; }
    </style>
@endpush

@section('content')
    <div class="vhist">
        <span class="ax-eyebrow"><i class="fas fa-clock-rotate-left"></i> Change history</span>
        <h1 class="ax-title" style="font-size:1.3rem">{{ $module->module_name ?: 'Instance' }}</h1>
        <div class="vhist-meta">
            <span>Reference date: <b>{{ $module->period ? $module->period->format('d.m.Y') : '—' }}</b></span>
            <span>Taxonomy: <b>{{ $module->taxonomy->name ?? '—' }}</b></span>
            <span>Changes: <b>{{ $audits->total() }}</b></span>
        </div>

        <div class="vhist-card">
            @if($audits->isEmpty())
                <div class="vhist-empty">No changes recorded yet. Every saved cell change appears here with its old and new value.</div>
            @else
                <table class="vhist-table">
                    <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Table</th>
                        <th>Cell</th>
                        <th>Change</th>
                        <th>Old value</th>
                        <th>New value</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($audits as $a)
                        @php
                            $kind = is_null($a->old_value) ? 'added' : (is_null($a->new_value) ? 'removed' : 'changed');
                            $tableCode = strtoupper(pathinfo((string) $a->table_path, PATHINFO_FILENAME));
                        @endphp
                        <tr>
                            <td class="vhist-when">{{ $a->created_at?->format('d.m.Y H:i:s') }}</td>
                            <td>{{ $a->user->name ?? $a->user->email ?? '—' }}</td>
                            <td>{{ $tableCode }}@if($a->cr_sheet_code && $a->cr_sheet_code !== '000') <span class="vhist-none">· sheet {{ $a->cr_sheet_code }}</span>@endif</td>
                            <td class="vhist-cell">{{ $a->cr_code }}</td>
                            <td><span class="vhist-kind {{ $kind }}">{{ $kind }}</span></td>
                            <td class="vhist-val">@if(is_null($a->old_value))<span class="vhist-none">—</span>@else<span class="vhist-old">{{ $a->old_value }}</span>@endif</td>
                            <td class="vhist-val">@if(is_null($a->new_value))<span class="vhist-none">—</span>@else<span class="vhist-new">{{ $a->new_value }}</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="vhist-pager">{{ $audits->links() }}</div>
    </div>
@endsection
