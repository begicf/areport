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
                <div class="container">
                    <div class="row">
                        <div class="modal-body">
                            <label for="period" class="mx-sm-0">Choose the Date</label>
                            <input placeholder="Datum" class="form-control mx-sm-0" name="period" id="period"
                                   type="date"
                                   required=""/>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-5">
                            <select id="multiselect" class="form-control"
                                    multiple="multiple" size="8">

                            </select>
                        </div>

                        <div class="col-1">
                            <button type="button" id="multiselect_rightSelected" class="btn btn-block"><i
                                    class="fas fa-chevron-right"></i></button>
                            <button type="button" id="multiselect_rightAll" class="btn btn-block"><i
                                    class="fas fa-forward"></i></button>
                            <button type="button" id="multiselect_leftAll" class="btn btn-block"><i
                                    class="fas fa-backward"></i></button>
                            <button type="button" id="multiselect_leftSelected" class="btn btn-block"><i
                                    class="fas fa-chevron-left"></i></button>

                        </div>

                        <div class="col-5">
                            <select name="table[]" id="multiselect_to" class="form-control" size="8"
                                    multiple="multiple"></select>
                        </div>
                    </div>
                </div>

                <input name="taxonomy" type="hidden" id="table"/>
                <input name="lang" type="hidden" id="lang"/>
                <input name="mod" type="hidden" id="mod"/>
                <input name="table_xsd" type="hidden" id="table_xsd"/>
                <input name="ext_code" type="hidden" id="ext_code"/>


                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="module" value="1">Next</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

    $('#multiselect').multiselect();
</script>
