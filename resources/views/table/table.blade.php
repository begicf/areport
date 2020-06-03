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
                                        <a class="dropdown-item" onclick="exportTable('xlsx')"><i
                                                class="text-success fas fa-file-excel"></i>
                                            Export to .xlsx</a>
                                        <a class="dropdown-item" onclick="exportTable('pdf')"><i class="text-danger fas fa-file-pdf"></i>
                                            Export to .pdf</a>
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
        <div id="tab" class="overflow-auto"></div>


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
    @stack('stacks/areport')





    <script type="text/javascript">


        window.onload = function () {
            const group = document.querySelector('#group');
            changeTable(group)
        }

        function module() {


            $("#period").val('{{$period}}');

            axios.post('modules/group', {

                module: '{{$mod}}'
            }).then(function (response) {

                var optionsHTML = [];

                for (var k in response.data) {

                    optionsHTML.push("<option value='" + response[k] + "'>" + k + "</option>")
                }

                $('#multiselect option').remove();
                $('#multiselect_to option').remove();


                $('#multiselect').append(optionsHTML);
                $('#module').modal();

            })

        }

        function exportTable(val) {


            $("#export_table").submit(function(eventObj) {
                $(this).append('<input type="hidden" name="someName" value="someValue">');
                return true;
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

            $("#tab").empty();
            $("#button_group").empty();
            $("#sheets").empty();

            $('#pleaseWaitDialog').modal();

            axios.post('/table/ajax', {

                    'group': group,
                    'tab': table,
                    'mod': '{{$mod}}',
                    'period': '{{$period}}'
                }
            ).then(function (response) {

                $('#pleaseWaitDialog').modal('hide');

                $('#tab').html(response.data.table);
                $('#sheets').html(response.data.sheets);
                $('#button_group').html(response.data.groups);
                $('#export_table_path').val(response.data.table_path);

                $('#sheet').selectpicker();


                if (response.data.aspectNode == true) {
                    var rowCount = $('#table tbody').find('tr').length;

                    $("#openY").show();


                }
            })
        }


    </script>
@endsection
