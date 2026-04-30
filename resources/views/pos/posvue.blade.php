@extends('layouts.app')

@section('content')
    {{-- JSON in data-* attributes can break (quotes/length/encoding). Use application/json + id for reliable parse. --}}
    <script type="application/json" id="pos-app-bootstrap" class="hidden">@json($posVueBootstrap)</script>
    <div id="pos-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/pos-app.js')
@endpush
