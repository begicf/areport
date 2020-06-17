@extends('layouts.app')

@section('content')

    @include('flash.flash-message')


    <div class="col-lg-12">
        <table id="table"
               data-toggle="table"
               data-height="750"
               data-show-refresh="true"
               data-show-fullscreen="true"
               data-ajax="ajaxRequest"
               data-search="true"
               data-side-pagination="server"
               data-page-size="15"
               data-page-list="[15, 30, 50, 100, all]"
               data-pagination="true">
            <thead>
            <tr>

                <th data-field="period" data-width="200" data-sortable="true" data-align="center">Period</th>
                <th data-field="module_name" data-width="400" data-sortable="true">Modul</th>
                <th data-field="module_path" data-align="center" data-sortable="true">Modul path</th>
                <th data-field="open" data-width="200" data-align="center" data-formatter="actionOpen">Open</th>
            </tr>
            </thead>
        </table>

    </div>



    <script>

        var $table = $('#table');

        function actionOpen(value, row, index) {

            return "<form method='POST' action='table'>" +


                "<input type='hidden' name='_token' value='{{ csrf_token() }}'>" +
                "<input type='hidden' name = 'period' value='" + row.period + "'/>\n" +
                "<input type='hidden' name = 'id' value='" + row.id + "'/>\n" +
                "<button name='view' value='true'  class='btn btn-sm btn-primary' type='submit'><span class='fas fa-layer-group' aria-hidden='true'></span> Open</button>" +
                "</form>";

        }


        function ajaxRequest(params) {

            $.ajax(
                {
                    method: "POST",
                    url: "/areport/json",
                    data: {
                        _token: "{{ csrf_token() }}",
                        sort: params.data.sort,
                        order: params.data.order,
                        offset: params.data.offset,
                        limit: params.data.limit,
                        search: params.data.search,
                    },
                    dataType: "json",
                    success: function (data) {

                        params.success(data, null, {})
                    },
                    error: function (er) {
                        //   params.error(er);
                    }
                });
        };

    </script>
@endsection
