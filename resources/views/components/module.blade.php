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
                        @php
                            $__entityCls = \Begicf\AReportPro\Models\DpmEntity::class;
                            $__entities = class_exists($__entityCls) ? $__entityCls::orderByDesc('is_default')->orderBy('name')->get() : collect();
                            $__settingsUrl = \Route::has('areport-pro.settings.index') ? route('areport-pro.settings.index') : url('/pro/settings');
                        @endphp
                        <div class="col-lg-4">
                            <label for="datepicker" class="form-label">Reporting period</label>
                            <input placeholder="Select date" class="form-control" name="period" id="datepicker" required>

                            <label for="entitySelect" class="form-label mt-3">Reporting entity</label>
                            <select name="entity_id" id="entitySelect" class="form-select" required>
                                <option value="">— select entity —</option>
                                @foreach($__entities as $e)
                                    <option value="{{ $e->id }}" @selected($e->is_default)>{{ $e->name }} ({{ $e->lei }})</option>
                                @endforeach
                            </select>
                            <p class="app-form-help mt-2">
                                @if($__entities->isEmpty())
                                    No entities yet — <a href="{{ $__settingsUrl }}" target="_blank" rel="noopener">add one in Settings</a> first.
                                @else
                                    <a href="{{ $__settingsUrl }}" target="_blank" rel="noopener"><i class="fas fa-plus"></i> Add a new entity</a>
                                @endif
                            </p>
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
