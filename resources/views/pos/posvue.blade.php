@extends('layouts.app')

@section('content')
    <div id="pos-app" data-bootstrap="@json($posVueBootstrap)"></div>
@endsection

@push('scripts')
    @vite('resources/js/pos-app.js')
@endpush
