@extends('layouts.app')

@section('content')

    <div class="container">
        @include('flash.flash-message')

        <div class="card">

            <div class="card-header">
                Taxonomy
            </div>


            <form class="form" method="post">
                <div class="card-body">

                    {{ csrf_field() }}
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Active</th>
                            <th>Original name</th>
                            <th class="text-center">Delete</th>
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
                                <td class="text-center">
                                    <button formaction="/taxonomy/delete" class="btn btn-outline-danger"><i
                                            class="fas fa-trash" type="submit"></i></button>
                                </td>
                            </tr>

                        @endforeach
                    </table>


                </div>
                <div class="card-footer text-right">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-cog"></i> Set
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
