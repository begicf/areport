<div class="modal fade app-modal-shell" id="module" tabindex="-1" aria-labelledby="moduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="app-page-kicker mb-2">
                        <i class="fas fa-layer-group"></i>
                        New instance
                    </div>
                    <h4 class="modal-title" id="moduleModalLabel">Create a reporting instance</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="table" method="POST">
                @csrf

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label for="datepicker" class="form-label">Reporting period</label>
                            <input placeholder="Select date" class="form-control" name="period" id="datepicker" required>
                            <p class="app-form-help mt-2">Choose the reporting date before selecting table groups.</p>
                        </div>

                        <div class="col-lg-8">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-5">
                                    <label for="multiselect" class="form-label">Available groups</label>
                                    <select id="multiselect" class="form-control" multiple="multiple" size="8"></select>
                                </div>

                                <div class="col-md-2">
                                    <div class="d-grid gap-2">
                                        <button type="button" id="multiselect_rightSelected" class="btn btn-outline-secondary">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <button type="button" id="multiselect_rightAll" class="btn btn-outline-secondary">
                                            <i class="fas fa-forward"></i>
                                        </button>
                                        <button type="button" id="multiselect_leftAll" class="btn btn-outline-secondary">
                                            <i class="fas fa-backward"></i>
                                        </button>
                                        <button type="button" id="multiselect_leftSelected" class="btn btn-outline-secondary">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <label for="multiselect_to" class="form-label">Selected groups</label>
                                    <select name="table[]" id="multiselect_to" class="form-control" size="8" multiple="multiple"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input name="module_name" type="hidden" id="module_name">
                <input name="module_path" type="hidden" id="module_path">

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="module" value="1">Continue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    window.initModulePicker = function () {
        const $multiselect = $('#multiselect');
        const $datepicker = $('#datepicker');

        if ($multiselect.length && !$multiselect.data('module-picker-ready')) {
            $multiselect.multiselect();
            $multiselect.data('module-picker-ready', true);
        }

        if ($datepicker.length && !$datepicker.data('datepicker')) {
            $datepicker.datepicker({ dateFormat: 'dd-mm-yy' }).val();
        }

        if ($datepicker.length) {
            $datepicker.datepicker('setDate', new Date("{{$period ?? ''}}"));
        }
    };

    $(function () {
        window.initModulePicker();
    });
</script>
