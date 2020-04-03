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
                        <h4 class="modal-title" id="myModalLabel">Nova instanca</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>
                    <form class="form-horizontal" action="tab" method="POST">
                        {{ csrf_field() }}
                        <div class="modal-body">


                            <label for="dateReport" class="mx-sm-0">Izaberite datum</label>
                            <input placeholder="Datum" class="form-control mx-sm-0" name="period" id="period"
                                   type="text"
                                   required=""/>

                        </div>
                        <input name="taxonomy" type="hidden" id="table" type="text"/>
                        <input name="lang" type="hidden" id="lang" type="text"/>
                        <input name="mod" type="hidden" id="mod" type="text"/>
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

        <?php if ($_SERVER['HTTP_HOST'] == 'batedis2.local.ba'): ?>


        <div class="modal fade" id="export" tabindex="-1" role="dialog" aria-labelledby="novaInstancal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel">Export to database</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>
                    <form class="form-horizontal" action="exportdb" method="POST">

                        {{ csrf_field() }}
                        <div class="modal-body">
                            <input name="taxonomy" type="hidden" id="export_table" type="text"/>
                            <input name="lang" type="hidden" id="export_lang" type="text"/>
                            <input name="mod" type="hidden" id="export_mod" type="text"/>
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Zatvori</button>
                            <button type="submit" class="btn btn-primary" name="module" value="1" id="module">Dalje
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <div class="modal fade" id="dwh_assertion" tabindex="-1" role="dialog" aria-labelledby="novaInstancal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel">Export asssertions</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>
                    <form class="form-horizontal" action="module" method="POST">

                        {{ csrf_field() }}
                        <div class="modal-body">
                            <input name="mod" type="hidden" id="dwh_mod" type="text"/>

                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Zatvori</button>
                            <button type="submit" class="btn btn-primary" name="module" value="1" id="module">Dalje
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php endif;?>


    </div>
@endsection

