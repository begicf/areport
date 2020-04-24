@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        @include('flash.flash-message')
        {!! $tableHtml['table'] !!}
    </div>
@endsection
