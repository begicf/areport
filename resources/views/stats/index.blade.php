@extends('layouts.app')

@push('styles')
    <style>
        .st-page { padding: .9rem 1rem 1.6rem; max-width: 1180px; }
        .st-card { background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 1px 2px rgba(16,24,40,.04); padding: 1rem 1.1rem; margin-bottom: .8rem; }
        .st-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .6rem; }
        .st-tile { border: 1px solid var(--line); border-radius: 8px; padding: .55rem .7rem; }
        .st-tile b { display: block; font-size: 1.35rem; font-weight: 600; color: var(--ink); font-variant-numeric: tabular-nums; }
        .st-tile span { font-size: .74rem; color: var(--muted); }
        .st-table { width: 100%; font-size: .78rem; border-collapse: collapse; }
        .st-table th { font-size: .66rem; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); font-weight: 600; padding: .35rem .45rem; border-bottom: 1px solid var(--line); text-align: right; white-space: nowrap; }
        .st-table td { padding: .3rem .45rem; border-bottom: 1px solid #f2f4f7; text-align: right; font-variant-numeric: tabular-nums; }
        .st-table th:first-child, .st-table td:first-child { text-align: left; }
        .st-table td.zero { color: #cbd2d9; }
        .st-bar { display: inline-block; height: .5rem; border-radius: 2px; background: #2563eb; vertical-align: middle; margin-right: .35rem; }
    </style>
@endpush

@section('content')
    <div class="st-page ax-stack">
        <div class="ax-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-chart-column"></i> Workspace</span>
                <h1 class="ax-title">Usage</h1>
                <p class="ax-sub">How much this installation is used, per day over the last 30 days. Only counts are kept — no e-mails, IP addresses or who did what — and they stay in this installation.</p>
            </div>
        </div>

        <div class="st-card">
            <div class="st-tiles">
                @foreach($metrics as $metric => $label)
                    <div class="st-tile"><b>{{ number_format($totals[$metric]) }}</b><span>{{ $label }}{{ $metric === 'active_users' ? ' (user-days)' : '' }}</span></div>
                @endforeach
            </div>
        </div>

        <div class="st-card">
            <div class="table-responsive">
                <table class="st-table">
                    <thead><tr><th>Day</th>@foreach($metrics as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($days as $day => $counts)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($day)->format('D d.m.Y') }}</td>
                                @foreach($counts as $metric => $n)
                                    <td class="{{ $n ? '' : 'zero' }}">
                                        @if($metric === 'active_users' && $n)<span class="st-bar" style="width:{{ max(2, round(60 * $n / $max)) }}px"></span>@endif{{ $n }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
