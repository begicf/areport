@extends('layouts.app')

@push('styles')
    <style>
        .tx-page { padding: .9rem 1rem 1.6rem; }
        .tx-page .app-panel { padding: 0; overflow: hidden; }
        .tx-page table.table { margin: 0; }
        .tx-page table.table thead th { font-size: .6rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #94a3b8; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: .5rem .75rem; white-space: nowrap; }
        .tx-page table.table td { padding: .7rem .75rem; border-color: #f1f5f9; }
        .tx-page tr.is-active td { background: #fdf8f1; }
        .tx-page tr.is-active td:first-child { box-shadow: inset 3px 0 0 #e4902f; }
        .tx-page .tax-name { font-weight: 600; color: #0f172a; }
        .tx-page .tax-pkg { font-size: .76rem; color: #64748b; }
        .tx-page .tax-active-tag { font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; background: #fdf3e7; color: #b8691b; border-radius: 4px; padding: 0 .35rem; margin-left: .35rem; }
        .tx-page .app-form-stack { gap: 0; }
        .tx-page .tx-actions { display: flex; justify-content: flex-end; padding: .7rem .9rem; border-top: 1px solid #eef2f7; }
        .tax-fw { display: inline-block; font-size: .72rem; font-weight: 600; color: var(--blue); background: #eef3f9; border: 1px solid #dbe4ef; border-radius: 5px; padding: .08rem .45rem; margin: 0 .25rem .25rem 0; }
        .tax-fw small { font-weight: 500; color: #64748b; }
        .tax-fws { white-space: normal !important; min-width: 420px; }
        .tax-fw.own { color: #2e7d4f; background: #e9f6ee; border-color: #c4e6d0; }
        .tax-pkgs { margin-top: .45rem; display: flex; flex-direction: column; gap: .25rem; }
        .tax-pkgs .p { font-size: .74rem; color: #334155; }
        .tax-pkgs .p i { color: #2e7d4f; }
        .tax-pkgs .p small { color: #94a3b8; }
        .tax-add summary { font-size: .74rem; color: var(--blue); cursor: pointer; list-style: none; display: inline-flex; align-items: center; gap: .3rem; margin-top: .35rem; }
        .tax-add summary::-webkit-details-marker { display: none; }
        .tax-add form { display: flex; gap: .4rem; align-items: center; margin-top: .35rem; }
        .tax-add input[type=file] { font-size: .74rem; max-width: 280px; }
        .tax-add .hint { font-size: .7rem; color: #64748b; margin-top: .2rem; }
    </style>
@endpush

@section('content')
    @php
        $activeTax = $tax->firstWhere('active', true);
        $activeFw = $activeTax ? ($frameworks[$activeTax->id] ?? []) : [];
    @endphp
    <div class="tx-page ax-stack">
        <div class="ax-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-sitemap"></i> Taxonomy management</span>
                <h1 class="ax-title">Taxonomies</h1>
                <p class="ax-sub">Choose the taxonomy you report against and remove packages you no longer need.</p>
            </div>
        </div>

        @include('flash.flash-message')

        <section class="ax-seg" aria-label="Taxonomy overview">
            <div class="ax-seg-item c-slate"><div class="k">Packages</div><div class="v">{{ $tax->count() }}</div><div class="d">uploaded to the workspace</div></div>
            <div class="ax-seg-item c-amber active"><div class="k">Active</div><div class="v txt">{{ $activeTax->name ?? '—' }}</div><div class="d">used for new reports</div></div>
            <div class="ax-seg-item c-blue"><div class="k">Frameworks</div><div class="v">{{ count($activeFw) }}</div><div class="d">in the active taxonomy</div></div>
            <div class="ax-seg-item c-green"><div class="k">Versions</div><div class="v">{{ collect($activeFw)->flatten()->count() }}</div><div class="d">framework versions it contains</div></div>
        </section>

        <section class="app-panel">
            <form method="post" class="app-form-stack">
                @csrf

                <div class="app-table-shell">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Active</th>
                            <th>Display name</th>
                            <th>Original package</th>
                            <th>Frameworks</th>
                            <th class="text-center">Remove</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tax as $row)
                            <tr class="{{ $row->active ? 'is-active' : '' }}">
                                <td class="text-center">
                                    <div class="form-check d-inline-flex align-items-center justify-content-center">
                                        <input class="form-check-input" type="radio" name="tax_active"
                                               id="tax_active_{{ $row->id }}"
                                               value="{{ $row->id }}" {{ $row->active == true ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <label class="form-check-label tax-name mb-0" for="tax_active_{{ $row->id }}">{{ $row->name }}</label>
                                    @if($row->active)<span class="tax-active-tag">active</span>@endif
                                </td>
                                <td class="tax-pkg">{{ $row->original_name }}</td>
                                <td class="tax-fws">
                                    @forelse(($frameworks[$row->id] ?? []) as $fw => $versions)
                                        @php
                                            // Release folders (4.0, 4.2) first; older dated packages (its-2016-…) as a count.
                                            $numbered = array_values(array_filter($versions, fn ($v) => preg_match('/^\d/', $v)));
                                            $older = count($versions) - count($numbered);
                                        @endphp
                                        <span class="tax-fw{{ str_contains($fw, ' · ') ? ' own' : '' }}" title="{{ $fw }}: {{ implode(', ', $versions) }}">{{ $fw }} <small>{{ implode(' · ', $numbered) }}@if($older){{ $numbered ? ' · ' : '' }}+{{ $older }} older @endif</small></span>
                                    @empty
                                        <span class="text-muted small">No EBA frameworks found in this package.</span>
                                    @endforelse
                                    @if(!empty($frameworks[$row->id]))
                                        <div class="text-muted small mt-1">{{ count($frameworks[$row->id]) }} frameworks · {{ collect($frameworks[$row->id])->flatten()->count() }} versions</div>
                                    @endif
                                    @if($row->packages->isNotEmpty())
                                        <div class="tax-pkgs">
                                            @foreach($row->packages as $p)
                                                <span class="p" title="{{ $p->identifier }}"><i class="fas fa-puzzle-piece"></i> {{ $p->name ?: $p->original_name }} @if($p->version)<b>{{ $p->version }}</b>@endif
                                                    <small>· {{ count($p->entry_points ?? []) }} entry point(s) · {{ $p->files }} files · added {{ $p->created_at?->format('d.m.Y') }}</small></span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if(config('areport.upload'))
                                        <details class="tax-add">
                                            <summary><i class="fas fa-plus"></i> Add extension package</summary>
                                            <div class="tax-add-box" data-action="{{ route('taxonomy.package', $row) }}">
                                                <div class="d-flex gap-2 align-items-center mt-1">
                                                    <input type="file" accept=".zip" class="tax-add-file">
                                                    <button type="button" class="btn btn-sm btn-outline-primary tax-add-btn"><i class="fas fa-upload"></i> Add</button>
                                                </div>
                                                <div class="hint">A taxonomy package (zip with META-INF/taxonomyPackage.xml) built on this one — e.g. a national regulator's. Its modules appear next to EBA's.</div>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button onclick='deleteTaxonomy(@json($row))' {{ ($row->active == true || ! config('areport.upload')) ? 'disabled' : '' }}
                                            type="button" class="btn btn-sm btn-outline-danger" title="Remove this package">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="tx-actions">
                    <button class="btn btn-sm btn-primary px-3" type="submit">
                        <i class="fas fa-check"></i>
                        Set active taxonomy
                    </button>
                </div>
            </form>
        </section>
    </div>

    @include('components.please-wait')

    <script type="text/javascript">
        // The add-package box sits inside the "active taxonomy" form, so it posts on its own.
        document.querySelectorAll('.tax-add-box').forEach(function (box) {
            box.querySelector('.tax-add-btn').addEventListener('click', function () {
                const file = box.querySelector('.tax-add-file').files[0];
                if (!file) { box.querySelector('.tax-add-file').click(); return; }
                // A normal multipart post (flash message on return): move the chosen file into its own form.
                const f = document.createElement('form');
                f.method = 'post'; f.action = box.dataset.action; f.enctype = 'multipart/form-data'; f.hidden = true;
                f.innerHTML = '<input type="hidden" name="_token" value="' + document.querySelector('meta[name="csrf-token"]').content + '">';
                const input = box.querySelector('.tax-add-file');
                input.name = 'file';
                f.appendChild(input);
                document.body.appendChild(f);
                window.appModal.show('pleaseWaitDialog');
                f.submit();
            });
        });

        function deleteTaxonomy(selectedTax) {
            window.appModal.show('pleaseWaitDialog');

            axios.post('/taxonomy/delete', {
                id: selectedTax.id
            }).then(function () {
                window.location.reload();
            }).catch(function () {
                window.appModal.hide('pleaseWaitDialog');
            });
        }
    </script>
@endsection
