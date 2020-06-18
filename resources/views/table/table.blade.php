@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <div class="row p-2">

            <div class="col-12 col-md-9">


                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                    <div class="btn-group mr-2" role="group" aria-label="First group">
                        <button type="button" class="btn btn-primary" data-toggle="modal" onclick="module()"><i
                                class="fas fa-table"></i></button>
                    </div>
                    <div class="btn-group mr-4" role="group" aria-label="Second group">
                        <select id="group" onchange="changeTable(this,'G',event)" class="form-control">
                            @foreach($groups as $key=>$row)
                                <option value="{{$row}}">
                                    {{$key}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                        <div class="btn-group mr-4" role="group" aria-label="Third group">

                            <div class="btn-group" role="group">
                                <button id="btnGroupDrop1" type="button" class="btn btn-secondary dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Export
                                </button>
                                <form id="export_table" method="post" action="table/export">
                                    @csrf
                                    <input name="period" type="hidden" value="{{$period}}">
                                    <input name="mod" type="hidden" value="{{$mod}}">
                                    <input id="export_table_path" name="table" type="hidden">

                                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                        <button class="dropdown-item" name="export_type" value="xlsx" type="submit"><i
                                                class="text-success fas fa-file-excel"></i>
                                            Export to .xlsx
                                        </button>
                                        <button class="dropdown-item" name="export_type" value="pdf" type="submit"><i
                                                class="text-danger fas fa-file-pdf"></i>
                                            Export to .pdf
                                        </button>
                                        <button class="dropdown-item" name="export_type" value="html" type="submit"><i
                                                class="text-primary fas fa-file-code"></i>
                                            Export to .html
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>


            </div>


            <div class="col-6 col-md-3">


                <div class="card">
                    <div class="card-header">Import *.xlsx, *.xml or *.json file</div>
                    <div class="card-body">
                        <form id="import" class="form-inline" method="POST" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="form-group">

                                <input type="file" name="fileToUpload" id="fileToUpload">
                                <input id="sheetcodeImport" name="sheetcode" type="hidden"/>

                            </div>
                            <div class="form-group float-right">
                                <button class="btn btn-primary" value="1">Import</button>
                            </div>
                        </form>
                    </div>
                </div>


            </div>
        </div>


        <!--Button Table Group -->
        <div id="button_group" class="col-lg-12 p-2"></div>
        <!--Sheets Z -->
        <div id="sheets" class="col-lg-3 p-2"></div>
        <!--Table -->
        <form id="table_form" method="post">

        </form>

        <div id="openY" class="form-group" style="display: none">
            <a class="btn btn-light" id="addRow">+</a>
            <a class="btn btn-light" id="delRow">-</a>
        </div>

    </div>

    <!--Flash message-->
    @include('flash.flash-message')
    <!--Call module modal-->
    @include('components.module')
    <!--Call module modal-->
    @include('components.please-wait')






    <script type="text/javascript">

        var aspectNode = null;

        window.onload = function () {
            const group = document.querySelector('#group');
            changeTable(group)
        }


        function intersection(o1, o2) {
            return Object.keys(o1).concat(Object.keys(o2)).sort().reduce(function (r, a, i, aa) {
                if (i && aa[i - 1] === a) {
                    r.push(a);
                }
                return r;
            }, []);
        }

        function module() {



        axios.post('modules/group', {

            module: '{{$mod}}',

        }).then(function (response) {

            var optionsHTML = [];

            $('#view_table').val('1');
            $groups = {!! json_encode($groups) !!};
            console.log($groups);
            console.log(response.data);

            console.log(intersection($groups,response.data));
            for (var k in response.data) {

                console.log(k);

                optionsHTML.push("<option value='" + response.data[k] + "'>" + k + "</option>")
            }
            $('#multiselect option').remove();
            $('#multiselect_to option').remove();


            $('#multiselect').append(optionsHTML);
            $('#module').modal();

        })

        }


        function save(sheet = null) {

            axios.post('table/save', {
                'table_data': $("#table_form").serialize(),
                'period': '{{$period}}',
                'module': '{{$mod}}',
                'module_name': '{{$module_name}}',
                'sheet': (sheet ? sheet : $('#sheets').find(':selected').val()),
                'tab': $('#export_table_path').val(),

            });

        }

        function changeTable(selectedOb, type = 'G') {

            var group;
            var table = null;

            $("#openY").hide();

            if (type == 'G') {
                group = selectedOb.value;
            } else {
                group = document.querySelector('#group').value;
                table = selectedOb.value;
            }


            $('#pleaseWaitDialog').modal();

            save();

            $("#tab").empty();
            $("#button_group").empty();
            $("#sheets").empty();

            axios.post('/table/ajax', {

                    'group': group,
                    'tab': table,
                    'mod': '{{$mod}}',
                    'period': '{{$period}}'
                }
            ).then(function (response) {


                $('#pleaseWaitDialog').modal('hide');

                $('#table_form').html(response.data.table);
                $('#sheets').html(response.data.sheets);
                $('#button_group').html(response.data.groups);
                $('#export_table_path').val(response.data.table_path);

                $('#sheet').selectpicker();


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
            })
        }


        /* Import*/
        $("#import").submit(function (e) {

            var formData = new FormData($(this)[0]);
            var col = 0;
            $('th[data-col]').each(function () {
                col = col + 1;
            });
            formData.append('column', col);
            formData.append('colspanmax', $(".xbrl-title").prop("colSpan"));
            formData.append('rowspanmax', $(".xbrl-title").prop("rowSpan"));
            $.ajax({
                type: "post",
                url: '/table/import',
                data: formData, /* serializes the form's elements. */
                success: function (data) {

                    if (aspectNode == true) {
                        //$(".datepicker").datepicker("destroy");
                        row(data['file']['row']);
                        dataSet();
                    }

                    for (var i in data['file']) {
                        if (document.getElementById(i) != null) {
                            $('#' + i).val(data['file'][i]);
                        }
                    }
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

        $("#sheets").on("changed.bs.select",
            function (e, clickedIndex, isSelected, oldValue) {

                save(oldValue);


                var sheet = $(this).find(':selected').val();
                axios.post('table/get_data', {

                    period: '{{$period}}',
                    mod: '{{$mod}}',
                    tab: $('#export_table_path').val(),
                    sheet: sheet

                }).then(function (response) {

                    //Set to empty
                    $(".xbrl-input,.xbrl-input-open,.xbrl-input-text ").each(function () {


                        var id = $(this).attr('id');
                        if (typeof id !== "undefined") {
                            $('#' + id).val('');
                        }

                    });


                    if (aspectNode == true) {
                        row(response.data.row);
                    }

                    var data = response.data;
                    for (var i in data) {

                        if ($('#' + i).is("[type=number]")) {
                            $('#' + i).val(data[i].integer);
                        } else {
                            $('#' + i).val(data[i].string);
                        }

                    }


                });


            });


    </script>
@endsection
