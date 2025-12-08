@extends('layouts.app')

@section('content')

<livewire:inventory::purchase-return.purchase-return-list />
<livewire:inventory::purchase-order.view-purchase-order />
<livewire:inventory::purchase-order.purchase-order-payment />

@endsection

