@extends('layouts.app')

@section('shell_class', 'app-shell app-shell-fluid')

@section('content')

    <style>
        .app-report-validation { margin: .6rem 0; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: .6rem .7rem; font-size: .8rem; }
        .dpm-val-head { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; }
        .dpm-val-pill { font-size: .72rem; font-weight: 700; padding: .12rem .5rem; border-radius: 3px; }
        .dpm-val-pill.ok { background: #dcfce7; color: #166534; } .dpm-val-pill.err { background: #fee2e2; color: #b91c1c; }
        .dpm-val-pill.ind { background: #fef3c7; color: #b45309; } .dpm-val-pill.mut { background: #f1f5f9; color: #64748b; }
        .dpm-val-meta { font-size: .72rem; color: #94a3b8; margin-left: .2rem; }
        .dpm-val-close { margin-left: auto; border: 0; background: transparent; font-size: 1.1rem; line-height: 1; color: #94a3b8; cursor: pointer; }
        .dpm-val-note { font-size: .74rem; color: #b45309; margin: .4rem 0 .3rem; }
        .dpm-val-fails { max-height: 32vh; overflow: auto; border: 1px solid #f1f5f9; border-radius: 6px; }
        .dpm-val-fail { padding: .35rem .5rem; border-bottom: 1px solid #f1f5f9; }
        .dpm-val-fh { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; font-size: .76rem; }
        .dpm-val-rule { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0 .3rem; border-radius: 3px; font-size: .72rem; }
        .dpm-val-facts { display: flex; flex-wrap: wrap; gap: .3rem; margin: .25rem 0 0 1rem; }
        .dpm-val-fact { font-size: .72rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: .05rem .4rem; border-radius: 3px; font-variant-numeric: tabular-nums; }
        .dpm-val-fact b { color: #2f557d; }
        .dpm-sev-error { color: #dc2626; } .dpm-sev-warning { color: #d97706; }
        .dpm-val-ok { color: #16a34a; margin-top: .4rem; } .dpm-val-err { color: #dc2626; }
        /* The cells the validator flagged — what the reporter must fix. */
        #table_form td.dpm-cell-fail { background: #fee2e2 !important; outline: 2px solid #dc2626; outline-offset: -2px; }
        #table_form td.dpm-cell-fail input, #table_form td.dpm-cell-fail select { border-color: #dc2626 !important; background: #fff1f1; }
.app-typed-sheet { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
        .app-typed-sheet .app-sheet-select { max-width: 320px; }
        .app-typed-sheet-add { display: flex; gap: .35rem; }
        .app-typed-sheet-add input { width: 200px; }
        #table_form td.dpm-cell-warn { background: #fff7ed !important; outline: 2px solid #f59e0b; outline-offset: -2px; }
        #table_form td.dpm-cell-warn input, #table_form td.dpm-cell-warn select { border-color: #f59e0b !important; background: #fffbeb; }
        #table_form td.dpm-cell-part { box-shadow: inset 0 0 0 1px #fca5a5; }
        .dpm-val-check { font-size: .74rem; color: #334155; margin: .2rem 0 0 1rem; display: flex; flex-wrap: wrap; gap: .25rem .45rem; align-items: baseline; font-variant-numeric: tabular-nums; }
        .dpm-val-check a { color: #2f557d; font-weight: 700; cursor: pointer; text-decoration: none; }
        .dpm-val-check .exp { color: #0f172a; font-weight: 600; }
        .dpm-val-check .diff { color: #b91c1c; }
        .dpm-val-check .parts { color: #94a3b8; font-size: .7rem; }
        .dpm-val-fix { border: 1px solid #cbd5e1; background: #fff; color: #2f557d; font-size: .68rem; font-weight: 600; padding: 0 .4rem; border-radius: 3px; cursor: pointer; }
        .dpm-val-fix:hover { background: #2f557d; color: #fff; border-color: #2f557d; }
        .dpm-val-more { font-size: .7rem; color: #94a3b8; margin: .15rem 0 0 1rem; }
        .dpm-val-recalc { margin-left: .4rem; }
        .dpm-val-toggle { background: none; border: 0; color: #2f557d; font-size: .76rem; font-weight: 600; padding: .2rem 0; cursor: pointer; }
        .dpm-val-live { font-size: .68rem; color: #94a3b8; margin-left: .4rem; }

        /* Dense reporting workspace — space is precious here */
        .app-report-toolbar-tight { padding: .4rem .6rem; gap: .5rem; align-items: center; }
        .app-report-rowbar { display: flex; gap: .4rem; padding: .45rem .6rem; border-top: 1px solid #eef1f4; }
        .app-report-controls-bar { gap: .6rem !important; }
        .app-report-panel, .app-report-controls { padding: .6rem .7rem !important; }
        /* Compact data grid + smaller font for large tables */
        #table_form table { font-size: .72rem; }
        #table_form table th, #table_form table td { padding: .16rem .32rem !important; }
        #table_form .xbrl-input, #table_form .xbrl-input-text, #table_form .xbrl-input-decimal,
        #table_form .xbrl-select, #table_form input, #table_form select {
            font-size: .72rem !important; padding: .04rem .2rem !important; min-height: 0 !important; }

        /* ===== Office-style ribbon toolbar ===== */
        .ribbon { display: flex; align-items: stretch; flex-wrap: wrap; gap: .2rem 0; background: #fff; border: 1px solid var(--line);
            border-radius: 9px; box-shadow: 0 1px 2px rgba(16,24,40,.04); padding: .35rem .5rem; margin-bottom: .6rem; position: relative; z-index: 5; }
        .rib-title { display: flex; flex-direction: column; justify-content: center; padding: .1rem .9rem .1rem .3rem; min-width: 0; }
        .rib-kicker { font-size: .56rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--amber); }
        .rib-name { font-size: .92rem; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 300px; }
        .rib-sep { width: 1px; background: var(--line); margin: .3rem .35rem; align-self: stretch; }
        .rib-group { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: .1rem .35rem; }
        .rib-btns { display: flex; align-items: center; gap: .25rem; }
        .rib-cap { order: -1; margin-bottom: .15rem; font-size: .54rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--faint); }
        .rib-title { justify-content: flex-start; }
        .rib-btn { display: flex; flex-direction: column; align-items: center; gap: .18rem; border: 1px solid transparent; background: transparent;
            border-radius: 7px; padding: .3rem .55rem; color: #3a4654; font-size: .66rem; font-weight: 500; cursor: pointer; min-width: 54px; line-height: 1.05; }
        .rib-btn i { font-size: 1.05rem; color: var(--blue); }
        .rib-btn:hover { background: var(--blue-soft); border-color: var(--blue-bd); color: var(--ink); }
        .rib-btn.dropdown-toggle::after { display: none; }
        .rib-select { height: 34px; border: 1px solid var(--line); border-radius: 7px; font-size: .82rem; padding: 0 .5rem; min-width: 160px; background: #fff; }
        .ribbon .dropdown-menu { font-size: .85rem; z-index: 1060; box-shadow: 0 10px 30px rgba(16,24,40,.14); }
        .rib-right { margin-left: auto; }
        /* Per-action colours so the ribbon reads at a glance */
        .rib-new i { color: #2f557d; }
        .rib-import i { color: #2f8a5a; }
        .rib-export i { color: #6d5ab5; }
        .rib-check i { color: #0f766e; }
        .rib-new:hover { background: #eef3f9; border-color: #dbe4ef; }
        .rib-import:hover { background: #e9f5ee; border-color: #cce6d8; }
        .rib-export:hover { background: #efecf8; border-color: #ddd6f1; }
        .rib-check:hover { background: #e7f4f2; border-color: #c7e6e1; }
        .rib-btn:disabled { opacity: .45; cursor: not-allowed; }
        .rib-meta { font-size: .66rem; color: var(--faint); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 340px; }
        .rib-meta b { font-weight: 600; color: #52606d; }
        .rib-save i { color: #2f557d; }
        .rib-save:hover { background: #eef3f9; border-color: #dbe4ef; }
        .rib-save.is-dirty i { color: #c2731f; }
        .rib-hist i { color: #52606d; }
        .save-status { font-size: .64rem; font-weight: 600; display: inline-flex; align-items: center; gap: .25rem; margin-top: .1rem; color: #2f8a5a; }
        .save-status.is-dirty { color: #c2731f; }
        .save-status.is-saving { color: #64748b; }
        .save-status.is-error { color: #dc2626; cursor: pointer; text-decoration: underline dotted; }
        .rib-settings i { color: #6b7280; }
        .rib-settings:hover { background: #f1f3f6; border-color: #e2e6eb; }
        #table_form input[data-num] { text-align: right; font-variant-numeric: tabular-nums; }
        #table_form input.num-bad { background: #fff1f1 !important; outline: 2px solid #dc2626; outline-offset: -2px; color: #991b1b; }
        #table_form td.kb-focus { box-shadow: inset 0 0 0 2px #2f557d; }
        .rib-prior i { color: #6d4fc2; }
        .prior-opt { border: 1px solid #e2e8f0; background: #f8fafc; padding: .55rem .7rem; font-size: .8rem; }
        .prior-opt b { display: block; color: #16202c; }
        .prior-opt span { display: block; color: #64748b; font-size: .74rem; }
        #table_form .pv { display: block; font-size: .64rem; line-height: 1.1; color: #64748b; text-align: right; white-space: nowrap; padding: 1px 3px 0; }
        #table_form .pv b { font-weight: 600; }
        #table_form .pv.up b { color: #2e7d4f; } #table_form .pv.down b { color: #b45309; }
        #table_form .pv.big { color: #5b21b6; background: #f5f3ff; }
        #table_form .pv.big b { color: #5b21b6; }
        .prior-bar { font-size: .74rem; color: #5b21b6; background: #f5f3ff; border: 1px solid #ddd6fe; padding: .3rem .6rem; margin-bottom: .45rem; display: flex; gap: .6rem; align-items: center; }
        .prior-bar button { border: 0; background: none; color: #5b21b6; text-decoration: underline; padding: 0; font-size: .74rem; }
        .demo-ro-bar { display: flex; align-items: center; gap: .8rem; background: #eef3f9; border: 1px solid #c7d6e8; border-left: 4px solid #2f557d; padding: .55rem .8rem; margin-bottom: .6rem; font-size: .82rem; color: #1f2933; }
        .demo-ro-bar > i { color: #2f557d; font-size: 1.05rem; }
        .demo-ro-bar .btn { margin-left: auto; white-space: nowrap; }
        #table_form.demo-ro input:not([type=hidden]), #table_form.demo-ro select, #table_form.demo-ro textarea { background: #f8fafc !important; cursor: default; }
        .report-unit { margin-left: auto; font-size: .7rem; color: var(--faint); white-space: nowrap; }
        /* One line above the table: title · table group · sheet / key · units (right edge). */
        .app-report-toolbar.app-report-toolbar-tight { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem .9rem; margin: 0 0 .55rem; }
        .app-report-toolbar-tight .report-title { flex: 0 1 auto; max-width: 48%; }
        .app-report-toolbar-tight .app-report-groupbar { flex: 0 0 auto; gap: .3rem; }
        #button_group > div { display: flex; gap: 0; padding: 0 !important; border: 0 !important; background: none !important; }
        #button_group button.btn + .btn { margin-left: -1px !important; }
        #button_group button.btn { padding: .12rem .5rem !important; font-size: .72rem !important; min-height: 0 !important; line-height: 1.35; border-radius: 0 !important; box-shadow: none !important; }
        #button_group button.btn { background: #fff !important; color: #475569 !important; border: 1px solid #cbd5e1 !important; font-weight: 600; }
        #button_group button.btn:hover { background: #f1f5f9 !important; color: #1e293b !important; }
        #button_group button.btn.active { background: #2f557d !important; color: #fff !important; border-color: #2f557d !important; position: relative; z-index: 1; }
        .app-report-toolbar-tight .app-report-sheets { flex: 0 1 auto; min-width: 0; }
        .app-report-toolbar-tight .app-report-sheets > * { width: auto; }
        .app-report-toolbar-tight .app-report-sheets select { max-width: 340px; }
        .app-report-toolbar-tight .app-typed-sheet { flex-wrap: nowrap; }
        .app-report-toolbar-tight .app-report-groupbar:empty, .app-report-toolbar-tight .app-report-sheets:empty { display: none; }
        /* Abstract rows (headings such as "VaR"): the renderer drops their side borders
           (xbrl-none-left/right) — keep the row-code cell and the data area framed. */
        #table_form tr:has(> td.xbrl-row-abstract) > td:first-child,
        #table_form tr:has(> td.xbrl-row-abstract) > td.xbrl-row-abstract ~ td {
            border-left: 1px solid var(--bs-border-color, #dee2e6) !important;
            border-right: 1px solid var(--bs-border-color, #dee2e6) !important; }
        /* Cells keep a sensible minimum width; the table itself fills the panel. */
        #table_form td.xbrl-td { min-width: 7.5rem; }
        #table_form td:has(> .xbrl-input-text), #table_form td:has(> textarea) { min-width: 16rem; }
        #table_form td:has(> .xbrl-select) { min-width: 11rem; }
        #table_form td:has(> .xbrl-input-open) { min-width: 10rem; }
        .report-unit b { color: #52606d; font-weight: 600; }
        .ribbon .dropdown-item small { display: block; font-size: .68rem; color: var(--faint); margin-left: 1.45rem; margin-top: -.1rem; }
        .ribbon .dropdown-header { font-size: .62rem; letter-spacing: .07em; text-transform: uppercase; }
        /* Instance settings + export check dialogs */
        .is-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem 1rem; }
        .is-grid .full { grid-column: 1 / -1; }
        .is-grid label { font-size: .74rem; font-weight: 600; color: #52606d; margin-bottom: .2rem; }
        .is-help { font-size: .7rem; color: var(--faint); margin-top: .2rem; }
        .is-module { font-size: .74rem; background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: .45rem .65rem; color: #52606d; }
        .fi-table { width: 100%; font-size: .78rem; border-collapse: collapse; }
        .fi-table th { font-size: .62rem; text-transform: uppercase; letter-spacing: .06em; color: var(--faint); padding: .35rem .5rem; border-bottom: 1px solid var(--line); text-align: left; position: sticky; top: 0; background: #fff; }
        .fi-table td { padding: .3rem .5rem; border-bottom: 1px solid #f1f5f9; }
        .fi-table select { font-size: .74rem; padding: .1rem .4rem; height: auto; }
        .fi-wrap { max-height: 48vh; overflow: auto; border: 1px solid var(--line); border-radius: 8px; }
        .fi-data { font-size: .66rem; font-weight: 700; padding: .05rem .4rem; border-radius: 4px; }
        .fi-data.yes { background: #e8f6ee; color: #166534; } .fi-data.no { background: #f1f5f9; color: #94a3b8; }
        .fi-row-warn td { background: #fff7ed; }
        .fi-row-err td { background: #fdecec; }
        .pf-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: .4rem; }
        .pf-item { display: flex; gap: .55rem; align-items: flex-start; font-size: .8rem; padding: .45rem .6rem; border-radius: 8px; border: 1px solid var(--line); }
        .pf-item.error { background: #fdecec; border-color: #f6cdcd; color: #991b1b; }
        .pf-item.warning { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
        .pf-ok { background: #e8f6ee; border: 1px solid #cce6d8; color: #166534; border-radius: 8px; padding: .55rem .7rem; font-size: .82rem; }
        .arl-sum { border-radius: 8px; padding: .55rem .7rem; font-size: .82rem; border: 1px solid; }
        .arl-sum span { color: #64748b; font-size: .74rem; }
        .arl-sum.ok { background: #e8f6ee; border-color: #cce6d8; color: #166534; }
        .arl-sum.warning { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
        .arl-sum.error { background: #fdecec; border-color: #f6cdcd; color: #991b1b; }
        .arl-list { list-style: none; padding: 0; margin: .5rem 0 0; max-height: 280px; overflow: auto; display: flex; flex-direction: column; gap: .3rem; }
        .arl-list li { font-size: .74rem; padding: .35rem .5rem; border-radius: 6px; border: 1px solid var(--line); display: flex; flex-direction: column; gap: .1rem; word-break: break-word; }
        .arl-list li.error { background: #fdecec; border-color: #f6cdcd; }
        .arl-list li.warning { background: #fff7ed; border-color: #fed7aa; }
        .arl-list code { font-size: .68rem; color: #52606d; }
        .pf-package { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .72rem; word-break: break-all; color: #52606d; margin-top: .6rem; }
        /* Table list beside the grid */
        .report-body { display: flex; gap: .6rem; align-items: flex-start; }
        .report-body > .app-report-panel { flex: 1 1 auto; min-width: 0; }
        .tnav { flex: 0 0 200px; background: #fff; border: 1px solid var(--line); border-radius: 9px; position: sticky; top: .5rem;
            max-height: calc(100vh - 140px); display: flex; flex-direction: column; }
        .tnav.collapsed { flex-basis: 34px; }
        .tnav.collapsed .tnav-list, .tnav.collapsed .tnav-title span, .tnav.collapsed .tnav-legend { display: none; }
        .tnav-title { display: flex; align-items: center; justify-content: space-between; padding: .45rem .6rem; border-bottom: 1px solid var(--line);
            font-size: .6rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .tnav-title button { border: 0; background: transparent; color: var(--faint); padding: 0 .1rem; }
        .tnav-list { overflow: auto; padding: .25rem; }
        .tnav-item { display: flex; align-items: center; gap: .45rem; width: 100%; border: 0; background: transparent; text-align: left;
            padding: .28rem .45rem; border-radius: 6px; font-size: .76rem; color: #3a4654; }
        .tnav-item:hover { background: var(--blue-soft); }
        .tnav-item.active { background: var(--blue); color: #fff; }
        .tnav-dot { width: 8px; height: 8px; border-radius: 50%; background: #d5dbe3; flex: 0 0 8px; }
        .tnav-item.data .tnav-dot { background: #4a9d6e; }
        .tnav-item.err .tnav-dot { background: #dc2626; }
        .tnav-err { margin-left: auto; font-size: .62rem; font-weight: 700; background: #fdecec; color: #b91c1c; border-radius: 999px; padding: 0 .35rem; }
        .tnav-item.active .tnav-err { background: #fff; }
        .tnav-legend { padding: .4rem .6rem; border-top: 1px solid var(--line); font-size: .62rem; color: var(--faint); display: flex; gap: .6rem; flex-wrap: wrap; }
        .tnav-legend i { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: .2rem; }
        .report-title { font-size: .86rem; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
        .report-title small { font-weight: 500; color: var(--faint); margin-left: .35rem; }
        #table_form td.dpm-cell-focus { outline: 3px solid #e4902f; outline-offset: -3px; background: #fff7ed !important; }
        /* Cell inspector hidden — reclaim vertical space */
        #cellInspector { display: none !important; }
    </style>

    <div class="app-page app-report-page">
        <div class="ribbon" role="toolbar" aria-label="Workspace controls">
            <div class="rib-title">
                <span class="rib-kicker"><i class="fas fa-table"></i> Report editor</span>
                <span class="rib-name" title="{{ $module_name }}">{{ $module_name }}</span>
                <span class="rib-meta" title="Reporting entity · reference date · taxonomy">
                    <b>{{ $entity->name ?? 'No entity' }}</b>@if(!empty($entity?->lei)) · {{ $entity->lei }}@endif
                    · {{ $period ? \Carbon\Carbon::parse($period)->format('d.m.Y') : '' }} · {{ $instance->consolidation ?? 'CON' }} · {{ $taxonomy_name }}
                </span>
                <span id="saveStatus" class="save-status" role="status" aria-live="polite">
                    <i class="fas fa-check"></i><span>{{ $last_saved ? 'Saved '.$last_saved->format('d.m. H:i') : 'Not saved yet' }}</span>
                </span>
            </div>

            <div class="rib-sep"></div>
            <div class="rib-group">
                <div class="rib-btns">
                    <button type="button" class="rib-btn rib-new" onclick="module()" title="Start a new instance">
                        <i class="fas fa-file-circle-plus"></i><span>New</span>
                    </button>
                    <button type="button" class="rib-btn rib-save" id="saveBtn" title="Save (Ctrl+S)">
                        <i class="fas fa-floppy-disk"></i><span>Save</span>
                    </button>
                    <a class="rib-btn rib-hist" href="{{ url('/table/history') }}?id={{ $instance_id }}" target="_blank" rel="noopener" title="Cell-level change history">
                        <i class="fas fa-clock-rotate-left"></i><span>History</span>
                    </a>
                    <button type="button" class="rib-btn rib-settings" id="instanceSettingsBtn" title="Entity, reference date, units and filing indicators">
                        <i class="fas fa-sliders"></i><span>Settings</span>
                    </button>
                </div>
                <span class="rib-cap">Instance</span>
            </div>

            {{-- The table list on the left picks the table; this select only holds its state. --}}
            <div class="rib-group d-none" aria-hidden="true">
                <div class="rib-btns">
                    <select id="group" onchange="changeTable(this,'G')" class="rib-select" aria-label="Table group" tabindex="-1">
                        @foreach($groups as $key=>$row)
                            <option value="{{$row}}">{{$key}}</option>
                        @endforeach
                    </select>
                </div>
                <span class="rib-cap">Table group</span>
            </div>

            <div class="rib-sep"></div>
            <div class="rib-group">
                <div class="rib-btns">
                    <button type="button" class="rib-btn rib-import" onclick="document.getElementById('fileToUpload').click()" title="Import a source file">
                        <i class="fas fa-file-import"></i><span>Import</span>
                    </button>
                    <button type="button" class="rib-btn rib-prior" id="priorBtn" title="Copy values from another period of this module, or compare with it">
                        <i class="fas fa-clock-rotate-left"></i><span>Prior period</span>
                    </button>
                    <div class="dropdown">
                        <button type="button" class="rib-btn rib-export dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Export the instance">
                            <i class="fas fa-file-export"></i><span>Export</span>
                        </button>
                        <form id="export_table" method="post" action="{{ url('/table/export') }}">
                            @csrf
                            <input name="period" type="hidden" value="{{$period}}">
                            <input name="mod" type="hidden" value="{{$mod}}">
                            <input name="instance_id" type="hidden" value="{{ $instance_id }}">
                            <input id="export_table_path" name="table" type="hidden">
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">Report for submission</h6>
                                <button class="dropdown-item fw-semibold" type="button" data-xbrl-export="csv"><i class="fas fa-file-archive"></i> xBRL-CSV package<small>EBA 4.x remittance format</small></button>
                                <button class="dropdown-item" type="button" data-xbrl-export="xml"><i class="fas fa-file-alt"></i> xBRL-XML instance<small>Legacy, where the authority still accepts it</small></button>
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header">This table only</h6>
                                <button class="dropdown-item" name="export_type" value="xlsx" type="submit"><i class="text-success fas fa-file-excel"></i> Excel (.xlsx)</button>
                                <button class="dropdown-item" name="export_type" value="pdf" type="submit"><i class="text-danger fas fa-file-pdf"></i> PDF</button>
                                <button class="dropdown-item" name="export_type" value="html" type="submit"><i class="text-primary fas fa-file-code"></i> HTML</button>
                            </div>
                        </form>
                    </div>
                </div>
                <span class="rib-cap">Data</span>
            </div>

            @if(class_exists(\Begicf\AReportDpm\Services\InstanceValidator::class) && \App\Support\License::covers('validation'))   {{-- DPM-XL validation: AReport DPM --}}
                <div class="rib-sep rib-right"></div>
                <div class="rib-group">
                    <div class="rib-btns">
                        <button type="button" class="rib-btn rib-check" id="validateInstanceBtn" disabled title="Check this table against the rules (the full report checks every table)">
                            <i class="fas fa-circle-check"></i><span>Validate</span>
                        </button>
                    </div>
                    <span class="rib-cap">Check</span>
                </div>
            @endif

            {{-- Hidden import form: the Import button opens the picker, which auto-submits via the existing handler --}}
            <form id="import" class="d-none" method="POST" enctype="multipart/form-data">
                {{ csrf_field() }}
                <input type="hidden" name="import" value="1">
                <input type="hidden" name="instance_id" value="{{ $instance_id }}">
                <input type="file" name="fileToUpload" id="fileToUpload"
                       onchange="this.value && (this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit())">
                <input id="sheetcodeImport" name="sheetcode" type="hidden"/>
            </form>
        </div>

        @include('flash.flash-message')
        @php
            $demoReadOnly = \App\Support\Demo::readOnlyInstance($instance ?? null);
            $editLock = \App\Support\InstanceHooks::lockReason($instance ?? null, 'edit');   // e.g. in review (plugins)
        @endphp
        @if(!empty($instance))
            @foreach(\App\Support\InstanceHooks::views('form.bar') as $barView)
                @include($barView, ['instance' => $instance, 'editLock' => $editLock])
            @endforeach
        @endif
        @if($demoReadOnly)
            <div class="demo-ro-bar"><i class="fas fa-eye"></i>
                <div><b>Sample report — shared by everyone in this demo.</b> Browse the tables, run <b>Validate</b> and try the <b>Export</b> with its Arelle check. To enter your own data, create a report of your own.</div>
                <a class="btn btn-sm btn-primary" href="{{ url('/modules') }}"><i class="fas fa-plus"></i> New report</a>
            </div>
        @endif

        <div class="report-body">
        <aside class="tnav" id="tableNav" aria-label="Tables">
            <div class="tnav-title"><span>Tables</span><button type="button" id="tnavToggle" title="Collapse"><i class="fas fa-angles-left"></i></button></div>
            <div class="tnav-list" id="tnavList"></div>
            <div class="tnav-legend"><span><i style="background:#4a9d6e"></i>data</span><span><i style="background:#d5dbe3"></i>empty</span><span><i style="background:#dc2626"></i>errors</span></div>
        </aside>

        <section class="app-panel app-report-panel">
            <div class="app-report-toolbar app-report-toolbar-tight">
                <div class="report-title" id="tableTitle"></div>
                <div id="button_group" class="app-report-groupbar"></div>
                <div id="sheets" class="app-report-sheets"></div>
                <div class="report-unit" id="reportUnit">Amounts in <b>{{ $instance ? $instance->amountUnitLabel() : 'thousands EUR' }}</b> · percentages in %</div>
            </div>

            <div id="validationResult" class="app-report-validation" style="display:none"></div>

            <input type="hidden" id="tableCode" name="tableCode">
            <input type="hidden" id="reportEntityId" value="{{ $entity_id ?? '' }}">

            <div id="cellInspector" class="app-report-inspector" aria-live="polite" aria-label="Cell details">
                <div class="app-report-inspector-bar">
                    <div class="app-report-inspector-summary">
                        <span class="app-report-inspector-kicker">Cell inspector</span>
                        <strong id="cellInspectorCell">No cell selected</strong>
                    </div>
                    <div id="cellInspectorMetric" class="app-report-inspector-metric">Metric: n/a</div>
                </div>
                <div class="app-report-inspector-grid">
                    <div class="app-report-inspector-block">
                        <span class="app-report-inspector-label">Dimensions</span>
                        <div id="cellInspectorDimensions" class="app-report-inspector-values">
                            <span class="app-report-chip app-report-chip-muted">Select a cell to inspect its context.</span>
                        </div>
                    </div>
                    <div class="app-report-inspector-block">
                        <span class="app-report-inspector-label">Metadata</span>
                        <div id="cellInspectorMeta" class="app-report-inspector-values">
                            <span class="app-report-chip app-report-chip-muted">No metadata loaded yet.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-report-table-shell">
                <div class="table-responsive">
                    <form id="table_form" method="post">
                        <div class="app-report-empty dpm-loading" id="tableEmptyState">
                            <i class="fas fa-table-cells-large d-block mb-2" style="font-size:1.4rem;color:#cbd5e1"></i>
                            Select a table group above to load the reporting table.
                        </div>
                    </form>
                </div>
            </div>

            {{-- Open-axis row controls live below the table --}}
            <div id="openY" class="app-report-rowbar" style="display: none">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="addRow"><i class="fas fa-plus"></i> Add row</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="delRow"><i class="fas fa-minus"></i> Remove row</button>
            </div>
        </section>
        </div>

    </div>
    {{-- Instance export (xBRL) goes through its own form after the pre-export check --}}
    <form id="instanceExportForm" method="post" class="d-none">
        @csrf
        <input type="hidden" name="instance_id" value="{{ $instance_id }}">
    </form>

    <div class="modal fade app-modal-shell" id="instanceSettings" tabindex="-1" aria-labelledby="instanceSettingsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="app-page-kicker mb-1"><i class="fas fa-sliders"></i> Instance settings</div>
                        <h5 class="modal-title" id="instanceSettingsLabel">{{ $module_name }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#isFiling" type="button">Filing</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#isIndicators" type="button">Filing indicators</button></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="isFiling">
                            <div id="isModule" class="is-module mb-3" style="display:none"></div>
                            <div class="is-grid">
                                <div class="full">
                                    <label for="isEntity">Reporting entity</label>
                                    <select id="isEntity" class="form-select form-select-sm"></select>
                                </div>
                                <div>
                                    <label for="isPeriod">Reference date</label>
                                    <input id="isPeriod" type="date" class="form-control form-control-sm">
                                    <div class="is-help">A period end, e.g. 31.12.2026.</div>
                                </div>
                                <div>
                                    <label for="isDue">Filing deadline</label>
                                    <input id="isDue" type="date" class="form-control form-control-sm">
                                    <div class="is-help" id="isDueHelp">Leave empty for the EBA remittance date.</div>
                                </div>
                                <div>
                                    <label for="isConsolidation">Consolidation scope</label>
                                    <select id="isConsolidation" class="form-select form-select-sm">
                                        <option value="CON">Consolidated (CON)</option>
                                        <option value="IND">Individual / solo (IND)</option>
                                    </select>
                                    <div class="is-help">Part of the entity identifier: LEI.CON or LEI.IND.</div>
                                </div>
                                <div>
                                    <label for="isCountry">Competent authority country</label>
                                    <input id="isCountry" class="form-control form-control-sm" maxlength="2" placeholder="e.g. FR">
                                    <div class="is-help" id="isCountryHelp">ISO country code, used in the EBA package name.</div>
                                </div>
                                <div>
                                    <label for="isCurrency">Base currency</label>
                                    <input id="isCurrency" class="form-control form-control-sm" maxlength="3" placeholder="EUR">
                                </div>
                                <div>
                                    <label for="isScale">Amounts entered in</label>
                                    <select id="isScale" class="form-select form-select-sm"></select>
                                    <div class="is-help">The report carries absolute amounts; entered values are multiplied accordingly.</div>
                                </div>
                                <div>
                                    <label>Decimals (monetary · percentage · integer · decimal)</label>
                                    <div class="d-flex gap-1">
                                        <input id="isDecMon" type="number" class="form-control form-control-sm" title="Monetary">
                                        <input id="isDecPct" type="number" class="form-control form-control-sm" title="Percentage">
                                        <input id="isDecInt" type="number" class="form-control form-control-sm" title="Integer">
                                        <input id="isDecDec" type="number" class="form-control form-control-sm" title="Decimal">
                                    </div>
                                    <div class="is-help">EBA defaults: -3 · 4 · 0 · 2.</div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="isIndicators">
                            <p class="is-help mb-2">Auto reports a template when it holds data. Set "Not reported" to file a negative indicator; such a template must hold no data.</p>
                            <div class="fi-wrap">
                                <table class="fi-table">
                                    <thead><tr><th>Template</th><th>Data</th><th>Filing indicator</th></tr></thead>
                                    <tbody id="isIndicatorRows"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div id="isError" class="text-danger small mt-2" style="display:none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="isSave">Save settings</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade app-modal-shell" id="priorModal" tabindex="-1" aria-labelledby="priorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="app-page-kicker mb-1"><i class="fas fa-clock-rotate-left"></i> Prior period</div>
                        <h5 class="modal-title" id="priorModalLabel">Another period of this module</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="priorBody">
                    <label class="form-label small fw-bold" for="priorSource">Instance</label>
                    <select id="priorSource" class="form-select form-select-sm"></select>
                    <div class="prior-opt mt-3">
                        <b><i class="fas fa-code-compare"></i> Compare on the form</b>
                        <span>Shows its value under each cell, with the change; changes over 50 % are highlighted.</span>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="priorCompare">Compare</button>
                        <button type="button" class="btn btn-sm btn-link mt-1" id="priorCompareOff" hidden>Stop comparing</button>
                    </div>
                    <div class="prior-opt mt-2">
                        <b><i class="fas fa-copy"></i> Copy its values into this instance</b>
                        <span>Every table — useful for identification tables and methodologies that rarely change. Recorded in the change history.</span>
                        <label class="small d-flex align-items-center gap-1 mt-1"><input type="checkbox" id="priorOverwrite"> Also overwrite values already entered</label>
                        <button type="button" class="btn btn-sm btn-primary mt-1" id="priorCopy">Copy values</button>
                    </div>
                    <div class="small mt-2" id="priorMsg"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade app-modal-shell" id="exportCheck" tabindex="-1" aria-labelledby="exportCheckLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="app-page-kicker mb-1"><i class="fas fa-clipboard-check"></i> Pre-export check</div>
                        <h5 class="modal-title" id="exportCheckLabel">Export</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="exportCheckBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="exportCheckSettings">Open settings</button>
                    @if(\App\Support\ArelleValidator::available())
                        <button type="button" class="btn btn-outline-primary" id="exportCheckArelle" title="Validate the exported file with Arelle (XBRL 2.1, dimensions, data types)"><i class="fas fa-shield-halved"></i> Validate with Arelle</button>
                    @endif
                    <button type="button" class="btn btn-primary" id="exportCheckGo">Download</button>
                </div>
            </div>
        </div>
    </div>

    <!--Call module modal-->
    @include('components.module')
    <!--Call module modal-->
    @include('components.please-wait')



    <script type="text/javascript">

        var aspectNode = null;
        var tableCode = null;
        const instanceId = @json($instance_id);
        // A shared demo sample, or an instance a plugin locks (e.g. in review):
        // viewable, validatable, exportable — never saved.
        const demoReadOnly = @json(($demoReadOnly ?? false) || !empty($editLock));
        const readOnlyLabel = @json(!empty($editLock) ? 'Read-only' : 'Read-only sample');
        if (demoReadOnly) {
            const lock = function () {
                const f = document.getElementById('table_form');
                if (!f) return;
                f.classList.add('demo-ro');
                f.querySelectorAll('input:not([type=hidden]), textarea').forEach(function (i) { i.readOnly = true; });
                f.querySelectorAll('select').forEach(function (s) { s.disabled = true; });
            };
            document.addEventListener('DOMContentLoaded', function () {
                const f = document.getElementById('table_form');
                if (f) new MutationObserver(lock).observe(f, { childList: true, subtree: true });
                lock();
            });
        }
        // Set when opened from a validation report link: select that table, mark that cell.
        let focusTarget = @json($focus ?? null);

        // ---- Table list (status per table group) ----
        function renderTableNav() {
            axios.get('/table/navigator', { params: { instance_id: instanceId } }).then(function (response) {
                const items = response.data.items || [];
                const current = document.querySelector('#group') ? document.querySelector('#group').selectedIndex : -1;
                document.getElementById('tnavList').innerHTML = items.map(function (it, i) {
                    const cls = (it.errors ? 'err' : (it.has_data ? 'data' : '')) + (i === current ? ' active' : '');
                    return '<button type="button" class="tnav-item ' + cls + '" data-index="' + i + '" title="' + escapeInspectorHtml(it.tables.join(', ')) + '">'
                        + '<span class="tnav-dot"></span><span>' + escapeInspectorHtml(it.name) + '</span>'
                        + (it.errors ? '<span class="tnav-err">' + it.errors + '</span>' : '') + '</button>';
                }).join('');
            }).catch(function () {});
        }

        $(document).on('click', '.tnav-item', function () {
            const select = document.querySelector('#group');
            const index = parseInt(this.dataset.index, 10);
            if (!select || select.selectedIndex === index) return;
            select.selectedIndex = index;
            changeTable(select);
        });

        $('#tnavToggle').on('click', function () {
            const nav = document.getElementById('tableNav');
            nav.classList.toggle('collapsed');
            this.innerHTML = nav.classList.contains('collapsed') ? '<i class="fas fa-angles-right"></i>' : '<i class="fas fa-angles-left"></i>';
        });

        function markActiveNav() {
            const select = document.querySelector('#group');
            document.querySelectorAll('.tnav-item').forEach(function (b) {
                b.classList.toggle('active', select && parseInt(b.dataset.index, 10) === select.selectedIndex);
            });
        }

        // Pick the group (and table within it) holding focusTarget.table.
        function applyFocusSelection() {
            const select = document.querySelector('#group');
            if (!focusTarget || !focusTarget.table || !select) return null;
            const needle = '/' + focusTarget.table + '/';
            for (let i = 0; i < select.options.length; i++) {
                // An option is {"C_01.00": {"eba_tC_01.00": "/…/c_01.00.xsd", …}}.
                let group = {};
                try { group = JSON.parse(select.options[i].value); } catch (e) {}
                const tables = Object.values(group)[0] || {};
                const paths = typeof tables === 'object' ? Object.values(tables) : [tables];
                const path = paths.find(p => String(p).toLowerCase().indexOf(needle) !== -1);
                if (path) {
                    select.selectedIndex = i;
                    return paths.length > 1 ? path : null;
                }
            }
            return null;
        }

        function applyFocusCell() {
            if (!focusTarget) return;
            const target = focusTarget;
            focusTarget = null;
            if (!target.cell) return;
            const td = document.querySelector('#table_form td[rc-code="' + target.cell + '"]');
            if (!td) return;
            td.classList.add('dpm-cell-focus');
            td.scrollIntoView({ block: 'center', inline: 'center' });
            const input = td.querySelector('input:not([type=hidden]), select');
            if (input) input.focus({ preventScroll: true });
        }
        var activeInspectorCell = null;
        var translationWorkspaceUrl = @json(Route::has('areport-pro.dpm-model.index') ? route('areport-pro.dpm-model.index') : null);

        function hidePleaseWait() {
            window.appModal.hide('pleaseWaitDialog');
        }

        function getRequestErrorMessage(error, fallbackMessage) {
            if (error && error.response && error.response.data) {
                if (typeof error.response.data === 'string' && error.response.data.trim() !== '') {
                    return error.response.data;
                }

                if (typeof error.response.data.error === 'string' && error.response.data.error.trim() !== '') {
                    return error.response.data.error;
                }
                if (typeof error.response.data.message === 'string' && error.response.data.message.trim() !== '') {
                    return error.response.data.message;
                }
            }

            return fallbackMessage;
        }

        function escapeInspectorHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function stringifyInspectorValue(value) {
            if (value === null || typeof value === 'undefined' || value === '') {
                return 'n/a';
            }

            if (typeof value === 'object') {
                return JSON.stringify(value);
            }

            return String(value);
        }

        function renderInspectorEntries(targetId, entries, emptyMessage) {
            const target = document.getElementById(targetId);

            if (!target) {
                return;
            }

            if (!entries.length) {
                target.innerHTML = "<span class='app-report-chip app-report-chip-muted'>" + escapeInspectorHtml(emptyMessage) + "</span>";
                return;
            }

            target.innerHTML = entries.map(function (entry) {
                return "<span class='app-report-chip'><strong>" + escapeInspectorHtml(entry.key) + "</strong><span>" + escapeInspectorHtml(entry.value) + "</span></span>";
            }).join('');
        }

        function clearInspectorSelection() {
            if (activeInspectorCell) {
                activeInspectorCell.classList.remove('app-report-cell-active');
                activeInspectorCell = null;
            }
        }

        function resetCellInspector(message) {
            clearInspectorSelection();

            const cell = document.getElementById('cellInspectorCell');
            const metric = document.getElementById('cellInspectorMetric');

            if (cell) {
                cell.textContent = 'No cell selected';
            }

            if (metric) {
                metric.textContent = 'Metric: n/a';
            }

            renderInspectorEntries('cellInspectorDimensions', [], message || 'Select a cell to inspect its context.');
            renderInspectorEntries('cellInspectorMeta', [], 'No metadata loaded yet.');
            document.dispatchEvent(new CustomEvent('areport:cell', { detail: { cell: null, td: null } }));
        }

        function initializeSheetSelector() {
            const sheetSelect = document.getElementById('sheet');

            if (!sheetSelect) {
                return;
            }

            sheetSelect.classList.add('form-select');
            sheetSelect.dataset.prevValue = sheetSelect.value || '';
        }

        function normalizeTranslationTableCode(value) {
            return String(value || '')
                .trim()
                .replace(/\.(xsd|xml)$/i, '')
                .toLowerCase();
        }

        function tableCodeFromPath(path) {
            const value = String(path || '');
            const tabMatch = value.match(/[\\/]tab[\\/]([^\\/]+)[\\/]/i);

            if (tabMatch && tabMatch[1]) {
                return normalizeTranslationTableCode(tabMatch[1]);
            }

            const parts = value.split(/[\\/]/).filter(Boolean);

            if (parts.length >= 2) {
                const parent = normalizeTranslationTableCode(parts[parts.length - 2]);

                if (/^[a-z]_[0-9]{2}\.[0-9]{2}/i.test(parent)) {
                    return parent;
                }
            }

            return null;
        }

        function currentTranslationTableCode() {
            const pathTableCode = tableCodeFromPath($('#export_table_path').val());

            if (pathTableCode) {
                return pathTableCode;
            }

            const normalizedTableCode = normalizeTranslationTableCode(tableCode);

            if (/^[a-z]_[0-9]{2}\.[0-9]{2}/i.test(normalizedTableCode)) {
                return normalizedTableCode;
            }

            return null;
        }

        function updateTranslationWorkspaceButton() {
            const button = document.getElementById('openTranslationWorkspace');

            if (!button) {
                return;
            }

            button.disabled = !translationWorkspaceUrl || !currentTranslationTableCode();
        }

        function openTranslationWorkspace() {
            const code = currentTranslationTableCode();

            if (!translationWorkspaceUrl || !code) {
                window.alert('Translation workspace is not available for the active table.');
                return;
            }

            const url = new URL(translationWorkspaceUrl, window.location.origin);
            url.searchParams.set('table', code);
            window.location.assign(url.toString());
        }

        function updateCellInspector(control) {
            const parentCell = control.closest('td, th');

            if (!parentCell) {
                resetCellInspector();
                return;
            }

            const hidden = parentCell.querySelector("input[type='hidden'][name$='[dim]']");

            if (!hidden) {
                resetCellInspector('The selected cell has no hidden dimension payload.');
                return;
            }

            let payload = {};

            try {
                payload = JSON.parse(hidden.value);
            } catch (error) {
                resetCellInspector('Unable to parse the hidden dimension payload.');
                return;
            }

            const meta = payload.__meta && typeof payload.__meta === 'object' ? payload.__meta : {};
            const metricValue = payload.metric || payload.concept || 'n/a';
            const dimensions = Object.keys(payload)
                .filter(function (key) {
                    return !['metric', 'concept', '__meta', 'typedMember'].includes(key);
                })
                .map(function (key) {
                    return {
                        key: key,
                        value: stringifyInspectorValue(payload[key])
                    };
                });
            const metadata = Object.keys(meta).map(function (key) {
                return {
                    key: key,
                    value: stringifyInspectorValue(meta[key])
                };
            });

            clearInspectorSelection();
            parentCell.classList.add('app-report-cell-active');
            activeInspectorCell = parentCell;

            const cell = document.getElementById('cellInspectorCell');
            const metric = document.getElementById('cellInspectorMetric');

            if (cell) {
                cell.textContent = control.id || hidden.name.replace('[dim]', '');
            }

            if (metric) {
                metric.textContent = 'Metric: ' + metricValue;
            }

            renderInspectorEntries('cellInspectorDimensions', dimensions, 'No explicit dimensions stored for this cell.');
            renderInspectorEntries('cellInspectorMeta', metadata, 'No JSON metadata was matched for this cell.');
            // Plugins (e.g. cell comments) act on the selected cell.
            document.dispatchEvent(new CustomEvent('areport:cell', { detail: { cell: parentCell.getAttribute('rc-code'), td: parentCell } }));
        }

        function setupCellInspector() {
            $('#table_form').off('.inspector');

            $('#table_form').on('focus.inspector click.inspector change.inspector', 'input:not([type=\"hidden\"]), select, textarea', function () {
                updateCellInspector(this);
            });

            resetCellInspector();
        }

        window.onload = function () {
            setupCellInspector();
            const group = document.querySelector('#group');
            if (group) {
                const focusPath = applyFocusSelection();
                if (focusPath) {
                    changeTable({ value: focusPath }, 'T');
                } else {
                    changeTable(group);
                }
            }
            renderTableNav();
        }


        function dataSet() {
            $('.datepicker').datepicker({
                autoclose: true,
                dateFormat: "dd-mm-yy",

            });


        }

        function module() {


            axios.post('/modules/group', {

                module: '{{$mod}}',
                instance_id: instanceId,

            }).then(function (response) {
                const entitySelect = document.getElementById('entitySelect');
                const currentEntity = document.getElementById('reportEntityId');
                if (entitySelect && currentEntity && currentEntity.value) {
                    entitySelect.value = currentEntity.value;
                }

                var optionsHTML = [];
                var optionsHTMLTo = [];

                $("#module_name").val('{{$module_name}}');
                $("#module_path").val('{{$mod}}');

                $groups = {!! json_encode($groups) !!};

                var arr1 = Object.keys(response.data);
                var arr2 = Object.keys($groups);

                let difference = arr1.filter(x => !arr2.includes(x));


                for (var k in response.data) {
                    if (difference.includes(k)) {
                        var temp = {};
                        temp[k] = response.data[k];

                        optionsHTML.push("<option value=" + '{' + '"' + k + '"' + ':' + response.data[k] + '}' + ">" + k + "</option>")
                    }
                }

                for (var k in $groups) {

                    optionsHTMLTo.push("<option value='" + $groups[k] + "'>" + k + "</option>")

                }


                $('#multiselect option').remove();
                $('#multiselect_to option').remove();


                $('#multiselect').append(optionsHTML);
                $('#multiselect_to').append(optionsHTMLTo);
                window.appModal.show('module');

            }).catch(function (error) {
                window.alert(getRequestErrorMessage(error, 'Unable to load instance options. Please try again.'));
            });

        }


        // ---- Saving: explicit Save, autosave, unsaved-changes guard ----
        // editVersion counts edits; a save only clears "dirty" if nothing was typed while it ran.
        let editVersion = 0, savedVersion = 0, saveInFlight = null, loadingTable = false, lastSavedText = null;
        const AUTOSAVE_MS = 30000;

        function isDirty() { return editVersion !== savedVersion; }

        function setSaveStatus(state, text) {
            const el = document.getElementById('saveStatus');
            if (!el) return;
            const icons = { saved: 'fa-check', dirty: 'fa-circle', saving: 'fa-circle-notch fa-spin', error: 'fa-triangle-exclamation' };
            el.className = 'save-status' + (state === 'saved' ? '' : ' is-' + state);
            el.innerHTML = '<i class="fas ' + icons[state] + '"></i><span>' + escapeInspectorHtml(text) + '</span>';
            el.title = state === 'error' ? 'Click to retry' : '';
            $('#saveBtn').toggleClass('is-dirty', state === 'dirty' || state === 'error');
        }

        function markDirty() {
            // Rendering a table fires change events of its own; those are not edits.
            if (loadingTable || demoReadOnly) return;
            editVersion++;
            if (!saveInFlight) setSaveStatus('dirty', 'Unsaved changes');
        }

        // ---- Numbers: shown in the user's style (Settings → Number format), stored raw ----
        // Monetary / integer / decimal cells become text inputs: 1,250,000.50 or 1.250.000,50 while
        // not focused, the plain number while editing. What is saved or validated is always the
        // plain number (1250000.50). Values from the server are plain numbers, never read as typed.
        const NUM = Object.assign({ decimal: ',', group: '.' }, window.AREPORT_NUM || {});
        function parseNum(v) {   // what the user typed or pasted → plain number (or the text, when it is not one)
            let s = String(v || '').trim().replace(/[\s'  ’]/g, '').replace(/^\+/, '');
            if (s === '') return '';
            const dec = NUM.decimal, other = dec === '.' ? ',' : '.';
            const count = (c) => s.split(c).length - 1;
            if (count(dec) > 1 && count(other) <= 1) {   // 1.250.000 / 1.250.000,5 typed in the other style
                return s.split(dec).join('').replace(other, '.');
            }
            if (count(dec) === 1) return s.split(other).join('').replace(dec, '.');
            if (count(other)) {
                // No decimal separator of the user's style: groups of three are thousands, anything else a decimal.
                const grouped = new RegExp('^-?\\d{1,3}(\\' + other + '\\d{3})+$').test(s);
                if (grouped) return s.split(other).join('');
                if (count(other) === 1) return s.replace(other, '.');
            }
            return s;
        }
        function formatNum(raw) {   // plain number → the user's style
            const m = /^(-?)(\d+)(?:\.(\d+))?$/.exec(String(raw));
            return m ? m[1] + m[2].replace(/\B(?=(\d{3})+(?!\d))/g, NUM.group) + (m[3] ? NUM.decimal + m[3] : '') : String(raw);
        }
        function editNum(raw) { return NUM.decimal === '.' ? raw : String(raw).replace('.', NUM.decimal); }
        function numProblem(raw, isInt) {
            if (raw === '') return null;
            if (!/^-?\d+(\.\d+)?$/.test(raw)) return 'Enter a number (e.g. 1250000 or ' + formatNum('1250000.50') + ').';
            if (isInt && raw.indexOf('.') !== -1) return 'Enter a whole number, without decimals.';
            return null;
        }
        function showNum(input, raw) {   // raw given: a plain number from the server; else read what is typed
            raw = raw === undefined ? parseNum(input.value) : String(raw).trim();
            const problem = numProblem(raw, input.dataset.num === 'int');
            input.classList.toggle('num-bad', !!problem);
            input.title = problem || input.dataset.title || '';
            input.value = problem ? input.value : formatNum(raw);
        }
        function enhanceNumberInputs() {
            document.querySelectorAll('#table_form input.xbrl-input[type="number"], #table_form input.xbrl-input-decimal, #table_form input.xbrl-input.percent').forEach(function (i) {
                if (i.dataset.num) return;
                i.dataset.num = (i.getAttribute('step') === '1') ? 'int' : 'dec';
                i.removeAttribute('pattern');
                i.dataset.title = i.title || '';
                const stored = i.value;   // as saved: a plain number
                i.type = 'text';
                i.inputMode = 'decimal';
                i.removeAttribute('step');
                showNum(i, stored);
            });
        }
        $('#table_form').on('focus', 'input[data-num]', function () {
            const raw = parseNum(this.value);
            if (!numProblem(raw, this.dataset.num === 'int')) this.value = editNum(raw);
        });
        $('#table_form').on('blur', 'input[data-num]', function () { showNum(this); });
        // The form as the server expects it: plain numbers.
        function formData() {
            const nums = document.querySelectorAll('#table_form input[data-num]'), shown = [];
            nums.forEach(function (i) {
                shown.push(i.value);
                const raw = parseNum(i.value);
                if (!numProblem(raw, i.dataset.num === 'int')) i.value = raw;
            });
            const data = $('#table_form').serialize();
            nums.forEach(function (i, k) { i.value = shown[k]; });
            return data;
        }

        // ---- Keyboard: Enter / Shift+Enter and ↑ / ↓ move down / up the column ----
        function cellKey(el) {
            const m = /^(c[0-9a-z._-]+?)r([0-9a-z._-]+)\[value\]/i.exec(el.name || '');
            return m ? m[1].toLowerCase() : null;
        }
        function moveVertical(el, dir) {
            const col = cellKey(el);
            if (!col) return false;
            const same = Array.prototype.filter.call(document.querySelectorAll('#table_form input:not([type=hidden]):not([disabled]), #table_form select:not([disabled])'),
                function (x) { return cellKey(x) === col && x.offsetParent !== null; });
            const next = same[same.indexOf(el) + dir];
            if (!next) return false;
            next.focus();
            if (next.select && next.tagName === 'INPUT') next.select();
            return true;
        }
        $('#table_form').on('keydown', 'input:not([type=hidden]), select', function (e) {
            if (e.altKey || e.ctrlKey || e.metaKey) return;
            if (e.key === 'Enter') {
                e.preventDefault();
                moveVertical(this, e.shiftKey ? -1 : 1);
            } else if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && this.tagName === 'INPUT' && !this.classList.contains('datepicker') && !this.getAttribute('list')
                && !document.querySelector('.ui-autocomplete:not([style*="display: none"])')) {
                if (moveVertical(this, e.key === 'ArrowDown' ? 1 : -1)) e.preventDefault();
            }
        });
        // ---- Prior period: copy values from another instance of the module, or compare with it ----
        const priorKey = 'areport.prior.' + instanceId;
        let priorSource = null;
        try { priorSource = localStorage.getItem(priorKey); } catch (e) {}
        function currentSheetCode() {
            const v = $('#sheets').find(':selected').val();
            try { const o = JSON.parse(v); return (o && o.sheet) || '000'; } catch (e) { return v && v !== 'null' ? v : '000'; }
        }
        function applyPrior() {
            document.querySelectorAll('#table_form .pv').forEach(function (e) { e.remove(); });
            $('#priorBar').remove();
            if (!priorSource) return;
            axios.get('/table/prior-values', { params: { instance_id: instanceId, source_id: priorSource, table_path: $('#export_table_path').val(), sheet: currentSheetCode() } })
                .then(function (r) {
                    const vals = r.data.values || {};
                    $('#table_form').before('<div class="prior-bar" id="priorBar"><i class="fas fa-code-compare"></i> Comparing with <b>' + escapeInspectorHtml(r.data.source || '') + '</b><button type="button" id="priorBarOff">stop</button></div>');
                    document.querySelectorAll('#table_form input:not([type=hidden]), #table_form select').forEach(function (el) {
                        const m = /^(c[0-9a-z._-]+?r[0-9a-z._-]+)\[value\]/i.exec(el.name || '');
                        if (!m || !(m[1] in vals) || vals[m[1]] === null || vals[m[1]] === '') return;
                        const prev = String(vals[m[1]]);
                        let cls = 'pv', label;
                        if (el.tagName === 'SELECT') {
                            const o = Array.prototype.find.call(el.options, function (x) { return x.value === prev; });
                            label = 'prev ' + (o ? o.text.trim() : prev);
                        } else {
                            const now = parseFloat(parseNum(el.value)), p = parseFloat(prev);
                            label = 'prev ' + (isNaN(p) ? prev : formatNum(String(prev)));
                            if (!isNaN(now) && !isNaN(p) && p !== 0) {
                                const d = (now - p) / Math.abs(p) * 100;
                                label += ' · <b>' + (d >= 0 ? '+' : '') + d.toFixed(Math.abs(d) < 10 ? 1 : 0) + '%</b>';
                                cls += d >= 0 ? ' up' : ' down';
                                if (Math.abs(d) > 50) cls += ' big';
                            }
                        }
                        const tag = document.createElement('span');
                        tag.className = cls; tag.innerHTML = label;
                        el.insertAdjacentElement('afterend', tag);
                    });
                }).catch(function () {});
        }
        $(document).on('click', '#priorBarOff, #priorCompareOff', function () {
            priorSource = null;
            try { localStorage.removeItem(priorKey); } catch (e) {}
            applyPrior();
            $('#priorCompareOff').prop('hidden', true);
        });
        $('#priorBtn').on('click', function () {
            $('#priorMsg').text('');
            $('#priorCompareOff').prop('hidden', !priorSource);
            $('#priorCopy').prop('disabled', demoReadOnly).attr('title', demoReadOnly ? readOnlyLabel : '');
            axios.get('/table/prior-instances', { params: { instance_id: instanceId } }).then(function (r) {
                const list = r.data.instances || [];
                $('#priorSource').html(list.length ? list.map(function (i) {
                    return '<option value="' + i.id + '"' + (String(i.id) === String(priorSource || r.data.suggested) ? ' selected' : '') + '>' + escapeInspectorHtml(i.label) + '</option>';
                }).join('') : '<option value="">No other instance of this module</option>');
                $('#priorCompare, #priorCopy').prop('disabled', !list.length || false);
                if (demoReadOnly) $('#priorCopy').prop('disabled', true);
                window.appModal.show('priorModal');
            });
        });
        $('#priorCompare').on('click', function () {
            priorSource = $('#priorSource').val();
            if (!priorSource) return;
            try { localStorage.setItem(priorKey, priorSource); } catch (e) {}
            window.appModal.hide('priorModal');
            applyPrior();
        });
        $('#priorCopy').on('click', function () {
            const src = $('#priorSource').val();
            if (!src) return;
            const btn = this;
            btn.disabled = true;
            $('#priorMsg').removeClass('text-danger').text('Copying…');
            saveIfDirty().then(function () {
                return axios.post('/table/copy-from', { instance_id: instanceId, source_id: src, overwrite: $('#priorOverwrite').is(':checked') });
            }).then(function (r) {
                const d = r.data || {};
                $('#priorMsg').text(d.added + ' value(s) added, ' + d.changed + ' overwritten' + (d.kept ? ', ' + d.kept + ' kept (already entered)' : '') + '.');
                renderTableNav();
                if (document.querySelector('#group')) changeTable(document.querySelector('#group'));   // show the copied values
            }).catch(function (e) {
                $('#priorMsg').addClass('text-danger').text(getRequestErrorMessage(e, 'Copy failed.'));
            }).finally(function () { btn.disabled = false; });
        });

        // ---- Paste a block copied from Excel: fills right and down from the focused cell ----
        function sameColumn(el) {
            const col = cellKey(el);
            return Array.prototype.filter.call(document.querySelectorAll('#table_form input:not([type=hidden]):not([disabled]), #table_form select:not([disabled])'),
                function (x) { return cellKey(x) === col && x.offsetParent !== null; });
        }
        // value: what was pasted (in the user's style), or with plain a number from the server.
        function setCell(el, value, plain) {
            value = String(value).trim();
            if (el.tagName === 'SELECT') {
                const v = value.toLowerCase();
                const opt = Array.prototype.find.call(el.options, function (o) { const ov = o.value.toLowerCase(); return ov === v || ov.endsWith(':' + v) || o.text.trim().toLowerCase() === v; })
                    || (v ? Array.prototype.find.call(el.options, function (o) { return o.text.trim().toLowerCase().indexOf(v) === 0; }) : null);
                if (!opt && value !== '') return false;
                el.value = opt ? opt.value : '';
            } else {
                el.value = value;
                if (el.dataset.num) showNum(el, plain ? value : undefined);
            }
            return true;
        }
        $('#table_form').on('paste', 'input:not([type=hidden])', function (e) {
            const text = (e.originalEvent.clipboardData || window.clipboardData).getData('text') || '';
            if (demoReadOnly || (text.indexOf('\t') === -1 && text.replace(/\r?\n$/, '').indexOf('\n') === -1)) return;   // one value: normal paste
            e.preventDefault();
            const grid = text.replace(/\r/g, '').replace(/\n$/, '').split('\n').map(function (r) { return r.split('\t'); });
            const rowInputs = Array.prototype.filter.call(this.closest('tr').querySelectorAll('input:not([type=hidden]):not([disabled]), select:not([disabled])'),
                function (x) { return cellKey(x); });
            const start = rowInputs.indexOf(this);
            let filled = 0, skipped = 0;
            for (let j = 0; j < (grid[0] || []).length; j++) {
                const top = rowInputs[start + j];
                if (!top) { skipped += grid.length; continue; }
                const col = sameColumn(top), base = col.indexOf(top);
                for (let i = 0; i < grid.length; i++) {
                    const target = col[base + i];
                    if (!target || grid[i][j] === undefined) { skipped++; continue; }
                    if (setCell(target, grid[i][j])) filled++; else skipped++;
                }
            }
            markDirty();
            setSaveStatus('dirty', 'Pasted ' + filled + ' cell(s)' + (skipped ? ', ' + skipped + ' skipped' : '') + ' — unsaved');
        });
        $('#table_form').on('focusin', 'input:not([type=hidden]), select', function () { $(this).closest('td').addClass('kb-focus'); });
        $('#table_form').on('focusout', 'input:not([type=hidden]), select', function () { $(this).closest('td').removeClass('kb-focus'); });

        // Persist the open sheet. Resolves when stored; rejects (and says so on screen) when not.
        function save(sheet = null) {
            if (demoReadOnly) { setSaveStatus('saved', readOnlyLabel); return Promise.resolve({ ok: true, changes: 0 }); }
            const version = editVersion;
            const payload = {
                'instance_id': instanceId,
                'table_data': formData(),
                'sheet': (sheet ? sheet : $('#sheets').find(':selected').val()),
                'tab': $('#export_table_path').val(),
            };
            setSaveStatus('saving', 'Saving…');

            saveInFlight = axios.post('/table/save', payload).then(function (response) {
                const d = response.data || {};
                if (!d.ok) throw new Error(d.error || 'Save failed.');
                if (d.changes) renderTableNav();
                if (editVersion === version) {
                    savedVersion = version;
                    lastSavedText = 'Saved ' + (d.saved_at || '');
                    setSaveStatus('saved', lastSavedText);
                } else {
                    setSaveStatus('dirty', 'Unsaved changes');
                }
                // Cells the server refused (not a number, not a date…): marked, the rest is saved.
                const invalid = Object.keys(d.invalid || {});
                invalid.forEach(function (code) {
                    const el = document.querySelector('#table_form [name="' + code + '[value]"]');
                    if (el) { el.classList.add('num-bad'); el.title = d.invalid[code]; }
                });
                if (invalid.length) {
                    setSaveStatus('error', invalid.length + (invalid.length === 1 ? ' cell' : ' cells') + ' not saved: ' + d.invalid[invalid[0]]);
                }
                return d;
            }).catch(function (error) {
                const msg = getRequestErrorMessage(error, error && error.message ? error.message : 'Save failed.');
                setSaveStatus('error', 'Not saved: ' + msg);
                throw error;
            }).finally(function () {
                saveInFlight = null;
            });

            return saveInFlight;
        }

        // Save only when there is something to save (table switches, validation).
        function saveIfDirty(sheet = null) {
            return isDirty() ? save(sheet) : Promise.resolve({ ok: true, changes: 0 });
        }

        $('#table_form').on('input change', 'input:not([type="hidden"]), select, textarea', markDirty);
        $('#table_form').on('change', 'input:not([type="hidden"]), select, textarea', function () { scheduleAutoValidate(900); });
        $('#saveBtn').on('click', function () { save().catch(function () {}); });
        $('#saveStatus').on('click', function () { if (this.classList.contains('is-error')) save().catch(function () {}); });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                save().catch(function () {});
            }
        });

        setInterval(function () {
            if (isDirty() && !saveInFlight && $('#export_table_path').val()) save().catch(function () {});
        }, AUTOSAVE_MS);

        window.addEventListener('beforeunload', function (e) {
            if (isDirty() || saveInFlight) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // ---- Instance validation (reporting flow) ----
        function resetValidation() {
            valSeq++; clearTimeout(autoValTimer);   // drop runs of the table being left
            $('#validateInstanceBtn').prop('disabled', true);
            const box = document.getElementById('validationResult');
            if (box) { box.style.display = 'none'; box.innerHTML = ''; }
            clearFailedCells();
        }

        function clearFailedCells() {
            document.querySelectorAll('#table_form td.dpm-cell-fail, #table_form td.dpm-cell-warn, #table_form td.dpm-cell-part').forEach(function (td) {
                td.classList.remove('dpm-cell-fail', 'dpm-cell-warn', 'dpm-cell-part');
                if (td.dataset.valTitle) { td.removeAttribute('title'); delete td.dataset.valTitle; }
            });
        }

        // Paint the cells the validator flagged. Cells are keyed "<tablecode>,r<row>,c<col>";
        // parser cells carry rc-code="c<col>r<row>". A sum rule that fails marks only its total
        // (red for an error, amber for a warning) and outlines the cells it adds up; any other
        // failing rule marks all its cells. A cell's title says what is wrong with it.
        function valKey(cell) { return String(cell || '').toLowerCase().replace(' · ', ',').replace(/\//g, ','); }
        function cellInput(key) {   // the input of a cell of the open table, by rule key
            const p = String(key).split(','), code = String(tableCode || '').toLowerCase();
            if (p[0] !== code || p.length !== 3) return null;
            const row = p.find(x => x[0] === 'r'), col = p.find(x => x[0] === 'c');
            const el = row && col ? document.getElementById(col + row) : null;
            return el && el.tagName === 'INPUT' && el.dataset.num ? el : null;
        }
        // What a sum check says now, in the form's units: from the inputs when every cell is on
        // this table (so a fix is typed in the units shown), else the validator's values.
        function checkNow(c) {
            const target = cellInput(c.key), parts = (c.parts || []).map(p => ({ p: p, el: cellInput(p.key) }));
            if (!target || parts.some(x => !x.el)) {
                return { local: false, actual: String(c.actual), expected: String(c.expected), diff: c.diff };
            }
            const num = el => { const v = parseFloat(parseNum(el.value)); return isNaN(v) ? 0 : v; };
            const places = Math.max(0, ...[target].concat(parts.map(x => x.el)).map(el => ((parseNum(el.value).split('.')[1]) || '').length));
            const expected = parts.reduce((t, x) => t + x.p.sign * num(x.el), 0);
            const round = v => Number(v.toFixed(Math.min(places, 10)));
            return { local: true, actual: String(round(num(target))), expected: String(round(expected)), diff: round(num(target) - expected) };
        }
        // A rule's long row / column lists shortened: (r0100, r0110, … r0531 · 51).
        function shortRule(rule) {
            return String(rule || '').replace(/\(([rc]\w+(?:,\s*[rc]\w+){5,})\)/g, function (all, list) {
                const items = list.split(/,\s*/);
                return '(' + items.slice(0, 3).join(', ') + ', … ' + items[items.length - 1] + ' · ' + items.length + ')';
            });
        }
        function opText(op) { return { '=': '=', '>=': '≥', '<=': '≤', '>': '>', '<': '<' }[op] || op; }
        function signed(v) { const n = Number(v); return (n > 0 ? '+' : n < 0 ? '−' : '') + formatNum(String(Math.abs(n))); }

        function highlightFailedCells(failedCells, failed, involvedCells) {
            clearFailedCells();
            const byCell = {}, parts = {};
            (failed || []).forEach(function (f) {
                if ((f.checks || []).length) {
                    f.checks.forEach(function (c) {
                        (byCell[c.key] = byCell[c.key] || []).push({ f: f, c: c });
                        (c.parts || []).forEach(function (p) { parts[p.key] = true; });
                    });
                    return;
                }
                (f.facts || []).forEach(function (x) { const k = valKey(x.cell); (byCell[k] = byCell[k] || []).push({ f: f }); });
            });
            (failedCells || []).forEach(function (k) { byCell[k] = byCell[k] || [{ f: { code: '', severity: 'error' } }]; });
            (involvedCells || []).forEach(function (k) { parts[k] = true; });
            if (!tableCode) return 0;
            const code = String(tableCode).toLowerCase();
            let hits = 0;
            document.querySelectorAll('#table_form td[rc-code]').forEach(function (td) {
                const m = /^c([^r]+)r(.+)$/.exec(td.getAttribute('rc-code') || '');
                if (!m) return;
                const col = 'c' + m[1].toLowerCase(), row = 'r' + m[2].toLowerCase();
                const key = code + ',' + row + ',' + col;
                const entries = byCell[key] || byCell[code + ',' + col + ',' + row];
                if (!entries) {
                    if (parts[key]) { td.classList.add('dpm-cell-part'); td.title = 'Part of a total that does not add up'; td.dataset.valTitle = '1'; }
                    return;
                }
                const isError = entries.some(function (e) { return (e.f.severity || 'error') === 'error'; });
                td.classList.add(isError ? 'dpm-cell-fail' : 'dpm-cell-warn');
                const lines = entries.map(function (e) {
                    if (!e.c) return e.f.code;
                    const n = checkNow(e.c);
                    return e.f.code + ': should be ' + opText(e.c.op) + ' ' + formatNum(n.expected) + ' (difference ' + signed(n.diff) + ')';
                }).filter(Boolean);
                if (lines.length) { td.title = (isError ? 'Fails — ' : 'Warning — ') + Array.from(new Set(lines)).join('\n'); td.dataset.valTitle = '1'; }
                hits++;
            });
            return hits;
        }

        // A total set to what its parts add up to.
        function setTotal(key, expected) {
            const el = cellInput(key);
            if (!el) return false;
            showNum(el, String(expected));
            markDirty();
            return true;
        }
        function fixCheck(btn) {
            const c = lastChecks[+btn.dataset.check];
            if (!c) return;
            const n = checkNow(c);
            if (n.local && setTotal(c.key, n.expected)) scheduleAutoValidate(150);
        }
        function focusCell(key) {
            const el = cellInput(key) || (function () { const p = String(key).split(','); return document.getElementById((p[2] || '') + (p[1] || '')); })();
            if (el) { el.scrollIntoView({ block: 'center' }); el.focus(); }
        }
        // Every "=" total of the open table set from its parts, repeated until nothing changes
        // (totals of totals settle bottom-up); totals defined twice in conflicting ways stop it.
        function recalcTotals() {
            const checks = lastChecks.filter(c => c.op === '=' && checkNow(c).local);
            let changed = 0, pass = 0, moved = true;
            while (moved && pass < 25) {
                moved = false; pass++;
                checks.forEach(function (c) {
                    const n = checkNow(c);
                    if (Number(n.diff) !== 0 && setTotal(c.key, n.expected)) { moved = true; changed++; }
                });
            }
            setSaveStatus('dirty', (changed ? 'Recalculated ' + changed + ' total(s)' : 'Totals already add up')
                + (moved ? '; some are defined by two rules that disagree, see the red cells' : '') + ' — unsaved');
            scheduleAutoValidate(150);
        }

        function escapeValHtml(v) {
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // Live validation: runs when a table opens and shortly after each change, so the
        // reporter always sees which cells break a rule (red) or raise a warning (amber).
        let valSeq = 0, autoValTimer = null, valDetailsOpen = false, lastChecks = [];
        function scheduleAutoValidate(delay) {
            clearTimeout(autoValTimer);
            // An empty table has nothing to check (the server would answer "enter some values").
            const hasValues = function () {
                return Array.prototype.some.call(document.querySelectorAll('#table_form [name$="[value]"]'), function (i) { return String(i.value || '').trim() !== ''; });
            };
            autoValTimer = setTimeout(function () { if (tableCode && hasValues()) validateInstance({ auto: true }); }, delay);
        }

        function validateInstance(opts) {
            const auto = !!(opts && opts.auto);
            const seq = ++valSeq;
            const box = document.getElementById('validationResult');
            if (!auto) {
                valDetailsOpen = true;
                box.style.display = 'block';
                box.innerHTML = '<div class="dpm-loading">Running validation…</div>';
            }
            // Manual run: persist the draft first so values are consistent. Live runs read the form as it is.
            (auto ? Promise.resolve() : saveIfDirty().catch(function () {})).finally(function () {
                axios.post('/table/validate', {
                    'instance_id': instanceId,
                    'table_data': formData(),
                    'table_code': tableCode,
                    'tab': $('#export_table_path').val()
                }).then(function (response) {
                    if (seq !== valSeq) return;   // a newer run is under way
                    const d = response.data || {};
                    if (!d.ok) { box.style.display = 'block'; box.innerHTML = '<span class="dpm-val-err">' + escapeValHtml(d.error || 'Validation failed.') + '</span>'; return; }
                    const s = d.summary || {};
                    const hits = highlightFailedCells(d.failed_cells, d.failed, d.involved_cells);
                    lastChecks = [];
                    // A clean live run stays out of the way; problems always show.
                    if (auto && !(d.failed || []).length) { box.style.display = 'none'; box.innerHTML = ''; return; }
                    box.style.display = 'block';
                    let html = '<div class="dpm-val-head">'
                        + '<span class="dpm-val-pill ok">' + (s.passed || 0) + ' passed</span>'
                        + '<span class="dpm-val-pill err">' + (s.failed || 0) + ' failed</span>'
                        + '<span class="dpm-val-pill ind">' + (s.indeterminate || 0) + ' indeterminate</span>'
                        + '<span class="dpm-val-pill mut">' + (s.unsupported || 0) + ' unsupported</span>'
                        + '<span class="dpm-val-meta">' + escapeValHtml(d.table) + ' · ' + (d.values || 0) + ' values · ' + (s.total || 0) + ' rules'
                        + (auto ? '<span class="dpm-val-live"><i class="fas fa-bolt"></i> live</span>' : '') + '</span>'
                        + '<button type="button" class="dpm-val-close" onclick="resetValidationPanel()">&times;</button>'
                        + '</div>';
                    if ((d.failed || []).length) {
                        const rows = d.failed.map(function (f) {
                            let body;
                            if ((f.checks || []).length) {
                                body = f.checks.map(function (c) {
                                    const i = lastChecks.push(c) - 1, n = checkNow(c);
                                    const partsText = (c.parts || []).map((p, j) => (j || p.sign < 0 ? (p.sign < 0 ? ' − ' : ' + ') : '') + p.cell.split(' · ').pop()).join('');
                                    return '<div class="dpm-val-check"><a onclick="focusCell(\'' + escapeValHtml(c.key) + '\')">' + escapeValHtml(c.cell.split(' · ').pop()) + '</a>'
                                        + '<span>is ' + escapeValHtml(formatNum(n.actual)) + ', should be ' + opText(c.op) + ' <span class="exp">' + escapeValHtml(formatNum(n.expected)) + '</span></span>'
                                        + '<span class="diff">difference ' + escapeValHtml(signed(n.diff)) + '</span>'
                                        + (c.op === '=' && n.local && !demoReadOnly ? '<button type="button" class="dpm-val-fix" data-check="' + i + '" onclick="fixCheck(this)">Set to ' + escapeValHtml(formatNum(n.expected)) + '</button>' : '')
                                        + '<span class="parts" title="' + escapeValHtml(partsText) + '">= ' + escapeValHtml(partsText.length > 90 ? partsText.slice(0, 90) + '…' : partsText) + '</span></div>';
                                }).join('') + (f.checks_count > f.checks.length ? '<div class="dpm-val-more">… and ' + (f.checks_count - f.checks.length) + ' more</div>' : '');
                            } else {
                                body = '<div class="dpm-val-facts">' + (f.facts || []).map(function (x) {
                                    return '<span class="dpm-val-fact"><b>' + escapeValHtml(x.cell) + '</b> = ' + escapeValHtml(x.value === null ? '∅' : formatNum(x.value)) + '</span>';
                                }).join('') + '</div>';
                            }
                            return '<div class="dpm-val-fail">'
                                + '<div class="dpm-val-fh"><i class="fas fa-circle dpm-sev-' + escapeValHtml(f.severity) + '"></i> <b>' + escapeValHtml(f.code) + '</b> '
                                + '<code class="dpm-val-rule" title="' + escapeValHtml(f.rule || '') + '">' + escapeValHtml(shortRule(f.rule)) + '</code></div>' + body + '</div>';
                        }).join('');
                        const fixable = lastChecks.filter(c => c.op === '=' && checkNow(c).local).length;
                        html += '<div class="dpm-val-note">' + hits + ' cell(s) marked below — red: a total that does not add up or a rule that fails, amber: a warning; outlined: the cells a total adds up. Hover a cell for details.'
                            + ' <button type="button" class="dpm-val-toggle" onclick="toggleValDetails(this)">' + (valDetailsOpen ? 'Hide' : 'Show') + ' ' + d.failed.length + ' rule(s)</button>'
                            + (fixable && !demoReadOnly ? '<button type="button" class="dpm-val-fix dpm-val-recalc" onclick="recalcTotals()" title="Set every total of this table to the sum of its parts, then check again">Recalculate ' + fixable + ' total(s)</button>' : '')
                            + '</div>'
                            + '<div class="dpm-val-fails"' + (valDetailsOpen ? '' : ' style="display:none"') + '>' + rows + '</div>';
                    } else if (d.note) {
                        html += '<div class="dpm-val-note"><i class="fas fa-circle-info"></i> ' + escapeValHtml(d.note) + '</div>';
                    } else {
                        html += '<div class="dpm-val-ok"><i class="fas fa-check"></i> No rule violations.</div>';
                    }
                    box.innerHTML = html;
                }).catch(function (error) {
                    if (seq !== valSeq) return;
                    const msg = getRequestErrorMessage(error, 'Validation request failed.');
                    // Live runs stay silent on an empty table or when no rules apply.
                    if (auto) { clearFailedCells(); box.style.display = 'none'; box.innerHTML = ''; return; }
                    box.innerHTML = '<span class="dpm-val-err">' + escapeValHtml(msg) + '</span>';
                });
            });
        }

        function toggleValDetails(btn) {
            const list = document.querySelector('#validationResult .dpm-val-fails');
            if (!list) return;
            valDetailsOpen = list.style.display === 'none';
            list.style.display = valDetailsOpen ? '' : 'none';
            btn.textContent = btn.textContent.replace(/^(Show|Hide)/, valDetailsOpen ? 'Hide' : 'Show');
        }

        function resetValidationPanel() {
            const box = document.getElementById('validationResult');
            if (box) { box.style.display = 'none'; box.innerHTML = ''; }
            clearFailedCells();
        }

        // Switch table/sheet only after the open one is stored; if saving fails, stay put.
        let lastGroupValue = null;

        function changeTable(selectedOb, type = 'G', sheet = null) {
            if (sheet == null && isDirty()) {
                const groupSelect = document.querySelector('#group');
                save().then(function () {
                    changeTable(selectedOb, type, sheet);
                }).catch(function () {
                    if (type == 'G' && groupSelect && lastGroupValue !== null) groupSelect.value = lastGroupValue;
                    window.alert('The open table could not be saved, so it stays open. Check the save status and try again.');
                });
                return;
            }

            var group;
            var table = null;

            $("#openY").hide();

            if (type == 'G') {
                group = selectedOb.value;
            } else if (type == 'S') {
                group = document.querySelector('#group').value;
                table = selectedOb
            } else {
                group = document.querySelector('#group').value;
                table = selectedOb.value;
            }


            window.appModal.show('pleaseWaitDialog');

            $("#tab").empty();
            $("#button_group").empty();
            $("#sheets").empty();
            tableCode = null;
            $('#tableCode').val('');
            updateTranslationWorkspaceButton();
            resetValidation();
            resetCellInspector('Loading the selected table.');

            axios.post('/table/ajax', {

                    'instance_id': instanceId,
                    'group': group,
                    'tab': table,
                    'mod': '{{$mod}}',
                    'period': '{{$period}}',
                    'sheet': sheet
                }
            ).then(function (response) {
                tableCode = response.data.tableCode;
                lastGroupValue = document.querySelector('#group') ? document.querySelector('#group').value : null;
                loadingTable = true;

                $('#table_form').html(response.data.table);
                $('#sheets').html(response.data.sheets);
                $('#button_group').html(response.data.groups);
                $('#export_table_path').val(response.data.table_path);
                $('#tableCode').val(tableCode);

                initializeSheetSelector();
                setupCellInspector();
                updateTranslationWorkspaceButton();
                const title = ($('#table_form .xbrl-title').first().text() || '').trim();
                $('#tableTitle').html(escapeInspectorHtml(String(tableCode || '').replace(/_/g, ' ').toUpperCase())
                    + (title && title.toLowerCase() !== String(tableCode || '').toLowerCase() ? '<small>' + escapeInspectorHtml(title) + '</small>' : ''));
                markActiveNav();
                applyFocusCell();
                $('#validateInstanceBtn').prop('disabled', false);
                setupKeySuggestions();
                enhanceNumberInputs();
                applyPrior();
                scheduleAutoValidate(200);
                // Plugins (e.g. cell comments) mark the cells of the table now shown.
                document.dispatchEvent(new CustomEvent('areport:table', { detail: { instanceId: instanceId, tablePath: response.data.table_path, tableCode: tableCode, sheet: currentSheetCode() } }));


                if (response.data.aspectNode == true) {
                    aspectNode = response.data.aspectNode;

                    $("#openY").show();

                    $('#addRow').off('click.add');

                    $("#addRow").on('click.add', function () {

                        var rowCount = $('#table tbody').find('tr').length + 1;

                        $('#table tbody>tr:last').clone(true).each(function () {

                            $(this).find('td input, td select').each(function () {


                                let name = $(this).attr('name').replace(/(c\d*r)\d*([^]*)/, "$1" + rowCount + "$2");
                                let id = name.substring(0, name.indexOf('['));
                                $(this).attr('name', name);
                                if ($(this).attr('type') != 'hidden') {
                                    $(this).val('');
                                    $(this).attr('id', id);
                                }
                            });

                        }).insertAfter('#table tbody>tr:last');

                        rowCount++;
                        markDirty();

                    });

                    $('#delRow').off('click.del');

                    $("#delRow").on('click.del', function () {

                        let rowCount = $('#table tbody').find('tr').length;
                        let $tbody = $("#table tbody");

                        let $last = $tbody.find('tr:last');
                        if ($last.is(':first-child')) {
                            alert('You cannot delete the last one!');
                        } else {
                            $last.remove();
                            rowCount--;
                            markDirty();
                        }
                    });


                }
            }).catch(function (error) {
                window.alert(getRequestErrorMessage(error, 'Unable to load the selected table. Please refresh and try again.'));
            }).finally(function () {
                hidePleaseWait();
                // A freshly loaded table matches what is stored; let its own init events settle first.
                setTimeout(function () {
                    loadingTable = false;
                    savedVersion = editVersion;
                    if (!saveInFlight && lastSavedText) setSaveStatus('saved', lastSavedText);
                }, 300);
            });
        }

        $(document).on('click', '#openTranslationWorkspace', function () {
            saveIfDirty().then(function () {
                openTranslationWorkspace();
            }).catch(function () {});
        });


        // ---- Instance settings & filing indicators ----
        function openInstanceSettings(tab) {
            $('#isError').hide();
            axios.get('/instance/settings', { params: { instance_id: instanceId } }).then(function (response) {
                const d = response.data, st = d.settings;
                const entity = document.getElementById('isEntity');
                entity.innerHTML = '<option value="">— no entity —</option>' + (d.entities || []).map(function (e) {
                    return '<option value="' + e.id + '"' + (d.entity && d.entity.id === e.id ? ' selected' : '') + '>'
                        + escapeInspectorHtml(e.name + ' (' + e.lei + ')') + '</option>';
                }).join('');
                $('#isPeriod').val(d.reference_date || '');
                $('#isDue').val(d.due_date || '').attr('placeholder', d.due_default || '');
                $('#isDueHelp').text(d.due_default ? 'Empty = EBA remittance date (' + d.due_default.split('-').reverse().join('.') + ').' : 'Leave empty for the EBA remittance date.');
                $('#isConsolidation').val(st.consolidation || 'CON');
                $('#isCountry').val(st.country || '').attr('placeholder', st.country_default ? st.country_default + ' (from entity)' : 'e.g. FR');
                $('#isCurrency').val(st.base_currency || 'EUR');
                $('#isScale').html(Object.keys(d.scales).map(function (k) {
                    return '<option value="' + k + '"' + (String(st.monetary_scale) === k ? ' selected' : '') + '>' + d.scales[k] + '</option>';
                }).join(''));
                $('#isDecMon').val(st.decimals_monetary); $('#isDecPct').val(st.decimals_percentage);
                $('#isDecInt').val(st.decimals_integer); $('#isDecDec').val(st.decimals_decimal);

                const mod = d.module;
                $('#isModule').toggle(!!mod).html(mod ? '<b>' + escapeInspectorHtml(mod.framework + ' · ' + mod.module) + '</b> module version '
                    + escapeInspectorHtml(mod.version || '?') + ' — applies to reference dates from <b>' + escapeInspectorHtml(mod.from || '—')
                    + '</b>' + (mod.to ? ' to <b>' + escapeInspectorHtml(mod.to) + '</b>' : '') : '');

                const rows = Object.keys(d.templates || {}).map(function (code) {
                    const t = d.templates[code];
                    const choice = t.choice === true ? 'true' : (t.choice === false ? 'false' : 'auto');
                    const cls = (t.choice === false && t.has_data) ? 'fi-row-err' : ((t.choice === true && !t.has_data) ? 'fi-row-warn' : '');
                    return '<tr class="' + cls + '"><td><b>' + escapeInspectorHtml(code) + '</b></td>'
                        + '<td><span class="fi-data ' + (t.has_data ? 'yes">has data' : 'no">empty') + '</span></td>'
                        + '<td><select class="form-select form-select-sm" data-fi="' + escapeInspectorHtml(code) + '">'
                        + '<option value="auto"' + (choice === 'auto' ? ' selected' : '') + '>Auto (' + (t.has_data ? 'reported' : 'not reported') + ')</option>'
                        + '<option value="true"' + (choice === 'true' ? ' selected' : '') + '>Reported</option>'
                        + '<option value="false"' + (choice === 'false' ? ' selected' : '') + '>Not reported</option>'
                        + '</select></td></tr>';
                });
                $('#isIndicatorRows').html(rows.join('') || '<tr><td colspan="3" class="text-muted">No templates found for this module.</td></tr>');

                window.appModal.show('instanceSettings');
                if (tab === 'indicators') {
                    bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#isIndicators"]')).show();
                }
            }).catch(function (error) {
                window.alert(getRequestErrorMessage(error, 'Unable to load the instance settings.'));
            });
        }

        $('#instanceSettingsBtn').on('click', function () { openInstanceSettings(); });

        $('#isSave').on('click', function () {
            const fi = {};
            document.querySelectorAll('#isIndicatorRows select[data-fi]').forEach(function (sel) { fi[sel.dataset.fi] = sel.value; });
            axios.post('/instance/settings', {
                instance_id: instanceId,
                entity_id: $('#isEntity').val() || null,
                period: $('#isPeriod').val(),
                due_date: $('#isDue').val() || null,
                consolidation: $('#isConsolidation').val(),
                country: $('#isCountry').val().trim() || null,
                base_currency: $('#isCurrency').val().trim(),
                monetary_scale: $('#isScale').val(),
                decimals_monetary: $('#isDecMon').val(),
                decimals_percentage: $('#isDecPct').val(),
                decimals_integer: $('#isDecInt').val(),
                decimals_decimal: $('#isDecDec').val(),
                filing_indicators: fi,
            }).then(function () {
                // Entity, date and scope show in the ribbon; reload so everything reflects them.
                window.appModal.hide('instanceSettings');
                saveIfDirty().finally(function () { window.location.reload(); });
            }).catch(function (error) {
                const errs = error.response && error.response.data && error.response.data.errors;
                $('#isError').show().text(errs ? Object.values(errs).flat().join(' ') : getRequestErrorMessage(error, 'Saving the settings failed.'));
            });
        });

        // ---- xBRL export: save, run the pre-export check, then download ----
        let pendingExport = null;

        $(document).on('click', '[data-xbrl-export]', function () {
            const format = this.dataset.xbrlExport;
            pendingExport = format;
            const body = document.getElementById('exportCheckBody');
            $('#exportCheckLabel').text(format === 'xml' ? 'xBRL-XML instance' : 'xBRL-CSV package');
            body.innerHTML = '<div class="dpm-loading">Checking the instance…</div>';
            $('#exportCheckGo').prop('disabled', true);
            window.appModal.show('exportCheck');

            saveIfDirty().then(function () {
                return axios.post('/instance/preflight', { instance_id: instanceId, format: format });
            }).then(function (response) {
                const d = response.data || {};
                const checks = d.checks || [];
                let html = d.ok
                    ? '<div class="pf-ok"><i class="fas fa-circle-check"></i> Ready to export' + (checks.length ? ' — review the warnings below.' : '.') + '</div>'
                    : '<p class="mb-2 small text-danger"><b>Fix these before exporting:</b></p>';
                if (checks.length) {
                    html += '<ul class="pf-list mt-2">' + checks.map(function (c) {
                        return '<li class="pf-item ' + c.level + '"><i class="fas ' + (c.level === 'error' ? 'fa-circle-xmark' : 'fa-triangle-exclamation') + ' mt-1"></i><span>' + escapeInspectorHtml(c.message) + '</span></li>';
                    }).join('') + '</ul>';
                }
                if (d.package) {
                    html += '<div class="pf-package">' + escapeInspectorHtml(d.package) + '</div>';
                }
                body.innerHTML = html;
                $('#exportCheckGo').prop('disabled', !d.ok);
            }).catch(function (error) {
                body.innerHTML = '<span class="dpm-val-err">' + escapeValHtml(getRequestErrorMessage(error, 'The check could not run (is the table saved?).')) + '</span>';
            });
        });

        $('#exportCheckGo').on('click', function () {
            const form = document.getElementById('instanceExportForm');
            form.action = pendingExport === 'xml' ? '/instance/export' : '/instance/export-csv';
            form.submit();
            window.appModal.hide('exportCheck');
        });

        // Validate the file the export would produce with Arelle (offline, local taxonomy).
        $('#exportCheckArelle').on('click', function () {
            const btn = this, body = document.getElementById('exportCheckBody');
            $('#arelleResult').remove();
            const box = document.createElement('div');
            box.id = 'arelleResult';
            box.className = 'arl-box mt-3';
            box.innerHTML = '<div class="dpm-loading">Arelle is validating the ' + (pendingExport === 'xml' ? 'xBRL-XML instance' : 'xBRL-CSV package') + '…</div>';
            body.appendChild(box);
            btn.disabled = true;
            axios.post('/instance/arelle', { instance_id: instanceId, format: pendingExport }).then(function (response) {
                const d = response.data || {};
                const issues = (d.messages || []).filter(function (m) { return m.level !== 'info'; });
                const cls = d.errors ? 'error' : (d.warnings ? 'warning' : 'ok');
                let html = '<div class="arl-sum ' + cls + '"><i class="fas ' + (d.errors ? 'fa-circle-xmark' : d.warnings ? 'fa-triangle-exclamation' : 'fa-circle-check') + '"></i> '
                    + '<b>Arelle: ' + (d.errors ? d.errors + ' error' + (d.errors === 1 ? '' : 's') : 'valid XBRL') + (d.warnings ? ', ' + d.warnings + ' warning' + (d.warnings === 1 ? '' : 's') : '') + '</b>'
                    + ' <span>· ' + d.seconds + ' s</span></div>';
                if (issues.length) {
                    html += '<ul class="arl-list">' + issues.map(function (m) {
                        return '<li class="' + escapeInspectorHtml(m.level) + '"><code>' + escapeInspectorHtml(m.code) + '</code><span>' + escapeInspectorHtml(m.text) + '</span></li>';
                    }).join('') + '</ul>';
                }
                box.innerHTML = html;
            }).catch(function (error) {
                box.innerHTML = '<span class="dpm-val-err">' + escapeValHtml(getRequestErrorMessage(error, 'Arelle validation failed.')) + '</span>';
            }).finally(function () { btn.disabled = false; });
        });

        $('#exportCheckSettings').on('click', function () {
            window.appModal.hide('exportCheck');
            openInstanceSettings();
        });

        /* Import*/
        $('#validateInstanceBtn').on('click', function () { validateInstance(); });

        $("#import").submit(function (e) {
            window.appModal.show('pleaseWaitDialog');

            var formData = new FormData($(this)[0]);
            var col = 0;
            $('th[data-col]').each(function () {
                col = col + 1;
            });
            formData.append('column', col);
            formData.append('colspanmax', $(".xbrl-title").prop("colSpan"));
            formData.append('rowspanmax', $(".xbrl-title").prop("rowSpan"));
            formData.append('typ_table', aspectNode);
            formData.append('table_code', tableCode);
            // An xBRL-XML instance is matched to the open table and sheet by data point.
            formData.append('tab', $('#export_table_path').val() || '');
            formData.append('sheet', $('#sheets').find(':selected').val() || '');
            const importInput = this.querySelector('#fileToUpload');
            $.ajax({
                type: "post",
                url: '/table/import',
                data: formData, /* serializes the form's elements. */
                success: function (data) {
                    if (importInput) importInput.value = '';

                    if (data && data.ext === 'cells') {   // Excel template read by row / column codes
                        let filled = 0, missing = 0;
                        for (const cell in (data.file || {})) {
                            const input = document.getElementById(cell);
                            if (input && !input.disabled && input.type !== 'hidden') { setCell(input, data.file[cell], true) ? filled++ : missing++; } else missing++;
                        }
                        const s = data.summary || {};
                        if (filled) markDirty();
                        window.alert(filled + ' of ' + (s.values || 0) + ' values from sheet "' + (s.sheet || '') + '" were filled into this table'
                            + (missing ? ' (' + missing + ' had no matching cell here)' : '') + '. Review them and save.');
                        return;
                    }
                    if (data && data.ext === 'xbrl') {
                        let filled = 0;
                        for (const cell in (data.file || {})) {
                            const input = document.getElementById(cell);
                            if (input) { $(input).val(data.file[cell]); if (input.dataset.num) showNum(input, data.file[cell]); filled++; }
                        }
                        const s = data.summary || {};
                        let message = filled + ' of ' + (s.facts || 0) + ' facts in the instance were filled into this table.';
                        if (s.typed) message += ' ' + s.typed + ' facts with typed (open) keys were not imported.';
                        if (filled) markDirty();
                        window.alert(message);
                        return;
                    }

                    if (aspectNode == true) {
                        console.log(data['file']['row']);
                        $(".datepicker").datepicker("destroy");
                        row(data['file']['row']);
                        dataSet();
                    }

                    for (var i in data['file']) {
                        if (document.getElementById(i) != null) {
                            $('#' + i).val(data['file'][i]);
                        }
                    }
                    markDirty();
                },
                error: function (xhr) {
                    if (importInput) importInput.value = '';
                    const reason = xhr && xhr.status === 422 && xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message);
                    window.alert(reason || 'Unable to import the selected file. Please verify the file and try again.');
                },
                complete: function () {
                    hidePleaseWait();
                },
                cache: false,
                contentType: false,
                processData: false
            });
            e.preventDefault(); /* avoid to execute the actual submit of the form. */
        });


        function row(len) {


            $("#table tbody").find("tr:gt(0)").remove();
            rowCount = 2


            for (i = 0; i < len; i++) {

                $('#table tbody>tr:last').clone(true).each(function () {

                    $(this).find('td input, td select').each(function () {
                        var tmpName = $(this).attr('name');
                        var name = $(this).attr('name').replace(/(c\d*r)\d*([^]*)/, "$1" + rowCount + "$2");
                        var id = name.substring(0, name.indexOf('['));
                        $(this).attr('name', name);
                        if ($(this).attr('type') != 'hidden') {
                            $(this).val('');
                            $(this).attr('id', id);
                        }
                    });
                }).insertAfter('#table tbody>tr:last');
                /* $('#table tbody>tr:last').each(function () {
                 this.reset();
                 });*/
                rowCount++;
            }

        }

        $("#sheets").on("change", "#sheet",
            function () {
                const previousSheetValue = this.dataset.prevValue || null;
                const nextSheetValue = this.value;

                if (previousSheetValue === nextSheetValue) {
                    return;
                }

                const sheetSelect = this;
                saveIfDirty(previousSheetValue).then(function () {
                    switchSheet(nextSheetValue);
                }).catch(function () {
                    sheetSelect.value = previousSheetValue;
                    window.alert('The open sheet could not be saved, so it stays open. Check the save status and try again.');
                });
            });

        // Key suggestions: an identifier entered in one table (the counterparty code in
        // C 27.00) is offered in every other key of the same dimension or typed domain
        // (C 28.00, C 29.00…), with the name found on its row.
        let keySuggestionSeq = 0;
        function setupKeySuggestions() {
            const seq = ++keySuggestionSeq;
            const groups = {};
            document.querySelectorAll('#table_form input[type="hidden"][name$="[dim]"]').forEach(function (hidden) {
                let dim;
                try { dim = JSON.parse(hidden.value); } catch (e) { return; }
                const key = Object.keys(dim).find(function (k) { return dim[k] === '*'; });
                if (!key || !dim.typ) return;   // explicit keys are selects already
                const cell = hidden.name.replace(/\[dim\]$/, '');
                const input = document.querySelector('#table_form input[name="' + cell + '[value]"]');
                if (!input) return;
                (groups[key + '|' + dim.typ] = groups[key + '|' + dim.typ] || []).push(input);
            });
            const sheetBox = document.querySelector('#sheets .app-typed-sheet');
            const sheetInput = document.getElementById('sheetKeyNew');
            if (sheetBox && sheetInput && sheetBox.dataset.dimension) {
                const k = sheetBox.dataset.dimension + '|' + (sheetBox.dataset.typ || '');
                (groups[k] = groups[k] || []).push(sheetInput);
            }
            Object.keys(groups).forEach(function (k, i) {
                const parts = k.split('|');
                axios.get('/table/key-suggestions', { params: { instance_id: instanceId, dimension: parts[0], typ: parts[1] } }).then(function (response) {
                    if (seq !== keySuggestionSeq) return;
                    const items = (response.data && response.data.items) || [];
                    if (!items.length) return;
                    const id = 'keysug-' + i;
                    let list = document.getElementById(id);
                    if (!list) { list = document.createElement('datalist'); list.id = id; document.body.appendChild(list); }
                    list.innerHTML = items.map(function (it) {
                        const label = (it.label ? it.label + ' · ' : '') + it.table;
                        return '<option value="' + escapeInspectorHtml(it.value) + '" label="' + escapeInspectorHtml(label) + '">' + escapeInspectorHtml(label) + '</option>';
                    }).join('');
                    groups[k].forEach(function (input) {
                        input.setAttribute('list', id);
                        input.setAttribute('autocomplete', 'off');
                        input.title = items.length + ' identifier(s) already used in this instance — start typing or pick one';
                    });
                }).catch(function () {});
            });
        }

        // Typed z-axis (one sheet per key value, e.g. a covered bond issue): add a key.
        $("#sheets").on("click", "#sheetKeyAdd", function () {
            const box = this.closest('.app-typed-sheet');
            const input = document.getElementById('sheetKeyNew');
            const value = (input.value || '').trim();
            if (!value) { input.focus(); return; }
            const select = document.getElementById('sheet');
            const code = 'k:' + value;
            let option = Array.from(select.options).find(function (o) { return o.text === value; });
            if (!option) {
                const typed = {};
                typed[box.dataset.dimension] = { typ: box.dataset.typ, value: value };
                option = new Option(value, JSON.stringify({ typedMember: typed, sheet: code }));
                select.add(option);
            }
            input.value = '';
            select.value = option.value;
            $(select).trigger('change');
        });
        $("#sheets").on("keydown", "#sheetKeyNew", function (e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#sheetKeyAdd').trigger('click'); }
        });

        function switchSheet(nextSheetValue) {
                $(".xbrl-input,.xbrl-input-open,.xbrl-input-text ").each(function () {


                    var id = $(this).attr('id');
                    if (typeof id !== "undefined") {
                        $('#' + id).val('');
                    }

                });

                var gr = 'G';
                var group = document.querySelector('#group');

                if ($('#button_group').children().length > 0) {
                    gr = 'S'
                    $('#button_group .active').each(function () {
                        group = $(this).val();
                    });

                }


                changeTable(group, gr, nextSheetValue);
        }

    </script>
@endsection
