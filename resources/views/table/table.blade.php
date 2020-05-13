@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <div class="row pb-3">
            <div class="col col-lg-5">

                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                    <div class="btn-group mr-2" role="group" aria-label="First group">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#module"><i
                                class="fas fa-table"></i></button>
                    </div>
                    <div class="btn-group mr-4" role="group" aria-label="First group">
                        <select id="group" onchange="changeTable(this,'G')" class="form-control">
                            @foreach($groups as $key=>$row)
                                <option value="{{$row}}">
                                    {{$key}}
                                </option>
                            @endforeach
                        </select>
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


    function changeTable(selectedOb, type = 'G') {

        var group;
        var table = null;

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
        })
    }

</script>
