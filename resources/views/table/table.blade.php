@extends('layouts.app')

@section('content')

    <div class="container">
        @include('flash.flash-message')
        {!! $tableHtml['table'] !!}
    </div>
@endsection
