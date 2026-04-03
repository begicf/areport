@foreach ([
    'success' => 'success',
    'error' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
    'danger' => 'danger',
] as $messageKey => $alertType)
    @if ($message = Session::get($messageKey))
        <div class="alert alert-{{ $alertType }} alert-dismissible fade show shadow-sm mb-4" role="alert">
            <strong>{{ $message }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <strong>Please check the form below for errors.</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
