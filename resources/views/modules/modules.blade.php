@extends('layouts.app')
@section('content')

    <div class="container" id="comp">
        @include('flash.flash-message')

        <tree-component></tree-component>

    </div>
    @include('components.module')
@endsection

