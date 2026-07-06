<?php

namespace App\Livewire\Dashboard;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TodayPaymentMethodEarnings extends Component
{
    public function render()
    {
        $start = now()->startOfDay()->toDateTimeString();
        $end = now()->endOfDay()->toDateTimeString();

        $paymentMethods = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.payment_method', '<>', 'due')
            ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total_amount'))
            ->whereDate('orders.date_time', '>=', $start)
            ->whereDate('orders.date_time', '<=', $end)
            ->groupBy('payments.payment_method')
            ->get()
            ->sortBy('total_amount', SORT_REGULAR, true);

        return view('livewire.dashboard.today-payment-method-earnings', [
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
