@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                    <form  class="form-inline" action="/upload" method="post" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group mb-2">
                            <label for="zip" class="sr-only">*zip</label>
                            <input id="zip" type="file" accept="application/zip" name="file">

                        </div>

                            <button class="btn btn-primary" type="submit">Upload</button>

                    </form>

            </div>
        </div>

@endsection
