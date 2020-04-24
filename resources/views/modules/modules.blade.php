@extends('layouts.app')
@section('content')

    <div class="container">
        @include('flash.flash-message')

        <tree-component></tree-component>

        <div class="col-md-3" id="instance"></div>

        <div class="modal fade" id="module" tabindex="-1" role="dialog" aria-labelledby="novaInstancal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel">New instance</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>
                    <form class="form-horizontal" action="table" method="POST">
                        {{ csrf_field() }}
                        <div class="modal-body">

                            <label for="dateReport" class="mx-sm-0">Choose the Date</label>
                            <input placeholder="Datum" class="form-control mx-sm-0" name="period" id="period"
                                   type="date"
                                   required=""/>

                        </div>
                        <input name="taxonomy" type="hidden" id="table" type="text"/>
                        <input name="lang" type="hidden" id="lang" type="text"/>
                        <input name="mod" type="hidden" id="mod" type="text"/>
                        <input name="table_xsd" type="hidden" id="table_xsd" type="text"/>
                        <input name="ext_code" type="hidden" id="ext_code" type="text"/>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Zatvori</button>
                            <button type="submit" class="btn btn-primary" name="module" value="1" id="module">Dalje
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

