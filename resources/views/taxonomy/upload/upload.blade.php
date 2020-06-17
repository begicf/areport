@extends('layouts.app')

@section('content')

    <div class="container">
        @include('flash.flash-message')

        <div class="card col-lg-8">
            <div class="card-body">

                <form class="form" action="/taxonomy/upload" method="post" enctype="multipart/form-data">
{{--                    {{ csrf_field() }}--}}
                    <div class="form-group">
                        <label for="tax">Taxonomy name</label>
                        <input type="text" name="name" class="form-control" id="tax" aria-describedby="TaxonomyName" placeholder="Taxonomy name" required>
                        <small  class="form-text text-muted"><span class="text-danger">*</span> Taxonomy name</small>
                    </div>
                    <div class="form-group">
                        <label for="zip" class="sr-only">*zip</label>
                        <input id="zip" form-control-file type="file" accept="application/zip" required name="file">
                        <small class="form-text text-muted"><span class="text-danger">*</span> Only *.zip</small>
                    </div>
                    <button class="btn btn-primary float-right" disabled type="submit">Upload</button>
                </form>

            </div>
        </div>
    </div>
@endsection
