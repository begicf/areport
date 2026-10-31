@extends('layouts.app')

@push('styles')
    <style>
        .mx-page { padding: .9rem 1rem 1.6rem; }
        /* Owner filter: one segmented bar, a colour per owner (as the home page's status bar). */
        .mx-owners { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; overflow: hidden; }
        .mx-owner { --c: #2f557d; --tint: #eef3f9; text-align: left; border: 0; border-left: 1px solid #e2e8f0; background: #fff; padding: .55rem .85rem;
            cursor: pointer; transition: background .15s ease; display: grid; grid-template-columns: 1fr auto; align-items: center; column-gap: .6rem; }
        .mx-owner:first-child { border-left: 0; }
        .mx-owner:hover { background: #f8fafc; }
        .mx-owner.active { background: var(--tint); }
        .mx-owner .k { font-size: .66rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #64748b; display: flex; align-items: center; gap: .4rem; }
        .mx-owner .k::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--c); }
        .mx-owner.active .k { color: var(--c); }
        .mx-owner .v { grid-row: 1 / span 2; grid-column: 2; font-size: 1.45rem; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; color: var(--c); }
        .mx-owner .d { font-size: .68rem; color: #94a3b8; margin-top: .1rem; }
        .mx-owner.o-eba { --c: #2f557d; --tint: #eef3f9; }
        .mx-owner.o-own { --c: #2e7d4f; --tint: #e9f6ee; }
        .mx-owner.o-all { --c: #475569; --tint: #f1f5f9; }

        .mx-layout { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: .8rem; align-items: start; }
        .mx-panel { background: #fff; border: 1px solid var(--line, #e2e8f0); border-radius: 10px; box-shadow: 0 1px 2px rgba(16,24,40,.04); }
        .mx-panel-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .7rem .9rem; border-bottom: 1px solid #eef2f7; }
        .mx-panel-head h2 { font-size: .9rem; font-weight: 600; margin: 0; color: #0f172a; }
        .mx-search { position: relative; flex: 1; max-width: 360px; }
        .mx-search i { position: absolute; left: .65rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .78rem; }
        .mx-search input { padding-left: 1.9rem; height: 32px; font-size: .8rem; }
        .mx-tools { display: flex; gap: .4rem; }
        .mx-tools .btn { font-size: .76rem; }
        .mx-tree { padding: .4rem .5rem .8rem; min-height: 320px; }

        /* jstree: compact rows, subtle hover / selection, calm icon colours */
        #modules.jstree .jstree-anchor { font-size: .84rem; color: #1f2937; height: 30px; line-height: 30px; }
        #modules.jstree .jstree-wholerow { height: 30px; }
        #modules.jstree .jstree-icon, #modules.jstree .jstree-ocl { height: 30px; line-height: 30px; background-position-y: center; }
        #modules.jstree .jstree-node { min-height: 30px; line-height: 30px; }
        #modules.jstree .jstree-wholerow-hovered { background: #f8fafc !important; background-image: none !important; }
        #modules.jstree .jstree-wholerow-clicked { background: #eef3f9 !important; background-image: none !important; box-shadow: inset 3px 0 0 #2f557d; }
        #modules.jstree .jstree-hovered, #modules.jstree .jstree-clicked { background: transparent !important; box-shadow: none !important; }
        #modules.jstree .jstree-clicked { color: #1d3d5c; font-weight: 600; }
        #modules.jstree > .jstree-container-ul > .jstree-node > .jstree-anchor { font-weight: 600; }
        #modules .jstree-icon.fa-folder { color: #2f557d !important; }
        #modules .jstree-icon.fa-box.text-info { color: #c2731f !important; }
        #modules .jstree-icon.fa-box.text-danger { color: #2f557d !important; }
        #modules .jstree-icon.fa-layer-group { color: #64748b !important; }
        #modules .jstree-icon.fa-table { color: #2e7d4f !important; }
        #modules .mx-own-node > .jstree-anchor .jstree-icon.fa-folder { color: #2e7d4f !important; }
        .mx-tag { display: inline-block; margin-left: .45rem; font-size: .58rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
            padding: 0 .35rem; line-height: 1.5; border-radius: 4px; vertical-align: 1px; }
        .mx-tag-eba { background: #eef3f9; color: #2f557d; }
        .mx-tag-own { background: #e9f6ee; color: #2e7d4f; }
        .mx-hidden { display: none !important; }

        /* Right panel */
        .mx-side { position: sticky; top: .8rem; }
        .mx-side .app-module-instance-state { padding: .9rem; }
        .mx-steps { list-style: none; margin: 0 0 .8rem; padding: 0; display: grid; gap: .45rem; }
        .mx-steps li { display: flex; gap: .6rem; align-items: flex-start; font-size: .8rem; color: #475569; }
        .mx-steps b { flex: 0 0 22px; height: 22px; border-radius: 50%; background: #eef3f9; color: #2f557d; font-size: .72rem; display: inline-flex; align-items: center; justify-content: center; }
        .mx-side .app-module-instance-empty, .mx-side .app-module-instance-card { border: 0; background: transparent; padding: 0; text-align: left; }
        .mx-side .app-module-instance-heading { font-size: .95rem; }
        .mx-side .btn-primary { width: 100%; }
        @media (max-width: 1100px) { .mx-layout { grid-template-columns: 1fr; } .mx-side { position: static; } }
    </style>
@endpush

@section('content')
    <div class="mx-page ax-stack">
        <div class="ax-head">
            <div>
                <span class="ax-eyebrow"><i class="fas fa-diagram-project"></i> Module explorer</span>
                <h1 class="ax-title">Modules</h1>
                <p class="ax-sub">Choose a framework, its version and a module to start a report.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="ax-tax-label">Active taxonomy</span>
                @if(!empty($activeTaxonomy))
                    <span class="ax-tax-badge">{{ $activeTaxonomy->name }}</span>
                @else
                    <span class="ax-tax-badge is-unset">Not set</span>
                @endif
                <a href="{{ url('/taxonomy/managing') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-sliders"></i> Manage taxonomy</a>
            </div>
        </div>

        @include('flash.flash-message')

        <section class="mx-owners" aria-label="Frameworks by owner">
            <button type="button" class="mx-owner o-all active" data-owner="all"><div class="k">All frameworks</div><div class="v" id="ownAll">–</div><div class="d">in the active taxonomy</div></button>
            <button type="button" class="mx-owner o-eba" data-owner="eba"><div class="k">EBA</div><div class="v" id="ownEba">–</div><div class="d">European Banking Authority frameworks</div></button>
            <button type="button" class="mx-owner o-own" data-owner="own"><div class="k">Own</div><div class="v" id="ownOwn">–</div><div class="d">generated in the DPM Architect</div></button>
        </section>

        <div class="mx-layout">
            <section class="mx-panel">
                <div class="mx-panel-head" role="toolbar" aria-label="Module tree tools">
                    <div class="mx-search">
                        <label for="moduleTreeSearch" class="visually-hidden">Search modules</label>
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input id="moduleTreeSearch" type="search" class="form-control" placeholder="Search frameworks, modules, tables…" autocomplete="off">
                    </div>
                    <div class="mx-tools">
                        <button type="button" id="moduleTreeExpandAll" class="btn btn-outline-secondary btn-sm app-tree-toggle-btn" data-expanded="false" aria-pressed="false">
                            <i class="fas fa-expand-arrows-alt"></i> <span>Expand all</span>
                        </button>
                        <button type="button" id="moduleTreeRefresh" class="btn btn-outline-secondary btn-sm app-tree-toggle-btn">
                            <i class="fas fa-rotate-right"></i> <span>Refresh</span>
                        </button>
                    </div>
                </div>
                <div class="mx-tree">
                    <div id="modules" class="app-tree-shell"
                        data-taxonomy-folder="{{ $activeTaxonomy->folder ?? 'default' }}"
                        data-pro-translation-url="{{ Route::has('areport-pro.dpm-architect.index') ? route('areport-pro.dpm-architect.index') : '' }}"
                    ><div class="dpm-loading">Loading modules…</div></div>
                </div>
            </section>

            <section class="mx-panel mx-side app-module-instance-panel">
                <div class="mx-panel-head"><h2>Create instance</h2></div>
                <div id="instance" class="app-module-instance-state">
                    <div class="app-module-instance-empty">
                        <ol class="mx-steps">
                            <li><b>1</b><span>Open a framework and its version in the tree.</span></li>
                            <li><b>2</b><span>Select a module — its table groups load here.</span></li>
                            <li><b>3</b><span>Choose the reference date, entity and tables, and continue.</span></li>
                        </ol>
                        <button type="button" id="moduleInstanceCreate" class="btn btn-primary" disabled>
                            <i class="fas fa-table"></i> Create instance
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @include('components.module')
@endsection

@push('scripts')
    <script>
        // Owner tags and the owner filter on the framework level of the module tree.
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.jQuery) return;
            const $tree = jQuery('#modules');
            let owner = 'all';
            const ownerOf = id => String(id || '').indexOf('eba_') === 0 ? 'eba' : 'own';

            function decorate() {
                const tree = $tree.jstree(true);
                if (!tree) return;
                const roots = tree.get_node('#').children || [];
                let eba = 0, own = 0;
                roots.forEach(function (id) {
                    const o = ownerOf(id);
                    o === 'eba' ? eba++ : own++;
                    const li = tree.get_node(id, true);
                    if (!li || !li.length) return;
                    li.toggleClass('mx-own-node', o === 'own');
                    li.toggleClass('mx-hidden', owner !== 'all' && owner !== o);
                    const a = li.children('.jstree-anchor');
                    if (!a.find('.mx-tag').length) {
                        a.append('<span class="mx-tag mx-tag-' + o + '">' + (o === 'eba' ? 'EBA' : 'Own') + '</span>');
                    }
                });
                document.getElementById('ownAll').textContent = roots.length;
                document.getElementById('ownEba').textContent = eba;
                document.getElementById('ownOwn').textContent = own;
            }
            $tree.on('loaded.jstree redraw.jstree after_open.jstree refresh.jstree search.jstree clear_search.jstree', function () { setTimeout(decorate, 0); });

            document.querySelectorAll('.mx-owner').forEach(function (b) {
                b.addEventListener('click', function () {
                    owner = b.dataset.owner;
                    document.querySelectorAll('.mx-owner').forEach(x => x.classList.toggle('active', x === b));
                    decorate();
                });
            });
        });
    </script>
@endpush
