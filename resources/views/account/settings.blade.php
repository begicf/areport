@extends('layouts.app')

@section('title', 'Settings')

@push('styles')
    <style>
        .acc-page { padding: .9rem 1rem 1.6rem; max-width: 980px; }
        .acc-card { background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 1px 2px rgba(16,24,40,.04); padding: 1rem 1.1rem; margin-bottom: .8rem; }
        .acc-card h2 { font-size: .98rem; font-weight: 600; color: var(--ink); margin-bottom: .2rem; }
        .acc-card .sub { font-size: .8rem; color: var(--muted); margin-bottom: .9rem; }
        .acc-styles { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .5rem; margin-bottom: .9rem; }
        .acc-style { display: flex; gap: .55rem; align-items: flex-start; border: 1px solid var(--line); border-radius: 8px; padding: .55rem .7rem; cursor: pointer; margin: 0; }
        .acc-style:has(input:checked) { border-color: #2f557d; background: #f3f6fa; }
        .acc-style input { margin-top: .25rem; }
        .acc-style b { display: block; font-variant-numeric: tabular-nums; font-size: .95rem; color: var(--ink); }
        .acc-style span { font-size: .74rem; color: var(--muted); }
    </style>
@endpush

@section('content')
    <div class="acc-page ax-stack">
        <div class="ax-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-user-gear"></i> Your account</span>
                <h1 class="ax-title">Settings</h1>
                <p class="ax-sub">How AReport shows things to you. Reports, exports and validation are the same whatever you choose here.</p>
            </div>
        </div>

        @include('flash.flash-message')

        @php
            $hints = [
                'en' => 'Point for decimals, comma between thousands (UK, US, Ireland)',
                'eu' => 'Comma for decimals, point between thousands (Germany, Austria, Italy, Spain, Bosnia and Herzegovina, …)',
                'space' => 'Comma for decimals, space between thousands (France, Poland, Nordics, Czechia, …)',
                'ch' => "Point for decimals, apostrophe between thousands (Switzerland, Liechtenstein)",
            ];
        @endphp
        <form method="post" action="{{ route('account.settings.update') }}" class="acc-card">
            @csrf
            <h2>Number format</h2>
            <p class="sub">How amounts are shown on the forms and in the validation report, and how what you type or paste is read: with 1.234,56, “1.250” is one thousand two hundred fifty; with 1,234.56 it is one point two five.</p>
            <div class="acc-styles">
                @foreach ($styles as $key => [$decimal, $group, $label])
                    <label class="acc-style">
                        <input type="radio" name="number_format" value="{{ $key }}" @checked($style === $key)>
                        <div><b>{{ $label }}</b><span>{{ $hints[$key] }}@if($key === \App\Support\NumberFormat::DEFAULT) — the default @endif</span></div>
                    </label>
                @endforeach
            </div>
            @error('number_format')<div class="text-danger small mb-2"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>@enderror
            <button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> Save</button>
        </form>
    </div>
@endsection
