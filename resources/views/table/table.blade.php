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
    </style>

    <div class="app-page app-report-page">
        <div class="app-report-topbar">
            <section class="app-page-header app-report-header">
                <div class="app-page-kicker">
                    <i class="fas fa-table"></i>
                    Report editor
                </div>
                <h1 class="app-page-title">{{ $module_name }}</h1>
                <p class="app-page-copy">
                    Dense workspace for group switching, import, export, and direct data entry.
                </p>
            </section>

            <section class="app-panel app-report-controls">
                <div class="app-panel-header">
                    <div>
                        <h2 class="app-panel-title">Workspace controls</h2>
                        <p class="app-panel-copy">Compact actions for navigation, export, and import.</p>
                    </div>
                </div>

                <div class="app-report-controls-bar" role="toolbar" aria-label="Table controls">
                    <button type="button" class="btn btn-primary app-report-instance-btn" onclick="module()">
                        <i class="fas fa-table"></i>
                        New instance
                    </button>

                    <div class="app-report-field app-report-group-field">
                        <label for="group" class="form-label">Table group</label>
                        <select id="group" onchange="changeTable(this,'G')" class="form-select">
                            @foreach($groups as $key=>$row)
                                <option value="{{$row}}">
                                    {{$key}}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="app-report-field app-report-export-field">
                        <label class="form-label d-block">Export</label>
                        <div class="dropdown">
                            <button id="btnGroupDrop1" type="button" class="btn btn-outline-secondary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                Export
                            </button>
                            <form id="export_table" method="post" action="table/export">
                                @csrf
                                <input name="period" type="hidden" value="{{$period}}">
                                <input name="mod" type="hidden" value="{{$mod}}">
                                <input id="export_table_path" name="table" type="hidden">

                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                    <button class="dropdown-item" name="export_type" value="xlsx" type="submit">
                                        <i class="text-success fas fa-file-excel"></i>
                                        Export as .xlsx
                                    </button>
                                    <button class="dropdown-item" name="export_type" value="pdf" type="submit">
                                        <i class="text-danger fas fa-file-pdf"></i>
                                        Export as .pdf
                                    </button>
                                    <button class="dropdown-item" name="export_type" value="html" type="submit">
                                        <i class="text-primary fas fa-file-code"></i>
                                        Export as .html
                                    </button>
                                    <button class="dropdown-item" formaction="/instance/export" type="submit">
                                        <i class="fas fa-file-alt"></i>
                                        Export as XBRL-XML instance
                                    </button>
                                    <button class="dropdown-item" formaction="/instance/export-csv" type="submit">
                                        <i class="fas fa-file-archive"></i>
                                        Export as xBRL-CSV package
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <form id="import" class="app-report-import-form" method="POST" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="app-report-field app-report-file-field">
                            <label for="fileToUpload" class="form-label">Source file</label>
                            <input type="file" name="fileToUpload" id="fileToUpload" class="form-control">
                            <input id="sheetcodeImport" name="sheetcode" type="hidden"/>
                        </div>

                        <div class="app-report-import-actions">
                            <button class="btn btn-primary" value="1">Import</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        @include('flash.flash-message')

        <section class="app-panel app-report-panel">
            <div class="app-panel-header mb-0">
                <div>
                    <h2 class="app-panel-title">Active table workspace</h2>
                    <p class="app-panel-copy">Use group tabs, sheet selection, and row actions without wasting vertical space.</p>
                </div>
            </div>

            <div class="app-report-toolbar">
                <div id="button_group" class="app-report-groupbar"></div>
                <div id="sheets" class="app-report-sheets"></div>
                @if(Route::has('areport-pro.dpm-architect.index'))
                    <div class="app-report-row-actions app-report-translate-actions">
                        <button type="button" class="btn app-report-translate-btn" id="openTranslationWorkspace" disabled>
                            <i class="fas fa-language"></i>
                            <span>Translate</span>
                        </button>
                    </div>
                @endif
                <div id="openY" class="app-report-row-actions" style="display: none">
                    <button type="button" class="btn btn-outline-secondary" id="addRow">
                        <i class="fas fa-plus"></i>
                        Add row
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="delRow">
                        <i class="fas fa-minus"></i>
                        Remove row
                    </button>
                </div>
                @if(Route::has('areport-pro.dpm-architect.index'))
                    <div class="app-report-row-actions app-report-validate-actions">
                        <button type="button" class="btn btn-primary" id="validateInstanceBtn" disabled>
                            <i class="fas fa-circle-check"></i>
                            Validate
                        </button>
                    </div>
                @endif
            </div>

            <div id="validationResult" class="app-report-validation" style="display:none"></div>

            <input type="hidden" id="tableCode" name="tableCode">

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
        </section>

    </div>
    <!--Call module modal-->
    @include('components.module')
    <!--Call module modal-->
    @include('components.please-wait')



    <script type="text/javascript">

        var aspectNode = null;
        var tableCode = null;
        var activeInspectorCell = null;
        var translationWorkspaceUrl = @json(Route::has('areport-pro.dpm-architect.index') ? route('areport-pro.dpm-architect.index') : null);

        function hidePleaseWait() {
            window.appModal.hide('pleaseWaitDialog');
        }

        function getRequestErrorMessage(error, fallbackMessage) {
            if (error && error.response && error.response.data) {
                if (typeof error.response.data === 'string' && error.response.data.trim() !== '') {
                    return error.response.data;
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
                changeTable(group);
            }
        }


        function dataSet() {
            $('.datepicker').datepicker({
                autoclose: true,
                dateFormat: "dd-mm-yy",

            });


        }

        function module() {


            axios.post('modules/group', {

                module: '{{$mod}}',

            }).then(function (response) {

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


        function save(sheet = null) {

            return axios.post('table/save', {
                'table_data': $("#table_form").serialize(),
                'period': '{{$period}}',
                'module': '{{$mod}}',
                'module_name': '{{$module_name}}',
                'sheet': (sheet ? sheet : $('#sheets').find(':selected').val()),
                'tab': $('#export_table_path').val(),

            }).catch(function (error) {
                console.error('Draft save failed.', error);
            });

        }

        // ---- Instance validation (reporting flow) ----
        function resetValidation() {
            $('#validateInstanceBtn').prop('disabled', true);
            const box = document.getElementById('validationResult');
            if (box) { box.style.display = 'none'; box.innerHTML = ''; }
            clearFailedCells();
        }

        function clearFailedCells() {
            document.querySelectorAll('#table_form td.dpm-cell-fail').forEach(function (td) {
                td.classList.remove('dpm-cell-fail');
            });
        }

        // Paint the cells the validator flagged red. Rules key cells as
        // "<tablecode>,r<row>,c<col>"; parser cells carry rc-code="c<col>r<row>".
        function highlightFailedCells(failedCells) {
            clearFailedCells();
            const set = new Set(failedCells || []);
            if (!set.size || !tableCode) return 0;
            const code = String(tableCode).toLowerCase();
            let hits = 0;
            document.querySelectorAll('#table_form td[rc-code]').forEach(function (td) {
                const m = /^c([^r]+)r(.+)$/.exec(td.getAttribute('rc-code') || '');
                if (!m) return;
                const col = 'c' + m[1].toLowerCase(), row = 'r' + m[2].toLowerCase();
                if (set.has(code + ',' + row + ',' + col) || set.has(code + ',' + col + ',' + row)) {
                    td.classList.add('dpm-cell-fail'); hits++;
                }
            });
            return hits;
        }

        function escapeValHtml(v) {
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function validateInstance() {
            const box = document.getElementById('validationResult');
            box.style.display = 'block';
            box.innerHTML = '<div class="dpm-loading">Running validation…</div>';
            // Persist the draft first so values are consistent, then validate the live form.
            save().finally(function () {
                axios.post('table/validate', {
                    'table_data': $("#table_form").serialize(),
                    'table_code': tableCode,
                    'tab': $('#export_table_path').val()
                }).then(function (response) {
                    const d = response.data || {};
                    if (!d.ok) { box.innerHTML = '<span class="dpm-val-err">' + escapeValHtml(d.error || 'Validation failed.') + '</span>'; return; }
                    const s = d.summary || {};
                    const hits = highlightFailedCells(d.failed_cells);
                    let html = '<div class="dpm-val-head">'
                        + '<span class="dpm-val-pill ok">' + (s.passed || 0) + ' passed</span>'
                        + '<span class="dpm-val-pill err">' + (s.failed || 0) + ' failed</span>'
                        + '<span class="dpm-val-pill ind">' + (s.indeterminate || 0) + ' indeterminate</span>'
                        + '<span class="dpm-val-pill mut">' + (s.unsupported || 0) + ' unsupported</span>'
                        + '<span class="dpm-val-meta">' + escapeValHtml(d.table) + ' · ' + (d.values || 0) + ' values · ' + (s.total || 0) + ' rules</span>'
                        + '<button type="button" class="dpm-val-close" onclick="resetValidationPanel()">&times;</button>'
                        + '</div>';
                    if ((d.failed || []).length) {
                        html += '<div class="dpm-val-note">' + hits + ' cell(s) highlighted in red below — fix them and re-validate.</div>'
                            + '<div class="dpm-val-fails">' + d.failed.map(function (f) {
                                return '<div class="dpm-val-fail">'
                                    + '<div class="dpm-val-fh"><i class="fas fa-circle dpm-sev-' + escapeValHtml(f.severity) + '"></i> <b>' + escapeValHtml(f.code) + '</b> '
                                    + '<code class="dpm-val-rule">' + escapeValHtml(f.rule || '') + '</code></div>'
                                    + '<div class="dpm-val-facts">' + (f.facts || []).map(function (x) {
                                        return '<span class="dpm-val-fact"><b>' + escapeValHtml(x.cell) + '</b> = ' + escapeValHtml(x.value === null ? '∅' : x.value) + '</span>';
                                    }).join('') + '</div></div>';
                            }).join('') + '</div>';
                    } else if (d.note) {
                        html += '<div class="dpm-val-note"><i class="fas fa-circle-info"></i> ' + escapeValHtml(d.note) + '</div>';
                    } else {
                        html += '<div class="dpm-val-ok"><i class="fas fa-check"></i> No rule violations.</div>';
                    }
                    box.innerHTML = html;
                }).catch(function (error) {
                    box.innerHTML = '<span class="dpm-val-err">' + escapeValHtml(getRequestErrorMessage(error, 'Validation request failed.')) + '</span>';
                });
            });
        }

        function resetValidationPanel() {
            const box = document.getElementById('validationResult');
            if (box) { box.style.display = 'none'; box.innerHTML = ''; }
            clearFailedCells();
        }

        function changeTable(selectedOb, type = 'G', sheet = null) {

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

            if (sheet == null) {
                save();
            }
            $("#tab").empty();
            $("#button_group").empty();
            $("#sheets").empty();
            tableCode = null;
            $('#tableCode').val('');
            updateTranslationWorkspaceButton();
            resetValidation();
            resetCellInspector('Loading the selected table.');

            axios.post('/table/ajax', {

                    'group': group,
                    'tab': table,
                    'mod': '{{$mod}}',
                    'period': '{{$period}}',
                    'sheet': sheet
                }
            ).then(function (response) {
                tableCode = response.data.tableCode;

                $('#table_form').html(response.data.table);
                $('#sheets').html(response.data.sheets);
                $('#button_group').html(response.data.groups);
                $('#export_table_path').val(response.data.table_path);
                $('#tableCode').val(tableCode);

                initializeSheetSelector();
                setupCellInspector();
                updateTranslationWorkspaceButton();
                $('#validateInstanceBtn').prop('disabled', false);


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
                        }
                    });


                }
            }).catch(function (error) {
                window.alert(getRequestErrorMessage(error, 'Unable to load the selected table. Please refresh and try again.'));
            }).finally(function () {
                hidePleaseWait();
            });
        }

        $(document).on('click', '#openTranslationWorkspace', function () {
            save().finally(function () {
                openTranslationWorkspace();
            });
        });


        /* Import*/
        $('#validateInstanceBtn').on('click', validateInstance);

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
            $.ajax({
                type: "post",
                url: '/table/import',
                data: formData, /* serializes the form's elements. */
                success: function (data) {

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
                },
                error: function () {
                    window.alert('Unable to import the selected file. Please verify the file and try again.');
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

                save(previousSheetValue);


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

                {{--var sheet = $(this).find(':selected').val();--}}
                {{--axios.post('table/get_data', {--}}

                {{--    period: '{{$period}}',--}}
                {{--    mod: '{{$mod}}',--}}
                {{--    tab: $('#export_table_path').val(),--}}
                {{--    sheet: sheet--}}

                {{--}).then(function (response) {--}}

                {{--    //Set to empty--}}
                //     $(".xbrl-input,.xbrl-input-open,.xbrl-input-text ").each(function () {
                //
                //
                //         var id = $(this).attr('id');
                //         if (typeof id !== "undefined") {
                //             $('#' + id).val('');
                //         }
                //
                //     });
                //

                {{--    if (aspectNode == true) {--}}
                {{--        row(response.data.row);--}}
                {{--    }--}}

                {{--    var data = response.data;--}}
                {{--    for (var i in data) {--}}

                {{--        if ($('#' + i).is("[type=number]")) {--}}
                {{--            $('#' + i).val(data[i].integer);--}}
                {{--        } else {--}}
                {{--            $('#' + i).val(data[i].string);--}}
                {{--        }--}}

                {{--    }--}}


                {{--});--}}


            });


    </script>
@endsection
