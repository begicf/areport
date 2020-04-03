@extends('layouts.app')

@section('content')

    <div class="container">
        @include('flash.flash-message')

        <div class="card col-lg-8">
            <div class="card-body">
                <form class="form" method="post">
                    {{ csrf_field() }}
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Active</th>
                            <th>Original name</th>
                            <th>Delete</th>
                        </tr>
                        </thead>
                        @foreach($tax as $row)

                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tax_active" id="tax_active"
                                               value="{{$row->id}}" {{ $row->active == true ? "checked" : "" }}>
                                        <label class="form-check-label" for="tax_active">
                                            {{$row->name}}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    {{$row->original_name}}
                                </td>
                                <td>
                                    <button formaction="tax_delete" class="btn btn-sn btn-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>

                        @endforeach
                    </table>
                    <button class="btn btn-primary float-right" type="submit"><i class="fas fa-cog"></i> Set</button>
                </form>
            </div>
        </div>
    </div>
@endsection
