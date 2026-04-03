@extends('layouts.app')

@section('content')
    <div class="app-page">
        <section class="app-page-header">
            <div class="app-page-kicker">
                <i class="fas fa-upload"></i>
                Taxonomy upload
            </div>
            <h1 class="app-page-title">Import a taxonomy package</h1>
            <p class="app-page-copy">
                Upload a taxonomy archive, assign a recognizable name, and prepare it for activation in the workspace.
            </p>
        </section>

        @include('flash.flash-message')

        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <h2 class="app-panel-title">Upload details</h2>
                    <p class="app-panel-copy">Provide a display name and choose a ZIP archive containing the taxonomy files.</p>
                </div>
            </div>

            <form class="app-form-stack" name="upload-form" method="post">
                @csrf

                <div>
                    <label for="tax" class="form-label">Taxonomy name</label>
                    <input type="text" name="name" class="form-control" id="tax"
                           placeholder="Enter a taxonomy name" required>
                    <p class="app-form-help mt-2"><span class="text-danger">*</span> This name will be shown across the workspace.</p>
                </div>

                <div>
                    <label for="zip" class="form-label">ZIP archive</label>
                    <input id="zip" class="form-control" type="file" accept="application/zip" required name="file">
                    <p class="app-form-help mt-2"><span class="text-danger">*</span> Only <code>.zip</code> files are supported.</p>
                </div>

                <div class="app-actions justify-content-end">
                    <button class="btn btn-primary px-4" {{ env('UPLOAD') ? '' : 'disabled' }}
                            onclick="uploadTaxonomy()" type="button">
                        Upload taxonomy
                    </button>
                </div>
            </form>
        </section>
    </div>

    @include('components.please-wait')

    <script type="text/javascript">
        function uploadTaxonomy() {
            window.appModal.show('pleaseWaitDialog');

            var form = $('form[name="upload-form"]')[0];
            var formData = new FormData(form);

            axios.post('/taxonomy/upload', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(function () {
                window.location.assign('/taxonomy/managing');
            }).catch(function () {
                window.appModal.hide('pleaseWaitDialog');
            });
        }
    </script>
@endsection
