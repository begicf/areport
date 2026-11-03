@extends('layouts.app')

@push('styles')
    <style>
        .ent-page { padding: .9rem 1rem 1.6rem; max-width: 980px; }
        .ent-card { background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 1px 2px rgba(16,24,40,.04); padding: 1rem 1.1rem; margin-bottom: .8rem; }
        .ent-card h2 { font-size: .98rem; font-weight: 600; color: var(--ink); margin-bottom: .2rem; }
        .ent-card .sub { font-size: .8rem; color: var(--muted); margin-bottom: .9rem; }
        .ent-card label { font-size: .76rem; font-weight: 600; color: #52606d; margin-bottom: .2rem; display: block; }
        .ent-hint { font-size: .72rem; color: var(--faint); margin-top: .2rem; }
        .ent-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .5rem; }
        .ent-form .f-lei { flex: 0 1 290px; } .ent-form .f-name { flex: 1 1 240px; } .ent-form .f-cc { flex: 0 0 90px; }
        .ent-default { font-size: .62rem; font-weight: 700; text-transform: uppercase; padding: .08rem .4rem; border-radius: 999px; background: #dcfce7; color: #166534; }
        @media (max-width: 640px) { .ent-form .f-lei, .ent-form .f-name { flex: 1 1 100%; } }
    </style>
@endpush

@section('content')
    <div class="ent-page ax-stack">
        <div class="ax-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-building-columns"></i> Workspace</span>
                <h1 class="ax-title">Reporting entities</h1>
                <p class="ax-sub">Who you report for. The LEI goes into every instance and into the name of the filing package.</p>
            </div>
        </div>

        @include('flash.flash-message')

        <div class="ent-card">
            <h2>Add an entity</h2>
            <p class="sub">Enter a LEI and fetch its legal name and country from the GLEIF register. The default entity is preselected when you create an instance.</p>

            <form method="post" action="{{ route('entities.store') }}" class="ent-form">
                @csrf
                <div class="f-lei">
                    <label for="entLei">LEI code</label>
                    <div class="input-group">
                        <input class="form-control" name="lei" id="entLei" maxlength="20" placeholder="20-character LEI" value="{{ old('lei') }}" autocomplete="off">
                        <button class="btn btn-outline-primary" type="button" id="entLookup" title="Fetch legal name from GLEIF"><i class="fas fa-magnifying-glass"></i></button>
                    </div>
                </div>
                <div class="f-name">
                    <label for="entName">Legal name</label>
                    <input class="form-control" name="name" id="entName" placeholder="Fetched from GLEIF (editable)" value="{{ old('name') }}">
                </div>
                <div class="f-cc">
                    <label for="entCountry">Country</label>
                    <input class="form-control" name="country" id="entCountry" maxlength="2" placeholder="FR" value="{{ old('country') }}" title="Legal address country (from GLEIF); the default authority country of its reports">
                </div>
                <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Add</button>
            </form>
            @foreach (['lei', 'name', 'country'] as $field)
                @error($field)<div class="text-danger small mt-1"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>@enderror
            @endforeach
            <div class="ent-hint mt-1" id="entMsg">e.g. 5493001KJTIIGC8Y1R12 — enter a LEI and click the search to fetch the name.</div>
        </div>

        <div class="ent-card">
            <h2>Your entities <span class="text-muted fw-normal">· {{ $entities->count() }}</span></h2>
            <div class="table-responsive mt-2">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th style="width:90px">Default</th><th>Legal name</th><th style="width:90px">Country</th><th style="width:230px">LEI</th><th style="width:100px" class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse($entities as $e)
                            <tr>
                                <td>
                                    @if($e->is_default)
                                        <span class="ent-default">default</span>
                                    @else
                                        <form method="post" action="{{ route('entities.default', $e->id) }}" class="d-inline">
                                            @csrf @method('patch')
                                            <button class="btn btn-sm btn-outline-secondary" type="submit" title="Set as default">set</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $e->name }}</td>
                                <td>{{ $e->country ?: '—' }}</td>
                                <td><code>{{ $e->lei }}</code>@unless(\AReportDpmXBRL\Library\Lei::isValid($e->lei)) <span class="text-danger small" title="Fails the ISO 17442 check digits"><i class="fas fa-triangle-exclamation"></i> invalid</span>@endunless</td>
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#entEdit{{ $e->id }}" aria-label="Edit {{ $e->name }}"><i class="fas fa-pen"></i></button>
                                    <form method="post" action="{{ route('entities.destroy', $e->id) }}" class="d-inline" onsubmit="return confirm(@js('Remove '.$e->name.'?'));">
                                        @csrf @method('delete')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove {{ $e->name }}"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @php($bag = $errors->getBag('edit'.$e->id))
                            <tr class="collapse {{ $bag->any() ? 'show' : '' }}" id="entEdit{{ $e->id }}">
                                <td></td>
                                <td colspan="4">
                                    <form method="post" action="{{ route('entities.update', $e->id) }}" class="ent-form py-1">
                                        @csrf @method('patch')
                                        <div class="f-name">
                                            <label for="entName{{ $e->id }}">Legal name</label>
                                            <input class="form-control form-control-sm" name="name" id="entName{{ $e->id }}" value="{{ $bag->any() ? old('name') : $e->name }}">
                                        </div>
                                        <div class="f-cc">
                                            <label for="entCountry{{ $e->id }}">Country</label>
                                            <input class="form-control form-control-sm" name="country" id="entCountry{{ $e->id }}" maxlength="2" value="{{ $bag->any() ? old('country') : $e->country }}" placeholder="FR">
                                        </div>
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                        @foreach ($bag->all() as $message)<div class="text-danger small w-100"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>@endforeach
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted small">No entities yet — add one above.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const lei = document.getElementById('entLei');
            const name = document.getElementById('entName');
            const msg = document.getElementById('entMsg');
            const btn = document.getElementById('entLookup');
            const token = document.querySelector('meta[name="csrf-token"]').content;

            function lookup() {
                const code = (lei.value || '').trim().toUpperCase();
                if (!/^[A-Z0-9]{20}$/.test(code)) { msg.textContent = 'Enter a valid 20-character LEI.'; msg.style.color = '#b45309'; return; }
                msg.textContent = 'Looking up GLEIF…'; msg.style.color = '';
                btn.disabled = true;
                fetch(@json(route('entities.lei-lookup')), {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify({ lei: code })
                }).then(r => r.json().then(d => ({ ok: r.ok, d }))).then(({ d }) => {
                    btn.disabled = false;
                    if (!d.ok) { msg.textContent = d.error || d.message || 'Lookup failed.'; msg.style.color = '#b91c1c'; return; }
                    name.value = d.name;
                    lei.value = d.lei;
                    if (d.country) document.getElementById('entCountry').value = d.country;
                    const lapsed = d.status && d.status !== 'ISSUED';
                    msg.innerHTML = '<i class="fas ' + (lapsed ? 'fa-triangle-exclamation' : 'fa-circle-check') + '" style="color:' + (lapsed ? '#b45309' : '#16a34a') + '"></i> '
                        + (d.country ? d.country + ' · ' : '') + 'name fetched from GLEIF'
                        + (d.status ? ' · registration ' + d.status + (lapsed ? ' — the LEI is not kept current' : '') : '');
                    msg.style.color = lapsed ? '#b45309' : '#16a34a';
                }).catch(() => { btn.disabled = false; msg.textContent = 'Lookup request failed.'; msg.style.color = '#b91c1c'; });
            }

            btn.addEventListener('click', lookup);
            lei.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookup(); } });
        })();
    </script>
@endpush
