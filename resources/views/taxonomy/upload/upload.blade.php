@extends('layouts.app')

@section('content')

    <div class="container">

        @include('flash.flash-message')

        <div class="card col-lg-8">
            <div class="card-body">

                <form class="form" name="upload-form" method="post">
                                        {{ csrf_field() }}
                    <div class="form-group">
                        <label for="tax">Taxonomy name</label>
                        <input type="text" name="name" class="form-control" id="tax" aria-describedby="TaxonomyName"
                               placeholder="Taxonomy name" required>
                        <small class="form-text text-muted"><span class="text-danger">*</span> Taxonomy name</small>
                    </div>
                    <div class="form-group">
                        <label for="zip" class="sr-only">*zip</label>
                        <input id="zip" form-control-file type="file" accept="application/zip" required name="file">
                        <small class="form-text text-muted"><span class="text-danger">*</span> Only *.zip</small>
                    </div>

                    <button class="btn btn-primary float-right" {{env('UPLOAD')?"":"disabled"}}
                        onclick="uploadTaxonomy()" type="button">Upload
                    </button>
                </form>

            </div>
        </div>
    </div>

    @include('components.please-wait')

    <script type="text/javascript">

        function uploadTaxonomy() {

            $('#pleaseWaitDialog').modal();

            var form = $('form[name="upload-form"]')[0];
            var formData = new FormData(form);

            axios.post('/taxonomy/upload', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            ).then(function (response) {
                $('#pleaseWaitDialog').modal('hide');
            });
        }

    </script>

@endsection
