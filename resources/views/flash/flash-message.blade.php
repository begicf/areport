@foreach ([
    'success' => 'success',
    'error' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
    'danger' => 'danger',
] as $messageKey => $alertType)
    @if ($message = Session::get($messageKey))
        @php($icon = ['success' => 'fa-circle-check', 'error' => 'fa-circle-xmark', 'danger' => 'fa-circle-xmark', 'warning' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info'][$messageKey] ?? 'fa-circle-info')
        <div class="alert alert-{{ $alertType }} alert-dismissible fade show mb-3 d-flex align-items-center gap-2" role="alert">
            <i class="fas {{ $icon }}"></i><strong>{{ $message }}</strong>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 d-flex align-items-center gap-2" role="alert">
        <i class="fas fa-circle-xmark"></i><strong>Please check the form below for errors.</strong>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
