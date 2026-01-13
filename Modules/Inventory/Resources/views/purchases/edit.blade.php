@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto py-6">
        @livewire('inventory::edit-direct-purchase', ['purchaseId' => $purchase->id])
    </div>
@endsection
