@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <div class="row">

            <div class="col-auto mr-auto">
                <div class="form-row align-items-center">

                    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                        <div class="btn-group mr-2" role="group" aria-label="First group">
                            <button type="button" class="btn btn-primary" data-toggle="modal" onclick="module()"><i
                                    class="fas fa-table"></i></button>
                        </div>
                        <div class="btn-group mr-4" role="group" aria-label="Second group">
                            <select id="group" onchange="changeTable(this,'G')" class="form-control">
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
                                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                        <a class="dropdown-item" href="#"><i class="text-success fas fa-file-excel"></i>
                                            Export to .xlsx</a>
                                        <a class="dropdown-item" href="#"><i class="text-danger fas fa-file-pdf"></i>
                                            Export to .pdf</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-auto">

                <form class="form-inline float-right">
                    <div class="form-group">
                        <label for="exampleFormControlFile1">*.xlsx </label>
                        <input type="file" class="form-control-file" id="exampleFormControlFile1">
                    </div>

                    <button type="submit" class="btn btn-primary">Import</button>

                </form>

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


@endsection

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

            $('#sheet').selectpicker();


            if (response.data.aspectNode == true) {
                var rowCount = null;
                rowCount = $('#table tbody').find('tr').length;
                console.log(rowCount);
                $("#openY").show();
                /*add rows*/
                $("#addRow").on('click', function (event) {
                    event.preventDefault();

                    $('#table tbody>tr:last').clone(true).each(function () {

                        $(this).find('td input, td select').each(function () {


                            var name = $(this).attr('name').replace(/(c\d*r)\d*([^]*)/, "$1" + rowCou


                            nt + "$2");
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
                    //dataSet();
                    rowCount++;
                    return false;
                });

                /* delete rows */
                var $tbody = $("#table tbody");
                $("#delRow").click(function () {
                    var $last = $tbody.find('tr:last');
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


</script>
