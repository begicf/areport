@extends('layouts.app')

@section('shell_class', 'app-shell app-shell-fluid')

@section('content')

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
            </div>

            <input type="hidden" id="tableCode" name="tableCode">

            <div id="cellInspector" class="app-report-inspector">
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
                    <form id="table_form" method="post"></form>
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

                initializeSheetSelector();
                setupCellInspector();


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


        /* Import*/
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
